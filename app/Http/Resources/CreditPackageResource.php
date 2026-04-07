<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchantId' => $this->merchant_id,
            'name' => $this->name,
            'description' => $this->description,
            'creditAmount' => $this->credit_amount,
            'sellingPrice' => $this->selling_price,
            'discountPercentage' => $this->discount_percentage,
            'validityDays' => $this->validity_days,
            'isActive' => $this->is_active,
            'purchaseLimitPerUser' => $this->purchase_limit_per_user,
            'totalQuantity' => $this->total_quantity,
            'soldQuantity' => $this->sold_quantity,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
