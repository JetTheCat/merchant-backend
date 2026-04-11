<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\PurchaseCreditPackageRequest;
use App\Models\CreditPackage;
use App\Services\CreditPurchaseService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PurchaseCreditPackageController extends Controller
{
    public function __construct(
        private readonly CreditPurchaseService $creditPurchaseService,
    ) {}

    public function __invoke(PurchaseCreditPackageRequest $request, CreditPackage $package): JsonResponse
    {
        $result = $this->creditPurchaseService->processPurchase($request->user(), $package);

        return response()->json([
            'data' => [
                'payment_id' => $result->paymentId,
                'client_secret' => $result->clientSecret,
            ],
        ], Response::HTTP_CREATED);
    }
}
