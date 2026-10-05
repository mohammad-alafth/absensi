<?php

namespace App\Services;

use App\Support\FaceSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klien HTTP ke microservice face recognition (self-hosted).
 *
 * Kontrak endpoint microservice:
 *   POST {FACE_SERVICE_URL}/embed  body: { image: <base64> }
 *        -> { success: true, embedding: [512 float], quality: 0..1 }
 *   POST {FACE_SERVICE_URL}/match  body: { image: <base64>, embedding: [512 float] }
 *        -> { success: true, score: 0..1, detected: true }
 *
 * Semua parameter (URL, token, timeout) dibaca dari FaceSettings sehingga dapat
 * diubah admin. Kegagalan jaringan TIDAK pernah dilempar keluar agar absensi
 * tidak terkunci.
 */
class FaceVerificationService
{
    /** Bersihkan prefix data URI dan whitespace dari base64 foto. */
    public function normalizeImage(?string $imageBase64): ?string
    {
        if (!is_string($imageBase64) || trim($imageBase64) === '') {
            return null;
        }

        $image = preg_replace('#^data:image/[a-zA-Z0-9.+-]+;base64,#', '', trim($imageBase64));
        $image = preg_replace('/\s+/', '', (string) $image);

        return $image === '' ? null : $image;
    }

    /**
     * Hitung vektor wajah dari satu foto.
     *
     * @return array{embedding: array<int, float>, quality: float}|null
     */
    public function embed(?string $imageBase64): ?array
    {
        $image = $this->normalizeImage($imageBase64);

        if ($image === null) {
            return null;
        }

        $response = $this->call('/embed', ['image' => $image]);

        if ($response === null || !($response['success'] ?? false)) {
            return null;
        }

        $embedding = $response['embedding'] ?? null;

        if (!is_array($embedding) || $embedding === []) {
            return null;
        }

        return [
            'embedding' => array_map('floatval', array_values($embedding)),
            'quality' => (float) ($response['quality'] ?? 1),
        ];
    }

    /**
     * Bandingkan foto dengan vektor terdaftar.
     *
     * @param array<int, float> $embedding
     */
    public function match(?string $imageBase64, array $embedding): ?float
    {
        $image = $this->normalizeImage($imageBase64);

        if ($image === null || $embedding === []) {
            return null;
        }

        $response = $this->call('/match', [
            'image' => $image,
            'embedding' => array_values(array_map('floatval', $embedding)),
        ]);

        if ($response === null || !($response['success'] ?? false)) {
            return null;
        }

        if (!array_key_exists('score', $response) || $response['score'] === null) {
            return null;
        }

        return (float) $response['score'];
    }

    /**
     * Panggil microservice; null bila gagal / timeout / non-2xx.
     */
    private function call(string $path, array $payload): ?array
    {
        $baseUrl = rtrim(FaceSettings::str('face.service_url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        try {
            $response = $this->request()->post($baseUrl . $path, $payload);

            if (!$response->successful()) {
                Log::warning('FaceService: respons tidak sukses', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::warning('FaceService: layanan tidak dapat dihubungi', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
/**
     * Cosine similarity dua vektor.
     *
     * @param array<int, float> $a
     * @param array<int, float> $b
     */
    /**
     * Klien HTTP dengan timeout & token dari FaceSettings.
     */
    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        $timeoutSeconds = max(0.5, FaceSettings::int('face.attempt_timeout_ms') / 1000);
        $request = Http::timeout($timeoutSeconds)->acceptJson()->asJson();
        $token = FaceSettings::str('face.service_token');

        return $token === '' ? $request : $request->withToken($token);
    }

    /**
     * Cek kesehatan layanan lewat GET /health.
     *
     * Dipakai tombol "Uji Layanan" di pengaturan dan command face:housekeeping.
     * Endpoint /health tidak memerlukan gambar, jadi tidak akan selalu gagal
     * hanya karena foto kosong (seperti bila memanggil match(null, [])).
     *
     * @return array{ok: bool, message: string}|null null bila tidak dapat dihubungi
     */
    public function health(): ?array
    {
        $baseUrl = rtrim(FaceSettings::str('face.service_url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        try {
            $response = $this->request()->get($baseUrl . '/health');

            if (!$response->successful()) {
                return null;
            }

            $body = $response->json();

            return [
                'ok' => (bool) ($body['success'] ?? true),
                'message' => (string) ($body['message'] ?? $body['status'] ?? 'OK'),
            ];
        } catch (Throwable $e) {
            Log::warning('FaceService: health check gagal', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Cosine similarity dua vektor (0..1).
     */
    public static function cosine(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Rata-rata beberapa vektor lalu dinormalisasi L2 (wajah terdaftar).
     *
     * @param array<int, array<int, float>> $vectors
     * @return array<int, float>
     */
    public static function averageEmbedding(array $vectors): array
    {
        $vectors = array_values(array_filter($vectors, static fn ($v) => is_array($v) && $v !== []));

        if ($vectors === []) {
            return [];
        }

        $size = min(array_map('count', $vectors));
        $sum = array_fill(0, $size, 0.0);

        foreach ($vectors as $vector) {
            for ($i = 0; $i < $size; $i++) {
                $sum[$i] += (float) $vector[$i];
            }
        }

        $mean = array_map(static fn (float $v): float => $v / count($vectors), $sum);

        return self::normalize($mean);
    }

    /**
     * Normalisasi L2.
     *
     * @param array<int, float> $vector
     * @return array<int, float>
     */
    public static function normalize(array $vector): array
    {
        $norm = sqrt(array_sum(array_map(static fn ($v) => $v * $v, $vector)));

        if ($norm <= 0.0) {
            return $vector;
        }

        return array_map(static fn ($v) => $v / $norm, $vector);
    }

    /**
     * Pemeriksaan anti-spoof (lapisan sebelum pencocokan wajah).
     *
     * Memastikan yang ada di depan kamera adalah orang sungguhan, bukan foto
     * atau video. Lapisan ini berada DI DEPAN Face Recognition, bukan pengganti.
     *
     * Yang diperiksa di sisi layanan:
     *  - tepat satu wajah terdeteksi,
     *  - ukuran wajah minimum,
     *  - Variance tinggi-frequency: foto cetak atau layar menghasilkan pola moire
     *    yang lebih datar dibanding permukaan kulit nyata,
     *  - kualitas pencahayaan (selfie di ruang gelap mudah dipalsukan),
     *  - opsional: model Face Anti-Spoofing (mis. MiniFASNet).
     *
     * CATATAN LISENSI: penggunaan MiniFASNet (atau model anti-spoof lain) WAJIB
     * diperiksa lisensi kode dan lisensi bobot modelnya sebelum dipakai di
     * produksi. Model anti-spoof berbasis model dinonaktifkan sampai itu dikonfirmasi.
     *
     * @return array{passed: bool, reason: ?string, score: ?float}|null
     */
    public function antiSpoof(?string $imageBase64): ?array
    {
        $image = $this->normalizeImage($imageBase64);

        if ($image === null) {
            return null;
        }

        $response = $this->call('/detect', ['image' => $image]);

        if ($response === null) {
            return null;
        }

        if (array_key_exists('passed', $response)) {
            return [
                'passed' => (bool) $response['passed'],
                'reason' => $response['reason'] ?? null,
                'score' => isset($response['score']) ? (float) $response['score'] : null,
            ];
        }

        // Layanan belum memiliki /detect: tidak menahan alur, dicatat di log.
        Log::info('FaceService: /detect tidak tersedia, spoof check dilewati.');

        return null;
    }}
