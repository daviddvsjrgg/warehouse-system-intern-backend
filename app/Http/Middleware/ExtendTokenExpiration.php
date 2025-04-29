<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExtendTokenExpiration
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $token = $user ? $user->currentAccessToken() : null;

        if ($token) {
            // Cek apakah token sudah expired
            if ($token->expires_at && $token->expires_at->isPast()) {
                // Token expired
                return response()->json([
                    'message' => 'Token expired.'
                ], 401);
            }

            // Kalau token masih hidup, extend lagi 5 menit
            $token->expires_at = now()->addHours(1);
            $token->save();
        }

        return $next($request);
    }
}
