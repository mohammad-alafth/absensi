<?php

namespace App\Http\Middleware;

use App\Services\PermissionDecision;
use App\Services\PermissionResolver;
use App\Services\ProtectedRoutePolicy;
use App\Services\ProtectedRouteDecision;
use App\Services\ApprovalFlowService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend enforcement for the server-owned route permission catalog.
 * Existing role/data-scope guards remain authoritative and are not replaced.
 */
class PermissionEnforcementMiddleware
{
    public function __construct(
        private readonly PermissionResolver $resolver,
        private readonly ProtectedRoutePolicy $policy,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()
            ?: Auth::user()
            ?: Auth::guard('web')->user()
            ?: Auth::guard('sanctum')->user();
        if ($user === null || $request->route() === null) {
            return $next($request);
        }

        $hasAuthenticationGuard = collect($request->route()->gatherMiddleware())
            ->contains(static fn (string $middleware): bool => $middleware === 'auth'
                || str_starts_with($middleware, 'auth:')
                || str_starts_with($middleware, 'Illuminate\\Auth\\Middleware\\Authenticate'));
        if (!$hasAuthenticationGuard) {
            return $next($request);
        }

        $metadata = $this->policy->inspect($request->route());
        if ($metadata->decision === ProtectedRouteDecision::METADATA_MISSING) {
            abort(403, 'Permission route metadata is unavailable.');
        }
        if ($metadata->decision === ProtectedRouteDecision::METADATA_CONFLICT) {
            abort(403, 'Permission route metadata is ambiguous.');
        }

        // Explicit legacy exception: existing role/ownership/approval guards
        // remain in force until the route gets a registered capability.
        if ($metadata->permissionKey === null) {
            return $next($request);
        }

        $keys = array_values(array_filter(explode('|', $metadata->permissionKey)));
        foreach ($keys as $key) {
            $decision = $this->resolver->resolve((string) $user->role, $key);
            if ($decision->decision === PermissionDecision::GRANTED) {
                return $next($request);
            }
        }

        // Tree Approval owns admission to its approval center, including a
        // newly configured stage role that cannot be known by this migration.
        // This does not grant any approve/reject action; those routes still
        // require their explicit capability and workflow validation.
        if ($this->isTreeApprovalCenter($request)
            && ApprovalFlowService::stageForApproverRole((string) $user->role) !== null) {
            return $next($request);
        }

        abort(403, 'Permission denied.');
    }

    private function isTreeApprovalCenter(Request $request): bool
    {
        return $request->route()?->getName() === 'hrd.dashboard'
            && collect($request->route()->gatherMiddleware())
                ->contains(static fn (string $middleware): bool => $middleware === 'role:approver');
    }
}
