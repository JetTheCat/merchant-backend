<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use Illuminate\Foundation\Http\FormRequest;

class CreateCreditPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOwner();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'credit_amount' => ['required', 'numeric', 'min:0.01'],
            'selling_price' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'purchase_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'total_quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
