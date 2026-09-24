<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restrukturisasi alur approval:
     *
     *  - Stage approver baru: medical_service (penunjang medis), kabag_umum,
     *    manager_umum, kabag_marketing, manager_finance
     *    (masing-masing: status/signature/note/approved_by/approved_at).
     *    Konvensi nama stage == nama role == prefix kolom.
     *  - Kolom lama medical_service_* tetap dipertahankan demi kompatibilitas
     *    data & akun lama (ditulis bersamaan dengan kolom yanmed_*).
     *  - leaves.hrd_status & leaves.hrd_note: kolom ini dipakai controller
     *    tetapi belum pernah ada -> pengajuan cuti selalu gagal SQL.
     *  - overtimes.status / pj_status / hrd_status masih ENUM sempit ->
     *    diperlebar ke VARCHAR(50) agar status waiting_* bisa disimpan.
     */
    private const TABLES = ['leaves', 'permissions', 'overtimes'];

    private const STAGES = ['kabag_umum', 'manager_umum', 'kabag_marketing', 'manager_finance', 'director'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {

                foreach (self::STAGES as $stage) {

                    if (!Schema::hasColumn($tableName, $stage . '_status')) {
                        $table->string($stage . '_status')
                            ->default('pending')
                            ->after('status');
                    }

                    if (!Schema::hasColumn($tableName, $stage . '_signature')) {
                        $table->longText($stage . '_signature')
                            ->nullable()
                            ->after($stage . '_status');
                    }

                    if (!Schema::hasColumn($tableName, $stage . '_note')) {
                        $table->text($stage . '_note')
                            ->nullable()
                            ->after($stage . '_signature');
                    }

                    if (!Schema::hasColumn($tableName, $stage . '_approved_by')) {
                        $table->unsignedBigInteger($stage . '_approved_by')
                            ->nullable()
                            ->after($stage . '_note');
                    }

                    if (!Schema::hasColumn($tableName, $stage . '_approved_at')) {
                        $table->timestamp($stage . '_approved_at')
                            ->nullable()
                            ->after($stage . '_approved_by');
                    }
                }

                if (!Schema::hasColumn($tableName, 'medical_service_note')) {
                    $table->text('medical_service_note')
                        ->nullable()
                        ->after('medical_service_signature');
                }

                // Kolom yanmed_* (nama baru penunjang medis) dibuat bersamaan
                // agar nama stage == nama role == prefix kolom tetap konsisten.
                // Kolom director_* dibuat bersamaan agar chair final direktur punya kolom lengkap.
                foreach (['medical_service', 'director'] as $extraStage) {
                    if (!Schema::hasColumn($tableName, $extraStage . '_status')) {
                        $table->string($extraStage . '_status')
                            ->default('pending')
                            ->after('status');
                    }

                    if (!Schema::hasColumn($tableName, $extraStage . '_signature')) {
                        $table->longText($extraStage . '_signature')
                            ->nullable()
                            ->after($extraStage . '_status');
                    }

                    if (!Schema::hasColumn($tableName, $extraStage . '_note')) {
                        $table->text($extraStage . '_note')
                            ->nullable()
                            ->after($extraStage . '_signature');
                    }

                    if (!Schema::hasColumn($tableName, $extraStage . '_approved_by')) {
                        $table->unsignedBigInteger($extraStage . '_approved_by')
                            ->nullable()
                            ->after($extraStage . '_note');
                    }

                    if (!Schema::hasColumn($tableName, $extraStage . '_approved_at')) {
                        $table->timestamp($extraStage . '_approved_at')
                            ->nullable()
                            ->after($extraStage . '_approved_by');
                    }
                }
            });
        }

        // leaves: kolom hrd_status / hrd_note yang belum pernah dibuat
        if (!Schema::hasColumn('leaves', 'hrd_status')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->string('hrd_status')->default('pending')->after('status');
            });
        }

        if (!Schema::hasColumn('leaves', 'hrd_note')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->text('hrd_note')->nullable()->after('hrd_status');
            });
        }

        // overtimes: ENUM sempit -> VARCHAR agar status waiting_* baru bisa disimpan
        DB::statement("ALTER TABLE `overtimes` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE `overtimes` MODIFY `pj_status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE `overtimes` MODIFY `hrd_status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // ENUM asal overtimes hanya menampung 4 nilai -> aman dikembalikan
        DB::statement("ALTER TABLE `overtimes` MODIFY `status` ENUM('pending','waiting_hrd','approved','rejected') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE `overtimes` MODIFY `pj_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE `overtimes` MODIFY `hrd_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");

        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {

                $columns = [];

                foreach (['medical_service', ...self::STAGES] as $stage) {
                    foreach (['_status', '_signature', '_note', '_approved_by', '_approved_at'] as $suffix) {
                        if (Schema::hasColumn($tableName, $stage . $suffix)) {
                            $columns[] = $stage . $suffix;
                        }
                    }
                }

                // Kolom lama medical_service_* adalah kolom kompatibilitas -> ikut dihapus saat rollback.
                foreach (['_status', '_signature', '_note', '_approved_by', '_approved_at'] as $suffix) {
                    if (Schema::hasColumn($tableName, 'medical_service' . $suffix)) {
                        $columns[] = 'medical_service' . $suffix;
                    }
                }

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
