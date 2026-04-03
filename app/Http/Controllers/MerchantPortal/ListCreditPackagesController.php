<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditPackageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListCreditPackagesController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $packages = $request->user()
            ->merchant()
            ->firstOrFail()
            ->creditPackages()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => CreditPackageResource::collection($packages),
        ]);
    }
}
