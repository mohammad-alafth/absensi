<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Filter periode rekap HRD: tanggal mulai - tanggal selesai (rentang bebas).
 * Parameter lama `month` tetap diterima demi kompatibilitas link/bookmark.
 */
class HRDRekapDateRangeFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Karyawan Rentang Test ' . uniqid(),
            'email' => 'range-' . uniqid() . '@example.test',
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
            'name' => 'HRD Rentang Test ' . uniqid(),
        ]));
    }

    private function hadir(string $tanggal, ?User $user = null): void
    {
        Attendance::create([
            'user_id' => $user->id,
            'tanggal' => $tanggal,
            'jam_masuk' => '08:00:00',
            'status' => 'hadir',
        ]);
    }

    public function test_filter_rentang_menghitung_presensi_dalam_periode(): void
    {
        $user = $this->makeUser();
        $this->hadir('2026-09-10', $user);

        $this->actingAsHrd()
            ->get(route('hrd.rekap', ['start_date' => '2026-09-01', 'end_date' => '2026-09-30']))
            ->assertOk()
            ->assertSee('1 Presensi');
    }

    public function test_presensi_di_luar_rentang_tidak_ikut_dihitung(): void
    {
        $user = $this->makeUser();
        $this->hadir('2026-08-10', $user); // Agustus, di luar rentang September

        $this->actingAsHrd()
            ->get(route('hrd.rekap', ['start_date' => '2026-09-01', 'end_date' => '2026-09-30']))
            ->assertOk()
            ->assertSee('0 Presensi');
    }

    public function test_urutan_tanggal_yang_terbalik_ditukar_otomatis(): void
    {
        $user = $this->makeUser();
        $this->hadir('2026-09-10', $user);

        $this->actingAsHrd()
            ->get(route('hrd.rekap', ['start_date' => '2026-09-30', 'end_date' => '2026-09-01']))
            ->assertOk()
            ->assertSee('1 Presensi');
    }

    public function test_parameter_bulan_lama_masih_diterima(): void
    {
        $user = $this->makeUser();
        $this->hadir('2026-09-10', $user);

        $this->actingAsHrd()
            ->get(route('hrd.rekap', ['month' => '2026-09']))
            ->assertOk()
            ->assertSee('1 Presensi');
    }

    public function test_export_mengikuti_rentang_dan_menampilkan_periode_di_header_excel(): void
    {
        $user = $this->makeUser();
        $this->hadir('2026-09-10', $user);

        $response = $this->actingAsHrd()->get(route('hrd.export.excel', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'role' => 'all',
        ]));

        $response->assertOk();
        $response->assertDownload('rekap-all-20260901-20260930.xlsx');

        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());

        // Export multi-sheet: cari sheet unit/role tempat pegawai berada
        $sheet = null;
        foreach ($spreadsheet->getAllSheets() as $candidate) {
            for ($row = 7; $row <= $candidate->getHighestRow(); $row++) {
                if ($candidate->getCell('A' . $row)->getValue() === $user->name) {
                    $sheet = $candidate;
                    break 2;
                }
            }
        }

        $this->assertNotNull($sheet, "Sheet berisi pegawai {$user->name} tidak ditemukan.");

        $this->assertSame(
            'Periode: 01/09/2026 s/d 30/09/2026',
            $sheet->getCell('B5')->getValue()
        );

        // Presensi dalam rentang harus masuk rekap export
        // (kolom E = total waktu terlambat)
        $this->assertSame('00:00', $sheet->getCell('E7')->getValue());

        $spreadsheet->disconnectWorksheets();
    }
}