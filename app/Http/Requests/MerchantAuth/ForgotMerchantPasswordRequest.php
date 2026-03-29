<?php

declare(strict_types=1);

namespace App\Http\Requests\MerchantAuth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotMerchantPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
