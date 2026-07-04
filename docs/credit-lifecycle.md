# Credit Lifecycle

How cash becomes credits, how credits become merchant payouts, and what happens when things are refunded.

---

## What Credits Are

A credit is a unit of face value stored in a customer's wallet (`UserCredit.balance`). One credit is worth one ringgit of spending power at the merchant — but the cash actually backing it is less than one ringgit, because two fees are deducted at purchase time before any cash enters the protected float.

The distinction matters because the platform settles merchants based on the cash backing, not the face value.

---

## The Purchase Flow

When a customer completes a payment, `CreditPurchaseService::grantCredits()` does three things:

1. Computes the two fees deducted from the customer's payment:
   - **Platform fee** — a percentage commission (`platform_fee_rate`) on `paid_amount`
   - **Processing fee** — a pass-through of the actual gateway cost (e.g. 3% + RM1 for card payments), resolved per payment method via `ProcessingFeeResolver`

2. Credits the customer's wallet with the face-value amount (`balance += credit_amount`).

3. Updates two cash-tracking columns on `UserCredit`:

| Column | What it tracks |
|---|---|
| `cash_balance` | `paid_amount − platform_fee − processing_fee` — the merchant's true net share of this purchase |
| `gross_paid_balance` | `paid_amount` — the full amount the customer paid, before any deductions |

`gross_paid_balance` exists because algebraic gross-up from `cash_balance` alone is not reliable once balances are blended across multiple purchases with different payment methods and fee rates. The flat component of the processing fee (the RM1 on card payments) does not scale proportionally with the credit amount, so the only safe approach is to track the gross figure independently.

Neither fee was ever the merchant's money — the merchant's entitlement was always `paid_amount − platform_fee − processing_fee` from the point of agreement.

**Example:** Customer pays RM100 for RM100 of credits. Platform fee rate = 5%, card processing fee = 3% + RM1.

```
platform_fee     = RM100 × 0.05  = RM5.00
processing_fee   = RM100 × 0.03 + RM1 = RM4.00

balance          += RM100  →  RM100.00
cash_balance     += RM100 − RM5 − RM4  →  RM91.00
gross_paid_balance += RM100  →  RM100.00
```

The customer holds RM100 of spending power. The merchant float holds RM91 of cash backing it.

---

## The Blended Cash Ratio

Rather than tracking which cash came from which purchase (lot-tracking), the system maintains a single pooled ratio: how much real cash backs each credit in the wallet right now.

```
netRatio = cash_balance / balance   (computed to 5 decimal places)
```

This is the **blended cash ratio** — it shifts with every purchase and redemption. When new purchases arrive at a different fee rate, the ratio moves. When redemptions drain the wallet, the ratio may shift slightly due to truncation.

**Why blended instead of lot-tracking?** `UserCredit` is already a pooled balance, not a lot-based ledger. Introducing lot-tracking would require a significant rebuild. The blended approach correctly handles settlement and refunds without it.

**Precision rule:** the ratio is computed to 5 decimal places internally. The final cash amount (multiplied by the redemption value) is truncated — not rounded — to 2 decimal places at the moment it is persisted. This matches the existing codebase convention and avoids inconsistent rounding behaviour across the ledger.

**Example:** Continuing from above — the customer then tops up again with RM50 via FPX (processing fee = 3% + RM1).

```
After second purchase:
  platform_fee   = RM50 × 0.05 = RM2.50
  processing_fee = RM50 × 0.03 + RM1 = RM2.50

  balance            = RM100 + RM50         = RM150.00
  cash_balance       = RM91  + RM45         = RM136.00
  gross_paid_balance = RM100 + RM50         = RM150.00

netRatio = 136.00 / 150.00 = 0.90667  (5dp)
```

The blended ratio is now 0.90667 — a weighted average of the two purchases with different fee structures.

---

## The Redemption Flow

When a customer redeems credits, `RedemptionService` computes the cash-equivalent of that redemption once and freezes it on the transaction record.

```
netRatio             = cash_balance / balance       (5dp)
settlement_amount    = redemption_amount × netRatio (truncated to 2dp)

grossRatio                  = gross_paid_balance / balance  (5dp)
gross_settlement_amount     = redemption_amount × grossRatio (truncated to 2dp)
```

Both values are written to the `CreditTransaction` row immediately. `cash_balance` and `gross_paid_balance` on `UserCredit` are decremented by the corresponding amounts.

