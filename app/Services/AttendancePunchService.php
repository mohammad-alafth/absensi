<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Support\FaceResult;
use App\Support\FaceSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya tempat logika punch absensi (check-in / check-out).
 *
 * Sebelumnya logika ini diduplikasi di `AttendanceController::store()` dan
 * `FaceController::matchFace()`, sehingga rawan berbeda hasil antara jalur
 * biasa dan jalur wajah. Sekarang keduanya memakai service ini.
 *
 * Yang ditangani: validasi GPS (opsional sesuai jalur pemanggil), jadwal hari
 * ini termasuk shift lintas tengah malam, jendela check-in / check-out dan
 * masa tenggang, status hadir / terlambat, serta menit lembur hasil absensi.
 *
 * Verifikasi wajah tidak dilakukan di sini; pemanggil sudah melakukannya lalu
 * menempelkan hasilnya lewat attachFaceResult().
 */
class AttendancePunchService
{
    public function __construct(
        private readonly ScheduleService $scheduleService = new ScheduleService(),
    ) {
    }

    /**
     * @param array{latitude?: ?float, longitude?: ?float, accuracy?: ?float} $context
     * @return array<string, mixed> payload JSON siap kirim
     */
    public function punch(User $user, array $context = [], bool $requireGps = false): array
    {
        $now = Carbon::now();

        if ($requireGps) {
            if ($error = $this->validateGps($context)) {
                return $error;
            }
        }

        $schedule = $this->scheduleService->getTodaySchedule($user);

        if (!$schedule) {
            return $this->fail('Hari ini anda libur', 403);
        }

        $shiftStart = $schedule['shift_start'];
        $shiftEnd = $schedule['shift_end'];
        $shiftDate = $schedule['shift_date'];
        $graceMinutes = $schedule['grace_minutes'] ?? ScheduleService::DEFAULT_GRACE_MINUTES;
        $lateLimit = $shiftStart->copy()->addMinutes($graceMinutes);

        // Absensi memakai tanggal mulai shift (bukan hari kalender) supaya
        // shift lintas tengah malam menemukan baris yang sama.
        $attendance = Attendance::where('user_id', $user->id)
            ->where('tanggal', $shiftDate)
            ->orderByDesc('jam_masuk')
            ->first();

        if (!$attendance) {
            return $this->checkIn($user, $context, $now, $shiftStart, $shiftEnd, $shiftDate, $lateLimit);
        }

        if ($attendance->jam_keluar) {
            return $this->fail('Anda sudah check out hari ini', 403);
        }

        return $this->checkOut($attendance, $now, $shiftEnd, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function checkIn(
        User $user,
        array $context,
        Carbon $now,
        Carbon $shiftStart,
        Carbon $shiftEnd,
        string $shiftDate,
        Carbon $lateLimit
    ): array {
        $checkinStart = $shiftStart->copy()->subHours(ScheduleService::EARLY_CHECKIN_HOURS);
        $checkinEnd = $shiftStart->copy()->addHours(ScheduleService::LATE_CHECKIN_HOURS);

        if ($now->lt($checkinStart)) {
            return $this->fail(
                'Belum masuk jam absensi (absensi dibuka mulai '
                . ScheduleService::EARLY_CHECKIN_HOURS . ' jam sebelum jam masuk)',
                403
            );
        }

        if ($now->gt($checkinEnd)) {
            return $this->fail(
                'Diluar jam checkin (maksimal ' . ScheduleService::LATE_CHECKIN_HOURS . ' jam setelah jam masuk)',
                403
            );
        }

        $status = 'hadir';
        $lateMinutes = 0;

        if ($now->gt($lateLimit)) {
            $status = 'terlambat';
            $lateMinutes = (int) round($lateLimit->diffInMinutes($now));
        }

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'tanggal' => $shiftDate,
            'jam_masuk' => $now,
            'latitude' => $context['latitude'] ?? null,
            'longitude' => $context['longitude'] ?? null,
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'scheduled_checkin' => $shiftStart,
            'scheduled_checkout' => $shiftEnd,
        ]);

        $message = $status === 'terlambat'
            ? 'Check In berhasil (Terlambat ' . $lateMinutes . ' menit)'
            : 'Check In berhasil';

        return $this->ok($message, [
            'type' => 'checkin',
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'attendance_id' => $attendance->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function checkOut(Attendance $attendance, Carbon $now, Carbon $shiftEnd, array $context): array
    {
        $checkoutTime = $shiftEnd->copy()->subMinutes(ScheduleService::CHECKOUT_GRACE_MINUTES);

        if ($now->lt($checkoutTime)) {
            // Perilaku lama: endpoint biasa membalas 200 dengan success=false.
            return $this->fail(
                'Belum waktu checkout (baru bisa '
                . ScheduleService::CHECKOUT_GRACE_MINUTES . ' menit sebelum jam pulang)',
                200,
                ['http_status' => 200]
            );
        }

        $overtimeMinutes = $now->gt($shiftEnd) ? $shiftEnd->diffInMinutes($now) : 0;

        $attendance->update([
            'jam_keluar' => $now,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        return $this->ok(
            $overtimeMinutes > 0
                ? 'Check Out berhasil (Lembur ' . $overtimeMinutes . ' menit)'
                : 'Check Out berhasil',
            [
                'type' => 'checkout',
                'overtime_minutes' => $overtimeMinutes,
                'attendance_id' => $attendance->id,
            ]
        );
    }
/**
     * Cek akurasi & radius GPS terhadap titik kantor (dari FaceSettings).
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null payload error, null bila aman
     */
    public function validateGps(array $context): ?array
    {
        $accuracy = $context['accuracy'] ?? null;

        if ($accuracy !== null && (float) $accuracy > 200) {
            return $this->fail(
                'GPS tidak akurat, aktifkan GPS (' . round((float) $accuracy) . ' meter)',
                403
            );
        }

        $latitude = $context['latitude'] ?? null;
        $longitude = $context['longitude'] ?? null;

        if ($latitude === null || $longitude === null) {
            return $this->fail('Lokasi tidak terbaca, izinkan akses lokasi.', 403);
        }

        $radius = FaceSettings::float('office.radius_meters');

        if ($radius <= 0) {
            return null; // radius 0 berarti validasi GPS dimatikan.
        }

        $distance = self::distanceBetween(
            (float) FaceSettings::float('office.latitude'),
            (float) FaceSettings::float('office.longitude'),
            (float) $latitude,
            (float) $longitude
        );

        if ($distance > $radius) {
            return $this->fail(
                'Anda berada di luar radius kantor (' . round($distance, 2) . ' meter)',
                403,
                ['distance' => round($distance, 2)]
            );
        }

        return null;
    }

    /** Jarak dua titik koordinat dalam meter (rumus Haversine). */
    public static function distanceBetween(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6372000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Tempelkan hasil verifikasi wajah pada baris attendance terakhir.
     *
     * Dipanggil setelah punch sukses supaya bukti wajah ikut tersimpan.
     */
    public function attachFaceResult(User $user, FaceResult $result, ?string $imagePath = null): void
    {
        try {
            $attendance = Attendance::where('user_id', $user->id)
                ->orderByDesc('id')
                ->first();

            if (!$attendance) {
                return;
            }

            $attendance->update([
                'face_image' => $imagePath ?? $attendance->face_image,
                'face_score' => $result->score,
                'face_match' => $result->toMatchColumn(),
                'face_method' => $result->method,
                'face_verified_at' => $result->status === FaceResult::EXEMPT ? null : Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            // Bukti wajah tidak boleh menggagalkan absensi.
            Log::warning('FacePunch: gagal menyimpan bukti wajah', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function ok(string $message, array $extra = []): array
    {
        return array_merge(['success' => true, 'message' => $message], $extra);
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function fail(string $message, int $status = 403, array $extra = []): array
    {
        return array_merge(['success' => false, 'message' => $message], $extra);
    }
}