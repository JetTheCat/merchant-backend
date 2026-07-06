<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parentTransactionId' => $this->parent_transaction_id,
            'parentTransactionReference' => $this->whenLoaded('parentTransaction', fn () => $this->parentTransaction?->reference_code),
            'merchantUser' => new MerchantUserResource($this->whenLoaded('merchantUser')),
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'firstName' => $this->user->first_name,
                'lastName' => $this->user->last_name,
            ]),
            'merchantLocationId' => $this->merchant_location_id,
            'type' => $this->type,
            'typeLabel' => $this->type->label(),
            'status' => $this->status,
            'statusLabel' => $this->status->label(),
            'referenceCode' => $this->reference_code,
            'amount' => $this->amount,
            'paidAmount' => $this->paid_amount,
            'platformFeeRate' => $this->platform_fee_rate,
            'platformFee' => $this->platform_fee,
            'processingFee' => $this->whenLoaded('payment', fn () => $this->payment?->processing_fee),
            'settlementAmount' => $this->settlement_amount,
            'balanceBefore' => $this->balance_before,
            'balanceAfter' => $this->balance_after,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at,
        ];
    }
}
