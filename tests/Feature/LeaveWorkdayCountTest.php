<?php

namespace Tests\Feature;

use App\Models\EmployeeShift;
use App\Models\Leave;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pengajuan cuti hanya menghitung hari yang punya jadwal kerja.
 *
 * Hari tanpa jadwal tidak mengurangi kuota cuti. Contoh: cuti tgl 1-5
 * dengan jadwal hanya di tgl 1, 2, dan 5 (tgl 3 & 4 tanpa jadwal)
 * maka total_days = 3.
 *
 * Aturan jadwal:
 *   - office_5 : Senin-Jumat (Sabtu/Minggu libur)
 *   - office_6 : Senin-Sabtu (Minggu libur)
 *   - shift    : tanggal yang memiliki baris employee_shifts
 */
class LeaveWorkdayCountTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $workType, int $quota = 12): User
    {
        return User::create([
            'name' => 'User Cuti Jadwal ' . uniqid(),
            'email' => 'cuti-jadwal-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'cs',
            'work_type' => $workType,
            'email_verified_at' => now(),
            'is_approved' => 1,
            'leave_quota' => $quota,
        ]);
    }

    private function submitLeave(User $user, string $start, string $end)
    {
        return $this->actingAs($user)->post('/cuti', [
            'start_date' => $start,
            'end_date' => $end,
            'leave_type' => 'cuti tahunan',
            'reason' => 'Keperluan keluarga',
            'employee_signature' => 'data:image/png;base64,AAAA',
        ]);
    }

    private function assignShift(User $user, string $date): EmployeeShift
    {
        $shift = Shift::create([
            'name' => 'Shift Cuti ' . uniqid(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'work_hours' => 8,
            'grace_minutes' => 5,
            'is_overnight' => false,
        ]);

        return EmployeeShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'shift_date' => $date,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_overnight' => false,
        ]);
    }

    private function latestLeave(User $user): Leave
    {
        return Leave::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_office_5_hanya_menghitung_hari_berjadwal(): void
    {
        $user = $this->makeUser('office_5');

        // 1 Okt 2026 = Kamis, 3 Okt = Sabtu, 4 Okt = Minggu, 5 Okt = Senin.
        $this->submitLeave($user, '2026-10-01', '2026-10-05')
            ->assertRedirect('/dashboard');

        // Sabtu & Minggu tidak berjadwal -> 5 hari kalender jadi 3 hari cuti.
        $this->assertSame(3, (int) $this->latestLeave($user)->total_days);
    }

    public function test_office_6_minggu_tidak_dihitung_sabtu_dihitung(): void
    {
        $user = $this->makeUser('office_6');

        // 2 Okt = Jumat, 3 Okt = Sabtu, 4 Okt = Minggu, 5 Okt = Senin.
        $this->submitLeave($user, '2026-10-02', '2026-10-05')
            ->assertRedirect('/dashboard');

        // Office_6 masuk Sabtu, libur Minggu -> 4 hari kalender jadi 3 hari cuti.
        $this->assertSame(3, (int) $this->latestLeave($user)->total_days);
    }

    public function test_shift_hanya_menghitung_tanggal_dengan_jadwal(): void
    {
        $user = $this->makeUser('shift');

        // Jadwal hanya tgl 1, 2, dan 5; tgl 3 & 4 sengaja tanpa employee_shifts.
        $this->assignShift($user, '2026-10-01');
        $this->assignShift($user, '2026-10-02');
        $this->assignShift($user, '2026-10-05');

        $this->submitLeave($user, '2026-10-01', '2026-10-05')
            ->assertRedirect('/dashboard');

        $this->assertSame(3, (int) $this->latestLeave($user)->total_days);
    }

    public function test_rentang_tanpa_jadwal_sama_sekali_ditolak(): void
    {
        $user = $this->makeUser('office_5');

        // Sabtu & Minggu saja -> office_5 tidak punya jadwal sama sekali.
        $this->submitLeave($user, '2026-10-03', '2026-10-04')
            ->assertSessionHasErrors('start_date');

        $this->assertStringContainsString(
            'Tidak ada jadwal kerja',
            session('errors')->first('start_date')
        );

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
    }

    public function test_kuota_dihitung_dari_hari_efektif(): void
    {
        // Kuota 3 hari; rentang kalender 5 hari (melebihi kuota) tapi hari
        // efektif hanya 3 -> lolos validasi dan tersimpan 3 hari.
        $user = $this->makeUser('office_5', 3);

        $this->submitLeave($user, '2026-10-01', '2026-10-05')
            ->assertRedirect('/dashboard');

        $this->assertSame(3, (int) $this->latestLeave($user)->total_days);
    }

    public function test_kuota_tetap_ditolak_bila_hari_efektif_lewat_kuota(): void
    {
        // Kuota 2 hari tapi hari efektif 3 (1, 2, 5 Okt) -> tetap ditolak.
        $user = $this->makeUser('office_5', 2);

        $this->submitLeave($user, '2026-10-01', '2026-10-05')
            ->assertSessionHasErrors(['start_date' => 'Sisa cuti tidak mencukupi']);

        $this->assertSame(0, Leave::where('user_id', $user->id)->count());
    }

    public function test_update_cuti_hitung_ulang_hari_efektif(): void
    {
        $user = $this->makeUser('office_5');

        $this->submitLeave($user, '2026-10-01', '2026-10-02')
            ->assertRedirect('/dashboard');

        $leave = $this->latestLeave($user);
        $this->assertSame(2, (int) $leave->total_days);

        // Perpanjang ke 5 Okt: Sabtu & Minggu tetap tidak dihitung.
        $this->actingAs($user)->put('/cuti/' . $leave->id, [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'leave_type' => 'cuti tahunan',
            'reason' => 'Keperluan keluarga',
        ])->assertRedirect();

        $this->assertSame(3, (int) $leave->fresh()->total_days);
    }

    /*
    |--------------------------------------------------------------------------
    | SKENARIO PENGGUNA: cuti 4-7, tgl 5-6 Sabtu & Minggu
    |--------------------------------------------------------------------------
    | Desember 2026: tgl 4 = Jumat, 5 = Sabtu, 6 = Minggu, 7 = Senin.
    */

    public function test_office_5_cuti_4_7_sabtu_minggu_tidak_dihitung(): void
    {
        $user = $this->makeUser('office_5');

        $this->submitLeave($user, '2026-12-04', '2026-12-07')
            ->assertRedirect('/dashboard');

        // Hanya tgl 4 (Jumat) & 7 (Senin); tgl 5-6 akhir pekan -> 2 hari.
        $this->assertSame(2, (int) $this->latestLeave($user)->total_days);
    }

    public function test_office_6_cuti_4_7_hanya_minggu_tidak_dihitung(): void
    {
        $user = $this->makeUser('office_6');

        $this->submitLeave($user, '2026-12-04', '2026-12-07')
            ->assertRedirect('/dashboard');

        // Office_6 masuk Sabtu: tgl 4, 5, 7 dihitung; tgl 6 (Minggu) tidak
        // -> 3 hari.
        $this->assertSame(3, (int) $this->latestLeave($user)->total_days);
    }

    public function test_shift_cuti_4_7_hanya_tanggal_dengan_jadwal(): void
    {
        $user = $this->makeUser('shift');

        // Jadwal shift hanya tgl 4 & 7; tgl 5 & 6 tanpa employee_shifts.
        $this->assignShift($user, '2026-12-04');
        $this->assignShift($user, '2026-12-07');

        $this->submitLeave($user, '2026-12-04', '2026-12-07')
            ->assertRedirect('/dashboard');

        // Shift mengikuti jadwal (bukan hari mingguan): 4 & 7 saja -> 2 hari.
        $this->assertSame(2, (int) $this->latestLeave($user)->total_days);
    }

    public function test_shift_jadwal_di_sabtu_tetap_dihitung(): void
    {
        $user = $this->makeUser('shift');

        // Ada jadwal di Sabtu tgl 5 (tetap dihitung), tanpa jadwal di
        // Minggu tgl 6 (tidak dihitung).
        $this->assignShift($user, '2026-12-04');
        $this->assignShift($user, '2026-12-05'); // Sabtu, ada jadwal
        $this->assignShift($user, '2026-12-07');

        $this->submitLeave($user, '2026-12-04', '2026-12-07')
            ->assertRedirect('/dashboard');

        // Tanggal berjadwal: 4, 5, 7 -> 3 hari (tgl 6 tanpa jadwal tidak).
        $this->assertSame(3, (int) $this->latestLeave($user)->total_days);
    }
}