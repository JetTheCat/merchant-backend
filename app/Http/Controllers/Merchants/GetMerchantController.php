<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchants;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantCustomerResource;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetMerchantController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $merchant = Merchant::with('category')
            ->active()
            ->find($id);

        if (! $merchant) {
            return response()->json([
                'message' => 'Merchant not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => new MerchantCustomerResource($merchant),
        ], Response::HTTP_OK);
    }
}
