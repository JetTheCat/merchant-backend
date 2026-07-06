# Settlement Operations

Day-to-day guide for generating settlements, reading invoices, and managing clawbacks in the admin panel.

---

## Generating a Settlement

Settlements are generated per merchant via an Artisan command. The merchant must have banking details on file before a settlement can be generated.

```bash
php artisan settlements:generate {merchant_id} {period_start} {period_end}
```

**Example:**

```bash
php artisan settlements:generate a1b2c3d4-... 2026-06-01 2026-06-30
```

The command prints a summary table on success:

| Column | Meaning |
|---|---|
| Reference | Settlement reference code (e.g. `STL-20260630-XXXXXX`) |
| Redemptions | Number of `CREDIT_REDEMPTION` rows batched |
| Total Redeemed | Sum of `settlement_amount` across those redemptions — what the merchant earned |
| Clawback Deductions | Total deducted from outstanding clawbacks this cycle (RM0.00 if none) |
| Net Payout | What the merchant actually receives (`Total Redeemed − Clawback Deductions`) |
| Status | `PENDING` until manually marked as paid |

A settlement is generated even when `net_amount = RM0.00` — this still marks the included redemptions as settled and partially or fully clears outstanding clawbacks.

---

## What Happens When a Settlement Is Generated

1. All unsettled `CREDIT_REDEMPTION` rows for the merchant within the period are locked and their `settlement_amount` values are summed into `total_sales`.
2. A `Settlement` record is created with `status = PENDING`.
3. The batched transactions are updated with `settlement_id` pointing to the new settlement — this is the flag that triggers a clawback if any of them are later refunded.
4. Outstanding `SettlementClawback` records are processed oldest-first. Each clawback is deducted only if its full amount fits within the remaining capacity. Recovered clawbacks are marked `recovered` and linked to the settlement.
5. `clawback_amount` and `net_amount` are persisted on the settlement record.

---

## Reading the Settlement Invoice

The settlement invoice (PDF or on-screen) is structured as follows:

**Merchant info** — name, bank name, account number, account holder name. Verify these match the merchant agreement before transferring.

**Summary boxes** — three figures side by side:
- **Total Sales** — gross redemption value before clawbacks
- **Clawback Deductions** — total clawback recovered this cycle (omitted or shows RM0.00 when none)
- **Net Payout** — the amount to transfer

**Redemption breakdown** — a line-item table of each `CREDIT_REDEMPTION` transaction: date, reference, credit amount redeemed, and `settlement_amount` (the cash-equivalent paid to the merchant for that redemption).

**Clawback deductions table** — only present when `clawback_amount > 0`. Shows each recovered `SettlementClawback` record: original redemption reference, refund date, and amount deducted. Clawbacks that were skipped this cycle (because their amount exceeded remaining capacity) do not appear — they carry to the next settlement.

---

## Admin Panel

Settlements are managed in the Filament admin under **Settlements**. Each record shows the merchant, period, `total_sales`, `clawback_amount`, `net_amount`, and status (`PENDING` / `COMPLETED`).

Mark a settlement as paid after the bank transfer is confirmed — this sets `status = COMPLETED`, records the payment reference, and timestamps the payout.

### Merchant Clawback Status Badges

The merchant list and merchant detail page display a clawback status badge when a merchant has outstanding clawbacks:

| Badge | Meaning |
|---|---|
| **Amber** | One or more `pending` clawbacks exist. Normal — expected to recover through future settlements. No immediate action required. |
| **Red** | One or more clawbacks have been `pending` for more than 30 days. The merchant is not generating enough redemptions to clear the liability. Requires follow-up. |

The **Outstanding Clawbacks** stat on the dashboard shows the total outstanding clawback amount platform-wide, highlighted amber when any amount is pending.

---

## What to Do When a Merchant Has a Red Badge

1. Check the merchant's settlement history — are they still generating redemptions? A dormant merchant will not clear clawbacks through future settlements.
2. Check whether the merchant is still operating. If redemption volume has dropped to zero, the clawback may need to be pursued directly.
3. Reference the **merchant agreement clawback clause**: refunded settlement amounts are outstanding liabilities that may be deducted from future payouts or pursued directly if unrecoverable.
4. Document the follow-up action in the merchant's admin notes and escalate to management if the merchant is unresponsive.

A red badge alone does not mean fraud — a legitimate merchant may have had a high-value refund in a low-volume period. Use judgement and check the underlying `SettlementClawback` records to understand the source.

---

## Refunds and Clawbacks

Not every refund creates a clawback. The rule is simple:

- **No clawback** — the original redemption has no `settlement_id` (it has not been included in any settlement batch yet). The refund simply reverses the redemption; no money has moved.
- **Clawback created** — the original redemption has a `settlement_id` (it was batched into a settlement, whether that settlement is pending or already paid). A `SettlementClawback` record is created immediately with `status = pending`.

**Clawback status:**

| Status | Meaning |
|---|---|
| `pending` | Liability outstanding — not yet recovered through a settlement |
| `recovered` | Deducted from a settlement payout; `settlement_id` on the clawback record identifies which one |

There is no partial recovery state. A clawback either clears in full or carries forward.
