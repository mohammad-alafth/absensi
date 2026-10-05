<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Katalog tahap approval (dikelola admin lewat menu Alur Approval).
 *
 * `key` = prefiks kolom di tabel pengajuan (konvensi: `{key}_status`,
 * `{key}_signature`, `{key}_note`, `{key}_approved_by`, `{key}_approved_at`),
 * sehingga tahap baru otomatis nyambung ke seluruh controller & surat PDF.
 */
class ApprovalStage extends Model
{
    protected $table = 'approval_stages';

    protected $fillable = [
        'key',
        'label',
        'role',
        'is_pj',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'is_pj' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Nama kolom status global untuk tahap ini (mis. waiting_medical_service). */
    public function getWaitingStatusAttribute(): string
    {
        return 'waiting_' . $this->key;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}