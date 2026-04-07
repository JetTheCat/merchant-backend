<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\CreateCreditPackageRequest;
use App\Http\Resources\CreditPackageResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateCreditPackageController extends Controller
{
    public function __invoke(CreateCreditPackageRequest $request): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $package = $merchant->creditPackages()
            ->create($request->validated())
            ->fresh();

        return response()->json([
            'data' => new CreditPackageResource($package),
        ], Response::HTTP_CREATED);
    }
}
