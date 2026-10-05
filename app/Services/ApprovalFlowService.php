<?php

namespace App\Services;

/**
 * Pusat konfigurasi alur approval (single source of truth).
 *
 *   MEDICAL   : ugd, ranap, ok, pipp, ro, rm, gizi (+pj_*) -> PJ -> medical_service (final)
 *   UMUM      : it, security, cs (+pj_*)                   -> PJ -> Kabag Umum -> Manager Umum
 *   CASEMIX   : casemix (+pj_casemix)                    -> PJ -> Direktur (final)
 *   HRD       : hrd                                      -> Manager Umum -> Direktur (final, tanpa PJ)
 *   ADMISSION : admission (+pj_admission)                   -> PJ -> Kabag Marketing -> Manager Umum
 *   FINANCE   : accounting, finance (+pj_*)                 -> PJ -> Manager Finance (final)
 *   DIREKTUR  : direktur, manajer, kabag, sekre, supervisor -> Direktur (final, tanpa PJ)
 *
 * Konvensi: nama STAGE == nama ROLE approver == prefix kolom database.
 * (stage "kabag_umum" -> role "kabag_umum" -> kolom kabag_umum_status,
 *  kabag_umum_signature, kabag_umum_note, kabag_umum_approved_by,
 *  kabag_umum_approved_at)
 */
class ApprovalFlowService
{
    /*
    |--------------------------------------------------------------------------
    | STAGE APPROVAL (approver tingkat atas)
    |--------------------------------------------------------------------------
    */
    public const APPROVER_STAGES = [
        'medical_service'          => ['role' => 'medical_service',          'label' => 'YANMED (Penunjang Medis)'],
        'kabag_umum'      => ['role' => 'kabag_umum',      'label' => 'Kabag Umum'],
        'manager_umum'    => ['role' => 'manager_umum',    'label' => 'Manager Umum'],
        'kabag_marketing' => ['role' => 'kabag_marketing', 'label' => 'Kabag Marketing'],
        'manager_finance' => ['role' => 'manager_finance', 'label' => 'Manager Finance'],
        'director'        => ['role' => 'director',        'label' => 'Direktur'],
    ];

    /**
     * Alias role lama -> stage baru. Akun lama 'medical_service' tetap bisa
     * membuka pusat approval & menyetujui tahap 'medical_service'.
     */
    public const APPROVER_ALIASES = [
        'medical_service' => 'medical_service',
    ];

    /*
    |--------------------------------------------------------------------------
    | STATUS DATABASE PER STAGE
    |--------------------------------------------------------------------------
    */
    public const STAGE_STATUS = [
        'medical_service'          => 'waiting_medical_service',
        'kabag_umum'      => 'waiting_kabag_umum',
        'manager_umum'    => 'waiting_manager_umum',
        'kabag_marketing' => 'waiting_kabag_marketing',
        'manager_finance' => 'waiting_manager_finance',
        'director'        => 'waiting_director',
    ];

    /**
     * Status lama sesi development ('waiting_yanmed', nama kolom lama)
     * diperlakukan sama dengan 'waiting_medical_service' agar pengajuan
     * yang masih menyimpan status lama tetap terbaca dengan benar.
     */
    public const LEGACY_STATUS_ALIASES = [
        'waiting_yanmed' => 'waiting_medical_service',
    ];

    /*
    |--------------------------------------------------------------------------
    | RANTAI APPROVAL PER GRUP (setelah tahap PJ)
    |--------------------------------------------------------------------------
    */
    public const GROUP_CHAINS = [
        'medical_service'    => ['medical_service'],
        'umum'      => ['kabag_umum', 'manager_umum'],
        'casemix'   => ['director'],
        'hrd'       => ['manager_umum', 'director'],
        'admission' => ['kabag_marketing', 'manager_umum'],
        'finance'   => ['manager_finance'],
        'direktur'  => ['director'],
    ];

    public const DEFAULT_GROUP = 'direktur';

