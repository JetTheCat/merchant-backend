<?php

declare(strict_types=1);

return [
    'login' => [
        'attempts' => env('RATE_LIMIT_LOGIN_ATTEMPTS', 10),
        'decay_minutes' => env('RATE_LIMIT_LOGIN_DECAY_MINUTES', 1),
    ],

    'forgot_password' => [
        'attempts' => env('RATE_LIMIT_FORGOT_PASSWORD_ATTEMPTS', 3),
        'decay_minutes' => env('RATE_LIMIT_FORGOT_PASSWORD_DECAY_MINUTES', 60),
    ],

    'resend_verification' => [
        'attempts' => env('RATE_LIMIT_RESEND_VERIFICATION_ATTEMPTS', 3),
        'decay_minutes' => env('RATE_LIMIT_RESEND_VERIFICATION_DECAY_MINUTES', 60),
    ],

    'redemption_qr' => [
        'attempts' => env('RATE_LIMIT_REDEMPTION_QR_ATTEMPTS', 30),
        'decay_minutes' => env('RATE_LIMIT_REDEMPTION_QR_DECAY_MINUTES', 1),
    ],
];
