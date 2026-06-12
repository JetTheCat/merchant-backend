<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchantId' => $this->merchant_id,
            'merchantLocationId' => $this->merchant_location_id,
            'merchant' => $this->whenLoaded('merchant', fn () => [
                'id' => $this->merchant->id,
                'name' => $this->merchant->name,
                'code' => $this->merchant->code,
            ]),
            'merchantLocation' => $this->whenLoaded('merchantLocation', fn () => [
                'id' => $this->merchantLocation->id,
                'name' => $this->merchantLocation->name,
            ]),
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'roleLabel' => $this->role->label(),
            'status' => $this->status,
            'statusLabel' => $this->status->label(),
            'emailVerifiedAt' => $this->email_verified_at,
            'lastLoginAt' => $this->last_login_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