    /*
    |--------------------------------------------------------------------------
    | MAPPING ROLE PENGAJU -> GRUP
    |--------------------------------------------------------------------------
    | Role yang tidak terdaftar otomatis masuk grup default (direktur).
    */
    public const ROLE_GROUPS = [
        // ---- medical_service / YANMED (penunjang medis) ----
        'ugd' => 'medical_service',
        'pj_ugd' => 'medical_service',
        'ranap' => 'medical_service',
        'pj_ranap' => 'medical_service',
        'ok' => 'medical_service',
        'pj_ok' => 'medical_service',
        'pipp' => 'medical_service',
        'pj_pipp' => 'medical_service',
        'ppp' => 'medical_service',
        'ro' => 'medical_service',
        'pj_ro' => 'medical_service',
        'rm' => 'medical_service',
        'pj_rm' => 'medical_service',
        'medical_record' => 'medical_service',
        'gizi' => 'medical_service',
        'pj_gizi' => 'medical_service',
        'nutrition' => 'medical_service',
        'pharmacist' => 'medical_service',
        'pj_pharmacist' => 'medical_service',
        'nurse' => 'medical_service',
        'pj_nurse' => 'medical_service',
        'nurse_ok' => 'medical_service',
        'pj_nurse_ok' => 'medical_service',
        'koor_nurse' => 'medical_service',

        // ---- UMUM ----
        'it' => 'umum',
        'pj_it' => 'umum',
        'security' => 'umum',
        'pj_security' => 'umum',
        'cs' => 'umum',
        'pj_cs' => 'umum',
        'ipsrs' => 'umum',
        'pj_ipsrs' => 'umum',

        // ---- MARKETING / ADMISSION ----
        'admission' => 'admission',
        'pj_admission' => 'admission',
        'kasir' => 'admission',
        'marketing' => 'admission',
        'pj_marketing' => 'admission',
        'creator' => 'admission',
        'konten_creator' => 'admission',

        // ---- DIRECT ----
        // ---- FINANCE ----
        'accounting' => 'finance',
        'pj_accounting' => 'finance',
        'finance' => 'finance',
        'pj_finance' => 'finance',
        'finance_mgr' => 'finance',

        // ---- DIRECT / DIREKTUR ----
        // Pengajuan casemix masih melewati PJ; sekretariat tidak.
        'casemix' => 'casemix',
        'pj_casemix' => 'casemix',

        // ---- HRD (tanpa PJ) : Manager Umum -> Direktur ----
        'hrd' => 'hrd',

        // ---- DIREKTUR (langsung, tanpa PJ) ----
        'head_pegawai' => 'direktur',
        'sekre' => 'direktur',
        'sekretariat' => 'direktur',
        // Supervisor juga pengaju langsung: approval hanya ke Direktur (tanpa PJ).
        'supervisor' => 'direktur',
        'director' => 'direktur',
        'medical_service' => 'direktur',
        'kabag_umum' => 'direktur',
        'manager_umum' => 'direktur',
        'kabag_marketing' => 'direktur',
        'manager_finance' => 'direktur',
        'administrasi' => 'direktur',
        'pj_administrasi' => 'direktur',
        'karyawan' => 'direktur',
        'staff' => 'direktur',
        'employee' => 'direktur',
    ];

    /*
    |--------------------------------------------------------------------------
    | ROLE ATASAN / TOP LEVEL (tidak perlu approval PJ)
    |--------------------------------------------------------------------------
    */
    public const TOP_LEVEL_ROLES = [
        'hrd',
        'director',
        'head_pegawai',
        'sekre',
        'sekretariat',
        'supervisor',
        'medical_service',
        'kabag_umum',
        'manager_umum',
        'kabag_marketing',
        'manager_finance',
    ];

    /**
     * Jenis pengajuan yang tetap wajib melewati approval PJ walau pengajunya
     * adalah PJ sendiri (role pj_*).
     *
     * Alasan: form IZIN tidak memungut tanda tangan pengaju, sehingga tanda
     * tangan pengaju diambil di menu approval PJ. Setelah PJ menyetujui,
     * pengajuan baru lanjut ke tahap berikutnya (lihat statusAfterPj()).
     */
    public const PJ_APPROVAL_REQUIRED_TYPES = [
        'permission',
    ];

    /**
     * Role sistem (bukan approver pengajuan).
     *
     * `admin` mengelola konfigurasi alur approval lewat menu
     * "Alur Approval" (App\Http\Controllers\Admin\ApprovalFlowController).
     */
    public const ADMIN_ROLES = [
        'admin',
    ];

