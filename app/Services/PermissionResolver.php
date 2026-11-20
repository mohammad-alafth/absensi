<?php

namespace App\Services;

use App\Models\PermissionRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Route;
use Throwable;

/**
 * Resolves an exact capability without deciding ownership or data scope.
 *
 * This service is intentionally not attached to routes or middleware. A
 * protected invariant is resolved from server-owned route metadata when the
 * route-aware method is used. Existing Admin/PJ/FacePolicy/P14 guards remain
 * authoritative outside this service.
 */
class PermissionResolver
{
    private const CACHE_KEY_PREFIX = 'permission-resolver:decision';
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly PermissionVersionService $versionService,
        private readonly ProtectedRoutePolicy $protectedRoutePolicy,
    ) {
    }

    public function resolve(
        string $roleKey,
        string $permissionKey,
    ): PermissionDecision {
        $cacheVersion = $this->currentVersion();
        if ($cacheVersion === null) {
            return new PermissionDecision(
                PermissionDecision::DENIED,
                $roleKey,
                $permissionKey,
                'permission_version_unavailable',
            );
        }

        $cacheKey = $this->cacheKey($cacheVersion, $roleKey, $permissionKey);

        if ($cacheVersion !== null) {
            try {
                $cached = Cache::get($cacheKey);
                if (is_array($cached)) {
                    return $this->decisionFromArray($cached);
                }
            } catch (Throwable) {
                // Cache failure is fail-closed for cache use: query the DB.
            }
        }

        $decision = $this->resolveFromDatabase($roleKey, $permissionKey);

        if ($cacheVersion !== null) {
            try {
                Cache::put($cacheKey, $decision->toArray(), self::CACHE_TTL_SECONDS);
            } catch (Throwable) {
                // A cache write failure must never turn into an implicit grant.
            }
        }

        return $decision;
    }

    public function resolveForRoute(
        string $roleKey,
        string $permissionKey,
        Route $route,
    ): PermissionDecision
    {
        $routeDecision = $this->protectedRoutePolicy->inspect($route);
        if (!$routeDecision->protected()) {
            return new PermissionDecision(
                PermissionDecision::DENIED,
                $roleKey,
                $permissionKey,
                $routeDecision->reason,
            );
        }

        if ($this->currentVersion() === null) {
            return new PermissionDecision(
                PermissionDecision::DENIED,
                $roleKey,
                $permissionKey,
                'permission_version_unavailable',
            );
        }

        if ($routeDecision->permissionKey !== null && $routeDecision->permissionKey !== $permissionKey) {
            return new PermissionDecision(
                PermissionDecision::DENIED,
                $roleKey,
                $permissionKey,
                'route_permission_key_mismatch',
            );
        }

        return new PermissionDecision(
            PermissionDecision::PROTECTED_INVARIANT,
            $roleKey,
            $permissionKey,
            'protected_route_metadata_match',
            $routeDecision->permissionKey !== null,
        );
    }

    private function resolveFromDatabase(
        string $roleKey,
        string $permissionKey,
    ): PermissionDecision {
        $permission = PermissionRegistry::query()
            ->with('module')
            ->where('key', $permissionKey)
            ->first();

        if ($permission === null) {
            return new PermissionDecision(
                PermissionDecision::DENIED,
                $roleKey,
                $permissionKey,
                'permission_key_unknown',
            );
        }

        $permissionActive = (bool) $permission->is_active;
        $moduleActive = $permission->module !== null && (bool) $permission->module->is_active;

        if (!$permissionActive) {
            return new PermissionDecision(
                PermissionDecision::DENIED,
                $roleKey,
                $permissionKey,
                'permission_inactive',
                true,
                false,
                $moduleActive,
            );
        }

        if (!$moduleActive) {
            return new PermissionDecision(
                PermissionDecision::DENIED,
                $roleKey,
                $permissionKey,
                'module_inactive',
                true,
                true,
                false,
            );
        }

        $mapping = $permission->rolePermissions()
            ->where('role_key', $roleKey)
            ->first();

        if ($mapping === null) {
            return new PermissionDecision(
                PermissionDecision::UNMAPPED,
                $roleKey,
                $permissionKey,
                'role_permission_mapping_missing',
                true,
                true,
                true,
            );
        }

        $grant = (bool) $mapping->granted;

        return new PermissionDecision(
            $grant ? PermissionDecision::GRANTED : PermissionDecision::DENIED,
            $roleKey,
            $permissionKey,
            $grant ? 'explicit_grant' : 'explicit_denial',
            true,
            true,
            true,
            $grant,
        );
    }

    private function cacheKey(int $version, string $roleKey, string $permissionKey): string
    {
        return implode(':', [
            self::CACHE_KEY_PREFIX,
            $version,
            hash('sha256', implode('|', [$roleKey, $permissionKey])),
        ]);
    }

    private function currentVersion(): ?int
    {
        try {
            return $this->versionService->current();
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $value */
    private function decisionFromArray(array $value): PermissionDecision
    {
        return new PermissionDecision(
            (string) ($value['decision'] ?? PermissionDecision::DENIED),
            (string) ($value['role_key'] ?? ''),
            (string) ($value['permission_key'] ?? ''),
            (string) ($value['reason'] ?? 'cached_decision'),
            (bool) ($value['permission_registered'] ?? false),
            (bool) ($value['permission_active'] ?? false),
            (bool) ($value['module_active'] ?? false),
            array_key_exists('grant', $value) && $value['grant'] !== null ? (bool) $value['grant'] : null,
        );
    }
}
