<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PJMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (
            !$user ||
            (
                !str_starts_with($user->role, 'pj_')
                && $user->role != 'hrd'
            )
        ) {
            abort(403);
        }

        return $next($request);
    }
}