    /*
    |--------------------------------------------------------------------------
    | ALIAS DIVISI (nama role lama <-> baru pada divisi yang sama)
    |--------------------------------------------------------------------------
    */
    public const DIVISION_ALIASES = [
        'nurse'     => ['nurse_ok', 'koor_nurse'],
        'gizi'      => ['nutrition'],
        'rm'        => ['medical_record'],
        'admission' => ['kasir'],
        'admin'     => ['administrasi'],
        'finance'   => ['finance_mgr'],
        'pipp'      => ['ppp'],
        'marketing' => ['creator', 'konten_creator'],
    ];

    /*
    |--------------------------------------------------------------------------
    | LABEL STATUS (untuk tampilan)
    |--------------------------------------------------------------------------
    */
    public const STATUS_LABELS = [
        'pending'                 => 'Pending PJ',
        'waiting_head'            => 'Waiting Kepala Bagian',
        'waiting_hrd'             => 'Waiting HRD',
        'waiting_medical_service' => 'Waiting YANMED',
        'waiting_yanmed'          => 'Waiting YANMED', // status lama sesi development, label sama
        'waiting_kabag_umum'      => 'Waiting Kabag Umum',
        'waiting_manager_umum'    => 'Waiting Manager Umum',
        'waiting_kabag_marketing' => 'Waiting Kabag Marketing',
        'waiting_manager_finance' => 'Waiting Manager Finance',
        'waiting_director'        => 'Waiting Direktur',
        'approved'                => 'Approved',
        'rejected'                => 'Rejected',
    ];

    /*
    |--------------------------------------------------------------------------
    | HELPER DASAR
    |--------------------------------------------------------------------------
    */
    public static function normalize(?string $role): string
    {
        return strtolower(trim((string) $role));
    }

    public static function isPjRole(?string $role): bool
    {
        return str_starts_with(self::normalize($role), 'pj_');
    }

    /**
     * Grup alur approval untuk sebuah role pengaju.
     */
    public static function groupFor(?string $role): string
    {
        return self::ROLE_GROUPS[self::normalize($role)] ?? self::DEFAULT_GROUP;
    }

    /**
     * Rantai stage approver untuk sebuah role pengaju (berurutan).
     */
    public static function chainFor(?string $role, ?string $type = null): array
    {
        // Konfigurasi admin (database) punya prioritas; null = belum ada config.
        $configured = ApprovalFlowConfig::chainFor((string) self::normalize($role), $type);

        if ($configured !== null) {
            return $configured;
        }

        $group = self::groupFor($role);

        return self::GROUP_CHAINS[$group] ?? self::GROUP_CHAINS[self::DEFAULT_GROUP];
    }

    /**
     * Apakah pengajuan role ini masih perlu approval PJ?
     */
    public static function needsPjApproval(?string $role): bool
    {
        $role = self::normalize($role);

        if (self::isPjRole($role)) {
            return false;
        }

        return !in_array($role, self::TOP_LEVEL_ROLES, true);
    }

    /**
     * Apakah pengajuan dengan jenis tertentu masih perlu approval PJ?
     *
     * Bedanya dengan needsPjApproval(): pengajuan IZIN dari PJ sendiri
     * (role pj_*) tetap wajib lewat PJ karena tanda tangannya diambil di
     * menu approval PJ (form izin tidak memungut tanda tangan pengaju).
     * Role atasan (hrd / direktur / supervisor / kabag) tidak diubah aturan
     *nya: mereka tetap melewati PJ seperti sebelumnya.
     */
    public static function needsPjApprovalFor(?string $role, ?string $type = null): bool
    {
        $role = self::normalize($role);

        // Konfigurasi admin: apakah langkah pertama alur adalah tahap PJ?
        $configured = ApprovalFlowConfig::startsWithPj((string) $role, $type);

        if ($configured !== null) {
            return $configured;
        }

        if (self::isPjRole($role) && self::requiresPjForType($type)) {
            return true;
        }

        return self::needsPjApproval($role);
    }

    /**
     * Apakah jenis pengajuan ini mewajibkan approval PJ untuk pengaju role pj_*?
     */
    public static function requiresPjForType(?string $type): bool
    {
        return in_array(self::normalize($type), self::PJ_APPROVAL_REQUIRED_TYPES, true);
    }

    /*
    |--------------------------------------------------------------------------
    | STAGE HELPERS
    |--------------------------------------------------------------------------
    */
    public static function statusForStage(string $stage): ?string
    {
        // Konvensi status global: waiting_<key>. Stage hasil konfigurasi admin
        // pun memakai konvensi yang sama, jadi tidak perlu daftar tambahan.
        return self::STAGE_STATUS[$stage] ?? 'waiting_' . $stage;
    }

