<?php

namespace Finxp\Flexcube\Tests\Mocks\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role, $guard = null)
    {
        if (Auth::guard($guard)->guest()) {
            return response()->json([
                'message' => 'Unauthorized to make a request'
            ], 401);
        }

        $roles = is_array($role)
            ? $role
            : explode('|', $role);

        if (!Auth::guard($guard)->user()->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        return $next($request);
    }
}
