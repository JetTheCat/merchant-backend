<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MerchantUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
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
            'name' => $this->name,
            'businessName' => $this->business_name,
            'businessRegistrationNumber' => $this->when(
                $this->business_registration_number,
                fn () => '****' . substr($this->business_registration_number, -4)
            ),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'description' => $this->description,
            'logoUrl' => $this->logo_url,
            'coverImageUrl' => $this->cover_image_url,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postcode' => $this->postcode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'platformFeeRate' => $this->platform_fee_rate,
            'formattedPlatformFeeRate' => $this->formatted_platform_fee_rate,
            'settlementCycle' => $this->settlement_cycle,
            'status' => $this->status,
            'bankName' => $this->bank_name,
            'bankAccountNumber' => $this->when(
                $this->bank_account_number,
                fn () => $this->resolveBankAccountNumber($request)
            ),
            'bankAccountName' => $this->bank_account_name,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    private function resolveBankAccountNumber(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof MerchantUser && $user->isOwner()) {
            return $this->bank_account_number;
        }

        return '****' . substr($this->bank_account_number, -4);
    }
}
