<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRoleFlow;
use App\Models\ApprovalStage;
use App\Services\ApprovalFlowConfig;
use App\Services\ApprovalFlowDefaults;
use App\Services\ApprovalFlowService;
use App\Services\ApprovalStageSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Menu "Alur Approval" untuk role admin.
 *
 * Admin dapat:
 *  - mengelola katalog tahap (tambah tahap baru, ubah label/role, aktif/nonaktif),
 *  - menyusun tree alur per jenis pengajuan (urutan tahap),
 *  - memetakan role pengaju ke alur tertentu,
 *  - memulihkan konfigurasi bawaan sistem.
 *
 * Tahap baru otomatis mendapat kolom `{stage}_status/_signature/_note/
 * _approved_by/_approved_at` di tabel cuti, izin, dan lembur, sehingga langsung
 * nyambung ke seluruh controller approval maupun surat PDF tanpa ubah kode.
 */
class ApprovalFlowController extends Controller
{
    /** Ringkasan + daftar seluruh konfigurasi. */
    public function index()
    {
        ApprovalFlowConfig::flush();

        $stages = ApprovalStage::orderBy('sort_order')->orderBy('id')->get();

        $flows = ApprovalFlow::with('steps')
            ->orderBy('submission_type')
            ->orderBy('id')
            ->get();

        $mappings = ApprovalRoleFlow::orderBy('role')->get();

        $preview = [];
        foreach (array_keys(ApprovalFlowConfig::TYPES) as $type) {
            $preview[$type] = $this->previewFor($type);
        }

        return view('admin.approval.index', [
            'stages' => $stages,
            'flows' => $flows,
            'mappings' => $mappings,
            'preview' => $preview,
            'types' => ApprovalFlowConfig::TYPES,
            'configEnabled' => ApprovalFlowConfig::isEnabled(),
        ]);
    }

    /** Pratinjau alur hasil konfigurasi sekarang untuk beberapa role contoh. */
    private function previewFor(string $type): array
    {
        $samples = [
            'nurse', 'pj_nurse', 'gizi', 'pj_gizi', 'security', 'admission',
            'hrd', 'direktur', 'casemix',
        ];

        $rows = [];

        foreach ($samples as $role) {
            if (!isset(ApprovalFlowService::ROLE_GROUPS[$role])) {
                continue;
            }

            $chain = ApprovalFlowService::chainFor($role, $type);
            $needsPj = ApprovalFlowService::needsPjApprovalFor($role, $type);

            $rows[$role] = [
                'steps' => $needsPj ? [ApprovalFlowConfig::PJ_STAGE, ...$chain] : $chain,
                'initial_status' => ApprovalFlowService::initialStatus($role, $type),
            ];
        }

        return $rows;
    }

    /* ------------------------------------------------------------------ */
    /* KATALOG TAHAP                                                       */
    /* ------------------------------------------------------------------ */

    public function storeStage(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('approval_stages', 'key')],
            'label' => 'required|string|max:120',
            'role' => 'required|string|max:60',
            'is_pj' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        $stage = ApprovalStage::create($this->stageAttributes($data));

        // Kolom pengajuan untuk tahap baru dibuat otomatis.
        ApprovalStageSchema::ensureStageColumns($stage->key);

        ApprovalFlowConfig::flush();

