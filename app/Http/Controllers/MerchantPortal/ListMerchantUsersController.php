<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListMerchantUsersController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $users = $request->user()
            ->merchant()
            ->firstOrFail()
            ->merchantUsers()
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => MerchantUserResource::collection($users),
        ]);
    }
}
