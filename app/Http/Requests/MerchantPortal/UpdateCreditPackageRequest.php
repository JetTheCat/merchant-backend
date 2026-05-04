<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'credit_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'selling_price' => ['sometimes', 'numeric', 'min:0.01'],
            'is_active' => ['sometimes', 'boolean'],
            'purchase_limit_per_user' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'total_quantity' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
