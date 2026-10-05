<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu alur approval untuk satu jenis pengajuan (cuti / izin / lembur).
 *
 * Alur berbentuk tree: langkah pertama (parent_id = null) adalah tahap yang
 * harus dilewati lebih dulu. Kalau langkah pertama adalah tahap PJ, pengajuan
 * baru menunggu PJ; kalau bukan, pengajuan langsung masuk ke tahap berikutnya.
 */
class ApprovalFlow extends Model
{
    protected $table = 'approval_flows';

    protected $fillable = [
        'name',
        'submission_type',
        'is_default',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalFlowStep::class, 'approval_flow_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function roleMappings()
    {
        return $this->hasMany(ApprovalRoleFlow::class, 'approval_flow_id');
    }

    /** Label jenis pengajuan (cuti / izin / lembur). */
    public function getTypeLabelAttribute(): string
    {
        return \App\Services\ApprovalFlowConfig::TYPES[$this->submission_type]
            ?? strtoupper($this->submission_type);
    }

    /**
     * Langkah alur untuk form editor: [{stage_key, parent_index}, ...].
     *
     * `parent_index` adalah posisi induk pada daftar datar (null = root),
     * bentuk ini yang dikirim controller saat menyimpan.
     */
    public function stepPayload(): array
    {
        $steps = $this->steps->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values();
        $indexById = $steps->keyBy('id');

        return $steps->map(function ($step) use ($indexById, $steps) {
            $parentKey = $step->parent_id !== null ? $indexById->get($step->parent_id)?->getKey() : null;
            $parentIndex = $parentKey === null ? null : $steps->search(fn ($item) => $item->id === $parentKey);

            return [
                'stage_key' => $step->stage_key,
                'parent_index' => $parentIndex === false ? null : $parentIndex,
            ];
        })->all();
    }

    /** Tahap dalam urutan approval (pre-order tree). */
    public function orderedStageKeys(): array
    {
        return \App\Services\ApprovalFlowConfig::orderedSteps($this);
    }
}