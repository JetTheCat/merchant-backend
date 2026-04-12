<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchants;

use App\Http\Controllers\Controller;
use App\Http\Requests\Merchants\GetMerchantsRequest;
use App\Http\Resources\MerchantCustomerResource;
use App\Http\Responses\CursorPaginationMeta;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetMerchantsController extends Controller
{
    public function __invoke(GetMerchantsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Merchant::query()
            ->with('category')
            ->active();

        if (isset($validated['category'])) {
            $query->whereHas('category', function ($q) use ($validated) {
                $q->where('slug', $validated['category']);
            });
        }

        if (isset($validated['city'])) {
            $query->where('city', $validated['city']);
        }

        if (isset($validated['state'])) {
            $query->where('state', $validated['state']);
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('business_name', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $radius = $validated['radius'] ?? 10;
            $query->nearLocation(
                $validated['latitude'],
                $validated['longitude'],
                $radius
            );
            $merchants = $query->cursorPaginate($request->getLimit());
        } else {
            $merchants = $query->orderBy('id')->cursorPaginate($request->getLimit());
        }

        return response()->json([
            'data' => MerchantCustomerResource::collection($merchants->items()),
            'meta' => CursorPaginationMeta::build($merchants, $request->getLimit()),
        ], Response::HTTP_OK);
    }
}
