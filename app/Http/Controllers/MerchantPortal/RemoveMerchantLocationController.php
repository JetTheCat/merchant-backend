<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RemoveMerchantLocationController extends Controller
{
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

        DB::transaction(function () use ($location): void {
            $location->merchantUsers()->update(['merchant_location_id' => null]);

            if (Transaction::where('merchant_location_id', $location->id)->exists()) {
                $location->delete();
            } else {
                $location->forceDelete();
            }
        });

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
