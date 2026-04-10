<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\QueueConnections;
use App\Constants\QueueNames;
use App\Models\Payment;
use App\Models\StripeEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly StripeEvent $stripeEvent,
    ) {
        $this->onConnection(QueueConnections::REDIS)
            ->onQueue(QueueNames::DEFAULT);
    }

    public function handle(): void
    {
        $payload = $this->stripeEvent->payload;
        $paymentIntentId = $payload['data']['object']['id'] ?? null;

        match ($this->stripeEvent->type) {
            'payment_intent.succeeded' => $this->handleSucceeded($paymentIntentId, $payload),
            'payment_intent.payment_failed' => $this->handleFailed($paymentIntentId, $payload),
            default => null,
        };

        $this->stripeEvent->markAsProcessed();
    }

    private function handleSucceeded(?string $paymentIntentId, array $payload): void
    {
        if ($paymentIntentId === null) {
            return;
        }

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($payment === null) {
            Log::warning('Payment not found for succeeded intent', ['payment_intent_id' => $paymentIntentId]);

            return;
        }

        $chargeId = $payload['data']['object']['latest_charge'] ?? '';

        $payment->markAsSucceeded($chargeId, $payload);
    }

    private function handleFailed(?string $paymentIntentId, array $payload): void
    {
        if ($paymentIntentId === null) {
            return;
        }

        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($payment === null) {
            Log::warning('Payment not found for failed intent', ['payment_intent_id' => $paymentIntentId]);

            return;
        }

        $reason = $payload['data']['object']['last_payment_error']['message'] ?? 'Payment failed.';

        $payment->markAsFailed($reason, $payload);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessStripeWebhook job failed permanently', [
            'stripe_event_id' => $this->stripeEvent->stripe_event_id,
            'type' => $this->stripeEvent->type,
            'error' => $exception->getMessage(),
        ]);
    }
}
