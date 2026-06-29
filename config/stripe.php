<?php

declare(strict_types=1);

return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'region' => env('STRIPE_REGION'),

    'fees' => [
        'my' => [
            'card' => [
                'percentage' => env('STRIPE_FEE_MY_CARD_PERCENTAGE'),
                'fixed' => env('STRIPE_FEE_MY_CARD_FIXED'),
            ],
            'fpx' => [
                'percentage' => env('STRIPE_FEE_MY_FPX_PERCENTAGE'),
                'fixed' => env('STRIPE_FEE_MY_FPX_FIXED'),
            ],
            'grabpay' => [
                'percentage' => env('STRIPE_FEE_MY_GRABPAY_PERCENTAGE'),
            ],
        ],
    ],
];
