<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak audit pemrosesan data biometrik (registrasi, verifikasi, review HRD,
 * perubahan pengaturan). Hanya dapat dilihat role `admin` dan otomatis dihapus
 * sesuai masa simpan (`face.audit_retention_days`, bawaan 60 hari).
 */
class FaceAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'face_audit_logs';

    protected $fillable = [
        'actor_role',
        'actor_id',
        'user_id',
        'action',
        'result',
        'ip_address',
        'user_agent',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}