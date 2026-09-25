<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Permission;
use App\Support\PermissionRange;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Satu lembar (sheet) rekap HRD untuk SATU unit/role.
 *
 * Dipakai oleh HRDRekapExport (WithMultipleSheets) yang membuat
 * satu sheet baru untuk setiap unit/role pada file Excel.
 *
 * Kolom:
 *   A NAMA PEGAWAI | B UNIT/ROLE | C TOTAL HADIR | D TOTAL TELAT
 *   E TOTAL WAKTU TERLAMBAT | F TOTAL JAM KERJA | G DENDA KETERLAMBATAN (formula)
 *   H TANGGAL IZIN | I JAM IZIN | J TANGGAL CUTI
 *   K TANGGAL LEMBUR | L JAM LEMBUR (per tanggal, format surat lembur: "X Jam")
 *   M RINCIAN TELAT PER HARI (menit aktual per tanggal, cth: "10/09: 7 mnt; 12/09: 5 mnt")
 *
 * Baris terakhir tiap sheet adalah BARIS TOTAL (label "TOTAL" di kolom A):
 *   - kolom E = rumus =SUM(E7:E..) (total menit telat dalam format HH:MM via [h]:mm)
 *   - kolom G = rumus =SUM(G7:G..) (total denda, format "Rp"#,##0)
 */
class HRDRekapSheet implements FromCollection, WithEvents, WithHeadings, WithColumnWidths, WithTitle
{
    protected $month, $role, $startDate, $endDate, $title;

    public function __construct($month = null, $role = 'all', $startDate = null, $endDate = null, $title = null)
    {
        $this->month = $month ?: now()->format('Y-m');
        $this->role = $role;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->title = $title;
    }

    /**
     * Label unit/role yang tampil di Excel dan dijadikan nama sheet.
     */
    public static function roleLabels(): array
    {
        return [
            'nurse' => 'PERAWAT',
            'nurse_ok' => 'PERAWAT OK',

            'pj_nurse' => 'PENANGGUNG JAWAB PERAWAT',
            'pj_nurse_ok' => 'PENANGGUNG JAWAB PERAWAT OK',

            'security' => 'SECURITY',
            'pj_security' => 'PENANGGUNG JAWAB SECURITY',

            'administrasi' => 'ADMINISTRASI',
            'pj_administrasi' => 'PENANGGUNG JAWAB ADMINISTRASI',

            'finance' => 'KEUANGAN',
            'pj_finance' => 'PENANGGUNG JAWAB KEUANGAN',

            'pharmacist' => 'APOTEKER',
            'pj_pharmacist' => 'PENANGGUNG JAWAB APOTEKER',

            'ro' => 'REFRAKSIONIS OPTISIEN',
            'pj_ro' => 'PENANGGUNG JAWAB REFRAKSIONIS OPTISIEN',

            'casemix' => 'CASEMIX',
            'pj_casemix' => 'PENANGGUNG JAWAB CASEMIX',

            'ipsrs' => 'IPSRS',
            'pj_ipsrs' => 'PENANGGUNG JAWAB IPSRS',

            'marketing' => 'MARKETING',
            'pj_marketing' => 'PENANGGUNG JAWAB MARKETING',

            'cs' => 'CLEANING SERVICE',
            'pj_cs' => 'PENANGGUNG JAWAB CLEANING SERVICE',

            'it' => 'IT',
            'hrd' => 'HRD',
            'medical_service' => 'MEDICAL SERVICE',
            'pipp' => 'PIPP',
            'director' => 'DIREKTUR',
            'head_pegawai' => 'KEPALA BAGIAN UMUM DAN KEPEGAWAIAN',
            'nutrition' => 'GIZI',
            'medical_record' => 'REKAM MEDIS',
            'creator' => 'KONTEN CREATOR',
        ];
    }

    public static function normalizeRole($role)
    {
        $role = strtolower($role);

        $role = str_replace('pj_', '', $role);
        $role = str_replace('_ok', '', $role);

        return $role;
    }

    /**
     * Nama sheet untuk sebuah unit/role.
     * Excel membatasi 31 karakter dan melarang karakter \ / ? * [ ] :
     */
    public static function sheetTitleFor($role): string
    {
        $labels = self::roleLabels();

        $name = $labels[$role]
            ?? $labels[self::normalizeRole($role)]
            ?? strtoupper(str_replace('_', ' ', $role));

        $name = strtoupper($name);
        $name = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name);

        return mb_substr(trim($name), 0, 31);
    }

    public function title(): string
    {
        return $this->title ?: self::sheetTitleFor($this->role);
    }

    /**
     * Menentukan periode rekap: rentang tanggal eksplisit bila diberikan,
     * selain itu satu bulan penuh dari parameter $month.
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    private function resolvePeriod(): array
    {
        return [
            $this->startDate
                ? Carbon::parse($this->startDate)->startOfDay()
                : Carbon::parse($this->month . '-01')->startOfMonth(),
            $this->endDate
                ? Carbon::parse($this->endDate)->endOfDay()
                : Carbon::parse($this->month . '-01')->endOfMonth(),
        ];
    }

    public function headings(): array
    {
        return [
            'NAMA PEGAWAI',
            'UNIT / ROLE',
            'TOTAL HADIR',
            'TOTAL TELAT',
            'TOTAL WAKTU TERLAMBAT',
            'TOTAL JAM KERJA',
            'DENDA KETERLAMBATAN',
            'TANGGAL IZIN',
            'JAM IZIN',
            'TANGGAL CUTI',
            'TANGGAL LEMBUR',
            'JAM LEMBUR',
            'RINCIAN TELAT PER HARI (MENIT)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 40,
            'C' => 15,
            'D' => 15,
            'E' => 30,
            'F' => 20,
            'G' => 28,
            'H' => 35,
            'I' => 18,
            'J' => 35,
            'K' => 30,
            'L' => 40,
            'M' => 46,
        ];
    }

    public function collection()
    {
        [$startDate, $endDate] = $this->resolvePeriod();

        $employees = User::whereNotIn('role', ['admin']);

        if ($this->role != 'all') {

            $employees = $employees->get()->filter(function ($user) {

                return self::normalizeRole($user->role)
                    == self::normalizeRole($this->role);
            });
        } else {

            $employees = $employees->get();
        }

        $roleLabels = self::roleLabels();

        $rows = $employees->map(function (
            $employee
        ) use (
            $startDate,
            $endDate,
            $roleLabels
        ) {

            $attendances = Attendance::where(
                'user_id',
                $employee->id
            )
                ->whereBetween(
                    'tanggal',
                    [$startDate, $endDate]
                )
                ->get();

            $hadir = 0;
            $telat = 0;

            $lateMinutes = 0;
            $totalWorkMinutes = 0;
            $lateDetails = [];

            foreach ($attendances as $attendance) {

                if ($attendance->jam_masuk) {
                    $hadir++;
                }

                $shift = EmployeeShift::where(
                    'user_id',
                    $employee->id
                )
                    ->whereDate(
                        'shift_date',
                        $attendance->tanggal
                    )
                    ->first();

                if (
                    $shift &&
                    $attendance->jam_masuk
                ) {

                    try {

                        $jadwalMasuk = Carbon::parse(
                            $shift->start_time
                        );

                        $jamMasuk = Carbon::parse(
                            $attendance->jam_masuk
                        );

                        $selisih = $jadwalMasuk
                            ->diffInMinutes(
                                $jamMasuk,
                                false
                            );

                        if ($selisih > 15) {

                            $telat++;

                            $lateMinutes += (
                                $selisih - 15
                            );

                            // Rincian per hari: menit aktual (tanpa pembulatan 5 menit).
                            // Pembulatan kelipatan 5 menit hanya dipakai rumus denda.
                            $lateDetails[] = Carbon::parse($attendance->tanggal)->format('d/m')
                                . ': ' . ($selisih - 15) . ' mnt';
                        }
                    } catch (\Exception $e) {
                    }
                }

                if (
                    $attendance->jam_masuk &&
                    $attendance->jam_keluar
                ) {

                    $masuk = Carbon::parse(
                        $attendance->jam_masuk
                    );

                    $keluar = Carbon::parse(
                        $attendance->jam_keluar
                    );

                    $totalWorkMinutes +=
                        $masuk->diffInMinutes(
                            $keluar
                        );
                }
            }

            /*
            |----------------------------------------------------------------------
            | TANGGAL IZIN (izin approved dalam rentang periode)
            |----------------------------------------------------------------------
            */
            // Izin multi-hari diambil lewat rentang (tanggal .. tanggal_selesai),
            // lalu tiap hari dalam rentang ditulis sebagai tanggal izin.
            $izinList = PermissionRange::applyOverlapsPeriod(
                Permission::where('user_id', $employee->id)
                    ->where('status', 'approved'),
                $startDate,
                $endDate
            )
                ->orderBy('tanggal')
                ->get();

            $izinDates = PermissionRange::datesWithin($izinList, $startDate, $endDate)
                ->map(fn($date) => Carbon::parse($date)->format('d/m'))
                ->sort()
                ->unique()
                ->values()
                ->implode(', ');

            // Jam izin: selisih jam_mulai - jam_selesai (bila 1 hari & ada jamnya),
            // dijumlahkan akumulasi; bila 0 tampil '-'.
            $izinMinutes = 0;

            foreach ($izinList as $izin) {
                if (!$izin->jam_mulai || !$izin->jam_selesai) {
                    continue;
                }

                try {
                    $mulai = Carbon::parse($izin->jam_mulai);
                    $selesai = Carbon::parse($izin->jam_selesai);

                    if ($selesai->lt($mulai)) {
                        $selesai->addDay();
                    }

                    $izinMinutes += $mulai->diffInMinutes($selesai);
                } catch (\Exception $e) {
                }
            }

            $jamIzinText = $izinMinutes > 0
                ? sprintf('%02d:%02d', floor($izinMinutes / 60), $izinMinutes % 60)
                : '-';

            /*
            |----------------------------------------------------------------------
            | TANGGAL CUTI (cuti approved yang beririsan dengan periode)
            |----------------------------------------------------------------------
            */
            $leaves = Leave::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $endDate->format('Y-m-d'))
                ->where('end_date', '>=', $startDate->format('Y-m-d'))
                ->get();

            $cutiDates = collect();

            foreach ($leaves as $leave) {
                $cursor = Carbon::parse($leave->start_date)
                    ->max($startDate->copy()->startOfDay());
                $limit = Carbon::parse($leave->end_date)
                    ->min($endDate->copy()->startOfDay());

                while ($cursor->lte($limit)) {
                    $cutiDates->push($cursor->format('d/m'));
                    $cursor->addDay();
                }
            }

            $cutiDates = $cutiDates->sort()->unique()->values()->implode(', ');

            /*
            |----------------------------------------------------------------------
            | JAM LEMBUR (sesuai surat lembur tiap user)
            |----------------------------------------------------------------------
            | Akumulasi total_hours dari surat lembur berstatus approved,
            | ditampilkan dengan format yang sama seperti "Total Durasi
            | Akumulasi Jam" pada surat lembur: "X Jam".
            */
            $overtimeJam = Overtime::where('user_id', $employee->id)
                ->where('status', 'approved')
                ->whereBetween('overtime_date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])
                ->orderBy('overtime_date')
                ->get();

            $overtimeDates = $overtimeJam
                ->map(fn($ot) => Carbon::parse($ot->overtime_date)->format('d/m'))
                ->sort()
                ->unique()
                ->values()
                ->implode(', ');

            $overtimeTotalJam = $overtimeJam->sum(fn($ot) => intval($ot->total_hours));

            // Rincian jam lembur per tanggal sesuai surat lembur: "10/09: 3 Jam; 12/09: 2 Jam".
            $overtimeDetails = $overtimeJam
                ->map(fn($ot) => Carbon::parse($ot->overtime_date)->format('d/m') . ': ' . intval($ot->total_hours) . ' Jam')
                ->implode('; ');

            return [

                $employee->name,

                $roleLabels[$employee->role]
                    ?? strtoupper(
                        str_replace(
                            '_',
                            ' ',
                            $employee->role
                        )
                    ),

                $hadir,

                $telat,

                sprintf(
                    '%02d:%02d',
                    floor($lateMinutes / 60),
                    $lateMinutes % 60
                ),

                sprintf(
                    '%02d:%02d',
                    floor($totalWorkMinutes / 60),
                    $totalWorkMinutes % 60
                ),

                // Placeholder kolom formula denda.
                // Formula-nya diisi pada event AfterSheet() setelah baris
                // header di-insert, agar referensi baris (mis. $E7) selalu benar.
                null,

                $izinDates,

                $jamIzinText,

                $cutiDates,

                $overtimeDates,

                $overtimeTotalJam . ' Jam',

                implode('; ', $lateDetails),
            ];
        })->values();

        // Susun ulang sebagai collection bernomor agar baris TOTAL bisa di-append
        // sebagai baris data terakhir (indeks numerik).
        $rows = $rows->values();

        // Placeholder baris TOTAL: rumus E & G diisi di AfterSheet agar
        // referensi baris selalu benar setelah 5 baris header di-insert.
        $totalRow = [
            'TOTAL',
            '',
            '',
            '',
            null, // E: rumus =SUM(E7:E..) diisi di AfterSheet (total jam telat)
            '',
            null, // G: rumus =SUM(G7:G..) diisi di AfterSheet (total denda)
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        // Total jam lembur semua user (format surat lembur: "X Jam") untuk baris TOTAL kolom L.
        $totalLemburJam = 0;
        foreach ($rows as $r) {
            if (preg_match('/(\d+)\s*Jam/', (string) ($r[11] ?? ''), $m)) {
                $totalLemburJam += (int) $m[1];
            }
        }
        $totalRow[11] = $totalLemburJam . ' Jam';

        return $rows->push($totalRow)->values();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Geser data ke bawah untuk memberi ruang header (Baris 1-5)
                $sheet->insertNewRowBefore(1, 5);

                // Masukkan Teks Header (Baris 1-4)
                $sheet->setCellValue('B1', 'RS MATA Pekanbaru Eye Center');
                $sheet->setCellValue('B2', 'Jl. Soekarno Hatta No. 236 – Pekanbaru – Riau');
                $sheet->setCellValue('B3', 'Telp. (0761) 7875191, 0811 7605191 Fax. (0761) 7875195');
                $sheet->setCellValue('B4', 'www.pekanbarueyecenter.com');

                // Keterangan periode rekap (rentang tanggal yang dipilih)
                [$periodStart, $periodEnd] = $this->resolvePeriod();
                $sheet->setCellValue(
                    'B5',
                    'Periode: ' . $periodStart->format('d/m/Y') . ' s/d ' . $periodEnd->format('d/m/Y')
                );

                $sheet->mergeCells('B1:I1');
                $sheet->mergeCells('B2:I2');
                $sheet->mergeCells('B3:I3');
                $sheet->mergeCells('B4:I4');
                $sheet->mergeCells('B5:I5');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('5a6ea8');
                $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('5a6ea8');
                $sheet->getStyle('B1:I5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Tambah Logo
                $this->addLogo($sheet, 'A1', public_path('images/rsprofile.png'));
                $this->addLogo($sheet, 'J1', public_path('images/Picture1.png'));

                // Styling Heading Tabel (Baris 6)
                $sheet->getStyle('A6:J6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(35);

                $lastRow = $sheet->getHighestRow();

                /*
                |--------------------------------------------------------------------------
                | KOLOM G : FORMULA DENDA KETERLAMBATAN
                |--------------------------------------------------------------------------
                | Ketentuan: bila akumulasi keterlambatan dalam 1 bulan melebihi
                | toleransi 30 menit, kelebihannya didenda Rp10.000 untuk SETIAP
                | kelipatan 5 menit (dibulatkan ke atas ke kelipatan terdekat).
                |
                |   30 mnt -> Rp0       |  40 mnt -> Rp20.000
                |   35 mnt -> Rp10.000  |  45 mnt -> Rp30.000
                |   50 mnt -> Rp40.000  |  dst.
                |
                | Sumber: kolom E ("TOTAL WAKTU TERLAMBAT") berupa teks "HH:MM".
                | Menit dihitung dengan FIND(":") + LEFT/MID (bukan TIMEVALUE agar
                | tetap benar untuk akumulasi > 24 jam, mis. "120:30").
                | Formula ditulis per baris di sini (bukan di collection()) agar
                | referensi $E{row} selalu benar setelah header 5 baris di-insert.
                |--------------------------------------------------------------------------
                */
                for ($row = 7; $row < $lastRow; $row++) {
                    // Ekspresi "total menit terlambat" dari teks kolom E.
                    $lateMinutesFormula = sprintf(
                        '(VALUE(LEFT($E%1$d,FIND(":",$E%1$d)-1))*60'
                            . '+VALUE(MID($E%1$d,FIND(":",$E%1$d)+1,2)))',
                        $row
                    );

                    $sheet->setCellValue(
                        'G' . $row,
                        sprintf(
                            '=IF(%s<=30,0,CEILING((%s-30)/5,1)*10000)',
                            $lateMinutesFormula,
                            $lateMinutesFormula
                        )
                    );

                    // Format tampilan Rupiah: nilai tetap numerik (bisa di-SUM),
                    // hanya tampilannya diberi prefix "Rp" dan pemisah ribuan.
                    $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('"Rp"#,##0');
                }

                // BARIS TOTAL (baris terakhir): jumlah jam telat (kolom E, format [h]:mm
                // agar akumulasi > 24 jam tetap benar) + jumlah denda (kolom G, format Rp).
                $sheet->setCellValue('A' . $lastRow, 'TOTAL');
                $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $lastRow . ':M' . $lastRow)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                    'font' => ['bold' => true],
                ]);

                if ($lastRow > 7) {
                    // Kolom E berisi TEKS "HH:MM" (bukan durasi Excel), jadi totalnya
                    // dihitung dari komponen jam & menit dengan rumus yang sama
                    // seperti rumus denda per baris (mendukung > 24 jam).
                    $jamSum = [];
                    $mntSum = [];

                    for ($r = 7; $r < $lastRow; $r++) {
                        $jamSum[] = sprintf('VALUE(LEFT($E%d,FIND(":",$E%d)-1))', $r, $r);
                        $mntSum[] = sprintf('VALUE(MID($E%d,FIND(":",$E%d)+1,2))', $r, $r);
                    }

                    $totalJamExpr = '(' . implode('+', $jamSum) . ')';
                    $totalMntExpr = '(' . implode('+', $mntSum) . ')';
                    $sheet->setCellValue(
                        'E' . $lastRow,
                        sprintf(
                            '=TEXT(%s+INT(%s/60),"00")&":"&TEXT(MOD(%s,60),"00")',
                            $totalJamExpr,
                            $totalMntExpr,
                            $totalMntExpr
                        )
                    );
                    $sheet->setCellValue('G' . $lastRow, sprintf('=SUM(G7:G%d)', $lastRow - 1));
                    $sheet->getStyle('G' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
                }

                // Border dan Vertical Alignment untuk data di bawah baris 6
                $sheet->getStyle('A6:M' . $lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                ]);

                // Kolom rincian (tanggal izin/cuti/lembur, jam, rincian telat): wrap text
                $sheet->getStyle('H7:M' . $lastRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
            },
        ];
    }

    private function addLogo($sheet, $coordinate, $path)
    {
        if (file_exists($path)) {
            $drawing = new Drawing();
            $drawing->setPath($path);
            $drawing->setHeight(50);
            $drawing->setCoordinates($coordinate);
            $drawing->setWorksheet($sheet->getDelegate());
        }
    }
}
