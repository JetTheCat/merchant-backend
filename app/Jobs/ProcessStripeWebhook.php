<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\QueueConnections;
use App\Constants\QueueNames;
use App\Models\Payment;
use App\Models\StripeEvent;
use App\Services\CreditPurchaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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

    public function handle(CreditPurchaseService $creditPurchaseService): void
    {
        $payload = $this->stripeEvent->payload;
        $paymentIntentId = $payload['data']['object']['id'] ?? null;

        match ($this->stripeEvent->type) {
            'payment_intent.succeeded' => $this->handleSucceeded($paymentIntentId, $payload, $creditPurchaseService),
            'payment_intent.payment_failed' => $this->handleFailed($paymentIntentId, $payload, $creditPurchaseService),
            default => null,
        };

        $this->stripeEvent->markAsProcessed();
    }

    private function handleSucceeded(?string $paymentIntentId, array $payload, CreditPurchaseService $creditPurchaseService): void
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

        DB::transaction(function () use ($payment, $chargeId, $payload, $creditPurchaseService) {
            $payment->markAsSucceeded($chargeId, $payload);
            $creditPurchaseService->grantCredits($payment);
        });
    }

    private function handleFailed(?string $paymentIntentId, array $payload, CreditPurchaseService $creditPurchaseService): void
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

        DB::transaction(function () use ($payment, $reason, $payload, $creditPurchaseService) {
            $payment->markAsFailed($reason, $payload);
            $creditPurchaseService->failPurchase($payment);
        });
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
