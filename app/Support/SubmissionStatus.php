<?php

namespace App\Support;

use App\Models\User;
use App\Services\ApprovalFlowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Status & catatan approval pengajuan personel (izin, cuti, lembur).
 *
 * Satu tempat untuk membaca:
 *   - catatan/alasan penolakan setiap tahap approval (PJ, HRD, dst),
 *   - apakah pengajuan masih boleh diubah pemiliknya,
 *   - nama approver (diambil sekaligus dalam satu query).
 *
 * Dipakai oleh komponen Blade `x-rejection-banner`, `x-approval-notes`, dan
 * `x-submission-edit-form` agar menu Riwayat konsisten di izin/cuti/lembur.
 *
 * Konvensi kolom mengikuti ApprovalFlowService: nama stage == prefix kolom
 * (status / signature / note / approved_by / approved_at).
 */
class SubmissionStatus
{
    /**
     * Status global yang masih boleh diubah pemilik pengajuan.
     * - pending   : pengajuan baru menunggu verifikasi tahap pertama (PJ).
     * - waiting_* : pengajuan sedang dalam proses menunggu approval stage lanjutan
     *               (misal: hrd, sekre, supervisor yang langsung waiting_director,
     *               atau pengajuan yang sudah diverifikasi PJ lalu menuju tahapan berikutnya).
     * - rejected  : ditolak di stage mana pun, boleh direvisi lalu dikirim ulang.
     *
     * Catatan: Hanya status 'approved' yang tidak boleh diubah.
     */
    public const EDITABLE = [
        'pending',
        'waiting_head',
        'waiting_hrd',
        'waiting_medical_service',
        'waiting_yanmed', // nama lama sesi development, tetap editable
        'waiting_kabag_umum',
        'waiting_manager_umum',
        'waiting_kabag_marketing',
        'waiting_manager_finance',
        'waiting_director',
        'rejected',
    ];

