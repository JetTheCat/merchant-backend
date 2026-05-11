<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

class DateTimeParser
{
    public static function toUtc(string $value, ?string $timezone = null): Carbon
    {
        return Carbon::parse($value, $timezone ?? 'UTC')->utc();
    }
}
