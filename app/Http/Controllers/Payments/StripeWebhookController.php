<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeWebhook;
use App\Models\StripeEvent;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeService $stripeService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $result = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (SignatureVerificationException) {
            return response()->json(['message' => 'Invalid signature.'], Response::HTTP_UNAUTHORIZED);
        }

        if (StripeEvent::where('stripe_event_id', $result->eventId)->exists()) {
            return response()->json(['message' => 'Event already processed.'], Response::HTTP_OK);
        }

        $stripeEvent = StripeEvent::create([
            'stripe_event_id' => $result->eventId,
            'type' => $result->type,
            'payload' => $result->payload,
        ]);

        ProcessStripeWebhook::dispatch($stripeEvent);

        return response()->json(['message' => 'Webhook received.'], Response::HTTP_OK);
    }
}