**Why freeze at redemption time?** The ratio keeps moving — every subsequent purchase or redemption changes it. The cash value of a specific redemption must be computed once and locked. If it were recomputed later, it would produce a different number, breaking both settlement correctness and refund accuracy.

`settlement_amount` is what the merchant earns from this redemption and what flows into the settlement batch. `gross_settlement_amount` is used only by the refund path (see below).

**Example:** Continuing from the blended ratio example — customer redeems RM40 of credits.

```
netRatio   = 136.00 / 150.00 = 0.90667
grossRatio = 150.00 / 150.00 = 1.00000

settlement_amount       = RM40 × 0.90667 = RM36.26  (truncated to 2dp)
gross_settlement_amount = RM40 × 1.00000 = RM40.00

After redemption:
  balance            = RM150.00 − RM40.00 = RM110.00
  cash_balance       = RM136.00 − RM36.26 = RM99.74
  gross_paid_balance = RM150.00 − RM40.00 = RM110.00
```

The merchant earns RM36.26 from this redemption. That figure is frozen on the transaction record.

---

## The Refund Flow

When a merchant refunds a redemption, `RedemptionService::refundRedemption()` restores the customer's wallet from the **frozen values on the original transaction**, not from any recomputed ratio:

```
balance           += redemption.amount
cash_balance      += redemption.settlement_amount
gross_paid_balance += redemption.gross_settlement_amount
```

This is correct regardless of what the ratio is now. The frozen values represent exactly how much was taken from each pool at redemption time — restoring them returns the wallet to its prior state precisely.

**Example:** The merchant refunds the RM40 redemption from above.

```
balance            += RM40.00  →  RM150.00
cash_balance       += RM36.26  →  RM136.00
gross_paid_balance += RM40.00  →  RM150.00
```

The wallet is restored to exactly what it was before the redemption — no ratio recomputation needed.

---

## The Clawback Mechanism

If the refunded redemption has already been included in a settlement batch (`settlement_id IS NOT NULL`), the merchant has already received — or will receive — cash for that redemption. Restoring the customer's credits without recovering that cash would create a double-payout: the merchant retains the settled amount while the customer can redeem again.

The platform's response is a **clawback**, not a refund block. Blocking post-settlement refunds creates unacceptable experience; the clawback handles the liability automatically.

**What happens:**
1. A `SettlementClawback` record is created for the merchant with `amount = redemption.settlement_amount` and `status = pending`.
2. On the next settlement run, `SettlementService` loads all pending clawbacks for the merchant, oldest first.
3. Each clawback is only deducted if its **full amount** fits within the remaining deductible capacity. If it does not fit, it is skipped and carries to the next cycle.
4. Recovered clawbacks are marked `recovered` and linked to the settlement. `clawback_amount` and `net_amount` are persisted on the `Settlement` row.

**Example:** Clawback A = RM100, Clawback B = RM150, `total_sales` = RM120.
- A (RM100 ≤ RM120) → recovered, remaining = RM20
- B (RM150 > RM20) → skipped, carries forward
- Settlement: `total_sales = RM120`, `clawback_amount = RM100`, `net_amount = RM20`

The trigger for creating a clawback is `settlement_id IS NOT NULL` — not `settlement.status = COMPLETED`. Once a redemption is batched into any settlement, the merchant will receive that amount regardless of whether the settlement is pending or already paid.

---

## The Wallet Cap

A customer's wallet balance (`UserCredit.balance`) cannot exceed **RM250 per merchant**. This is the regulatory ceiling set by BNM's Limited Purpose EMI exemption — not a platform-imposed buffer. A purchase is rejected if it would push the balance above this limit.

---

## Key Invariants

| Invariant | Why it holds |
|---|---|
| `cash_balance ≤ balance` at all times | Both are funded proportionally on purchase and decremented proportionally on redemption; the net ratio is always ≤ 1 |
| `net_amount ≥ 0` on every settlement | Only whole clawbacks that fit within `total_sales` are deducted; the floor is naturally zero |
| A clawback is either fully recovered or not recovered — never partially | The whole-record-only constraint keeps `status` binary; no partial recovery tracking is needed |
| Refunds restore from frozen values, not recomputed ratios | `settlement_amount` and `gross_settlement_amount` are written once at redemption and never updated |
| Settlement only generates if `CREDIT_REDEMPTION` rows exist in the period | If there are no redemptions, clawback processing is also skipped |
