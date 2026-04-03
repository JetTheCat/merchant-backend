<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\UpdateCreditPackageRequest;
use App\Http\Resources\CreditPackageResource;
use Illuminate\Http\JsonResponse;

class UpdateCreditPackageController extends Controller
{
    public function __invoke(UpdateCreditPackageRequest $request, string $id): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $package = $merchant->creditPackages()->findOrFail($id);

        $package->update($request->validated());

        return response()->json([
            'data' => new CreditPackageResource($package),
        ]);
    }
}