        return back()->with('success', "Tahap {$stage->label} ditambahkan dan kolom pengajuannya dibuat otomatis.");
    }

    public function updateStage(Request $request, string $stage)
    {
        $model = ApprovalStage::where('key', $stage)->firstOrFail();

        $data = $request->validate([
            'key' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('approval_stages', 'key')->ignore($model->id)],
            'label' => 'required|string|max:120',
            'role' => 'required|string|max:60',
            'is_pj' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        $keyChanged = $data['key'] !== $model->key;

        $model->update($this->stageAttributes($data));

        if ($keyChanged) {
            // Key juga dipakai sebagai nama kolom & node tree, jadi keduanya
            // ikut diperbarui dan kolom baru dibuat untuk key yang baru.
            ApprovalFlowStep::where('stage_key', $stage)->update(['stage_key' => $data['key']]);
            ApprovalStageSchema::ensureStageColumns($data['key']);
        }

        ApprovalFlowConfig::flush();

        return back()->with('success', 'Tahap ' . $model->label . ' diperbarui.');
    }

    public function toggleStage(string $stage)
    {
        $model = ApprovalStage::where('key', $stage)->firstOrFail();

        $model->update(['is_active' => !$model->is_active]);

        ApprovalFlowConfig::flush();

        return back()->with(
            'success',
            $model->is_active ? 'Tahap diaktifkan.' : 'Tahap dinonaktifkan (tidak muncul di alur & antrean approval).'
        );
    }

    public function destroyStage(string $stage)
    {
        $model = ApprovalStage::where('key', $stage)->firstOrFail();

        if ($stage === ApprovalFlowConfig::PJ_STAGE) {
            return back()->with('error', 'Tahap PJ tidak dapat dihapus karena dipakai semua pengajuan.');
        }

        if (ApprovalFlowStep::where('stage_key', $stage)->exists()) {
            $model->update(['is_active' => false]);
            ApprovalFlowConfig::flush();

            return back()->with('error', 'Tahap masih dipakai pada alur, jadi dinonaktifkan. Hapus dulu dari tree alur.');
        }

        $model->delete();
        ApprovalFlowConfig::flush();

        return back()->with('success', 'Tahap dihapus.');
    }

    private function stageAttributes(array $data): array
    {
        return [
            'key' => strtolower($data['key']),
            'label' => $data['label'],
            'role' => strtolower($data['role']),
            'is_pj' => (bool) ($data['is_pj'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'description' => $data['description'] ?? null,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* TREE ALUR                                                           */
    /* ------------------------------------------------------------------ */

    public function storeFlow(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'submission_type' => 'required|string|in:' . implode(',', array_merge(array_keys(ApprovalFlowConfig::TYPES), [ApprovalFlowConfig::ALL_TYPES])),
            'is_default' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $flow = ApprovalFlow::create([
            'name' => $data['name'],
            'submission_type' => $data['submission_type'],
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_active' => true,
            'description' => $data['description'] ?? null,
        ]);

        $this->syncSteps($flow, $request->input('steps', []));

        ApprovalFlowConfig::flush();

        return back()->with('success', "Alur \"{$flow->name}\" disimpan.");
    }

    public function updateFlow(Request $request, ApprovalFlow $flow)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'submission_type' => 'required|string|in:' . implode(',', array_merge(array_keys(ApprovalFlowConfig::TYPES), [ApprovalFlowConfig::ALL_TYPES])),
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $makeDefault = (bool) ($data['is_default'] ?? false);

        DB::transaction(function () use ($flow, $data, $makeDefault, $request) {
            if ($makeDefault && $flow->submission_type !== ApprovalFlowConfig::ALL_TYPES) {
                ApprovalFlow::where('submission_type', $flow->submission_type)
                    ->where('id', '!=', $flow->id)
                    ->update(['is_default' => false]);
            }

            $flow->update([
                'name' => $data['name'],
                'submission_type' => $data['submission_type'],
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_default' => $makeDefault,
                'description' => $data['description'] ?? null,
            ]);

            $this->syncSteps($flow, $request->input('steps', []));
        });

        ApprovalFlowConfig::flush();

        return back()->with('success', "Alur \"{$flow->name}\" diperbarui.");
    }

    public function destroyFlow(ApprovalFlow $flow)
    {
        if (ApprovalRoleFlow::where('approval_flow_id', $flow->id)->exists()) {
            $flow->update(['is_active' => false]);
            ApprovalFlowConfig::flush();

            return back()->with('error', 'Alur masih dipakai role pengaju, jadi dinonaktifkan alih-alih dihapus.');
        }

        $flow->delete();
        ApprovalFlowConfig::flush();

        return back()->with('success', 'Alur dihapus.');
    }

    /**
     * Simpan ulang langkah alur dari payload form.
     *
     * Payload `steps` adalah daftar [{stage_key, parent_index|null}, ...];
     * `parent_index` menunjuk posisi langkah sebelumnya pada daftar yang sama,
     * jadi frontend cukup mengirim satu array sederhana.
     */
    private function syncSteps(ApprovalFlow $flow, array $steps): void
    {
        $validKeys = array_keys(ApprovalStage::pluck('label', 'key')->all());
        $created = [];

        $flow->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            $stageKey = is_array($step) ? ($step['stage_key'] ?? null) : $step;

            if (!is_string($stageKey) || !in_array($stageKey, $validKeys, true)) {
                continue;
            }

            $parentIndex = is_array($step) ? ($step['parent_index'] ?? null) : null;

            $created[$index] = ApprovalFlowStep::create([
                'approval_flow_id' => $flow->id,
                'stage_key' => $stageKey,
                'parent_id' => is_numeric($parentIndex) ? ($created[(int) $parentIndex]->id ?? null) : null,
                'sort_order' => $index,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /* PEMETAAN ROLE -> ALUR                                                */
    /* ------------------------------------------------------------------ */

    public function storeRoleFlow(Request $request)
    {
        $data = $request->validate([
            'role' => 'required|string|max:60|regex:/^[a-z][a-z0-9_]*$/',
            'submission_type' => 'required|string|in:' . implode(',', array_merge(array_keys(ApprovalFlowConfig::TYPES), [ApprovalFlowConfig::ALL_TYPES])),
            'approval_flow_id' => 'nullable|integer|exists:approval_flows,id',
            'note' => 'nullable|string|max:255',
        ]);

        ApprovalRoleFlow::updateOrCreate(
            [
                'role' => strtolower($data['role']),
                'submission_type' => $data['submission_type'],
            ],
            [
                'approval_flow_id' => $data['approval_flow_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]
        );

        ApprovalFlowConfig::flush();

        return back()->with('success', 'Pemetaan role ' . strtoupper($data['role']) . ' disimpan.');
    }

    public function destroyRoleFlow(ApprovalRoleFlow $roleFlow)
    {
        $roleFlow->delete();

        ApprovalFlowConfig::flush();

        return back()->with('success', 'Pemetaan role dihapus (kembali memakai alur default).');
    }

    /* ------------------------------------------------------------------ */
    /* DEFAULT SISTEM                                                      */
    /* ------------------------------------------------------------------ */

    public function syncDefaults()
    {
        $result = ApprovalFlowDefaults::sync();

        return back()->with(
            'success',
            "Konfigurasi dipulihkan: {$result['stages']} tahap, {$result['flows']} alur, {$result['mappings']} pemetaan role."
        );
    }
}