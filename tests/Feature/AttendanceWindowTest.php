<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pengujian window absensi (check-in / check-out).
 *
 * Fokus:
 * 1. Check-in boleh dilakukan mulai 2 jam sebelum jam masuk shift
 *    (sebelumnya hanya 1 jam) - API AttendanceController & FaceController.
 * 2. Error "Diluar jam absensi" tidak boleh muncul saat karyawan masih
 *    berada di jam dinas (mis. shift malam yang melewati tengah malam
 *    tetapi flag is_overnight pada employee_shifts tidak tersimpan).
 * 3. Check-out shift malam keesokan paginya tetap bisa dilakukan walau
 *    flag is_overnight tidak tersimpan (data lama / bulk assign / PJ).
 */
class AttendanceWindowTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $workType = 'shift'): User
    {
        return User::create([
            'name' => 'Karyawan Window Test',
            'email' => 'window-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'security',
            'work_type' => $workType,
            'email_verified_at' => now(),
        ]);
    }

    private function makeShift(string $start, string $end, bool $isOvernight = false): Shift
    {
        return Shift::create([
            'name' => 'Shift Test ' . uniqid(),
            'start_time' => $start,
            'end_time' => $end,
            'work_hours' => 8,
            'grace_minutes' => 15,
            'is_overnight' => $isOvernight,
        ]);
    }

    private function assignShift(User $user, string $date, string $start, string $end, bool $isOvernight = false): EmployeeShift
    {
        $shift = $this->makeShift($start, $end, $isOvernight);

        return EmployeeShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'is_overnight' => $isOvernight,
        ]);
    }

    private function hitAttendance(User $user, Carbon $now)
    {
        Carbon::setTestNow($now);

        return $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance', [
                'latitude' => 0.4761258,
                'longitude' => 101.4190600,
            ]);
    }

    public function test_checkin_dua_jam_sebelum_jam_masuk_diterima(): void
    {
        $user = $this->makeUser('shift');
        $this->assignShift($user, '2026-09-07', '08:00:00', '17:00:00', false);

        $now = Carbon::create(2026, 9, 7, 6, 0, 0); // 08:00 - 2 jam
        $response = $this->hitAttendance($user, $now);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', 'checkin')
            ->assertJsonPath('status', 'hadir');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'tanggal' => '2026-09-07',
            'status' => 'hadir',
        ]);
    }

    public function test_checkin_satu_setengah_jam_sebelum_jam_masuk_diterima(): void
    {
        $user = $this->makeUser('shift');
        $this->assignShift($user, '2026-09-07', '08:00:00', '17:00:00', false);

        // Sebelum perbaikan: 06:30 berada di luar window 1 jam (07:00),
        // sehingga ditolak "Diluar jam checkin / Belum masuk jam absensi".
        $now = Carbon::create(2026, 9, 7, 6, 30, 0);
        $response = $this->hitAttendance($user, $now);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', 'checkin');
    }

    public function test_checkin_karyawan_office_dua_jam_sebelum_masuk_diterima(): void
    {
        $user = $this->makeUser('office_5');
        // 2026-09-07 adalah hari Senin (hari kerja office_5 08:00-17:00)
        $now = Carbon::create(2026, 9, 7, 6, 0, 0);
        $response = $this->hitAttendance($user, $now);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', 'checkin')
            ->assertJsonPath('status', 'hadir');
    }

    public function test_checkin_lebih_dari_dua_jam_sebelum_masuk_ditolak_dengan_pesan_jelas(): void
    {
        $user = $this->makeUser('shift');
        $this->assignShift($user, '2026-09-07', '08:00:00', '17:00:00', false);

        // 05:50 = 2 jam 10 menit sebelum jam masuk -> masih ditolak,
        // tetapi pesan error harus jelas (bukan "Diluar jam absensi").
        $now = Carbon::create(2026, 9, 7, 5, 50, 0);
        $response = $this->hitAttendance($user, $now);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Belum masuk jam absensi (absensi dibuka mulai 2 jam sebelum jam masuk)');
    }

    public function test_checkin_terlambat_lebih_dari_dua_jam_setelah_jam_masuk_ditolak(): void
    {
        $user = $this->makeUser('shift');
        $this->assignShift($user, '2026-09-07', '08:00:00', '17:00:00', false);

        // 10:10 = 2 jam 10 menit setelah jam masuk -> melewati batas check-in.
        $now = Carbon::create(2026, 9, 7, 10, 10, 0);
        $response = $this->hitAttendance($user, $now);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Diluar jam checkin (maksimal 2 jam setelah jam masuk)');
    }

    public function test_shift_malam_tanpa_flag_overnight_tidak_ditolak_saat_jam_dinas(): void
    {
        $user = $this->makeUser('shift');

        // Simulasi data lama dari bulk assign: is_overnight TIDAK tersimpan (0)
        // padahal shift 22:00 - 06:00 melewati tengah malam.
        $this->assignShift($user, '2026-09-07', '22:00:00', '06:00:00', false);

        // 23:30 -> karyawan sedang bertugas (masih di jam dinas).
        // Sebelum perbaikan: ScheduleService menghitung shift_end = 06:00
        // (hari yang sama, sudah lewat) sehingga window tidak valid dan
        // controller mengembalikan error "Diluar jam absensi".
        $now = Carbon::create(2026, 9, 7, 23, 30, 0);
        $response = $this->hitAttendance($user, $now);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', 'checkin')
            ->assertJsonPath('status', 'terlambat');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'tanggal' => '2026-09-07',
            'status' => 'terlambat',
        ]);
    }

    public function test_checkout_shift_malam_pagi_harinya_tetap_bisa_walau_flag_overnight_hilang(): void
    {
        $user = $this->makeUser('shift');

        // Shift malam kemarin (2026-09-06, Minggu malam) 22:00 - 06:00.
        // Data sengaja dibuat TANPA flag is_overnight (kondisi bulk assign lama).
        $this->assignShift($user, '2026-09-06', '22:00:00', '06:00:00', false);

        Attendance::create([
            'user_id' => $user->id,
            'tanggal' => '2026-09-06',
            'jam_masuk' => '22:00:00',
            'status' => 'hadir',
        ]);

        // Keesokan paginya 07:00 (shift sudah selesai 06:00, lewat 1 jam).
        // Sebelum perbaikan: shift kemarin tidak ditemukan (flag 0),
        // jadwal hari ini kosong -> "Hari ini anda libur" / tidak bisa checkout.
        $now = Carbon::create(2026, 9, 7, 7, 0, 0);
        $response = $this->hitAttendance($user, $now);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', 'checkout')
            ->assertJsonPath('overtime_minutes', 60);

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', '2026-09-06')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->jam_keluar);
        $this->assertEquals(60, $attendance->overtime_minutes);
    }

    public function test_checkout_terlalu_awal_ditolak_dan_sesudah_jam_selesai_diterima(): void
    {
        $user = $this->makeUser('shift');
        $this->assignShift($user, '2026-09-07', '08:00:00', '17:00:00', false);

        // Check-in normal 06:00
        $response = $this->hitAttendance($user, Carbon::create(2026, 9, 7, 6, 0, 0));
        $response->assertOk()->assertJsonPath('type', 'checkin');

        // Check-out 16:00 -> masih 1 jam sebelum selesai (batas 16:55) -> ditolak
        $response = $this->hitAttendance($user, Carbon::create(2026, 9, 7, 16, 0, 0));
        $response->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Belum waktu checkout (baru bisa 5 menit sebelum jam pulang)');

        // Check-out 17:00 -> tepat jam selesai -> diterima
        $response = $this->hitAttendance($user, Carbon::create(2026, 9, 7, 17, 0, 0));
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('type', 'checkout')
            ->assertJsonPath('overtime_minutes', 0);
    }
}
