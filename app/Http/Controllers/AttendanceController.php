<?php

namespace App\Http\Controllers;

use App\Contracts\FaceVerifier;
use App\Models\User;
use App\Services\AttendancePunchService;
use App\Services\FaceAuditLogger;
use App\Services\Face\NullFaceVerifier;
use App\Services\Face\RealFaceVerifier;
use App\Support\FacePolicy;
use App\Support\FaceResult;
use App\Support\FaceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Absensi biasa (tanpa halaman scan wajah).
 *
 * Jalur ini TIDAK lagi menjadi bypass: wajah diaktifkan dan pengguna
 * diwajibkan memakai wajah, permintaan wajib menyertakan foto wajah sehingga
 * tidak bisa memotong verifikasi dengan memakai endpoint biasa.
 *
 * Bila wajah tidak wajib atau layanan dimatikan, perilaku lama dipertahankan
 * sepenuhnya (absen tetap jalan seperti sebelumnya).
 */
class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $context = [
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'accuracy' => $request->input('accuracy'),
        ];

        $faceResult = null;

        if (FacePolicy::isRequired($user)) {
            $request->validate([
                'image' => 'required|string',
            ]);

            $faceResult = $this->verifier()->verify($user, $request->input('image'));
            $attempt = (int) $request->input('attempt', 1);
            $maxAttempts = max(1, FaceSettings::int('face.max_attempts'));

            // Tidak boleh absen bila wajah gagal diverifikasi dan tidak ada alternatif.
            if (!$faceResult->allowsAttendance()) {
                return response()->json([
                    'success' => false,
                    'face' => $faceResult->toArray(),
                    'message' => $faceResult->message,
                    'attempt' => $attempt,
                    'attempt_remaining' => max(0, $maxAttempts - $attempt),
                ], 403);
            }

            // Zona abu / tidak dikenali: minta coba lagi selagi masih ada kesempatan.
            if ($faceResult->needsReview() && $attempt < $maxAttempts) {
                return response()->json([
                    'success' => false,
                    'face' => $faceResult->toArray(),
                    'message' => $faceResult->message,
                    'attempt' => $attempt,
                    'attempt_remaining' => $maxAttempts - $attempt,
                    'retry' => true,
                ], 403);
            }
        }

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::VERIFY,
            $user->id,
            $faceResult?->status ?? 'not_required',
            ['score' => $faceResult?->score, 'method' => $faceResult?->method],
            $request
        );

        $punch = app(AttendancePunchService::class)->punch($user, $context, false);

        if (!($punch['success'] ?? false)) {
            // Menghormati status bawaan service (checkout terlalu awal dibalas 200).
            return response()->json($punch, (int) ($punch['http_status'] ?? 403));
        }

        if ($faceResult instanceof FaceResult) {
            $imagePath = $this->storeImage($user, $request->input('image'));
            app(AttendancePunchService::class)->attachFaceResult($user, $faceResult, $imagePath);
            $punch['face'] = $faceResult->toArray();
            $punch['face_image'] = $imagePath;
        }

        return response()->json($punch);
    }

    /** Verifier aktif: layanan wajah bila aktif dan endpoint terisi. */
    private function verifier(): FaceVerifier
    {
        if (!FacePolicy::enabled() || FaceSettings::str('face.service_url') === '') {
            return new NullFaceVerifier('Layanan wajah belum dikonfigurasi.');
        }

        return new RealFaceVerifier();
    }

    /** Simpan foto bukti absen. */
    private function storeImage(?User $user, ?string $image): ?string
    {
        if ($user === null || !is_string($image) || trim($image) === '') {
            return null;
        }

        $binary = base64_decode(
            (string) preg_replace('/\s+/', '', (string) preg_replace(
                '#^data:image/[a-zA-Z0-9.+-]+;base64,#',
                '',
                trim($image)
            )),
            true
        );

        if ($binary === false || $binary === '') {
            return null;
        }

        $fileName = 'face-proof/' . $user->id . '_' . time() . '.jpg';

        // Foto bukti TIDAK ditampilkan atau diunduh di mana pun (keputusan B1):
        // disimpan di disk privat sehingga tidak punya URL publik.
        Storage::disk('local')->put($fileName, $binary);

        return $fileName;
    }
}