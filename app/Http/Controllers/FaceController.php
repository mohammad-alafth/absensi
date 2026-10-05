<?php

namespace App\Http\Controllers;

use App\Contracts\FaceVerifier;
use App\Models\User;
use App\Services\AttendancePunchService;
use App\Services\Face\NullFaceVerifier;
use App\Services\Face\RealFaceVerifier;
use App\Services\FaceAuditLogger;
use App\Services\FaceConsentService;
use App\Services\FaceEnrollmentService;
use App\Support\FacePolicy;
use App\Support\FaceResult;
use App\Support\FaceSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Absensi dengan verifikasi wajah.
 *
 * Catatan penting: jalur absen ber-wajah ini sudah ada sejak awal, tetapi
 * sebelumnya TIDAK memverifikasi wajah sama sekali (hanya GPS + simpan
 * foto). Sekarang alurnya:
 *
 *   1. validasi GPS,
 *   2. verifikasi wajah lewat FaceVerifier (bila wajah diaktifkan & wajib),
 *   3. simpan foto bukti,
 *   4. catat absen lewat AttendancePunchService (logika bersama).
 *
 * Semua parameter (ambang, maksimal percobaan, role bebas) dibaca dari
 * FaceSettings sehingga dapat diubah admin tanpa menyentuh kode.
 */
