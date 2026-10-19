<?php

use App\Models\ApprovalStage;
use App\Services\ApprovalStageSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftarkan tahap approval `kabag_nurse` (Kabag Perawat).
 *
 * Konstanta ApprovalFlowService::APPROVER_STAGES sudah memuat kabag_nurse,
 * tetapi bila konfigurasi admin di database sudah aktif, stagesConfig()
 * membaca tabel approval_stages — tanpa baris ini role kabag_nurse tidak
 * dikenali sebagai approver (middleware role:approver membalas 403).
 *
 * Migrasi ini hanya menulis baris tahap bila konfigurasi DB memang sudah
 * terisi (tabel tidak kosong), supaya instalasi yang belum memakai
 * konfigurasi DB tetap memakai konstanta bawaan apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approval_stages')) {
            return;
        }

        // Tabel kosong = konfigurasi DB belum dipakai; konstanta bawaan sudah
        // memuat kabag_nurse, jadi tidak perlu baris tahap.
        if (ApprovalStage::query()->count() === 0) {
            return;
        }

        if (!ApprovalStage::query()->where('key', 'kabag_nurse')->exists()) {
            ApprovalStage::create([
                'key'         => 'kabag_nurse',
                'label'       => 'Kabag Perawat',
                'role'        => 'kabag_nurse',
                'is_pj'       => false,
                'is_active'   => true,
                'sort_order'  => ((int) ApprovalStage::query()->max('sort_order')) + 1,
                'description' => 'Kepala Bagian Perawat.',
            ]);
        }

        // Kolom {stage}_status/_signature/_note/_approved_by/_approved_at di
        // tabel pengajuan, sesuai konvensi stage == role == prefix kolom.
        ApprovalStageSchema::ensureStageColumns('kabag_nurse');
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_stages')) {
            return;
        }

        ApprovalStage::query()->where('key', 'kabag_nurse')->delete();

        foreach (ApprovalStageSchema::SUBMISSION_TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = [];

            foreach (['_status', '_signature', '_note', '_approved_by', '_approved_at'] as $suffix) {
                $column = 'kabag_nurse' . $suffix;

                if (Schema::hasColumn($table, $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                    $blueprint->dropColumn($columns);
                });
            }
        }
    }
};
