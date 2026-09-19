<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaikan skema alur approval izin/cuti:
     *
     * 1. Kolom pj_* pada tabel permissions wajib ada (dipakai alur
     *    approval PJ -> HRD). Pada sebagian database kolom ini belum ada.
     * 2. Kolom `status` pada permissions & leaves semula ENUM terbatas
     *    ('pending','approved','rejected') padahal alur approval
     *    berjenjang menyimpan nilai waiting_head, waiting_director,
     *    waiting_medical_service, waiting_hrd -> data terpotong/error.
     *    Diperlebar menjadi VARCHAR(50).
     */
    public function up(): void
    {
        // 1. Pastikan kolom pj_* ada
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'pj_status')) {
                $table->string('pj_status')->default('pending')->after('status');
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'pj_approved_by')) {
                $table->unsignedBigInteger('pj_approved_by')->nullable()->after('status');
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'pj_approved_at')) {
                $table->timestamp('pj_approved_at')->nullable()->after('status');
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'pj_note')) {
                $table->text('pj_note')->nullable()->after('status');
            }
        });

        // 2. Perlebar kolom status agar mendukung approval berjenjang
        DB::statement("ALTER TABLE `permissions` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE `leaves` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Kembalikan ke enum awal (nilai waiting_* tidak valid pada enum lama)
        DB::statement("ALTER TABLE `permissions` MODIFY `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE `leaves` MODIFY `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
    }
};