    /**
     * Katalog tahap aktif.
     *
     * Selama konfigurasi admin belum dipakai, sumbernya tetap konstanta bawaan.
     * Setelah admin menyimpan konfigurasi, database menjadi sumber kebenaran
     * tunggal — tahap yang dinonaktifkan admin benar-benar hilang dari alur,
     * antrean approval, dan surat PDF.
     *
     * @return array<string, array{role: string, label: string}>
     */
    public static function stagesConfig(): array
    {
        if (!ApprovalFlowConfig::isEnabled()) {
            return self::APPROVER_STAGES;
        }

        $config = [];

        foreach (ApprovalFlowConfig::activeStages() as $key => $stage) {
            $config[$key] = [
                'role' => $stage->role,
                'label' => $stage->label,
            ];
        }

        return $config;
    }

    /** Semua key tahap (konstanta + konfigurasi admin). */
    public static function stageKeys(): array
    {
        return array_keys(self::stagesConfig());
    }

    public static function labelForStage(string $stage): string
    {
        return self::stagesConfig()[$stage]['label']
            ?? strtoupper(str_replace('_', ' ', $stage));
    }

    public static function statusLabel(?string $status): string
    {
        if ($status === null || $status === '') {
            return '-';
        }

        return self::STATUS_LABELS[$status]
            ?? strtoupper(str_replace('_', ' ', $status));
    }

    /**
     * Stage yang ditangani sebuah role approver (null bila bukan approver).
     *
     * Role lama 'medical_service' dipetakan ke stage 'medical_service' agar akun lama
     * tetap dapat membuka pusat approval dan menyetujui tahap medical_service.
     * Kolom DB lama (medical_service_*) disinkronkan saat approve/reject.
     */
    public static function stageForApproverRole(?string $role): ?string
    {
        $role = self::normalize($role);

        // Stage dari konfigurasi admin (punya prioritas, bisa di-toggle admin).
        $configured = ApprovalFlowConfig::stageForRole($role);

        if ($configured !== null) {
            return $configured;
        }

        if (isset(self::APPROVER_ALIASES[$role])) {
            return self::APPROVER_ALIASES[$role];
        }

        return array_key_exists($role, self::stagesConfig()) ? $role : null;
    }

    /**
     * Prefiks kolom yang harus ditulis saat sebuah stage disetujui/ditolak.
     * Stage 'medical_service' hanya menulis kolom 'medical_service_*'.
     */
    public static function columnPrefixesForStage(string $stage): array
    {
        return [$stage];
    }

    /**
     * Status global (kolom `status`) yang harus dicocokkan untuk sebuah stage.
     * Mencakup status lama agar data lama tetap muncul di antrean approver.
     */
    public static function statusesForStage(string $stage): array
    {
        $status = self::statusForStage($stage);

        if ($status === null) {
            return [];
        }

        $statuses = [$status];

        foreach (self::LEGACY_STATUS_ALIASES as $legacy => $current) {
            if ($current === $status && !in_array($legacy, $statuses, true)) {
                $statuses[] = $legacy;
            }
        }

        return $statuses;
    }

    /**
     * Payload update kolom stage (status/signature/note/approved_by/approved_at)
     * untuk sebuah stage, termasuk kolom kompatibilitas bila ada.
     */
    public static function stagePayload(string $stage, string $decision, ?string $signature = null, ?string $note = null): array
    {
        $now = now();
        $by = auth()->id();
        $payload = [];

        foreach (self::columnPrefixesForStage($stage) as $prefix) {
            $payload[$prefix . '_status'] = $decision;

            if ($signature !== null) {
                $payload[$prefix . '_signature'] = $signature;
            }

            if ($note !== null) {
                $payload[$prefix . '_note'] = $note;
            }

            $payload[$prefix . '_approved_by'] = $by;
            $payload[$prefix . '_approved_at'] = $now;
        }

        return $payload;
    }

    /**
     * Semua role yang berhak membuka pusat approval.
     */
    public static function approverRoles(): array
    {
        return array_keys(self::stagesConfig());
    }

