<?php

namespace App\Services\Face;

use App\Contracts\FaceVerifier;
use App\Models\User;
use App\Services\FaceVerificationService;
use App\Support\FaceEmbedding;
use App\Support\FacePolicy;
use App\Support\FaceResult;
use App\Support\FaceSettings;

/**
 * Verifikasi wajah sesungguhnya: memanggil microservice InsightFace.
 *
 * Alur:
 *  1. Wajib wajah? tidak -> exempt.
 *  2. Sudah terdaftar? tidak -> no_enrollment (absen tetap dicatat untuk review).
 *  3. Panggil microservice. Gagal / timeout -> unavailable (fail-open).
 *  4. Skor >= ambang match -> match; >= ambang abu -> gray; selain itu mismatch.
 */
class RealFaceVerifier implements FaceVerifier
{
    public function __construct(
        private readonly FaceVerificationService $service = new FaceVerificationService(),
    ) {
    }

    public function verify(User $user, ?string $imageBase64): FaceResult
    {
        if (!FacePolicy::enabled() || FacePolicy::isExempt($user)) {
            return FaceResult::exempt();
        }

        if (!FacePolicy::isEnrolled($user)) {
            return FaceResult::noEnrollment('Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi.');
        }

        if (!$imageBase64 || trim($imageBase64) === '') {
            return FaceResult::noFace();
        }

        $embedding = FaceEmbedding::decode($user->face_embedding);

        if ($embedding === []) {
            return FaceResult::noEnrollment('Data wajah tersimpan tidak valid. Hubungi HRD.');
        }

        // Lapisan anti-spoof DI DEPAN pencocokan: memastikan yang ada di depan
        // kamera adalah orang sungguhan, bukan foto atau video.
        if (FaceSettings::bool('face.spoof_enabled')) {
            $spoof = $this->service->antiSpoof($imageBase64);

            if ($spoof !== null && !$spoof['passed']) {
                return FaceResult::spoof($spoof['reason'], $spoof['score']);
            }
        }

        $score = $this->service->match($imageBase64, $embedding);

        if ($score === null) {
            return FaceResult::unavailable(
                'Layanan verifikasi wajah sedang tidak tersedia. Absen dicatat dan akan diverifikasi HRD.'
            );
        }

        $matchThreshold = FaceSettings::matchThresholdFor(
            $user->face_threshold !== null ? (float) $user->face_threshold : null
        );
        $grayThreshold = FaceSettings::float('face.gray_threshold');

        return $this->classify($score, $matchThreshold, $grayThreshold);
    }

    /**
     * Klasifikasikan skor menjadi status (dipisah agar mudah diuji).
     */
    public function classify(float $score, float $matchThreshold, float $grayThreshold): FaceResult
    {
        if ($score >= $matchThreshold) {
            return FaceResult::match(round($score, 3));
        }

        if ($score >= $grayThreshold) {
            return FaceResult::gray(round($score, 3), sprintf(
                'Wajah belum pasti (skor %.2f). Absen dicatat dan perlu verifikasi HRD.',
                $score
            ));
        }

        return FaceResult::mismatch(round($score, 3), sprintf(
            'Wajah tidak dikenali (skor %.2f). Silakan coba lagi atau hubungi HRD.',
            $score
        ));
    }
}