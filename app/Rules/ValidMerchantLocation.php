<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\MerchantLocation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMerchantLocation implements ValidationRule
{
    public function __construct(private readonly string $merchantId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = MerchantLocation::where('id', $value)
            ->where('merchant_id', $this->merchantId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $exists) {
            $fail('The selected :attribute is invalid.');
        }
    }
}
