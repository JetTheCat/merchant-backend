<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsMerchantOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isOwner()) {
            return response()->json([
                'message' => 'Only the merchant owner can perform this action.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
