<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| INDEX PERFORMA (TANPA MENGUBAH ALUR APLIKASI)
|--------------------------------------------------------------------------
| Semua penambahan bersifat additive: hanya index baru, tidak ada kolom/data
| yang diubah. Tujuan:
|   - Dashboard/antrean approval (filter `status` + urut `created_at`)
|   - Rekap & laporan HRD per rentang tanggal
|   - Halaman PJ/HRD per divisi (filter `user_id` + `status`)
|   - Laporan harian kehadiran (filter `tanggal` + `status`)
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'leaves_status_created_at_index');
            $table->index(['user_id', 'status'], 'leaves_user_id_status_index');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'permissions_status_created_at_index');
            $table->index(['user_id', 'status'], 'permissions_user_id_status_index');
            $table->index(['user_id', 'tanggal'], 'permissions_user_id_tanggal_index');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'overtimes_status_created_at_index');
            $table->index(['user_id', 'status'], 'overtimes_user_id_status_index');
            $table->index(['user_id', 'overtime_date'], 'overtimes_user_id_date_index');
        });

        Schema::table('shift_change_requests', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'scr_status_created_at_index');
            $table->index(['user_id', 'status'], 'scr_user_id_status_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['tanggal', 'status'], 'attendances_tanggal_status_index');
        });

        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->index(['shift_date', 'user_id'], 'employee_shifts_date_user_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
            $table->index('is_approved', 'users_is_approved_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
            $table->dropIndex('users_is_approved_index');
        });

        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->dropIndex('employee_shifts_date_user_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_tanggal_status_index');
        });

        Schema::table('shift_change_requests', function (Blueprint $table) {
            $table->dropIndex('scr_status_created_at_index');
            $table->dropIndex('scr_user_id_status_index');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropIndex('overtimes_status_created_at_index');
            $table->dropIndex('overtimes_user_id_status_index');
            $table->dropIndex('overtimes_user_id_date_index');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex('permissions_status_created_at_index');
            $table->dropIndex('permissions_user_id_status_index');
            $table->dropIndex('permissions_user_id_tanggal_index');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('leaves_status_created_at_index');
            $table->dropIndex('leaves_user_id_status_index');
        });
    }
};
