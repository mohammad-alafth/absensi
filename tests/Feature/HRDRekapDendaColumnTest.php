<?php

namespace Tests\Feature;

use App\Exports\HRDRekapExport;
use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Menguji kolom "DENDA KETERLAMBATAN" (kolom G) pada export rekap HRD.
 *
 * Ketentuan: akumulasi keterlambatan dalam 1 bulan yang MELEBIHI toleransi
 * 30 menit didenda Rp10.000 untuk setiap kelipatan 5 menit (kelebihan
 * dibulatkan ke atas):
 *
 *   30 mnt -> Rp0        40 mnt -> Rp20.000
 *   35 mnt -> Rp10.000   45 mnt -> Rp30.000
 *   50 mnt -> Rp40.000   dst.
 *
 * Nilai kolom G berupa FORMULA Excel (bukan angka statis) yang membaca
 * kolom E ("TOTAL WAKTU TERLAMBAT", teks HH:MM) pada baris yang sama.
 */
class HRDRekapDendaColumnTest extends TestCase
{
    use DatabaseTransactions;

    private const MONTH = '2026-09';
    private const DATE = '2026-09-10';
    private const FILE = 'hrd-rekap-denda-test.xlsx';

    /**
     * Kasus sesuai ketentuan: isi kolom E ("HH:MM") => denda yang benar.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function dendaCases(): array
    {
        return [
            'tanpa keterlambatan' => ['00:00', 0],
            'tepat toleransi 30 menit' => ['00:30', 0],
            'kelebihan 4 menit dibulatkan ke atas' => ['00:34', 10000],
            'contoh 35 menit' => ['00:35', 10000],
            'contoh 40 menit' => ['00:40', 20000],
            'contoh 45 menit' => ['00:45', 30000],
            'contoh 50 menit' => ['00:50', 40000],
            '80 menit' => ['01:20', 100000],
            '155 menit' => ['02:35', 250000],
            'akumulasi lebih dari 24 jam' => ['120:30', 14400000],
        ];
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->delete(self::FILE);

        parent::tearDown();
    }

    /**
     * Membuat karyawan (non-admin) agar file rekap memiliki baris data.
     */
    private function seedEmployees(int $count, ?string $jamMasuk = null): User
    {
        $user = null;

        for ($i = 0; $i < $count; $i++) {
            $user = User::create([
                'name' => 'Karyawan Denda Test ' . uniqid(),
                'email' => 'denda-' . uniqid() . '@example.test',
                'password' => 'password',
                'role' => 'pj_ipsrs',
                'work_type' => 'office_6',
                'email_verified_at' => now(),
            ]);
        }

        if ($jamMasuk !== null && $user !== null) {
            $shift = Shift::create([
                'name' => 'Shift Denda Test ' . uniqid(),
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
            ]);

            EmployeeShift::create([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'shift_date' => self::DATE,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
            ]);

            Attendance::create([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'tanggal' => self::DATE,
                'jam_masuk' => $jamMasuk,
                'jam_keluar' => '17:00:00',
                'status' => 'terlambat',
            ]);
        }

        return $user;
    }

    private function storeExport(): Spreadsheet
    {
        Excel::store(new HRDRekapExport(self::MONTH, 'all'), self::FILE, 'local');

        return IOFactory::load(Storage::disk('local')->path(self::FILE));
    }

    private function findRowByName(Spreadsheet $spreadsheet, string $name): int
    {
        $sheet = $spreadsheet->getActiveSheet();

        for ($row = 7; $row <= $sheet->getHighestRow(); $row++) {
            if ($sheet->getCell('A' . $row)->getValue() === $name) {
                return $row;
            }
        }

        $this->fail("Baris rekap untuk '{$name}' tidak ditemukan pada file export.");
    }

    public function test_kolom_denda_ada_setelah_total_jam_kerja(): void
    {
        $this->seedEmployees(1);

        $spreadsheet = $this->storeExport();
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('TOTAL JAM KERJA', $sheet->getCell('F6')->getValue());
        $this->assertSame('DENDA KETERLAMBATAN', $sheet->getCell('G6')->getValue());

        $spreadsheet->disconnectWorksheets();
    }

    public function test_kolom_denda_berisi_formula_per_baris_bukan_angka_statis(): void
    {
        $this->seedEmployees(2);

        $spreadsheet = $this->storeExport();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ([7, 8] as $row) {
            $formula = $sheet->getCell('G' . $row)->getValue();

            $this->assertIsString($formula, "Sel G{$row} harus berisi formula.");
            $this->assertStringStartsWith('=', $formula);
            $this->assertStringContainsString('$E' . $row, $formula, "Formula G{$row} harus membaca kolom E baris {$row}.");
            $this->assertStringContainsString('CEILING', $formula);
            $this->assertStringContainsString('10000', $formula);
            $this->assertStringContainsString('30', $formula);
        }

        // Tampilan Rupiah (nilai tetap numerik agar bisa dijumlahkan)
        $this->assertSame(
            '"Rp"#,##0',
            $sheet->getStyle('G7')->getNumberFormat()->getFormatCode()
        );

        $spreadsheet->disconnectWorksheets();
    }

    public function test_denda_dihitung_dari_akumulasi_keterlambatan_karyawan(): void
    {
        // Masuk 09:37 => selisih 97 menit, setelah toleransi harian 15 menit
        // menjadi 82 menit akumulasi keterlambatan bulan ini.
        $user = $this->seedEmployees(1, '09:37:00');

        $spreadsheet = $this->storeExport();
        $sheet = $spreadsheet->getActiveSheet();
        $row = $this->findRowByName($spreadsheet, $user->name);

        $this->assertSame(
            '01:22',
            $sheet->getCell('E' . $row)->getValue(),
            'Akumulasi keterlambatan harus 1 jam 22 menit.'
        );

        // 82 menit - 30 menit toleransi = 52 menit => 11 kelipatan 5 menit => Rp110.000
        $this->assertSame(110000, (int) $sheet->getCell('G' . $row)->getCalculatedValue());

        $spreadsheet->disconnectWorksheets();
    }

    #[DataProvider('dendaCases')]
    public function test_formula_denda_sesuai_ketentuan(string $lateText, int $expected): void
    {
        $this->seedEmployees(1);

        $spreadsheet = $this->storeExport();
        $sheet = $spreadsheet->getActiveSheet();

        // Isi kolom E (akumulasi keterlambatan) lalu hitung dengan engine Excel.
        $sheet->setCellValue('E7', $lateText);

        $this->assertSame(
            $expected,
            (int) $sheet->getCell('G7')->getCalculatedValue(),
            "Denda untuk akumulasi {$lateText} tidak sesuai ketentuan."
        );

        $spreadsheet->disconnectWorksheets();
    }

    public function test_hrd_dapat_mengunduh_rekap_yang_memuat_kolom_denda(): void
    {
        $this->seedEmployees(1);

        $hrd = User::create([
            'name' => 'HRD Denda Test ' . uniqid(),
            'email' => 'hrd-denda-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'hrd',
            'work_type' => 'office_6',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($hrd)->get(route('hrd.export.excel', [
            'month' => self::MONTH,
            'role' => 'all',
        ]));

        $response->assertOk();
        $response->assertDownload('rekap-all-' . self::MONTH . '.xlsx');

        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('TOTAL JAM KERJA', $sheet->getCell('F6')->getValue());
        $this->assertSame('DENDA KETERLAMBATAN', $sheet->getCell('G6')->getValue());
        $this->assertStringStartsWith('=', (string) $sheet->getCell('G7')->getValue());

        $spreadsheet->disconnectWorksheets();
    }
}