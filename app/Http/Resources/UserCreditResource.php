<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchantId' => $this->merchant_id,
            'merchant' => new MerchantCustomerResource($this->whenLoaded('merchant')),
            'balance' => $this->balance,
            'totalPurchased' => $this->total_purchased,
            'totalRedeemed' => $this->total_redeemed,
            'lastPurchaseAt' => $this->last_purchase_at,
            'lastRedemptionAt' => $this->last_redemption_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
