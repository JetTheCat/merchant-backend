<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'Invalid password reset link.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ], Response::HTTP_OK);
    }
}
