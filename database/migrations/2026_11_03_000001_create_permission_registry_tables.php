<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permission registry P20.1.
 *
 * Catatan penting: tabel `permissions` sudah dipakai sebagai tabel transaksi
 * IZIN. Registry akses sengaja memakai nama `permission_registry` agar tidak
 * mengubah atau menduplikasi kontrak transaksi existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('label', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('permission_registry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('permission_modules')
                ->restrictOnDelete();
            $table->string('key', 120)->unique();
            $table->string('label', 160);
            $table->string('category', 30);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module_id', 'category']);
            $table->index(['is_active', 'category']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            // Snapshot string dipertahankan persis seperti users.role.
            // Tidak dibuat FK agar role lama tetap tersimpan walau user count 0.
            $table->string('role_key', 255);
            $table->foreignId('permission_id')
                ->constrained('permission_registry')
                ->restrictOnDelete();
            $table->boolean('granted')->default(true);
            $table->timestamps();

            $table->unique(['role_key', 'permission_id'], 'role_permissions_role_permission_unique');
            $table->index('role_key', 'role_permissions_role_key_index');
        });

        Schema::create('permission_legacy_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('http_method', 30);
            $table->string('uri_pattern', 255);
            $table->string('route_name', 160)->nullable();
            $table->string('controller_action', 255);
            $table->text('reason');
            $table->text('existing_guard')->nullable();
            $table->text('risk')->nullable();
            $table->string('migration_status', 40)->default('active');
            $table->string('owner_module', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['route_name', 'is_active']);
            $table->index(['http_method', 'uri_pattern']);
            $table->index(['migration_status', 'is_active']);
        });

        Schema::create('permission_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('target_role_key', 255);
            $table->foreignId('permission_id')
                ->nullable()
                ->constrained('permission_registry')
                ->nullOnDelete();
            $table->boolean('before_granted')->nullable();
            $table->boolean('after_granted')->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['target_role_key', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_change_logs');
        Schema::dropIfExists('permission_legacy_exceptions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permission_registry');
        Schema::dropIfExists('permission_modules');
    }
};