    /**
     * Label status global siap tampil (badge daftar pengajuan).
     * Status stage lain (waiting_*) tetap dianggap menunggu verifikasi.
     */
    public const STATUS_LABELS = [
        'pending' => 'Menunggu Verifikasi',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    /**
     * Suffix kolom pemilik keputusan (dipakai saat mengambil nama approver).
     */
    public const APPROVED_BY_SUFFIX = '_approved_by';

    /**
     * Cache nama approver per request (id => name).
     */
    private static array $approverNames = [];

    /**
     * Stage + label yang catatannya bisa ditampilkan (urut tampilan).
     */
    public static function stages(): array
    {
        $stages = [
            'pj' => 'Penanggung Jawab (PJ)',
            'hrd' => 'HRD Verifikator',
            'head' => 'Kepala Bagian',
        ];

        foreach (ApprovalFlowService::APPROVER_STAGES as $stage => $config) {
            $stages[$stage] = $config['label'];
        }

        return $stages;
    }

    /**
     * Nama relasi approver untuk sebuah stage, mis. 'pj' -> pjApprover,
     * 'manager_finance' -> managerFinanceApprover.
     */
    public static function relationFor(string $stage): string
    {
        if (in_array($stage, ['pj', 'hrd', 'head'], true)) {
            return $stage . 'Approver';
        }

        return Str::camel($stage) . 'Approver';
    }

    /**
     * Catatan approval yang perlu ditampilkan untuk sebuah pengajuan.
     *
     * Sebuah stage dianggap punya catatan bila kolom `_note` terisi atau
     * `_status`-nya 'rejected' (ditolak tanpa catatan tertulis tetap tampil).
     *
     * Setiap item: stage, label, note, status, rejected, revision, approver, at.
     */
    public static function notes(?Model $submission): array
    {
        if (!$submission instanceof Model) {
            return [];
        }

        $attributes = $submission->getAttributes();
        $notes = [];

        foreach (self::stages() as $stage => $label) {
            $noteColumn = $stage . '_note';

            // Dataset lama bisa belum memiliki kolom stage baru.
            if (!array_key_exists($noteColumn, $attributes)) {
                continue;
            }

            $note = $attributes[$noteColumn];
            $note = is_string($note) ? trim($note) : $note;

            $status = $attributes[$stage . '_status'] ?? null;
            $approvedBy = $attributes[$stage . self::APPROVED_BY_SUFFIX] ?? null;

            $isRejected = $status === 'rejected';

            if (($note === null || $note === '') && !$isRejected) {
                continue;
            }

            $notes[] = [
                'stage' => $stage,
                'label' => $label,
                'note' => ($note === '' ? null : $note),
                'status' => $status,
                'rejected' => $isRejected,
                // Stage pernah memutus (approved_by terisi) tetapi status kembali
                // 'pending' => catatan berasal dari siklus sebelum revisi.
                'revision' => !$isRejected && $status === 'pending' && $approvedBy !== null,
                'approver' => self::approverName($approvedBy),
                'at' => $attributes[$stage . '_approved_at'] ?? null,
            ];
        }

        $weight = fn(array $note) => $note['rejected'] ? 0 : ($note['revision'] ? 1 : 2);

        usort($notes, fn(array $a, array $b) => $weight($a) <=> $weight($b));

        return $notes;
    }

    /**
     * Catatan yang benar-benar berupa alasan penolakan.
     */
    public static function rejections(?Model $submission): array
    {
        return array_values(array_filter(
            self::notes($submission),
            fn(array $note) => $note['rejected']
        ));
    }

    /**
     * Apakah pengajuan sedang/pernah ditolak pada siklus berjalan.
     */
    public static function hasRejection(?Model $submission): bool
    {
        if (!$submission instanceof Model) {
            return false;
        }

        if ($submission->status === 'rejected') {
            return true;
        }

        return self::rejections($submission) !== [];
    }

    /**
     * Apakah pengajuan masih boleh diubah pemiliknya.
     */
    public static function isEditable(?Model $submission): bool
    {
        if (!$submission instanceof Model) {
            return false;
        }

        return in_array($submission->status, self::EDITABLE, true);
    }

    /**
     * Label status siap tampil untuk sebuah pengajuan.
     * Status tahap (waiting_*) tetap dibaca sebagai "Menunggu Verifikasi".
     */
    public static function statusLabel(?Model $submission): string
    {
        if (!$submission instanceof Model) {
            return '-';
        }

        return self::STATUS_LABELS[$submission->status] ?? 'Menunggu Verifikasi';
    }

    /**
     * Kelas Tailwind untuk badge status sebuah pengajuan.
     */
    public static function statusTone(?Model $submission): string
    {
        $status = $submission instanceof Model ? (string) $submission->status : '';

        return match ($status) {
            'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'rejected' => 'bg-red-50 text-red-700 border-red-200',
            default => 'bg-amber-50 text-amber-700 border-amber-200',
        };
    }

    /**
     * Ambil nama approver sekaligus (satu query) untuk banyak pengajuan,
     * supaya tampilan riwayat tidak menimbulkan N+1.
     */
    public static function primeApproverNames(iterable $submissions): void
    {
        $stages = array_keys(self::stages());
        $ids = [];

        foreach ($submissions as $submission) {
            if (!$submission instanceof Model) {
                continue;
            }

            $attributes = $submission->getAttributes();

            foreach ($stages as $stage) {
                $id = $attributes[$stage . self::APPROVED_BY_SUFFIX] ?? null;

                if ($id) {
                    $ids[(int) $id] = true;
                }
            }
        }

        if ($ids === []) {
            return;
        }

        self::loadApproverNames(array_values(array_diff(
            array_keys($ids),
            array_keys(self::$approverNames)
        )));
    }

    /**
     * Nama approver berdasarkan id (hasil cache per request).
     */
    public static function approverName($id): ?string
    {
        if (!$id) {
            return null;
        }

        $id = (int) $id;

        if (!array_key_exists($id, self::$approverNames)) {
            self::loadApproverNames([$id]);
        }

        return self::$approverNames[$id] ?? null;
    }

    /**
     * Kosongkan cache nama approver (dipakai pengujian / request baru).
     */
    public static function flushApproverNames(): void
    {
        self::$approverNames = [];
    }

    /**
     * Muat nama approver dari database lalu simpan di cache per request.
     */
    private static function loadApproverNames(array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return;
        }

        foreach (User::whereIn('id', $ids)->pluck('name', 'id') as $id => $name) {
            self::$approverNames[(int) $id] = $name;
        }

        // Tandai id yang tidak ditemukan supaya tidak di-query berulang.
        foreach ($ids as $id) {
            if (!array_key_exists($id, self::$approverNames)) {
                self::$approverNames[$id] = null;
            }
        }
    }
}
