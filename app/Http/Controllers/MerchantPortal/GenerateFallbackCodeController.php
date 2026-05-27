<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\GenerateFallbackCodeRequest;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GenerateFallbackCodeController extends Controller
{
    public function __construct(
        private readonly RedemptionService $redemptionService,
    ) {}

    public function __invoke(GenerateFallbackCodeRequest $request): JsonResponse
    {
        $merchantUser = $request->user();

        $transaction = $this->redemptionService->generateFallbackCode(
            $merchantUser,
            (float) $request->validated('amount'),
        );

        return response()->json([
            'data' => [
                'transactionId' => $transaction->id,
                'code' => $transaction->fallback_code,
                'amount' => $transaction->amount,
                'expiresAt' => $transaction->expires_at,
            ],
        ], Response::HTTP_CREATED);
    }
}
