<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class LookupRedemptionQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => $this->route('code')]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'uuid'],
        ];
    }
}
