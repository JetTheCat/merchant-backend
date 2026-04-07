<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant' => new MerchantCustomerResource($this->whenLoaded('merchant')),
            'type' => $this->type,
            'typeLabel' => $this->type->label(),
            'status' => $this->status,
            'statusLabel' => $this->status->label(),
            'referenceCode' => $this->reference_code,
            'amount' => $this->amount,
            'paidAmount' => $this->paid_amount,
            'balanceBefore' => $this->balance_before,
            'balanceAfter' => $this->balance_after,
            'createdAt' => $this->created_at,
        ];
    }
}
