<?php

declare(strict_types=1);

namespace App\Http\Controllers\MerchantAuth;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantAuth\LoginMerchantUserRequest;
use App\Http\Resources\MerchantUserResource;
use App\Models\MerchantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginMerchantUserController extends Controller
{
    public function __invoke(LoginMerchantUserRequest $request): JsonResponse
    {
        $merchantUser = MerchantUser::where('email', $request->email)->first();

        if (! $merchantUser || ! Hash::check($request->input('password'), $merchantUser->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'credentials' => ['Invalid email or password'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $merchantUser->canLogin()) {
            return response()->json([
                'message' => 'Your account is not active. Please contact your administrator.',
            ], Response::HTTP_FORBIDDEN);
        }

        $merchantUser->update([
            'last_login_at' => now()
        ]);

        $token = $merchantUser->createToken(
            name: config('sanctum.token_name'),
            abilities: ['*'],
            expiresAt: config('sanctum.expiration') ? now()->addSeconds(config('sanctum.expiration')) : null
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->timestamp,
            'user' => new MerchantUserResource($merchantUser),
        ]);
    }
}
