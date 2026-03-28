<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetMerchantProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $merchant = $request->user()->merchant()->with('category')->firstOrFail();

        return response()->json([
            'data' => new MerchantResource($merchant),
        ]);
    }
}
