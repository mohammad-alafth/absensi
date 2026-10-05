<?php

namespace App\Services;

use App\Models\FaceAuditLog;
use Illuminate\Http\Request;

/**
 * Pencatatan jejak audit data biometrik (registrasi, verifikasi, spoof check,
 * review HRD, perubahan pengaturan).
 *
 * Sengaja dibuat best-effort: kegagalan menulis log TIDAK boleh menggagalkan
 * proses absensi, namun juga diam-diam diabaikan (dicatat ke log aplikasi).
 */
class FaceAuditLogger
{
    /** Aksi yang dicatat. */
    public const ENROLL = 'face.enroll';
    public const VERIFY = 'face.verify';
    public const SPOOF = 'face.spoof_check';
    public const REVIEW_APPROVE = 'face.review.approve';
    public const REVIEW_REJECT = 'face.review.reject';
    public const SETTINGS_UPDATE = 'face.settings.update';
    public const SETTINGS_RESET = 'face.settings.reset';
    public const CONSENT = 'face.consent';
    public const CONSENT_REVOKE = 'face.consent.revoke';

    /**
     * @param array<string, mixed> $context
     */
    public function log(
        string $action,
        ?int $subjectUserId = null,
        ?string $result = null,
        array $context = [],
        ?Request $request = null
    ): void {
        try {
            FaceAuditLog::create([
                'actor_role' => auth()->user()?->role,
                'actor_id' => auth()->id(),
                'user_id' => $subjectUserId,
                'action' => $action,
                'result' => $result,
                'ip_address' => $request?->ip(),
                'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255) ?: null,
                'context' => $context === [] ? null : $context,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}