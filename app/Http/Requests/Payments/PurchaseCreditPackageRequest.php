<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCreditPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $package = $this->route('package');

        return $package && $package->is_active;
    }

    public function rules(): array
    {
        return [];
    }
}
