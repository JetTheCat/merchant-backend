<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\GenerateRedemptionQrRequest;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GenerateRedemptionQrController extends Controller
{
    public function __construct(
        private readonly RedemptionService $redemptionService,
    ) {}

    public function __invoke(GenerateRedemptionQrRequest $request): JsonResponse
    {
        $merchantUser = $request->user();

        $transaction = $this->redemptionService->generateQr(
            $merchantUser,
            (float) $request->validated('amount'),
        );

        return response()->json([
            'data' => [
                'transactionId' => $transaction->id,
                'qrCode' => $transaction->qr_code,
                'redemptionCode' => $transaction->redemption_code,
                'amount' => $transaction->amount,
                'expiresAt' => $transaction->qr_expires_at,
            ],
        ], Response::HTTP_CREATED);
    }
}
