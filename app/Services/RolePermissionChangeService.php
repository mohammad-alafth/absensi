<?php

namespace App\Services;

use App\Models\PermissionChangeLog;
use App\Models\PermissionRegistry;
use App\Models\PermissionStateVersion;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RolePermissionChangeService
{
    public function __construct(
        private readonly ProtectedRoutePolicy $protectedRoutePolicy,
        private readonly PermissionVersionService $versionService,
        private readonly PermissionResolver $permissions,
    ) {
    }

    public function change(
        User $actor,
        string $targetRole,
        string $permissionKey,
        bool $granted,
        string $reason,
        string $correlationId,
    ): PermissionGrantChangeResult {
        if (!$actor->exists || $actor->getKey() === null || $actor->role !== 'admin') {
            throw new AuthorizationException('Only the exact admin role may change permission mappings.');
        }

        if ($targetRole === '' || !User::query()->where('role', $targetRole)->exists()) {
            throw ValidationException::withMessages(['target_role_key' => 'Target role must match an existing users.role exactly.']);
        }

        $permission = PermissionRegistry::query()
            ->with('module')
            ->where('key', $permissionKey)
            ->first();

        if ($permission === null || !$permission->is_active || $permission->module === null || !$permission->module->is_active) {
            throw ValidationException::withMessages(['permission_key' => 'Permission key and module must be active exact registry entries.']);
        }

        if ($this->protectedRoutePolicy->isSystemProtectedCapability($permissionKey)) {
            throw new AuthorizationException('Protected invariant capabilities cannot receive ordinary role mappings.');
        }

        return DB::transaction(function () use ($actor, $targetRole, $permission, $permissionKey, $granted, $reason, $correlationId): PermissionGrantChangeResult {
            $mapping = RolePermission::query()
                ->where('role_key', $targetRole)
                ->where('permission_id', $permission->id)
                ->lockForUpdate()
                ->first();

            $beforeGranted = $mapping?->granted;
            if ($mapping === null) {
                $mapping = RolePermission::query()->create([
                    'role_key' => $targetRole,
                    'permission_id' => $permission->id,
                    'granted' => $granted,
                ]);
            } else {
                $mapping->granted = $granted;
                $mapping->save();
            }

            $audit = PermissionChangeLog::query()->create([
                'actor_id' => $actor->id,
                'target_role_key' => $targetRole,
                'permission_id' => $permission->id,
                'before_granted' => $beforeGranted,
                'after_granted' => $granted,
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            $version = $this->versionService->incrementWithinTransaction();

            return new PermissionGrantChangeResult(
                $mapping->id,
                $audit->id,
                $version,
                $targetRole,
                $permissionKey,
                $granted,
                $beforeGranted,
            );
        }, 3);
    }

    /**
     * Apply an explicit permission delta for one role in one transaction.
     *
     * The map is intentionally sparse: every supplied key is an explicit
     * desired state, while omitted keys are outside this operation's scope
     * and remain unchanged. This prevents a malformed partial request from
     * implicitly revoking unrelated permissions.
     *
     * @param array<string, bool> $desiredPermissions
     */
    public function changeMany(
        User $actor,
        string $targetRole,
        array $desiredPermissions,
        int $expectedVersion,
        string $reason,
        string $correlationId,
    ): PermissionBatchChangeResult {
        $this->assertBatchInput($actor, $targetRole, $desiredPermissions, $expectedVersion, $reason, $correlationId);

        if (!config('admin_control_center.enabled', false)) {
            throw new AuthorizationException('Admin Control Center permission management is disabled.');
        }

        $parentKey = (string) config('admin_control_center.permission_key', 'admin.control-center.view');
        if (!$this->permissions->resolve((string) $actor->role, $parentKey)->granted()) {
            throw new AuthorizationException('The actor lacks the Admin Control Center permission.');
        }

        return DB::transaction(function () use ($actor, $targetRole, $desiredPermissions, $expectedVersion, $reason, $correlationId): PermissionBatchChangeResult {
            $versionState = PermissionStateVersion::query()
                ->where('state_key', PermissionVersionService::STATE_KEY)
                ->lockForUpdate()
                ->first();

            if ($versionState === null) {
                throw new \RuntimeException('Permission state version row is missing.');
            }

            $actualVersion = (int) $versionState->version;
            if ($actualVersion !== $expectedVersion) {
                throw new PermissionVersionConflictException($expectedVersion, $actualVersion);
            }

            if (!User::query()->where('role', $targetRole)->exists()) {
                throw ValidationException::withMessages(['target_role_key' => 'Target role must match an existing users.role exactly.']);
            }

            $permissions = PermissionRegistry::query()
                ->with('module')
                ->whereIn('key', array_keys($desiredPermissions))
                ->get()
                ->keyBy('key');

            if ($permissions->count() !== count($desiredPermissions)) {
                throw ValidationException::withMessages(['permission_keys' => 'Every permission key must be an exact registry entry.']);
            }

            foreach ($permissions as $permission) {
                if (!$permission->is_active || $permission->module === null || !$permission->module->is_active) {
                    throw ValidationException::withMessages(['permission_keys' => 'Every permission and module must be active.']);
                }

                if ($this->protectedRoutePolicy->isSystemProtectedCapability($permission->key)) {
                    throw new AuthorizationException('Protected invariant capabilities cannot receive ordinary role mappings.');
                }
            }

            $mappings = RolePermission::query()
                ->where('role_key', $targetRole)
                ->whereIn('permission_id', $permissions->pluck('id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('permission_id');

            $changes = [];
            foreach ($permissions as $permission) {
                $mapping = $mappings->get($permission->id);
                $beforeGranted = $mapping?->granted;
                $afterGranted = (bool) $desiredPermissions[$permission->key];

                // An absent false mapping is already fail-closed and is not a
                // real revoke. Do not create audit noise for it.
                if ($beforeGranted === null && !$afterGranted) {
                    continue;
                }

                if ($beforeGranted !== null && (bool) $beforeGranted === $afterGranted) {
                    continue;
                }

                if ($mapping === null) {
                    $mapping = RolePermission::query()->create([
                        'role_key' => $targetRole,
                        'permission_id' => $permission->id,
                        'granted' => $afterGranted,
                    ]);
                } else {
                    $mapping->granted = $afterGranted;
                    $mapping->save();
                }

                $audit = PermissionChangeLog::query()->create([
                    'actor_id' => $actor->id,
                    'target_role_key' => $targetRole,
                    'permission_id' => $permission->id,
                    'before_granted' => $beforeGranted,
                    'after_granted' => $afterGranted,
                    'correlation_id' => $correlationId,
                    'reason' => $reason,
                    'created_at' => now(),
                ]);

                $changes[] = [
                    'permission_key' => $permission->key,
                    'before_granted' => $beforeGranted,
                    'after_granted' => $afterGranted,
                    'audit_id' => (int) $audit->id,
                ];
            }

            $newVersion = $actualVersion;
            if ($changes !== []) {
                $versionState->version = $actualVersion + 1;
                $versionState->save();
                $newVersion = (int) $versionState->version;
            }

            return new PermissionBatchChangeResult($targetRole, $newVersion, $actualVersion, $changes);
        }, 3);
    }

    /** @param array<string, bool> $desiredPermissions */
    private function assertBatchInput(
        User $actor,
        string $targetRole,
        array $desiredPermissions,
        int $expectedVersion,
        string $reason,
        string $correlationId,
    ): void {
        if (!$actor->exists || $actor->getKey() === null || $actor->role !== 'admin') {
            throw new AuthorizationException('Only the exact admin role may change permission mappings.');
        }

        if ($targetRole === '' || mb_strlen($targetRole) > 255) {
            throw new AuthorizationException('The protected or malformed target role cannot be changed.');
        }

        if ($expectedVersion < 0 || $reason === '' || mb_strlen($reason) > 500 || $correlationId === '' || mb_strlen($correlationId) > 100) {
            throw ValidationException::withMessages(['payload' => 'The permission batch payload is malformed.']);
        }

        foreach ($desiredPermissions as $key => $value) {
            if (!is_string($key) || $key === '' || !is_bool($value)) {
                throw ValidationException::withMessages(['permission_keys' => 'Permission keys and boolean values are required.']);
            }
        }
    }
}
