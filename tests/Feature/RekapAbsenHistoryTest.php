<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RekapAbsenHistoryTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Karyawan Rekap Test',
            'email' => 'rekap-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'pj_ipsrs',
            'work_type' => 'office_6',
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_history_page_menampilkan_tombol_rekap_absen(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('history'))
            ->assertOk()
            ->assertSee('Rekap Absen')
            ->assertSee(route('history.rekap'));
    }

    public function test_rekap_bulanan_menampilkan_absen_user_yang_login(): void
    {
        $user  = $this->makeUser();
        $today = Carbon::today();

        Attendance::create([
            'user_id'   => $user->id,
            'tanggal'   => $today->format('Y-m-d'),
            'jam_masuk' => '08:30:00',
            'status'    => 'hadir',
        ]);

        $this->actingAs($user)
            ->get(route('history.rekap', ['bulan' => $today->format('Y-m')]))
            ->assertOk()
            ->assertSee('Rekap Absensi Saya')
            ->assertSee('08:30')
            ->assertSee('Hadir')
            ->assertSee('Hari Ini');
    }

    public function test_filter_tanggal_hari_ini_menampilkan_satu_tanggal(): void
    {
        $user  = $this->makeUser();
        $today = Carbon::today();

        Attendance::create([
            'user_id'   => $user->id,
            'tanggal'   => $today->format('Y-m-d'),
            'jam_masuk' => '08:30:00',
            'status'    => 'hadir',
        ]);

        $this->actingAs($user)
            ->get(route('history.rekap', [
                'bulan'   => $today->format('Y-m'),
                'mode'    => 'tanggal',
                'tanggal' => $today->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Menampilkan rekap tanggal')
            ->assertSee($today->format('d-m-Y'))
            ->assertSee('08:30');
    }

    public function test_filter_tanggal_sebelumnya_tidak_menampilkan_absen_user_lain(): void
    {
        $user      = $this->makeUser();
        $otherUser = $this->makeUser(['name' => 'User Lain Rekap Test', 'email' => 'rekap-other-' . uniqid() . '@example.test']);
        $today     = Carbon::today();

        // User lain absen hari ini dengan jam unik
        Attendance::create([
            'user_id'   => $otherUser->id,
            'tanggal'   => $today->format('Y-m-d'),
            'jam_masuk' => '13:13:13',
            'status'    => 'terlambat',
        ]);

        $response = $this->actingAs($user)
            ->get(route('history.rekap', [
                'bulan'   => $today->format('Y-m'),
                'mode'    => 'tanggal',
                'tanggal' => $today->format('Y-m-d'),
            ]));

        $response->assertOk();
        $response->assertDontSee('13:13');
        $response->assertDontSee('User Lain Rekap Test');
    }
}
