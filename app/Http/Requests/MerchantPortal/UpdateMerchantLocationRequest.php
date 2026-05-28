<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Enums\MerchantArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMerchantLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'area' => ['sometimes', 'string', Rule::enum(MerchantArea::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
