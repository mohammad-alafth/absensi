<?php

namespace Tests\Feature;

use App\Exports\HRDAbsentExport;
use App\Models\Permission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Izin yang diajukan lebih dari 1 hari harus mengubah status SELURUH tanggal
 * yang diajukan (bukan hanya hari pertama).
 *
 * Sebelum perbaikan, semua rekap per tanggal hanya membaca kolom `tanggal`
 * sehingga hari ke-2 dst muncul sebagai "Alpa" pada rekap harian karyawan,
 * dihitung sebagai hari tidak hadir pada laporan HRD, dan ikut terdaftar pada
 * export absen. Izin yang mulai sebelum periode juga terbuang karena memakai
 * `whereBetween('tanggal', ...)`.
 */
class PermissionMultiDayIzinStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Jumat, 25 September 2026 -> 21..24 September 2026 sudah berlalu.
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Karyawan Izin Rentang ' . uniqid(),
            'email' => 'izin-rentang-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'pj_ipsrs',
            'work_type' => 'office_6',
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function actingAsHrd()
    {
        return $this->actingAs($this->makeUser([
            'role' => 'hrd',
            'name' => 'HRD Izin Rentang ' . uniqid(),
        ]));
    }

    private function approvedPermission(User $user, string $tanggal, ?string $tanggalSelesai = null): Permission
    {
        return Permission::create([
            'user_id'         => $user->id,
            'tanggal'         => $tanggal,
            'tanggal_selesai' => $tanggalSelesai,
            'jenis'           => 'izin pribadi',
            'alasan'          => 'Urusan pribadi',
            'status'          => 'approved',
        ]);
    }

    /**
     * Baris rekap harian karyawan (key = tanggal 'Y-m-d').
     */
    private function rowsRekapHarian(User $user, string $bulan = '2026-09'): array
    {
        $response = $this->actingAs($user)
            ->get(route('history.rekap', ['bulan' => $bulan]))
            ->assertOk();

        return collect($response->viewData('rows'))->keyBy('date_str')->all();
    }

    public function test_izin_dua_hari_menandai_izin_untuk_kedua_tanggal(): void
    {
        $user = $this->makeUser();
        $this->approvedPermission($user, '2026-09-21', '2026-09-22');

        $rows = $this->rowsRekapHarian($user);

        $this->assertSame('izin', $rows['2026-09-21']['status']);
        $this->assertSame('izin', $rows['2026-09-22']['status']);
        $this->assertSame('Izin', $rows['2026-09-22']['status_label']);
        $this->assertNotSame('alpa', $rows['2026-09-22']['status']);
    }

    public function test_izin_tiga_hari_menandai_izin_untuk_semua_tanggal(): void
    {
        $user = $this->makeUser();
        $this->approvedPermission($user, '2026-09-21', '2026-09-23');

        $rows = $this->rowsRekapHarian($user);

        foreach (['2026-09-21', '2026-09-22', '2026-09-23'] as $tanggal) {
            $this->assertSame('izin', $rows[$tanggal]['status'], "Status {$tanggal} harus izin");
        }
    }

    public function test_izin_yang_mulai_sebelum_periode_tetap_menandai_hari_di_dalam_periode(): void
    {
        $user = $this->makeUser();
        $this->approvedPermission($user, '2026-08-31', '2026-09-02');

        $rows = $this->rowsRekapHarian($user);

        $this->assertSame('izin', $rows['2026-09-01']['status']);
        $this->assertSame('izin', $rows['2026-09-02']['status']);
    }

    public function test_izin_satu_hari_tanpa_tanggal_selesai_tetap_izin(): void
    {
        $user = $this->makeUser();
        $this->approvedPermission($user, '2026-09-24');

        $rows = $this->rowsRekapHarian($user);

        $this->assertSame('izin', $rows['2026-09-24']['status']);
    }

    public function test_laporan_pegawai_tidak_hadir_tidak_menghitung_izin_multi_hari(): void
    {
        $employee = $this->makeUser();
        $this->approvedPermission($employee, '2026-09-21', '2026-09-23');

        $response = $this->actingAsHrd()
            ->get(route('hrd.reports.absent.daily', [
                'start_date' => '2026-09-21',
                'end_date'   => '2026-09-23',
            ]))
            ->assertOk();

        $data = $response->viewData('data');

        $this->assertNull(
            $data->firstWhere('id', $employee->id),
            'Karyawan dengan izin 21-23 September tidak boleh dihitung tidak hadir.'
        );
    }

    public function test_export_absen_tidak_menghitung_izin_multi_hari_sebagai_tidak_hadir(): void
    {
        $employee = $this->makeUser();
        $this->approvedPermission($employee, '2026-09-21', '2026-09-23');

        $rows = (new HRDAbsentExport('2026-09-21', '2026-09-23'))->collection();

        $this->assertTrue(
            $rows->filter(fn($row) => $row[1] === $employee->name)->isEmpty(),
            'Karyawan dengan izin 21-23 September tidak boleh ada di export pegawai tidak hadir.'
        );
    }
}
