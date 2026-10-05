<?php

namespace App\Services;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRoleFlow;
use App\Models\ApprovalStage;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun konfigurasi alur approval di database dari aturan bawaan aplikasi.
 *
 * Dipakai oleh:
 *  - seeder `ApprovalFlowSeeder`,
 *  - tombol "Pulihkan Default" di menu admin,
 *  - test, untuk memastikan hasil konfigurasi sama persis dengan perilaku lama.
 *
 * Setiap grup pada ApprovalFlowService::GROUP_CHAINS menjadi satu alur, dan
 * tiap alur dibuat dalam dua varian:
 *   - "lewat PJ"  : langkah pertama `pj` (karyawan biasa).
 *   - "tanpa PJ"  : langsung ke approver (role PJ & role atasan).
 * Alur ini berlaku untuk semua jenis pengajuan (submission_type = '*'),
 * kecuali role `pj_*` pada IZIN yang dipetakan khusus ke varian "lewat PJ".
 */
class ApprovalFlowDefaults
{
    /**
     * Bangun / segarkan konfigurasi dari konstanta ApprovalFlowService.
     *
     * @return array{flows: int, stages: int, mappings: int}
     */
    public static function sync(): array
    {
        return DB::transaction(static function (): array {
            self::syncStages();
            $flows = self::syncFlows();
            $mappings = self::syncRoleMappings($flows);

            ApprovalFlowConfig::flush();

            return [
                'stages' => ApprovalStage::count(),
                'flows' => array_sum(array_map('count', $flows)),
                'mappings' => $mappings,
            ];
        });
    }

    /** Katalog tahap: approver bawaan + tahap PJ. */
    private static function syncStages(): void
    {
        $stages = [
            [
                'key' => ApprovalFlowConfig::PJ_STAGE,
                'label' => 'Penanggung Jawab (PJ)',
                'role' => 'pj',
                'is_pj' => true,
                'is_active' => true,
                'sort_order' => 0,
                'description' => 'Atasan langsung pengaju. Kolom pj_* sudah tersedia di semua tabel pengajuan.',
            ],
        ];

        $order = 1;

        foreach (ApprovalFlowService::APPROVER_STAGES as $key => $config) {
            $stages[] = [
                'key' => $key,
                'label' => $config['label'],
                'role' => $config['role'],
                'is_pj' => false,
                'is_active' => true,
                'sort_order' => $order++,
                'description' => null,
            ];
        }

        foreach ($stages as $stage) {
            ApprovalStage::updateOrCreate(['key' => $stage['key']], $stage);
        }
    }

    /**
     * Alur per grup (lewat PJ & tanpa PJ).
     *
     * @return array<string, array{lewat_pj: ApprovalFlow, tanpa_pj: ApprovalFlow}>
     */
    private static function syncFlows(): array
    {
        $flows = [];
        $defaultGroup = ApprovalFlowService::DEFAULT_GROUP;

        foreach (ApprovalFlowService::GROUP_CHAINS as $group => $chain) {
            $label = strtoupper(str_replace('_', ' ', $group));

            $flows[$group] = [
                'lewat_pj' => self::storeFlow("Alur $label (lewat PJ)", $group === $defaultGroup, ['pj', ...$chain]),
                'tanpa_pj' => self::storeFlow("Alur $label (tanpa PJ)", false, $chain),
            ];
        }

        return $flows;
    }

    /**
     * Satu alur dengan langkah berupa rantai (parent_id mengikuti langkah
     * sebelumnya sehingga tetap membentuk tree).
     */
    private static function storeFlow(string $name, bool $isDefault, array $stageKeys): ApprovalFlow
    {
        $flow = ApprovalFlow::updateOrCreate(
            [
                'submission_type' => ApprovalFlowConfig::ALL_TYPES,
                'name' => $name,
            ],
            [
                'is_default' => $isDefault,
                'is_active' => true,
                'description' => 'Dibuat otomatis dari konfigurasi bawaan sistem.',
            ]
        );

        $flow->steps()->delete();

        $previousId = null;

        foreach ($stageKeys as $order => $stageKey) {
            $step = ApprovalFlowStep::create([
                'approval_flow_id' => $flow->id,
                'stage_key' => $stageKey,
                'parent_id' => $previousId,
                'sort_order' => $order,
            ]);

            $previousId = $step->id;
        }

        return $flow;
    }

    /** Pemetaan role -> alur sesuai aturan bawaan (PJ dilewati / lewat PJ). */
    private static function syncRoleMappings(array $flows): int
    {
        $count = 0;

        foreach (ApprovalFlowService::ROLE_GROUPS as $role => $group) {
            if (!isset($flows[$group])) {
                continue;
            }

            $isPjRole = ApprovalFlowService::isPjRole($role);
            $skipsPj = $isPjRole
                || in_array($role, ApprovalFlowService::TOP_LEVEL_ROLES, true);

            $flow = $skipsPj ? $flows[$group]['tanpa_pj'] : $flows[$group]['lewat_pj'];

            ApprovalRoleFlow::updateOrCreate(
                ['role' => $role, 'submission_type' => ApprovalFlowConfig::ALL_TYPES],
                ['approval_flow_id' => $flow->id]
            );
            $count++;

            // Pengajuan izin dari PJ tetap lewat PJ (ttd diambil di menu PJ).
            if ($isPjRole) {
                ApprovalRoleFlow::updateOrCreate(
                    ['role' => $role, 'submission_type' => 'permission'],
                    [
                        'approval_flow_id' => $flows[$group]['lewat_pj']->id,
                        'note' => 'Izin dari PJ tetap melalui approval PJ.',
                    ]
                );
                $count++;
            }
        }

        return $count;
    }
}