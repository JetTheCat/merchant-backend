<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case REQUIRES_PAYMENT_METHOD = 'requires_payment_method';
    case REQUIRES_CONFIRMATION = 'requires_confirmation';
    case REQUIRES_ACTION = 'requires_action';
    case PROCESSING = 'processing';
    case REQUIRES_CAPTURE = 'requires_capture';
    case SUCCEEDED = 'succeeded';
    case CANCELED = 'canceled';
    case PAYMENT_FAILED = 'payment_failed';

    public function label(): string
    {
        return match ($this) {
            self::REQUIRES_PAYMENT_METHOD => 'Requires Payment Method',
            self::REQUIRES_CONFIRMATION => 'Requires Confirmation',
            self::REQUIRES_ACTION => 'Requires Action',
            self::PROCESSING => 'Processing',
            self::REQUIRES_CAPTURE => 'Requires Capture',
            self::SUCCEEDED => 'Succeeded',
            self::CANCELED => 'Canceled',
            self::PAYMENT_FAILED => 'Payment Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REQUIRES_PAYMENT_METHOD,
            self::REQUIRES_CONFIRMATION,
            self::REQUIRES_ACTION,
            self::REQUIRES_CAPTURE => 'warning',
            self::PROCESSING => 'info',
            self::SUCCEEDED => 'success',
            self::CANCELED,
            self::PAYMENT_FAILED => 'danger',
        };
    }
}