    /**
     * Status berikutnya pada rantai setelah sebuah stage disetujui.
     * 'approved' bila stage tersebut adalah tahap terakhir.
     */
    public static function nextStatus(array $chain, string $currentStage): string
    {
        $index = array_search($currentStage, $chain, true);

        if ($index === false) {
            return 'approved';
        }

        $nextStage = $chain[$index + 1] ?? null;

        return $nextStage
            ? (self::STAGE_STATUS[$nextStage] ?? 'approved')
            : 'approved';
    }

    /**
     * Status global saat pengajuan pertama kali dibuat.
     */
    public static function initialStatus(?string $role, ?string $type = null): string
    {
        if (self::needsPjApprovalFor($role, $type)) {
            return 'pending';
        }

        $chain = self::chainFor($role, $type);

        return isset($chain[0]) ? (self::statusForStage($chain[0]) ?? 'approved') : 'approved';
    }

    /**
     * Status global setelah PJ menyetujui (tahap PJ selesai).
     */
    public static function statusAfterPj(?string $role, ?string $type = null): string
    {
        $chain = self::chainFor($role, $type);

        return isset($chain[0]) ? (self::statusForStage($chain[0]) ?? 'approved') : 'approved';
    }

    /**
     * Hasil flow lengkap untuk sebuah role pengaju (dipakai saat create).
     *
     * Mengembalikan seluruh kolom status stage (default 'pending') ditambah:
     *  - status     : status global tahap berikutnya
     *  - pj_status  : 'pending' bila masih perlu PJ, 'approved' bila dilewati
     *  - hrd_status : tetap ada demi kompatibilitas kolom lama
     *
     * Parameter $type bersifat opsional. Contoh: handle('pj_gizi', 'permission') tetap
     * menunggu approval PJ, sedangkan handle('pj_gizi', 'leave') langsung
     * ke tahap berikutnya.
     */
    public static function handle(?string $role, ?string $type = null): array
    {
        $chain = self::chainFor($role, $type);
        $needsPj = self::needsPjApprovalFor($role, $type);

        $stageStatuses = [];

        foreach (self::stageKeys() as $stage) {
            // pj_status dihitung dari $needsPj (bukan selalu pending): tahap PJ
            // dilewati untuk role PJ & role atasan. Tahap PJ pun bisa dimatikan
            // admin lewat katalog tahap, jadi tidak ikut ditulis sebagai pending.
            if ($stage === ApprovalFlowConfig::PJ_STAGE) {
                continue;
            }

            foreach (self::columnPrefixesForStage($stage) as $prefix) {
                $stageStatuses[$prefix . '_status'] = 'pending';
            }
        }

        return array_merge([
            'status' => $needsPj
                ? 'pending'
                : (self::statusForStage($chain[0]) ?? 'approved'),
            'pj_status' => $needsPj ? 'pending' : 'approved',
            'hrd_status' => 'pending',
        ], $stageStatuses);
    }

    /*
    |--------------------------------------------------------------------------
    | DIVISI (filter daftar PJ & shift)
    |--------------------------------------------------------------------------
    */

    /**
     * Daftar role yang berada dalam tanggung jawab seorang PJ.
     * Contoh: 'pj_gizi' -> ['gizi', 'pj_gizi', 'nutrition', 'pj_nutrition'].
     */
    public static function divisionRoles(?string $role): array
    {
        $role = self::normalize($role);
        $base = self::isPjRole($role) ? substr($role, 3) : $role;

        if ($base === '') {
            return [];
        }

        $aliased = [];

        foreach (self::DIVISION_ALIASES[$base] ?? [] as $alias) {
            $aliased[] = $alias;
            $aliased[] = 'pj_' . $alias;
        }

        return array_values(array_unique(array_merge(
            [$base, 'pj_' . $base],
            $aliased
        )));
    }

    /**
     * Semua role pengaju yang tergabung dalam satu grup approval.
     */
    public static function rolesInGroup(string $group): array
    {
        return array_keys(array_filter(
            self::ROLE_GROUPS,
            fn($roleGroup) => $roleGroup === $group
        ));
    }

    /**
     * Daftar role yang boleh di-assign ke user (dipakai dropdown approval user).
     */
    public static function assignableRoles(): array
    {
        $roles = array_unique(array_merge(
            array_keys(self::ROLE_GROUPS),
            self::stageKeys(),
            self::ADMIN_ROLES
        ));

        sort($roles);

        return array_values($roles);
    }
}
