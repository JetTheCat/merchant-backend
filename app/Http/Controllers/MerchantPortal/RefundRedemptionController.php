<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\RefundRedemptionRequest;
use App\Http\Resources\CreditTransactionResource;
use App\Models\CreditTransaction;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RefundRedemptionController extends Controller
{
    public function __construct(
        private readonly RedemptionService $redemptionService,
    ) {}

    public function __invoke(RefundRedemptionRequest $request, string $id): JsonResponse
    {
        $merchantUser = $request->user();

        $redemption = CreditTransaction::where('id', $id)
            ->where('merchant_id', $merchantUser->merchant_id)
            ->firstOrFail();

        $refund = $this->redemptionService->refundRedemption(
            $redemption,
            $merchantUser,
            $request->validated('notes'),
        );

        $refund->load('merchant');

        return response()->json(['data' => new CreditTransactionResource($refund)], Response::HTTP_CREATED);
    }
}
