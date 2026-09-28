<?php

namespace App\Support;

use App\Services\ApprovalFlowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Susun daftar blok tanda tangan untuk dokumen PDF (cuti / izin / lembur).
 *
 * Masalah pada surat lama: area tanda tangan hanya menyediakan satu kolom
 * "Mengetahui", padahal rantai approval grup UMUM / MARKETING melewati dua
 * pejabat berbeda (Kabag -> Manager). Tanda tangan Kabag atau Manager akhirnya
 * tidak punya tempat di dokumen.
 *
 * Kelas ini menghitung ulang seluruh tahap yang benar dan mengurutkannya:
 *
 *   Atasan Langsung (PJ)  ->  KABAG (setengah kiri)  ->  MANAJEMEN (setengah kanan)
 *
 * sehingga Kabag dan Manajemen selalu tampil sebagai dua blok terpisah.
 * Tahap yang dirender = tahap milik rantai role pengaju DITAMBAH tahap lama
 * (head / hrd / medical_service) yang sudah punya data, supaya surat lama
 * tetap tampil lengkap.
 *
 * Konvensi kolom mengikuti ApprovalFlowService: nama stage == prefix kolom
 * (status / signature / note / approved_by / approved_at).
 */
class PdfSignatureBlocks
{
    /** Pengisi nama pada kolom yang belum ditandatangani. */
    public const EMPTY_NAME = '........................';

    /** Stage yang masuk kelompok KABAG (tampil sebelum manajemen). */
    public const KABAG_STAGES = ['kabag_umum', 'kabag_marketing'];

    /** Tahap warisan (pra restrukturisasi) yang tetap tampil bila ada datanya. */
    public const LEGACY_STAGES = ['head', 'hrd'];

    /**
     * Daftar blok tanda tangan approver, sudah urut tampilan.
     * Kolom pemohon/pegawai dirender terpisah oleh template.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function for(?Model $submission): array
    {
        if (!$submission instanceof Model) {
            return [];
        }

        $blocks = [];

        if (self::showsPj($submission)) {
            $blocks[] = self::block($submission, 'pj', 'Menyetujui,', 'Atasan Langsung');
        }

        foreach (self::stagesFor($submission) as $stage) {
            $blocks[] = self::block($submission, $stage, 'Mengetahui,', self::labelFor($stage));
        }

        return $blocks;
    }

    /**
     * Jumlah kolom tanda tangan pada baris yang sama (approver + pemohon).
     */
    public static function columnCount(array $blocks, int $extraColumns = 1): int
    {
        return max(count($blocks) + $extraColumns, 1);
    }

    /**
     * Lebar (persen) tiap kolom tanda tangan agar Kabag & Manajemen tetap
     * proporsional berapa pun jumlah tahapnya.
     */
    public static function columnWidth(array $blocks, int $extraColumns = 1): string
    {
        return number_format(100 / self::columnCount($blocks, $extraColumns), 4, '.', '');
    }

    /**
     * Rantai tahap approver untuk sebuah pengajuan, urut tampilan
     * (Kabag lebih dulu, lalu Manajemen).
     *
     * @return array<int, string>
     */
    public static function stagesFor(?Model $submission): array
    {
        if (!$submission instanceof Model) {
            return [];
        }

        $role = self::role($submission);
        $stages = $role === null ? [] : ApprovalFlowService::chainFor($role);

        // Data lama bisa disetujui pada tahap yang tidak lagi ada di rantai
        // (head / hrd) atau pada stage yang datanya masih di kolom lama.
        $candidates = array_merge(
            self::LEGACY_STAGES,
            array_keys(ApprovalFlowService::APPROVER_STAGES)
        );

        foreach ($candidates as $stage) {
            if (!in_array($stage, $stages, true) && self::hasStageData($submission, $stage)) {
                $stages[] = $stage;
            }
        }

        return self::sortStages($stages);
    }

    /**
     * Kabag selalu mendahului manajemen, urutan lain dipertahankan.
     *
     * @param  array<int, string>  $stages
     * @return array<int, string>
     */
    public static function sortStages(array $stages): array
    {
        $kabag = array_values(array_intersect($stages, self::KABAG_STAGES));
        $lain = array_values(array_diff($stages, self::KABAG_STAGES));

        return array_merge($kabag, $lain);
    }

