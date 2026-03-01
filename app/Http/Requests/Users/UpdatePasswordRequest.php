<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'The current password is incorrect.',
        ];
    }
}
