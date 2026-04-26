<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'camel.response' => \App\Http\Middleware\ConvertResponseKeysToCamelCase::class,
            'snake.request' => \App\Http\Middleware\ConvertRequestKeysToSnakeCase::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'user.active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (RouteNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Route not found.',
                ], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Not found',
                ], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Method not allowed.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Method not allowed',
                ], Response::HTTP_METHOD_NOT_ALLOWED);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Not found',
                ], Response::HTTP_NOT_FOUND);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error' => 'Authentication required',
                ], Response::HTTP_UNAUTHORIZED);
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                logger()->error('Database error', [
                    'message' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                ]);

                return response()->json([
                    'message' => 'Something went wrong. Please try again later.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'An error occurred.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Server error',
                ], $e->getStatusCode());
            }
        });
    })->create();
