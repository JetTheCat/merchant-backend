<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;
use Symfony\Component\HttpFoundation\Response;

class ConvertResponseKeysToCamelCase
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $originalData = $response->getData(true); // associative array
            $camelData = $this->convertArrayKeysToCamelCase($originalData);
            $response->setData($camelData);
        }

        return $response;
    }

    protected function convertArrayKeysToCamelCase(array $data): array
    {
        return collect($data)->mapWithKeys(
            function ($value, $key) {
                $camelKey = Str::camel($key);

                if (is_array($value)) {
                    $value = $this->convertArrayKeysToCamelCase($value);
                }

                return [$camelKey => $value];
            }
        )->toArray();
    }
}
