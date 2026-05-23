<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantPortal\UpdateMerchantUserPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UpdateMerchantUserPasswordController extends Controller
{
    public function __invoke(UpdateMerchantUserPasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ], Response::HTTP_OK);
    }
}
