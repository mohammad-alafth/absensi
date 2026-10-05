<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menjamin kolom approve/reject sebuah tahap benar-benar ada di tabel pengajuan.
 *
 * Seluruh aplikasi memakai konvensi kolom per tahap
 * (`{stage}_status`, `{stage}_signature`, `{stage}_note`, `{stage}_approved_by`,
 * `{stage}_approved_at`). Karena itu ketika admin menambah tahap baru di UI,
 * method ini membuat kolomnya otomatis pada tabel cuti, izin, dan lembur —
 * tanpa perlu menulis migration baru.
 */
class ApprovalStageSchema
{
    /** Tabel pengajuan yang menyimpan status approval. */
    public const SUBMISSION_TABLES = ['leaves', 'permissions', 'overtimes'];

    /**
     * Pastikan kolom tahap tersedia.
     *
     * @return array<string, array<int, string>> kolom yang dibuat per tabel
     */
    public static function ensureStageColumns(string $stage): array
    {
        $created = [];

        foreach (self::SUBMISSION_TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $added = [];

            Schema::table($table, function (Blueprint $blueprint) use ($table, $stage, &$added) {
                if (!Schema::hasColumn($table, $stage . '_status')) {
                    $blueprint->string($stage . '_status', 50)->default('pending');
                    $added[] = 'status';
                }

                if (!Schema::hasColumn($table, $stage . '_signature')) {
                    $blueprint->longText($stage . '_signature')->nullable();
                    $added[] = 'signature';
                }

                if (!Schema::hasColumn($table, $stage . '_note')) {
                    $blueprint->text($stage . '_note')->nullable();
                    $added[] = 'note';
                }

                if (!Schema::hasColumn($table, $stage . '_approved_by')) {
                    $blueprint->unsignedBigInteger($stage . '_approved_by')->nullable();
                    $added[] = 'approved_by';
                }

                if (!Schema::hasColumn($table, $stage . '_approved_at')) {
                    $blueprint->timestamp($stage . '_approved_at')->nullable();
                    $added[] = 'approved_at';
                }
            });

            if ($added !== []) {
                $created[$table] = $added;
            }
        }

        return $created;
    }

    /** Apakah kolom tahap sudah lengkap di semua tabel pengajuan? */
    public static function hasStageColumns(string $stage): bool
    {
        foreach (self::SUBMISSION_TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, $stage . '_status')) {
                return false;
            }
        }

        return true;
    }
}