<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $userRole = auth()->user()->role;

        foreach ($roles as $role) {

            // PJ GROUP
            if ($role === 'pj') {
                if (str_starts_with($userRole, 'pj_')) {
                    return $next($request);
                }
            }

            // HRD
            if ($role === 'hrd') {
                if ($userRole === 'hrd') {
                    return $next($request);
                }
            }

            // exact match fallback
            if ($userRole === $role) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak');
    }
}
