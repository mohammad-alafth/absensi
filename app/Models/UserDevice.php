<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perangkat yang terikat ke satu akun.
 *
 * Sesuai keputusan D1, perangkat TIDAK dipakai sebagai gerbang absensi:
 * hanya dicatat dan ditampilkan. Status `revoked` berarti perangkat sudah
 * dicabut admin (mis. karyawan ganti HP), bukan alasan menolak absen.
 */
class UserDevice extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_TRUSTED = 'trusted';
    public const STATUS_REVOKED = 'revoked';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'device_uuid',
        'label',
        'platform',
        'user_agent',
        'first_ip',
        'first_seen_at',
        'last_seen_at',
        'status',
        'revoked_at',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /** Perangkat yang masih berlaku (pending maupun trusted). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_REVOKED);
    }

    public function scopeTrusted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_TRUSTED);
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function isTrusted(): bool
    {
        return $this->status === self::STATUS_TRUSTED;
    }

    /** Payload aman untuk ditampilkan di UI (tanpa IP / user agent mentah). */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label ?: 'Perangkat tanpa nama',
            'platform' => $this->platform,
            'status' => $this->status,
            'first_seen_at' => optional($this->first_seen_at)->toIso8601String(),
            'last_seen_at' => optional($this->last_seen_at)->toIso8601String(),
        ];
    }
}
