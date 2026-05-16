<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidMerchantCredential implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $parts = explode('@', $value, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            $fail('The :attribute must be a valid email or a username in the format username@merchantcode');
        }
    }
}
