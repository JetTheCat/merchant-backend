<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class WebhookEventResult
{
    public function __construct(
        public string $eventId,
        public string $type,
        public array $payload,
    ) {}
}
