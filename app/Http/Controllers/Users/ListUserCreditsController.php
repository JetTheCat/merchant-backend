<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\ListUserCreditsRequest;
use App\Http\Resources\UserCreditResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ListUserCreditsController extends Controller
{
    public function __invoke(ListUserCreditsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = $request->user()
            ->userCredits()
            ->with('merchant')
            ->hasBalance()
            ->orderByDesc('balance')
            ->orderBy('id');

        if (isset($validated['category'])) {
            $query->whereHas('merchant.category', function (Builder $query) use ($validated) {
                $query->where('slug', $validated['category']);
            });
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->whereHas('merchant', function (Builder $query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('business_name', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $credits = $query->cursorPaginate($request->getLimit());

        return response()->json([
            'data' => UserCreditResource::collection($credits->items()),
            'meta' => [
                'per_page' => $request->getLimit(),
                'next_cursor' => $credits->nextCursor()?->encode(),
                'prev_cursor' => $credits->previousCursor()?->encode(),
                'has_more' => $credits->hasMorePages(),
            ],
        ], Response::HTTP_OK);
    }
}
