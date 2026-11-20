<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $moduleId = DB::table('permission_modules')->where('key', 'device')->value('id');

        if ($moduleId === null) {
            $moduleId = DB::table('permission_modules')->insertGetId([
                'key' => 'device',
                'label' => 'Perangkat',
                'description' => 'Pencatatan perangkat milik akun terautentikasi.',
                'sort_order' => 85,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('permission_registry')->updateOrInsert(
            ['key' => 'device.bind'],
            [
                'module_id' => $moduleId,
                'label' => 'Ikat perangkat sendiri',
                'category' => 'action',
                'description' => 'Mencatat perangkat untuk akun yang sedang terautentikasi.',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => DB::table('permission_registry')->where('key', 'device.bind')->value('created_at') ?? now(),
            ],
        );

        Schema::create('device_binding_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('user_devices')->nullOnDelete();
            $table->string('device_uuid_hash', 64)->nullable();
            $table->string('action', 40);
            $table->string('outcome', 40);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['target_user_id', 'created_at']);
            $table->index(['device_uuid_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_binding_audits');
        DB::table('permission_registry')->where('key', 'device.bind')->delete();
        DB::table('permission_modules')->where('key', 'device')->delete();
    }
};
