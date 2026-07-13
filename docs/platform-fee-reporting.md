# Platform Fee Reporting & Cash Flow

How platform fees flow from Stripe to escrow, how the weekly report period is determined, and why generation waits a few days after each period ends.

---

## The Cash Flow

```
Stripe → Company account → Escrow account
```

Escrow is a single pooled account holding both merchant and customer funds — nothing is split into per-merchant or per-customer sub-accounts. It's funded weekly, driven by the platform fee report: each report tells the platform how much of that week's cash to keep as fee revenue and how much to transfer into escrow.

**The fee is deducted before the remainder moves to escrow, never after.** The report's `total_platform_fees` figure is pulled out as revenue first; only `total_gmv − total_platform_fees − total_processing_costs` — cash already known to exclude the platform's cut — is transferred into escrow. Escrow should never hold money that includes an un-deducted fee, since that would mean briefly commingling the platform's revenue with funds that are supposed to be safeguarded for customers and merchants.

**Example:** A single RM100 purchase, 5% platform fee rate, card processing (3% + RM1). Stripe deducts its own cut before the money ever reaches the company account, so what actually lands is already net of that:

```
platform_fee   = RM100 × 0.05          = RM5.00
processing_fee = RM100 × 0.03 + RM1    = RM4.00

Cash received in company account: RM100 − RM4 (Stripe's cut) = RM96.00

Step 1 — deduct the platform fee:  RM96.00 − RM5.00 = RM5.00 kept as revenue, RM91.00 remains
Step 2 — transfer the remainder:   RM91.00 → Escrow
```

RM91.00 is exactly `cash_balance` for this purchase (`paid_amount − platform_fee − processing_fee`, see `docs/credit-lifecycle.md`) — the report's escrow transfer and the per-transaction cash ledger always agree, because they're both derived from the same underlying fee data.

**Escrow tracks `cash_balance`, not `balance`.** `UserCredit.balance` is the customer's face-value spending power; `cash_balance` is the real cash backing it, net of platform and processing fees (see `docs/credit-lifecycle.md`). Escrow only ever needs to hold the cash figure — the face-value gap (from promotional bonus credit) is a separate liability-monitoring concern, not a cash-safeguarding one.

**Settlement pulls from escrow, not from the report.** `SettlementService` pays merchants out of whatever's already sitting in escrow, based on completed redemptions — it has no dependency on `PlatformFeeReport` at all. The two features share the same underlying transaction data but run on independent cadences.

---

## What the Report Computes

`PlatformFeeReportService::generateForPeriod()` sums every `CREDIT_PURCHASE` transaction with `status = COMPLETED` inside the period, and writes both a CSV and a `PlatformFeeReport` row with the same totals:

| Column | What it tracks |
|---|---|
| `total_gmv` | Sum of `paid_amount` — the full cash collected across all purchases in the period |
| `total_platform_fees` | Sum of `platform_fee` — what the platform keeps as revenue |
| `total_processing_costs` | Sum of `processing_fee` (`0.00` where no `Payment` record exists) — informational only, already absorbed by the merchant via `cash_balance`, not deducted from the platform's earnings |

**Example:** A week with two purchases — RM100 at a 5% platform fee rate, RM50 at the same rate, both paid by card (processing fee 3% + RM1):

```
Purchase 1: platform_fee = RM5.00,  processing_fee = RM4.00
Purchase 2: platform_fee = RM2.50,  processing_fee = RM2.50

total_gmv               = RM100 + RM50 = RM150.00
total_platform_fees     = RM5.00 + RM2.50 = RM7.50
total_processing_costs  = RM4.00 + RM2.50 = RM6.50
```

