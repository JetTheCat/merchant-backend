<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Rules\ValidMerchantLocation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMerchantOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_location_id' => [
                'sometimes',
                'nullable',
                'uuid',
                new ValidMerchantLocation($this->user()->merchant_id),
            ],
        ];
    }
}
