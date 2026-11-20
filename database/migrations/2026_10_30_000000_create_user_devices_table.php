<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| PERANGKAT TERIKAT (DEVICE BINDING)
|--------------------------------------------------------------------------
| Tabel ini mencatat perangkat yang dipakai pengguna saat login/absen.
|
| Keputusan pemilik sistem (30 Oktober 2026):
|   D1 device HANYA DICATAT, tidak pernah memblokir absen.
|   D2 maksimal 3 perangkat aktif per akun.
|   D3 pencabutan perangkat hanya oleh role admin.
|   D5 data disimpan selama akun aktif (cascade saat akun dihapus).
|
| ID perangkat dibuat oleh browser (crypto.randomUUID) dan disimpan di
| localStorage. Nilai ini BUKAN identitas hardware dan bukan faktor
| autentikasi; hanya penanda untuk audit siapa memakai perangkat mana.
|
| Kolom device_id di attendances/overtime_punches dipakai agar penanda
| "device tidak dikenal" bisa dibuktikan saat review admin.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->char('device_uuid', 36)->comment('UUID dari browser (localStorage)');

            $table->string('label', 120)->nullable()->comment('Contoh: Chrome - Android');
            $table->string('platform', 60)->nullable()->comment('Contoh: Android 14');
            $table->string('user_agent', 255)->nullable();
            $table->string('first_ip', 45)->nullable()->comment('IP pencatatan pertama (IPv6 safe)');

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            /*
            |----------------------------------------------------------------------
            | STATUS
            |----------------------------------------------------------------------
            | pending  : menunggu persetujuan (bila device.require_trust = true)
            | trusted  : perangkat terikat normal
            | revoked  : dicabut admin (ganti HP / hilang)
            */
            $table->string('status', 16)->default('trusted');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'device_uuid'], 'user_devices_user_uuid_unique');
            $table->index(['user_id', 'status'], 'user_devices_user_status_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('device_id')
                ->nullable()
                ->after('user_id')
                ->constrained('user_devices')
                ->nullOnDelete();
        });

        Schema::table('overtime_punches', function (Blueprint $table) {
            $table->foreignId('device_id')
                ->nullable()
                ->after('user_id')
                ->constrained('user_devices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('overtime_punches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('device_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('device_id');
        });

        Schema::dropIfExists('user_devices');
    }
};