class FaceController extends Controller
{
    /** Scan wajah: verifikasi wajah + check in / check out. */
    public function matchFace(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy'  => 'nullable|numeric',
            'image'     => 'nullable|string',
            'attempt'   => 'nullable|integer|min:1',
        ]);

        $context = [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
        ];

        $faceResult = $this->verifier()->verify($user, $request->input('image'));
        $required = FacePolicy::isRequired($user);
        $attempt = (int) $request->input('attempt', 1);
        $maxAttempts = max(1, FaceSettings::int('face.max_attempts'));

        // 1. Gagal diverifikasi dan tidak ada jalan lain -> tolak.
        if ($required && !$faceResult->allowsAttendance()) {
            return response()->json([
                'success' => false,
                'face' => $faceResult->toArray(),
                'message' => $faceResult->message,
                'attempt' => $attempt,
                'attempt_remaining' => max(0, $maxAttempts - $attempt),
            ], 403);
        }

        // 2. Zona abu / tidak dikenali: minta coba lagi (B3) selagi percobaan
        //    masih tersedia. Setelah habis, absen tetap dicatat untuk review HRD.
        if ($required && $faceResult->needsReview() && $attempt < $maxAttempts) {
            return response()->json([
                'success' => false,
                'face' => $faceResult->toArray(),
                'message' => $faceResult->message,
                'attempt' => $attempt,
                'attempt_remaining' => $maxAttempts - $attempt,
                'retry' => true,
            ], 403);
        }

        return $this->recordAttendance($request, $user, $context, $faceResult);
    }

    /**
     * Scan wajah KERAS: kalau wajah tidak dikenali, TIDAK ada data absen yang
     * ditulis sama sekali, dan pengguna diarahkan ke halaman absen biasa.
     *
     * Kenapa perlu handler terpisah dari `matchFace()`:
     * matchFace() sengaja fail-open (kebijakan B3) - setelah batas percobaan
     * habis, absen tetap dicatat untuk review HRD. Itu perilaku yang benar
     * untuk halaman /face yang sudah jadi alur absen utama.
     *
     * Tapi untuk /face/scan, menulis absen padahal wajah DITOLAK adalah bug:
     * pengguna mengira absennya sah, padahal tidak terverifikasi. Jadi di sini
     * alurnya "ditolak -> lempar ke /face", dan keputusan fail-open diambil
     * halaman /face yang memang tugasnya.
     */
    public function scanFace(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy'  => 'nullable|numeric',
            'image'     => 'nullable|string',
        ]);

        $context = [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
        ];

        $faceResult = $this->verifier()->verify($user, $request->input('image'));

        // Role bebas, atau wajah sudah cocok: catat absen seperti biasa.
        if (!FacePolicy::isRequired($user) || $faceResult->isAccepted()) {
            return $this->recordAttendance($request, $user, $context, $faceResult);
        }

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::VERIFY,
            $user->id,
            $faceResult->status,
            ['score' => $faceResult->score, 'method' => $faceResult->method, 'entry' => 'face_scan'],
            $request
        );

        // TIDAK ada AttendancePunchService::punch() di sini. Itu inti perbaikannya.
        return response()->json([
            'success' => false,
            'redirect' => route('face'),
            'face' => $faceResult->toArray(),
            'message' => ($faceResult->message ?: 'Wajah tidak dikenali.')
                . ' Tidak ada absen yang dicatat. Silakan lanjut ke halaman absen biasa.',
        ], 403);
    }

    /**
     * Catat absen setelah wajah lolos (atau role bebas), lalu lampirkan hasil
     * verifikasi wajah + foto bukti.
     */
    private function recordAttendance(
        Request $request,
        User $user,
        array $context,
        FaceResult $faceResult
    ) {
        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::VERIFY,
            $user->id,
            $faceResult->status,
            ['score' => $faceResult->score, 'method' => $faceResult->method, 'attempt' => 1],
            $request
        );

        $imagePath = $this->storeImage($user, $request->input('image'));

        // Absen tetap dicatat (kebijakan B3), TETAPI harus ada tanda jelas
        // bahwa verifikasi wajah TIDAK dijalankan.
        //
        // Fail-open tanpa penanda ini berbahaya: absen wajah orang lain tetap
        // lolos, dan hasilnya terlihat 100% normal seperti absen sah. Serve-
        // nya hanya bisa tahu lewat face_match = NULL / face_method =
        // manual_fallback, sedangkan itu tidak pernah ditampilkan ke user.
        //
        // Jadi frontend wajib menampilkan peringatan kalau degraded = true.
        $degraded = $faceResult->status === FaceResult::UNAVAILABLE
            || $faceResult->method === 'manual_fallback';

        $punch = app(AttendancePunchService::class)->punch($user, $context, true);

        $payload = $punch;

        if ($degraded && ($punch['success'] ?? false)) {
            $payload['degraded'] = true;
            $payload['face'] = $faceResult->toArray();
            $payload['warning'] = 'ABSEN TERCATAT TANPA VERIFIKASI WAJAH: '
                . 'layanan pengenalan wajah sedang tidak tersedia, sehingga wajah Anda '
                . 'TIDAK diperiksa. Absen ini menunggu verifikasi HRD dan tidak boleh '
                . 'dianggap sah sebelum disetujui.';
        }

        if (!($punch['success'] ?? false)) {
            return response()->json($payload, 403);
        }

        app(AttendancePunchService::class)->attachFaceResult($user, $faceResult, $imagePath);

        return response()->json(array_merge($payload, [
            'face' => $faceResult->toArray(),
            'face_image' => $imagePath,
        ]));
    }

    /** Verifier aktif: layanan wajah bila aktif dan endpoint terisi. */
    private function verifier(): FaceVerifier
    {
        if (!FacePolicy::enabled() || FaceSettings::str('face.service_url') === '') {
            return new NullFaceVerifier('Layanan wajah belum dikonfigurasi.');
        }

        return new RealFaceVerifier();
    }

    /**
     * Registrasi wajah (beberapa sampel sekaligus).
     */
    /**
     * Registrasi wajah (beberapa sampel sekaligus).
     *
     * Biometrik hanya boleh diambil setelah pengguna membaca informasi dan
     * persetujuannya tercatat sebagai bukti (bukan sekadar checkbox agree = 1).
     */
    public function register(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $samples = $request->validate([
            'samples'   => 'required|array|min:1|max:20',
            'samples.*' => 'nullable|string',
])['samples'] ?? [];

        if (!FaceConsentService::hasValidConsent($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda harus menyetujui informasi biometrik wajah sebelum registrasi. Buka halaman Registrasi Wajah dan berikan persetujuan terlebih dahulu.',
                'requires_consent' => true,
            ], 428);
        }

        $result = app(FaceEnrollmentService::class)->enroll($user, $samples);

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::ENROLL,
            $user->id,
            $result['success'] ? 'success' : 'failed',
            [
                'accepted' => $result['accepted'] ?? 0,
                'required' => $result['required'] ?? 0,
            ],
            $request
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Simpan bukti persetujuan biometrik (versi informasi + tanda tangan).
     */
    public function consent(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'confirmed' => 'required|boolean',
            'typed_name' => 'required|string|max:120',
            'signature' => 'nullable|string|max:200000',
        ]);

        if (!$data['confirmed']) {
            return response()->json([
                'success' => false,
                'message' => 'Persetujuan belum diberikan.'
            ], 422);
        }

        // Nama yang diketik harus sama dengan nama akun: bukti kesediaan
        // minimal berupa pernyataan identitas oleh pemilik akun.
        if (trim($data['typed_name']) !== trim((string) $user->name)) {
            return response()->json([
                'success' => false,
            'message' => 'Nama yang diketik harus sama dengan nama akun Anda: ' . $user->name . '.'
            ], 422);
        }

        $method = filled($data['signature'] ?? null) ? 'drawn_signature' : 'typed_name';

        app(FaceConsentService::class)->grant($user, $method, $data['signature'] ?? null, $request);

        return response()->json([
            'success' => true,
            'message' => 'Persetujuan tersimpan. Sekarang Anda dapat mendaftarkan wajah.',
            'notice_version' => FaceConsentService::noticeVersion(),
            'notice_hash' => substr(FaceConsentService::noticeHash(), 0, 16),
        ]);
    }

    /**
     * Tarik kembali persetujuan; data wajah dihapus dan absen memakai
     * verifikasi manual oleh HRD.
     */
    public function revokeConsent(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        app(FaceConsentService::class)->revoke($user, $data['reason'] ?? null, $request);
        app(FaceEnrollmentService::class)->reset($user);

        return response()->json([
            'success' => true,
            'message' => 'Persetujuan dicabut dan data wajah dihapus. Absen dilakukan lewat verifikasi manual HRD.',
        ]);
    }

    /** Hapus pendaftaran wajah pengguna ini. */
    public function resetFace()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        app(FaceEnrollmentService::class)->reset($user);

        return response()->json(['success' => true, 'message' => 'Pendaftaran wajah dihapus.']);
    }

    /**
     * Simpan foto bukti ke storage publik.
     *
     * @return string|null path relatif, null bila tidak ada gambar / gagal
     */
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

    /** Halaman registrasi wajah (self-service). */
    public function showRegisterPage()
    {
        $user = auth()->user();

        return view('face.register', [
            'userName' => (string) $user->name,
            'consented' => FaceConsentService::hasValidConsent($user),
            'enrolled' => FacePolicy::isEnrolled($user),
            'consentedAt' => optional(\App\Models\FaceConsent::activeFor($user->id, FaceConsentService::noticeVersion()))?->consented_at,
            'enrolledSamples' => (int) ($user->face_samples ?? 0),
            'minSamples' => max(1, FaceSettings::int('face.min_samples')),
            'faceRequired' => FacePolicy::isRequired($user),
        ]);
    }

    /** Halaman scan wajah. */
    public function showFacePage()
    {
        $user = auth()->user();

        if ($user && $this->isHoliday(Carbon::today())) {
            return redirect()->back()->with('error', 'Tidak bisa absen di hari libur!');
        }

        return view('face.scan', [
            'faceRequired' => FacePolicy::isRequired($user),
            'enrolled' => FacePolicy::isEnrolled($user),
            'maxAttempts' => FaceSettings::int('face.max_attempts'),
            'matchThreshold' => FaceSettings::float('face.match_threshold'),
        ]);
    }

    /** Hari Minggu dianggap libur (bisa diubah sesuai kalender kantor). */
    private function isHoliday(Carbon $date): bool
    {
        return $date->isSunday();
    }
}
