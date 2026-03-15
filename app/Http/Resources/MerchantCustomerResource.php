<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantCustomerResource extends JsonResource
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
            'fullAddress' => $this->full_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
        ];
    }
}
