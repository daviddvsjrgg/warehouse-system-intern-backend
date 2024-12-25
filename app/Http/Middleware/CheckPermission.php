<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Permission;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        // Get the authenticated user
        $user = auth()->user();

        // Check if the user has any of the specified permissions
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        // If none of the permissions match, return a 403 Forbidden response
        return response()->json(['message' => 'Forbidden'], 403);
    }
}
