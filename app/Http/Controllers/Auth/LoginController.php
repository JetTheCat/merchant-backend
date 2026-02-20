<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => [
                    'credentials' => ['Invalid email or password']
                ]
            ], 422);
        }

        $token = $user->createToken(
            name: config('sanctum.token_name'),
            abilities: ['*'],
            expiresAt: config('sanctum.expiration') ? now()->addSeconds(config('sanctum.expiration')) : null
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->timestamp,
            'user' => new UserResource($user),
        ]);
    }
}