    /**
     * Label jabatan siap tampil.
     */
    public static function labelFor(string $stage): string
    {
        return match ($stage) {
            'pj' => 'Atasan Langsung',
            'head' => 'Kepala Bagian',
            'hrd' => 'HRD',
            default => ApprovalFlowService::labelForStage($stage) ?: Str::headline($stage),
        };
    }

    /**
     * Alamat gambar tanda tangan yang aman dipakai DomPDF.
     * Berkas yang hilang diabaikan supaya PDF tetap tercetak.
     */
    public static function imageUrl(?string $signature): ?string
    {
        $signature = trim((string) $signature);

        if ($signature === '') {
            return null;
        }

        if (str_starts_with($signature, 'data:image') || str_starts_with($signature, 'http')) {
            return $signature;
        }

        $normalized = str_replace('\\', '/', $signature);

        // Path absolut (penyimpanan lama) dipakai apa adanya bila masih ada.
        if (preg_match('#^(?:[a-zA-Z]:/|/)#', $normalized) === 1) {
            return file_exists($signature) ? $signature : null;
        }

        $path = 'storage/' . ltrim($normalized, '/');

        return file_exists(public_path($path)) ? public_path($path) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | INTERNAL
    |--------------------------------------------------------------------------
    */

    /**
     * Satu blok tanda tangan lengkap.
     *
     * @return array<string, mixed>
     */
    private static function block(Model $submission, string $stage, string $heading, string $label): array
    {
        [$status, $signature, $approvedBy] = self::stageData($submission, $stage);

        return [
            'key' => $stage,
            'heading' => $heading,
            'label' => $label,
            'status_text' => $status ? strtoupper($status) : null,
            'image' => self::imageUrl($signature),
            'name' => SubmissionStatus::approverName($approvedBy) ?? self::EMPTY_NAME,
        ];
    }

    /**
     * Status / tanda tangan / approver sebuah tahap, lengkap dengan fallback
     * ke kolom lama (yanmed_* <-> medical_service_*).
     *
     * @return array{0: ?string, 1: ?string, 2: int|null}
     */
    private static function stageData(Model $submission, string $stage): array
    {
        $status = $submission->{$stage . '_status'} ?? null;
        $signature = $submission->{$stage . '_signature'} ?? null;
        $approvedBy = $submission->{$stage . '_approved_by'} ?? null;

        // Stage YANMED memakai dua set kolom: yanmed_* (baru) dan
        // medical_service_* (kompatibilitas data lama). Bila kolom utama belum
        // diproses, pakai kolom lawas agar surat lama tetap tampil benar.
        if ($stage === 'medical_service' && !self::isDecided($status)) {
            $status = $submission->yanmed_status ?: $status;
            $signature = $signature ?: ($submission->yanmed_signature ?? null);
            $approvedBy = $approvedBy ?: ($submission->yanmed_approved_by ?? null);
        } elseif ($stage === 'medical_service' && self::isDecided($submission->yanmed_status ?? null)) {
            // Bila yanmed_* sudah diproses, kolom itulah yang dipakai (lebih baru).
            $status = $submission->yanmed_status;
            $signature = $submission->yanmed_signature ?: $signature;
            $approvedBy = $submission->yanmed_approved_by ?: $approvedBy;
        }

        return [$status, $signature, $approvedBy];
    }

    /**
     * Apakah tahap ini sudah punya data (pernah diproses approver)?
     */
    private static function hasStageData(Model $submission, string $stage): bool
    {
        [$status, $signature, $approvedBy] = self::stageData($submission, $stage);

        return $approvedBy !== null
            || ($signature !== null && trim((string) $signature) !== '')
            || self::isDecided($status);
    }

    /**
     * Tahap dianggap sudah diproses bila statusnya selain kosong / pending.
     */
    private static function isDecided(?string $status): bool
    {
        $status = $status === null ? '' : trim((string) $status);

        return $status !== '' && $status !== 'pending';
    }

    /**
     * Blok PJ hanya tampil untuk role yang memang melewati PJ,
     * atau bila tahap PJ sudah terlanjur diproses.
     */
    private static function showsPj(Model $submission): bool
    {
        $role = self::role($submission);

        if ($role !== null && ApprovalFlowService::needsPjApproval($role)) {
            return true;
        }

        return $submission->pj_approved_by !== null
            || self::isDecided($submission->pj_status ?? null);
    }

    /**
     * Role pengaju (pemilik pengajuan).
     */
    private static function role(?Model $submission): ?string
    {
        return $submission?->user?->role;
    }
}
