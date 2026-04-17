<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\ConfirmRedemptionRequest;
use App\Http\Resources\CreditTransactionResource;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ConfirmRedemptionController extends Controller
{
    public function __construct(private readonly RedemptionService $redemptionService) {}

    public function __invoke(ConfirmRedemptionRequest $request): JsonResponse
    {
        $transaction = $this->redemptionService->confirmRedemption(
            $request->validated('token'),
            $request->user(),
        );

        return response()->json([
            'data' => new CreditTransactionResource($transaction->load('merchant')),
        ], Response::HTTP_OK);
    }
}
