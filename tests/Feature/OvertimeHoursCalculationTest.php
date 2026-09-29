<?php

namespace Tests\Feature;

use App\Models\Overtime;
use App\Models\User;
use App\Support\SubmissionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Perhitungan volume jam lembur: setiap 60 menit penuh dihitung 1 jam dan
 * sisa menit di bawah 60 tidak dihitung (60 menit -> 1 jam, 110 menit ->
 * 1 jam, 120 menit -> 2 jam). Pengajuan di bawah 60 menit ditolak agar
 * surat perintah lembur tidak pernah bernilai 0 jam.
 */
class OvertimeHoursCalculationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        SubmissionStatus::flushApproverNames();
    }

    /* --------------------------------------------------------------------- */
    /* HELPER                                                                */
    /* --------------------------------------------------------------------- */

    private function makeUser(): User
    {
        return User::create([
            'name' => 'User lembur ' . uniqid(),
            'email' => 'lembur-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'admission',
            'work_type' => 'office_5',
            'email_verified_at' => now(),
            'is_approved' => 1,
            'leave_quota' => 12,
        ]);
    }

    private function payload(string $start, string $end, string $date = '2026-10-01'): array
    {
        return [
            'overtime_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'reason' => 'Penyelesaian pekerjaan',
            'day_type' => 'hari_kerja',
            'employee_signature' => 'data:image/png;base64,AAA',
        ];
    }

    private function submit(User $user, string $start, string $end, string $date = '2026-10-01'): Overtime
    {
        $this->actingAs($user)
            ->post(route('lembur.store'), $this->payload($start, $end, $date))
            ->assertRedirect('/dashboard');

        return Overtime::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    /* --------------------------------------------------------------------- */
    /* STORE                                                                 */
    /* --------------------------------------------------------------------- */

    public function test_setiap_60_menit_penuh_dihitung_satu_jam(): void
    {
        $user = $this->makeUser();

        // 60 menit tepat -> 1 jam
        $overtime = $this->submit($user, '17:00', '18:00', '2026-10-01');
        $this->assertSame(1, (int) $overtime->total_hours);

        // 110 menit -> sisa 50 menit dibuang, tetap 1 jam
        $overtime = $this->submit($user, '17:00', '18:50', '2026-10-02');
        $this->assertSame(1, (int) $overtime->total_hours);

        // 120 menit -> 2 jam
        $overtime = $this->submit($user, '17:00', '19:00', '2026-10-03');
        $this->assertSame(2, (int) $overtime->total_hours);

        // 179 menit -> sisa 59 menit dibuang, tetap 2 jam
        $overtime = $this->submit($user, '17:00', '19:59', '2026-10-04');
        $this->assertSame(2, (int) $overtime->total_hours);
    }

    public function test_lembur_melewati_tengah_malam_dihitung_dengan_aturan_yang_sama(): void
    {
        $user = $this->makeUser();

        // 23:00 -> 01:00 (lewat tengah malam) = 120 menit -> 2 jam
        $overtime = $this->submit($user, '23:00', '01:00', '2026-10-05');

        $this->assertSame(2, (int) $overtime->total_hours);
    }

    public function test_pengajuan_di_bawah_60_menit_ditolak(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->from(route('lembur'))
            ->post(route('lembur.store'), $this->payload('17:00', '17:45'))
            ->assertRedirect(route('lembur'))
            ->assertSessionHasErrors('end_time');

        $this->assertStringContainsString(
            'Durasi lembur minimal 60 menit',
            session('errors')->first('end_time')
        );

        $this->assertSame(0, Overtime::where('user_id', $user->id)->count());
    }

    /* --------------------------------------------------------------------- */
    /* UPDATE / REVISI                                                       */
    /* --------------------------------------------------------------------- */

    public function test_revisi_menggunakan_aturan_pembulatan_yang_sama(): void
    {
        $user = $this->makeUser();

        $overtime = $this->submit($user, '17:00', '19:00');
        $this->assertSame(2, (int) $overtime->total_hours);

        // Revisi menjadi 110 menit -> 1 jam
        $this->actingAs($user)
            ->from(route('lembur'))
            ->put(route('lembur.update', $overtime->id), $this->payload('17:00', '18:50', '2026-10-06'))
            ->assertRedirect(route('lembur'))
            ->assertSessionHas('success');

        $overtime->refresh();
        $this->assertSame(1, (int) $overtime->total_hours);

        // Revisi di bawah 60 menit -> ditolak, jam lembur tidak berubah
        $this->actingAs($user)
            ->from(route('lembur'))
            ->put(route('lembur.update', $overtime->id), $this->payload('17:00', '17:45', '2026-10-06'))
            ->assertRedirect(route('lembur'))
            ->assertSessionHasErrors('end_time');

        $overtime->refresh();
        $this->assertSame(1, (int) $overtime->total_hours);
    }
}
