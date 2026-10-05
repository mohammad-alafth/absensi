<?php

namespace App\Services;

use App\Models\ApprovalFlow;
use App\Models\ApprovalRoleFlow;
use App\Models\ApprovalStage;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pembacaan konfigurasi alur approval yang disimpan di database.
 *
 * Semua aturan alur (tahap apa saja, urutannya, dan apakah lewat PJ atau
 * tidak) dibaca dari sini, sehingga tidak perlu mengubah kode setiap kali
 * struktur approval berubah. Role `admin` mengelolanya lewat UI
 * (App\Http\Controllers\Admin\ApprovalFlowController).
 *
 * Sifat penting: kalau tabel konfigurasi belum ada / masih kosong, semua
 * method mengembalikan null atau array kosong dan pemanggilannya memakai
 * fallback ke konstanta lama ApprovalFlowService. Dengan begitu aplikasi tetap
 * berfungsi sama persis seperti sebelumnya sampai admin benar-benar menyimpan
 * konfigurasi.
 *
 * Aturan tree:
 *  - Langkah approval dibaca pre-order (urut sort_order, anak mengikuti induk).
 *  - Langkah pertama menentukan status awal: bila tahapnya `pj`, pengajuan
 *    menunggu PJ; bila bukan, pengajuan langsung masuk tahap tersebut.
 */
class ApprovalFlowConfig
{
    /** Jenis pengajuan yang punya alur. */
    public const TYPES = [
        'leave' => 'Cuti',
        'permission' => 'Izin',
        'overtime' => 'Lembur',
    ];

    /** submission_type untuk pemetaan role yang berlaku semua jenis. */
    public const ALL_TYPES = '*';

    /** Key tahap PJ (kolom pj_*). */
    public const PJ_STAGE = 'pj';

    private static bool $loaded = false;

    /** @var array{stages?: array, flows?: array, role_flows?: array} */
    private static array $data = [];

    /** Buang cache (dipanggil setelah admin menyimpan konfigurasi). */
    public static function flush(): void
    {
        self::$loaded = false;
        self::$data = [];
    }

    /**
     * Snapshot konfigurasi. Array kosong = konfigurasi belum dipakai,
     * sehingga pemanggil wajib memakai fallback ApprovalFlowService.
     */
    public static function snapshot(): array
    {
        if (self::$loaded) {
            return self::$data;
        }

        self::$loaded = true;
        self::$data = [];

        try {
            if (!Schema::hasTable('approval_stages') || !Schema::hasTable('approval_flows')) {
                return self::$data;
            }

            $stages = ApprovalStage::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if ($stages->isEmpty()) {
                return self::$data;
            }

            self::$data = [
                'stages' => $stages->keyBy('key')->all(),
                'flows' => ApprovalFlow::with('steps')->orderBy('submission_type')->orderBy('id')->get()->keyBy('id'),
                'role_flows' => ApprovalRoleFlow::orderBy('role')->get(),
            ];
        } catch (Throwable $e) {
            // Konfigurasi belum siap / database belum termigrasi: fallback ke kode.
            self::$data = [];
        }

        return self::$data;
    }

    /** Apakah konfigurasi DB sudah terisi? */
    public static function isEnabled(): bool
    {
        return self::snapshot() !== [];
    }

    /* ------------------------------------------------------------------ */
    /* KATALOG TAHAP                                                        */
    /* ------------------------------------------------------------------ */

    /** @return array<string, ApprovalStage> */
    public static function stages(): array
    {
        return self::snapshot()['stages'] ?? [];
    }

    /** @return array<string, ApprovalStage> */
    public static function activeStages(): array
    {
        return array_filter(
            self::stages(),
            static fn (ApprovalStage $stage) => (bool) $stage->is_active
        );
    }

    /** @return array<int, string> */
    public static function stageKeys(): array
    {
        return array_keys(self::stages());
    }
    public static function stage(string $key): ?ApprovalStage
    {
        return self::stages()[$key] ?? null;
    }

    public static function labelForStage(string $key): ?string
    {
        return self::stage($key)?->label;
    }

    /** Stage yang dipegang sebuah role approver (null bila bukan approver). */
    public static function stageForRole(string $role): ?string
    {
        foreach (self::activeStages() as $key => $stage) {
            if (ApprovalFlowService::normalize($stage->role) === ApprovalFlowService::normalize($role)) {
                return $key;
            }
        }

        return null;
    }
/* ------------------------------------------------------------------ */
    /* FLOW                                                                 */
    /* ------------------------------------------------------------------ */

