<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Retrieve API key from query string or headers
        $apiKey = $request->query('api-key') ?? $request->header('X-API-KEY');

        // Validate the API key against the value in the .env file
        if (!$apiKey || $apiKey !== config('app.api_key')) {
            return response()->json(['message' => 'Unauthorized: Invalid API Key'], 401);
        }

        return $next($request);
    }
}
