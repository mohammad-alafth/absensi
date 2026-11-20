<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The registry seeder is intentionally not part of DatabaseSeeder;
        // migrations must still be reproducible on the isolated test DB.
        app(\Database\Seeders\PermissionRegistrySeeder::class)->run();
        $matrix = require base_path('config/p2_1_role_permission_matrix.php');
        $employee = $matrix['employee'];
        $approval = $matrix['approval'];
        $roleCapabilities = [];

        foreach ($matrix['self_service_roles'] as $role) {
            $roleCapabilities[$role] = $employee;
        }

        foreach ($matrix['pj_roles'] as $role) {
            $roleCapabilities[$role] = array_merge($employee, $approval, [
                'shift_request.approve', 'shift_request.reject',
                'shift.view', 'shift.assign', 'roster.view', 'roster.update',
            ]);
        }

        foreach ($matrix['approver_roles'] as $role) {
            $roleCapabilities[$role] = array_merge($employee, $approval, ['report.view']);
        }

        $roleCapabilities['hrd'] = array_merge($employee, $approval, [
            'report.view', 'report.export', 'calendar.view', 'calendar.export',
            'face.review.view', 'face.review.approve', 'face.review.reject',
            'shift.view', 'shift.assign',
            'user.approval', 'user.password.reset', 'user.quota.update',
        ]);
        $roleCapabilities['director'] = array_merge($employee, $approval, [
            'report.view', 'report.export', 'calendar.view', 'calendar.export',
            'face.review.view', 'face.review.approve', 'face.review.reject',
            'shift.view', 'shift.assign',
            'user.approval', 'user.password.reset', 'user.quota.update',
        ]);
        $roleCapabilities['casemix'] = array_values(array_diff($employee, ['dashboard.view']));
        $roleCapabilities['admin'] = [
            'dashboard.view', 'admin.control-center.view',
            'approval_config.view', 'approval_config.create', 'approval_config.update',
            'approval_config.delete', 'approval_config.activate',
            'face_settings.view', 'face_settings.update', 'face_settings.reset',
            'face_service.test', 'face_service.control', 'face_audit.view',
            'user.approval', 'user.password.reset', 'user.quota.update',
        ];

        DB::transaction(function () use ($roleCapabilities): void {
            $adminId = DB::table('users')->where('role', 'admin')->value('id');
            $changed = 0;

            foreach ($roleCapabilities as $role => $capabilities) {
                foreach (array_values(array_unique($capabilities)) as $permissionKey) {
                    $permission = DB::table('permission_registry')
                        ->join('permission_modules', 'permission_modules.id', '=', 'permission_registry.module_id')
                        ->where('permission_registry.key', $permissionKey)
                        ->where('permission_registry.is_active', true)
                        ->where('permission_modules.is_active', true)
                        ->select('permission_registry.id')
                        ->first();

                    if ($permission === null || DB::table('role_permissions')
                        ->where('role_key', $role)
                        ->where('permission_id', $permission->id)
                        ->exists()) {
                        continue;
                    }

                    DB::table('role_permissions')->insert([
                        'role_key' => $role,
                        'permission_id' => $permission->id,
                        'granted' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    DB::table('permission_change_logs')->insert([
                        'actor_id' => $adminId,
                        'target_role_key' => $role,
                        'permission_id' => $permission->id,
                        'before_granted' => null,
                        'after_granted' => true,
                        'correlation_id' => 'p2.1-role-matrix-complete',
                        'reason' => 'P2.1 completion for test/deployment databases where users are created after migrations.',
                        'created_at' => now(),
                    ]);
                    $changed++;
                }
            }

            if ($changed > 0) {
                DB::table('permission_state_versions')
                    ->where('state_key', 'permission')
                    ->increment('version');
            }
        });
    }

    public function down(): void
    {
        // Intentionally irreversible; mappings and audit history are retained.
    }
};
