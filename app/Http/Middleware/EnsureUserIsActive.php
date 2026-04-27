<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== UserStatus::ACTIVE) {
            return response()->json([
                'message' => 'Your account has been suspended. Please contact support.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
