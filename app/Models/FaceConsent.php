<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bukti persetujuan pemrosesan data biometrik wajah.
 *
 * Yang disimpan BUKAN sekadar boolean, melainkan jejak yang dapat dibuktikan:
 * versi dan hash informasi yang ditampilkan, waktu persetujuan, cara persetujuan,
 * serta bukti pendukung (IP, user agent, tanda tangan).
 *
 * Catatan kepatuhan: penyimpanan bukti persetujuan TIDAK otomatis berarti
 * patuh UU PDP. UU PDP mengakui beberapa dasar pemrosesan (persetujuan hanya
 * salah satunya); pemilihan dasar pemrosesan tetap keputusan legal/compliance
 * organisasi.
 */
class FaceConsent extends Model
{
    protected $table = 'face_consents';

    protected $fillable = [
        'user_id',
        'notice_version',
        'notice_hash',
        'method',
        'evidence',
        'ip_address',
        'user_agent',
        'consented_at',
        'revoked_at',
        'revoke_reason',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Persetujuan yang masih berlaku. */
    public static function activeFor(int $userId, string $noticeVersion): ?self
    {
        return self::where('user_id', $userId)
            ->where('notice_version', $noticeVersion)
            ->whereNull('revoked_at')
            ->whereNotNull('consented_at')
            ->latest('id')
            ->first();
    }
}