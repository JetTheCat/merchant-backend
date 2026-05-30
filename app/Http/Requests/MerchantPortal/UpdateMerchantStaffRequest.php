<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use App\Enums\MerchantUserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateMerchantStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => strtolower($this->input('username'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => [
                'sometimes',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('merchant_users', 'username')
                    ->where('merchant_id', $this->user()->merchant_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->route('id')),
            ],
            'password' => ['sometimes', 'string', Password::defaults()],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'merchant_location_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('merchant_locations', 'id')
                    ->where('merchant_id', $this->user()->merchant_id)
                    ->whereNull('deleted_at'),
            ],
            'status' => ['sometimes', Rule::enum(MerchantUserStatus::class), Rule::notIn([MerchantUserStatus::PENDING_VERIFICATION->value])],
        ];
    }
}