The RM7.50 is what gets pulled out as platform revenue; the remainder of the collected cash (`total_gmv − total_platform_fees − total_processing_costs`, which is exactly the sum of that week's `cash_balance` deltas) is what moves to escrow.

**Why this can never overdraw the account it's computed from:** `platform_fee` is always a fixed percentage of `paid_amount` *for the same transaction*, and only `COMPLETED` transactions are ever summed. The fee taken from a batch can't exceed the cash that batch represents, because it's a fraction of that exact cash, not an independent figure.

---

## How the Reporting Period Is Determined

Periods are fixed at 7 days and chain from the previous report — never recomputed from "today's date." `PlatformFeeReportService::nextPeriod()`:

```
periodStart = lastReport?.period_end + 1 day   (or the configured first_period_start if no report exists yet)
periodEnd   = periodStart + 6 days
```

This makes the period independent of *when* the admin actually clicks "Generate" — whether that happens exactly on schedule, a few days late, or after a skipped week, the next period always picks up exactly where the last one ended. There's no weekday rule to enforce ("must run on Monday") because there's nothing to enforce — continuity is structural, not a matter of timing discipline.

**Bootstrapping the first period:** `config('platform-fee-report.first_period_start')` (env: `PLATFORM_FEE_REPORT_FIRST_PERIOD_START`) is only consulted when no `PlatformFeeReport` exists yet. It must be set before the first report can ever be generated — `PlatformFeeReportService::firstPeriodStart()` throws `MissingFirstPeriodStartException` with an actionable message otherwise, rather than silently computing a wrong anchor date. It's parsed with `createFromFormat('Y-m-d', ...)`, not `parse()`, so a misconfigured value (wrong shape, e.g. `07/06/2026` instead of `2026-07-06`) fails loudly instead of silently producing the wrong date — see "Timezone-safe date conversion" in `CLAUDE.md` for why `parse()` alone isn't safe for this kind of input.

**Example chain**, with `first_period_start = 2026-01-05` (a Monday):

| Period | Covers |
|---|---|
| 1 | Mon 5 Jan – Sun 11 Jan |
| 2 | Mon 12 Jan – Sun 18 Jan |
| 3 | Mon 19 Jan – Sun 25 Jan |

Each period's start is always the day after the previous period's end — regardless of which calendar date the report was actually generated on.

---

## The Availability Buffer (+3 Days Safeguard)

A transaction being `COMPLETED` in the database means Stripe confirmed the charge — it doesn't mean the cash has landed in the platform's bank account yet. Stripe's payout cycle typically takes a few days after a charge clears before funds actually arrive.

`nextPeriod()` returns `available_from = periodEnd + availability_buffer_days` (config: `platform-fee-report.availability_buffer_days`, env: `PLATFORM_FEE_REPORT_AVAILABILITY_BUFFER_DAYS`, default `3`). `GeneratePlatformFeeReportAction` disables the "Generate Report" button with an explanatory tooltip until that date — an admin can't accidentally generate a report for a period whose funds likely haven't cleared yet.

**Why a buffer instead of tracking which specific transactions have cleared:** trying to attribute cash arrival to individual transactions (a running "owed vs. transferred" ledger) was considered and rejected as unnecessary complexity for the current scale. Waiting a fixed number of days after a period ends is far simpler and, given typical Stripe payout timing, sufficient — the exact number is a config value specifically because real payout timing hasn't been confirmed yet (see `CASH_FLOW_AND_ESCROW_DESIGN.md`'s open items).

**Example**, with a 3-day buffer: Period 1 covers Mon 5 Jan – Sun 11 Jan → `available_from` = Wed 14 Jan. The report can't be generated before then, even though the period itself already ended on the 11th.

---

## Generating a Report

Report generation is **semi-automated**, not scheduled: an admin clicks "Generate Report" in the Filament admin, but the period itself is always computed automatically (never hand-picked) via `nextPeriod()`. This was a deliberate choice over a cron-scheduled command — it removes the risk of date-selection errors without requiring this app's first piece of scheduler infrastructure, failure handling, and monitoring for a feature still finding its operational cadence.

The actual money movement (pulling the fee, transferring the remainder to escrow) happens manually, outside this system, and is recorded afterward via "Mark as Paid" (`MarkPlatformFeeReportAsPaidAction`) — the same pattern as `MarkSettlementAsPaidAction` in `docs/settlement-operations.md`. The report never moves money itself; it only tells the admin how much to move.

`PlatformFeeReportService::previousUnpaidReport()` and the table's status filter both surface reports still awaiting that manual step.

---

## Key Invariants

| Invariant | Why it holds |
|---|---|
| No two `PlatformFeeReport` periods can overlap | `assertPeriodIsAvailable()` checks `PlatformFeeReport::scopeOverlapping()` before every generation and throws `OverlappingPeriodException` — this also guards against accidental double-generation if the manual "Generate Report" action is ever clicked twice for the same period |
| Periods are always contiguous | Each period's start is derived from the previous report's `period_end`, never from the current date |
| The computed fee can never exceed the cash it's computed from | `platform_fee` is a percentage of `paid_amount` on the same transaction, and only `COMPLETED` transactions are summed |
| A report can't be generated before its funds have likely cleared | `available_from` gates the "Generate Report" action via `availability_buffer_days` |
| A misconfigured `first_period_start` fails loudly, not silently | `createFromFormat('Y-m-d', ...)` throws on a malformed value instead of guessing at its meaning |
| Report generation never moves money | Money movement is manual and external; the report only computes and records the amounts, confirmed afterward via "Mark as Paid" |
