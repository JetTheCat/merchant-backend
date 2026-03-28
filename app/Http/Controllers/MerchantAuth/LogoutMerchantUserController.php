<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LogoutMerchantUserController extends Controller
{
    public function __invoke(): JsonResponse
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
