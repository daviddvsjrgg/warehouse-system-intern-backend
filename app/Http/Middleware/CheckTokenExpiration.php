<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckTokenExpiration
{
    public function handle(Request $request, Closure $next)
    {
        // Get the authenticated user
        $user = $request->user();

        // Check if the user is authenticated and if the token is expired
        if ($user) {
            // Get the current token
            $token = $user->currentAccessToken();
            
            // Check if token exists and validate its expiration
            if ($token) {
                // Calculate expiration time based on GMT+7
                $expiresAt = $token->created_at->timezone('GMT+7')->addHours(2);
                
                // Check if the token is expired
                if ($expiresAt->isPast()) {
                    // Token is expired - delete it
                    $token->delete();
                    
                    // Return response indicating the token is expired
                    return response()->json(['message' => 'Token expired. Please log in again.'], 401);
                }
            }
        }

        return $next($request);
    }
}
