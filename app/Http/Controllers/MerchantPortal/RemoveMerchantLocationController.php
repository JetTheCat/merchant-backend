<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Services\MerchantLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveMerchantLocationController extends Controller
{
    public function __construct(private readonly MerchantLocationService $merchantLocationService) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();

        $location = $merchant->merchantLocations()->find($id);

        if (! $location) {
            return response()->json(['message' => 'Location not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($location->is_active) {
            return response()->json(
                ['message' => 'Location must be deactivated before it can be removed.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->merchantLocationService->remove($location);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
