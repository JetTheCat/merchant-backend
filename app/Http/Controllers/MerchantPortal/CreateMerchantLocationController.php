<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\CreateMerchantLocationRequest;
use App\Http\Resources\MerchantLocationResource;
use App\Models\MerchantLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CreateMerchantLocationController extends Controller
{
    public function __invoke(CreateMerchantLocationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $location = MerchantLocation::create([
            'merchant_id' => $request->user()->merchant_id,
            'name' => $validated['name'],
            'street' => $validated['street'],
            'city' => $validated['city'],
            'postcode' => $validated['postcode'],
            'state' => $validated['state'],
            'redemption_code' => Str::uuid()->toString(),
        ])->fresh();

        return response()->json([
            'message' => 'Location created successfully.',
            'data' => new MerchantLocationResource($location),
        ], Response::HTTP_CREATED);
    }
}
