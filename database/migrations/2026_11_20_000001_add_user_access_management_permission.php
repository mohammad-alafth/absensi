<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = DB::table('permission_modules')->where('key', 'user_admin')->value('id');

        if ($moduleId === null) {
            $moduleId = DB::table('permission_modules')->insertGetId([
                'key' => 'user_admin',
                'label' => 'Administrasi Pengguna',
                'sort_order' => 100,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionId = DB::table('permission_registry')->where('key', 'user.access.manage')->value('id');
        if ($permissionId === null) {
            $permissionId = DB::table('permission_registry')->insertGetId([
                'module_id' => $moduleId,
                'key' => 'user.access.manage',
                'label' => 'Kelola Penerimaan dan Akses Akun',
                'category' => 'action',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminExists = DB::table('users')->where('role', 'admin')->exists();
        if ($adminExists && !DB::table('role_permissions')->where([
            'role_key' => 'admin',
            'permission_id' => $permissionId,
        ])->exists()) {
            DB::table('role_permissions')->insert([
                'role_key' => 'admin',
                'permission_id' => $permissionId,
                'granted' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permission_registry')->where('key', 'user.access.manage')->value('id');
        if ($permissionId !== null) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permission_registry')->where('id', $permissionId)->delete();
        }
    }
};
