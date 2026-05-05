<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next, $role)
    {
        if ($request->user()->role !== $role) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        return $next($request);
    }
}
