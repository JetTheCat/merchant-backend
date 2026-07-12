<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | First Period Start
    |--------------------------------------------------------------------------
    |
    | The start date (a Monday, in app.reporting_timezone) of the very first
    | weekly platform fee report period. Only consulted when no PlatformFeeReport
    | exists yet — every subsequent period is derived from the previous report's
    | period_end instead. Must be set before the first report can be generated.
    |
    */

    'first_period_start' => env('PLATFORM_FEE_REPORT_FIRST_PERIOD_START'),

    /*
    |--------------------------------------------------------------------------
    | Availability Buffer Days
    |--------------------------------------------------------------------------
    |
    | Number of days after a period's end before that period becomes eligible
    | for report generation, to give Stripe's payout cycle time to clear the
    | period's funds into the platform's account before it's reported on.
    |
    */

    'availability_buffer_days' => env('PLATFORM_FEE_REPORT_AVAILABILITY_BUFFER_DAYS', 3),

];
