<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Enums\MerchantUserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMerchantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'status' => ['sometimes', Rule::enum(MerchantUserStatus::class), Rule::notIn([MerchantUserStatus::PENDING_VERIFICATION->value])],
        ];
    }
}
