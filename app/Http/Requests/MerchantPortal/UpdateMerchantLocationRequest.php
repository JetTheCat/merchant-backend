<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Enums\MalaysianState;
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
            'street' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'postcode' => ['sometimes', 'digits:5'],
            'state' => ['sometimes', 'string', Rule::enum(MalaysianState::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
