<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu node dalam tree alur approval.
 *
 * Urutan approval = pre-order (urut berdasarkan sort_order, anak mengikuti
 * induknya), sehingga alur berbentuk rantai maupun pohon dapat dijalankan
 * dengan aturan yang sama: sebuah langkah hanya bisa disetujui setelah
 * langkah sebelumnya selesai.
 */
class ApprovalFlowStep extends Model
{
    protected $table = 'approval_flow_steps';

    protected $fillable = [
        'approval_flow_id',
        'stage_key',
        'parent_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ApprovalStage::class, 'stage_key', 'key');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}