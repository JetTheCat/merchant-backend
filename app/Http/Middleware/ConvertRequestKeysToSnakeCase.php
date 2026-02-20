<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Str;
use Symfony\Component\HttpFoundation\Response;

class ConvertRequestKeysToSnakeCase
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isJson()) {
            $data = $this->convertKeysToSnakeCase($request->all());
            $request->replace($data);
        }

        return $next($request);
    }

    protected function convertKeysToSnakeCase(array $data): array
    {
        return collect($data)->mapWithKeys(
            function ($value, $key) {
                $snakeKey = Str::snake($key);

                if (is_array($value)) {
                    $value = $this->convertKeysToSnakeCase($value);
                }

                return [$snakeKey => $value];
            }
        )->toArray();
    }
}
