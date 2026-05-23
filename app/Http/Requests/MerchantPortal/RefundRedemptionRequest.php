<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use Illuminate\Foundation\Http\FormRequest;

class RefundRedemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
