<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\ListMerchantLocationsRequest;
use App\Http\Resources\MerchantLocationResource;
use App\Http\Responses\CursorPaginationMeta;
use App\Models\MerchantLocation;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ListMerchantLocationsController extends Controller
{
    public function __invoke(ListMerchantLocationsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = MerchantLocation::where('merchant_id', $request->user()->merchant_id)
            ->when(isset($validated['is_active']), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when(isset($validated['state']), fn ($q) => $q->where('state', $validated['state']))
            ->when(isset($validated['name']), fn ($q) => $q->where('name', 'ilike', '%'.$validated['name'].'%'))
            ->orderBy('name')
            ->orderBy('id');

        $locations = $query->cursorPaginate($request->getLimit());

        return response()->json([
            'data' => MerchantLocationResource::collection($locations),
            'meta' => CursorPaginationMeta::build($locations, $request->getLimit()),
        ], Response::HTTP_OK);
    }
}
