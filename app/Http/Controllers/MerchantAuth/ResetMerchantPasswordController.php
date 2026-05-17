<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantAuth;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantAuth\ResetMerchantPasswordRequest;
use App\Models\MerchantUser;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResetMerchantPasswordController extends Controller
{
    public function __invoke(ResetMerchantPasswordRequest $request, string $id): JsonResponse
    {
        $merchantUser = MerchantUser::find($id);

        if (! $merchantUser || ! $merchantUser->isOwner()) {
            return response()->json([
                'message' => 'Invalid password reset link.',
            ], Response::HTTP_FORBIDDEN);
        }

        $merchantUser->update([
            'password' => $request->input('password'),
        ]);

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ], Response::HTTP_OK);
    }
}
