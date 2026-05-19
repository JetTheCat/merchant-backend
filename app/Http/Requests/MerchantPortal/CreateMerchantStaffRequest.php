<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantPortal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateMerchantStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('merchant_users', 'username')
                    ->where('merchant_id', $this->user()->merchant_id)
                    ->whereNull('deleted_at'),
            ],
            'password' => ['required', 'string', Password::defaults()],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
