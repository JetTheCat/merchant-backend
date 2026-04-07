<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchants;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditPackageResource;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMerchantCreditPackagesController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $merchant = Merchant::active()->findOrFail($id);

        $packages = $merchant->creditPackages()
            ->active()
            ->orderByDesc('credit_amount')
            ->get();

        return response()->json([
            'data' => CreditPackageResource::collection($packages),
        ]);
    }
}
