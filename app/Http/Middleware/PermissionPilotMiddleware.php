<?php

namespace App\Http\Middleware;

use App\Services\PermissionDecision;
use App\Services\PermissionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opt-in, exact-identity pilot enforcement.
 *
 * The middleware is explicitly attached only to the approved dashboard route
 * and is a no-op unless the explicit Group A feature flag is enabled and the
 * current route matches the server-owned pilot entry exactly.
 */
class PermissionPilotMiddleware
{
    public function __construct(private readonly PermissionResolver $resolver)
    {
    }

    public function handle(Request $request, Closure $next, string $pilot = 'group-a'): Response
    {
        if ($pilot !== 'group-a' || !config('permission_pilot.group_a.enabled', false)) {
            return $next($request);
        }

        $route = $request->route();
        $identity = $route === null ? null : [
            'method' => implode(',', $route->methods()),
            'uri' => ltrim($route->uri(), '/'),
            'name' => (string) $route->getName(),
            'action' => $route->getActionName(),
        ];

        $pilot = $this->pilotRoute($identity);
        if ($pilot === null) {
            return $next($request);
        }

        $role = (string) $request->user()?->role;
        $decision = $this->resolver->resolve($role, $pilot['key']);

        if ($decision->decision !== PermissionDecision::GRANTED) {
            abort(403, 'Permission pilot access denied.');
        }

        return $next($request);
    }

    /** @param array{method:string,uri:string,name:string,action:string}|null $identity */
    private function pilotRoute(?array $identity): ?array
    {
        if ($identity === null) {
            return null;
        }

        foreach (config('permission_pilot.group_a.routes', []) as $route) {
            if ($route['method'] === $identity['method']
                && $route['uri'] === $identity['uri']
                && $route['name'] === $identity['name']
                && $route['action'] === $identity['action']) {
                return $route;
            }
        }

        return null;
    }
}
