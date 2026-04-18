<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'referenceCode' => $this->reference_code,
            'periodStart' => $this->period_start->toDateString(),
            'periodEnd' => $this->period_end->toDateString(),
            'totalRedemptions' => $this->total_redemptions,
            'totalFees' => $this->total_fees,
            'netAmount' => $this->net_amount,
            'redemptionCount' => $this->redemption_count,
            'status' => $this->status,
            'statusLabel' => $this->status->label(),
            'bankName' => $this->bank_name,
            'bankAccountNumber' => $this->bank_account_number,
            'bankAccountName' => $this->bank_account_name,
            'paymentReference' => $this->payment_reference,
            'paidAt' => $this->paid_at,
            'createdAt' => $this->created_at,
        ];
    }
}
