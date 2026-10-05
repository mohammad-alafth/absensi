<?php

namespace App\Services;

use App\Models\User;
use App\Support\FaceEmbedding;
use App\Support\FaceSettings;

/**
 * Registrasi wajah seorang karyawan.
 *
 * Alur:
 *  1. Terima beberapa sampel foto (base64).
 *  2. Setiap sampel di-embed oleh microservice; sampel yang gagal dibaca atau
 *     kualitasnya di bawah ambang dilewati (dihitung sebagai gagal).
 *  3. Vektor yang lolos dirata-ratakan lalu dinormalisasi L2 -> makin banyak
 *     sampel, makin stabil, dan makin kecil risiko false reject.
 *  4. Simpan ke `users.face_embedding` dengan status `enrolled`.
 *
 * Semua ambang dibaca dari FaceSettings sehingga dapat disetel admin.
 */
class FaceEnrollmentService
{
    public function __construct(
        private readonly FaceVerificationService $service = new FaceVerificationService(),
    ) {
    }

    /**
     * @param array<int, string|null> $samples foto base64
     * @return array<string, mixed>
     */
    public function enroll(User $user, array $samples): array
    {
        $required = max(1, FaceSettings::int('face.min_samples'));
        $minQuality = FaceSettings::float('face.min_quality');

        $vectors = [];
        $failed = 0;

        foreach (array_values($samples) as $sample) {
            if (!is_string($sample) || trim($sample) === '') {
                continue;
            }

            $embedded = $this->service->embed($sample);

            if ($embedded === null) {
                $failed++;
                continue;
            }

            if (($embedded['quality'] ?? 0.0) < $minQuality) {
                $failed++;
                continue;
            }

            $vectors[] = $embedded['embedding'];
        }

        $accepted = count($vectors);

        if ($accepted < $required) {
            return [
                'success' => false,
                'message' => "Wajah belum terdaftar: {$accepted}/{$required} sampel terbaca. "
                    . 'Pastikan wajah terlihat jelas, pencahayaan cukup, dan tidak memakai masker atau kacamata.',
                'accepted' => $accepted,
                'required' => $required,
                'failed' => $failed,
            ];
        }

        $embedding = FaceVerificationService::averageEmbedding($vectors);

        if ($embedding === []) {
            return [
                'success' => false,
                'message' => 'Wajah gagal diproses. Silakan ulangi.',
                'accepted' => $accepted,
                'required' => $required,
                'failed' => $failed,
            ];
        }

        $user->update([
            'face_embedding' => FaceEmbedding::encode($embedding),
            'face_status' => 'enrolled',
            'face_enrolled_at' => now(),
            'face_samples' => $accepted,
        ]);

        return [
            'success' => true,
            'message' => "Wajah berhasil didaftarkan ({$accepted} sampel).",
            'accepted' => $accepted,
            'required' => $required,
            'failed' => $failed,
            'dimensions' => count($embedding),
        ];
    }

    /** Hapus pendaftaran wajah (mis. karyawan berhenti / ganti device). */
    public function reset(User $user): void
    {
        $user->update([
            'face_embedding' => null,
            'face_status' => 'none',
            'face_enrolled_at' => null,
            'face_samples' => 0,
        ]);
    }
}