    /** @return \Illuminate\Support\Collection<int, ApprovalFlow> */
    public static function flows(?string $type = null)
    {
        $flows = self::snapshot()['flows'] ?? [];

        if ($type === null) {
            return collect($flows)->values();
        }

        return collect($flows)
            ->filter(static fn (ApprovalFlow $flow) => $flow->submission_type === $type)
            ->values();
    }

    /**
     * Flow yang berlaku untuk semua jenis pengajuan (submission_type = '*').
     */
    public static function sharedFlows()
    {
        $flows = self::snapshot()['flows'] ?? [];

        return collect($flows)
            ->filter(static fn (ApprovalFlow $flow) => $flow->submission_type === self::ALL_TYPES)
            ->values();
    }

    /**
     * Alur default untuk satu jenis pengajuan.
     *
     * Alur khusus jenis pengajuan lebih diutamakan daripada alur bersama ('*').
     */
    public static function defaultFlow(string $type): ?ApprovalFlow
    {
        $specific = self::flows($type)->where('is_active', true);
        $default = $specific->firstWhere('is_default', true) ?? $specific->first();

        if ($default) {
            return $default;
        }

        $shared = self::sharedFlows()->where('is_active', true);

        return $shared->firstWhere('is_default', true) ?? $shared->first();
    }

    public static function flow(int $id): ?ApprovalFlow
    {
        return self::snapshot()['flows'][$id] ?? null;
    }

    /**
     * Alur yang dipakai role pengaju untuk satu jenis pengajuan.
     *
     * Urutan prioritas:
     *   1. pemetaan role + jenis persis -> 2. pemetaan role semua jenis
     *   -> 3. alur default jenis tersebut.
     */
    public static function flowFor(string $role, ?string $type = null): ?ApprovalFlow
    {
        $mappings = self::snapshot()['role_flows'] ?? [];
        $role = ApprovalFlowService::normalize($role);

        if ($type !== null) {
            foreach ($mappings as $mapping) {
                if (
                    ApprovalFlowService::normalize($mapping->role) === $role
                    && $mapping->submission_type === $type
                    && $mapping->approval_flow_id
                ) {
                    return self::flow((int) $mapping->approval_flow_id);
                }
            }
        }

        foreach ($mappings as $mapping) {
            if (
                ApprovalFlowService::normalize($mapping->role) === $role
                && $mapping->submission_type === self::ALL_TYPES
                && $mapping->approval_flow_id
            ) {
                return self::flow((int) $mapping->approval_flow_id);
            }
        }

        return $type !== null ? self::defaultFlow($type) : null;
    }

    /**
     * Urutan tahap pada sebuah flow (pre-order tree).
     *
     * @return array<int, string>
     */
    public static function orderedSteps(ApprovalFlow $flow): array
    {
        $steps = $flow->steps->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values();
        $byParent = [];

        foreach ($steps as $step) {
            $byParent[$step->parent_id ?: 0][] = $step;
        }

        $ordered = [];

        $walk = function ($parentId) use (&$walk, &$ordered, $byParent) {
            foreach ($byParent[$parentId] ?? [] as $step) {
                $ordered[] = $step->stage_key;
                $walk($step->id);
            }
        };

        $walk(0);

        return $ordered;
    }

    /**
     * Rantai tahap SETELAH tahap PJ untuk role + jenis pengajuan tertentu.
     *
     * @return array<int, string>|null null = belum ada konfigurasi, pakai kode.
     */
    public static function chainFor(string $role, ?string $type): ?array
    {
        if (!self::isEnabled()) {
            return null;
        }

        $flow = self::flowFor($role, $type);

        if (!$flow || !$flow->is_active) {
            return null;
        }

        return array_values(array_filter(
            self::orderedSteps($flow),
            static fn (string $key) => $key !== self::PJ_STAGE
        ));
    }

    /**
     * Apakah pengajuan ini harus menunggu PJ lebih dulu?
     *
     * @return bool|null null = belum ada konfigurasi, pakai kode.
     */
    public static function startsWithPj(string $role, ?string $type): ?bool
    {
        if (!self::isEnabled()) {
            return null;
        }

        $flow = self::flowFor($role, $type);

        if (!$flow || !$flow->is_active) {
            return null;
        }

        $steps = self::orderedSteps($flow);

        return ($steps[0] ?? null) === self::PJ_STAGE;
    }
}
