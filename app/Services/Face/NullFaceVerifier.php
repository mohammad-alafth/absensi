<?php

namespace App\Services\Face;

use App\Contracts\FaceVerifier;
use App\Models\User;
use App\Support\FacePolicy;
use App\Support\FaceResult;

/**
 * Verifier bawaan yang dipakai saat layanan wajah belum aktif.
 *
 * Tidak pernah menolak absen:
 *  - `face.enabled` false atau role bebas -> FaceResult::exempt()
 *  - layanan wajah nonaktif             -> FaceResult::unavailable()
 *
 * Dipakai juga sebagai fallback bila mikroservernya down, sehingga absensi
 * tetap tercatat (dengan face_method = manual_fallback) untuk ditinjau HRD.
 */
class NullFaceVerifier implements FaceVerifier
{
    public function __construct(
        private readonly string $reason = 'Verifikasi wajah tidak diaktifkan.',
    ) {
    }

    public function verify(User $user, ?string $imageBase64): FaceResult
    {
        if (!FacePolicy::enabled() || FacePolicy::isExempt($user)) {
            return FaceResult::exempt();
        }

        if (!$imageBase64 || trim($imageBase64) === '') {
            return FaceResult::noFace();
        }

        return FaceResult::unavailable($this->reason);
    }
}