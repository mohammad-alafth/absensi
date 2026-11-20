<?php

namespace App\Services;

use App\Models\PermissionChangeLog;
use App\Models\PermissionRegistry;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AdminPermissionBootstrapService
{
    public const TARGET_ROLE = 'admin';
    public const PERMISSION_KEY = 'admin.control-center.view';

    public function __construct(
        private readonly PermissionVersionService $versionService,
    ) {
    }

    /**
     * Bootstrap exactly one local/testing Admin capability.
     *
     * @return array{mapping_id:int|null,audit_id:int|null,version:int,idempotent:bool}
     */
    public function bootstrap(User $actor, string $reason, string $correlationId): array
    {
        if (!$actor->exists || $actor->getKey() === null || $actor->role !== self::TARGET_ROLE) {
            throw new LogicException('Bootstrap actor must be an existing exact admin user.');
        }

        if (trim($reason) === '' || trim($correlationId) === '') {
            throw new LogicException('Bootstrap reason and correlation ID are required.');
        }

        return DB::transaction(function () use ($actor, $reason, $correlationId): array {
            $permission = PermissionRegistry::query()
                ->with('module')
                ->where('key', self::PERMISSION_KEY)
                ->lockForUpdate()
                ->first();

            if ($permission === null || !$permission->is_active || $permission->module === null || !$permission->module->is_active) {
                throw new LogicException('The exact active Admin Control Center permission is unavailable.');
            }

            $mapping = RolePermission::query()
                ->where('role_key', self::TARGET_ROLE)
                ->where('permission_id', $permission->id)
                ->lockForUpdate()
                ->first();

            if ($mapping !== null) {
                if ($mapping->granted) {
                    $version = $this->versionService->current();
                    if ($version === null) {
                        throw new LogicException('Permission version state is unavailable.');
                    }

                    return [
                        'mapping_id' => $mapping->id,
                        'audit_id' => null,
                        'version' => $version,
                        'idempotent' => true,
                    ];
                }

                throw new LogicException('An explicit denied Admin mapping already exists; review is required.');
            }

            $mapping = RolePermission::query()->create([
                'role_key' => self::TARGET_ROLE,
                'permission_id' => $permission->id,
                'granted' => true,
            ]);

            $audit = PermissionChangeLog::query()->create([
                'actor_id' => $actor->id,
                'target_role_key' => self::TARGET_ROLE,
                'permission_id' => $permission->id,
                'before_granted' => null,
                'after_granted' => true,
                'correlation_id' => $correlationId,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            $version = $this->versionService->incrementWithinTransaction();

            return [
                'mapping_id' => $mapping->id,
                'audit_id' => $audit->id,
                'version' => $version,
                'idempotent' => false,
            ];
        }, 3);
    }
}
