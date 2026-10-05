<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pemetaan role pengaju -> alur approval.
 *
 * `submission_type` = '*' berarti berlaku untuk semua jenis pengajuan.
 * Baris ini menimpa alur default, jadi admin bisa memisahkan misalnya
 * "izin untuk perawat" dan "cuti untuk perawat" tanpa membuat alur baru.
 */
class ApprovalRoleFlow extends Model
{
    protected $table = 'approval_role_flows';

    protected $fillable = [
        'role',
        'submission_type',
        'approval_flow_id',
        'note',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }
}