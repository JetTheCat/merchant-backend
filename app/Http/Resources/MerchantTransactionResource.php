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
            'merchantUser' => new MerchantUserResource($this->whenLoaded('merchantUser')),
            'merchantLocationId' => $this->merchant_location_id,
            'type' => $this->type,
            'typeLabel' => $this->type->label(),
            'status' => $this->status,
            'statusLabel' => $this->status->label(),
            'referenceCode' => $this->reference_code,
            'amount' => $this->amount,
            'paidAmount' => $this->paid_amount,
            'balanceBefore' => $this->balance_before,
            'balanceAfter' => $this->balance_after,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at,
        ];
    }
}
