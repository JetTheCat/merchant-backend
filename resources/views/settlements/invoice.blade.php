<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settlement Statement — {{ $settlement->reference_code }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #1a202c;
            background: #ffffff;
            padding: 40px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 24px;
            border-bottom: 3px solid #059669;
            margin-bottom: 32px;
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
            color: #059669;
            letter-spacing: -0.5px;
        }

        .brand-sub {
            font-size: 11px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 2px;
        }

        .doc-meta {
            text-align: right;
        }

        .doc-meta .reference {
            font-size: 16px;
            font-weight: 700;
            color: #1a202c;
        }

        .doc-meta .generated {
            font-size: 11px;
            color: #718096;
            margin-top: 4px;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-processing {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-failed {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Two column info section */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .info-block h3 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #059669;
            margin-bottom: 10px;
        }

        .info-block p {
            font-size: 13px;
            color: #2d3748;
            margin-bottom: 3px;
        }

        .info-block .label {
            font-size: 11px;
            color: #718096;
        }

        /* Summary boxes */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 32px;
        }

        .summary-box {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }

        .summary-box .summary-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #718096;
            margin-bottom: 6px;
        }

        .summary-box .summary-value {
            font-size: 18px;
            font-weight: 700;
            color: #1a202c;
        }

        .summary-box.net .summary-value {
            color: #059669;
        }

        /* Transaction table */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #059669;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
            font-size: 12px;
        }

        thead th {
            background-color: #f7fafc;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #718096;
        }

        thead th.text-right {
            text-align: right;
        }

        tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #2d3748;
            vertical-align: middle;
        }

        tbody td.text-right {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        tbody td.mono {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: #4a5568;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Totals row */
        .totals-row td {
            border-top: 2px solid #e2e8f0;
            border-bottom: none !important;
            padding-top: 12px;
            font-weight: 700;
            color: #1a202c;
        }

        /* Payment info */
        .payment-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 32px;
        }

        .payment-box.paid {
            border-color: #059669;
            background-color: #f0fdf4;
        }

        .payment-box.pending {
            border-color: #f59e0b;
            background-color: #fffbeb;
        }

        .payment-box h3 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 12px;
        }

        .payment-box.paid h3 {
            color: #059669;
        }

        .payment-box.pending h3 {
            color: #92400e;
        }

        .payment-row {
            display: flex;
            gap: 40px;
        }

        .payment-field .field-label {
            font-size: 11px;
            color: #718096;
            margin-bottom: 2px;
        }

        .payment-field .field-value {
            font-size: 13px;
            font-weight: 600;
            color: #1a202c;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer p {
            font-size: 11px;
            color: #a0aec0;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="brand">{{ config('app.name') }}</div>
            <div class="brand-sub">Settlement Statement</div>
        </div>
        <div class="doc-meta">
            <div class="reference">{{ $settlement->reference_code }}</div>
            <div class="generated">Generated {{ now()->format('d M Y, h:i A') }}</div>
            <div>
                <span class="status-badge status-{{ $settlement->status->value }}">
                    {{ $settlement->status->label() }}
                </span>
            </div>
        </div>
    </div>

    {{-- Merchant & Period Info --}}
    <div class="info-grid">
        <div class="info-block">
            <h3>Merchant</h3>
            <p style="font-weight: 600;">{{ $settlement->merchant->name }}</p>
            <p>{{ $settlement->merchant->business_name }}</p>
        </div>

        <div class="info-block">
            <h3>Settlement Period</h3>
            <p style="font-weight: 600;">
                {{ $settlement->period_start->format('d M Y') }} — {{ $settlement->period_end->format('d M Y') }}
            </p>
            <p class="label">{{ $settlement->transaction_count }} {{ Str::plural('transaction', $settlement->transaction_count) }}</p>
        </div>

        <div class="info-block">
            <h3>Banking Details</h3>
            <p style="font-weight: 600;">{{ $settlement->bank_name }}</p>
            <p>{{ $settlement->bank_account_name }}</p>
            <p class="label">{{ $settlement->bank_account_number }}</p>
        </div>
    </div>

    {{-- Summary Boxes --}}
    <div class="summary-grid">
        <div class="summary-box">
            <div class="summary-label">Total Sales</div>
            <div class="summary-value">RM {{ number_format((float) $settlement->total_sales, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Platform Fee</div>
            <div class="summary-value">RM {{ number_format((float) $settlement->total_fees, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Transactions</div>
            <div class="summary-value">{{ $settlement->transaction_count }}</div>
        </div>
        <div class="summary-box net">
            <div class="summary-label">Net Payout</div>
            <div class="summary-value">RM {{ number_format((float) $settlement->net_amount, 2) }}</div>
        </div>
    </div>

    {{-- Transaction Breakdown --}}
    <div class="section-title">Transaction Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th class="text-right">Amount (RM)</th>
                <th class="text-right">Fee (RM)</th>
                <th class="text-right">Net (RM)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->created_at->format('d M Y, h:i A') }}</td>
                    <td class="mono">{{ $purchase->reference_code }}</td>
                    <td class="text-right">{{ number_format((float) $purchase->paid_amount, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $purchase->platform_fee, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $purchase->paid_amount - (float) $purchase->platform_fee, 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2">Total</td>
                <td class="text-right">{{ number_format((float) $settlement->total_sales, 2) }}</td>
                <td class="text-right">{{ number_format((float) $settlement->total_fees, 2) }}</td>
                <td class="text-right">{{ number_format((float) $settlement->net_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Payment Info --}}
    @if ($settlement->paid_at)
        <div class="payment-box paid">
            <h3>Payment Confirmed</h3>
            <div class="payment-row">
                <div class="payment-field">
                    <div class="field-label">Payment Reference</div>
                    <div class="field-value">{{ $settlement->payment_reference }}</div>
                </div>
                <div class="payment-field">
                    <div class="field-label">Paid On</div>
                    <div class="field-value">{{ $settlement->paid_at->format('d M Y, h:i A') }}</div>
                </div>
                <div class="payment-field">
                    <div class="field-label">Amount Paid</div>
                    <div class="field-value">RM {{ number_format((float) $settlement->net_amount, 2) }}</div>
                </div>
            </div>
        </div>
    @else
        <div class="payment-box pending">
            <h3>Payment Pending</h3>
            <p style="font-size: 13px; color: #92400e;">
                This settlement has not been paid yet. Payment of
                <strong>RM {{ number_format((float) $settlement->net_amount, 2) }}</strong>
                will be transferred to the banking details above.
            </p>
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>{{ config('app.name') }} &mdash; {{ $settlement->reference_code }}</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>

</body>
</html>
