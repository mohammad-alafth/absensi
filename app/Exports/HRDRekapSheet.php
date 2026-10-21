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
 *   E TOTAL WAKTU TERLAMBAT | F TOTAL JAM KERJA
 *   G TANGGAL IZIN | H JAM IZIN | I TANGGAL CUTI
 *   J TANGGAL LEMBUR | K JAM LEMBUR (per tanggal, format surat lembur: "X Jam")
 *   L RINCIAN TELAT PER HARI (menit aktual per tanggal, cth: "10/09: 7 mnt; 12/09: 5 mnt")
 *   M TOTAL RINCIAN TELAT (MENIT) - jumlah menit dari kolom L
 *   N DENDA KETERLAMBATAN (formula dari total menit telat)
 *   O RINCIAN TIDAK ABSEN MASUK (tanggal tanpa jam masuk)
 *   P RINCIAN TIDAK ABSEN KELUAR (tanggal tanpa jam keluar)
 *   Q RINCIAN ALPHA (ada jadwal/hari kerja tetapi tidak ada absensi)
 *   R DENDA ABSEN (tidak absen masuk & keluar Rp100.000 - termasuk alpha,
 *     hanya salah satu Rp50.000)
 *   S TOTAL DENDA (N + R)
 *
 * Baris terakhir tiap sheet adalah BARIS TOTAL (label "TOTAL" di kolom A):
 *   - kolom E = total waktu telat dalam format HH:MM
 *   - kolom M = total menit telat
 *   - kolom N = total denda keterlambatan
 *   - kolom P = total denda absen
 *   - kolom Q = total seluruh denda
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
    /**
     * Label tanggal untuk kolom rincian: "05/10/2026 (Senin)".
     * Satu tanggal ditulis pada satu baris di dalam sel.
     */
    private static function dateLabel($date): string
    {
        return Carbon::parse($date)
            ->locale('id')
            ->translatedFormat('d/m/Y (l)');
    }

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
            'TANGGAL IZIN',
            'JAM IZIN',
            'TANGGAL CUTI',
            'TANGGAL LEMBUR',
            'JAM LEMBUR',
            'RINCIAN TELAT PER HARI (MENIT)',
            'TOTAL RINCIAN TELAT (MENIT)',
            'DENDA KETERLAMBATAN',
            'RINCIAN TIDAK ABSEN MASUK',
            'RINCIAN TIDAK ABSEN KELUAR',
            'RINCIAN ALPHA',
            'DENDA ABSEN',
            'TOTAL DENDA',
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
            'G' => 35,
            'H' => 18,
            'I' => 35,
            'J' => 30,
            'K' => 40,
            'L' => 46,
            'M' => 20,
            'N' => 20,
            'O' => 35,
            'P' => 35,
            'Q' => 35,
            'R' => 20,
            'S' => 20,
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
                ->orderBy('tanggal')
                ->get();

            // Tanggal yang sudah punya record attendance (lengkap/tidak lengkap).
            $attendanceDates = $attendances
                ->pluck('tanggal')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->unique();

            $hadir = 0;
            $telat = 0;

            $lateMinutes = 0;
            $totalWorkMinutes = 0;
            $lateDetails = [];

            // Denda absen: dihitung per hari/kejadian dari data attendance.
            // Rincian dipisah per jenis agar mudah dibaca di Excel.
            $absentPenalty = 0;
            $absentInDates = [];
            $absentOutDates = [];
            $alphaDates = [];

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

                /*
                |------------------------------------------------------------------
                | DENDA ABSEN (jam masuk / jam keluar tidak lengkap)
                |------------------------------------------------------------------
                | - tidak absen masuk & tidak absen keluar : Rp100.000
                | - hanya absen masuk (tanpa jam keluar)    : Rp50.000
                | - hanya absen keluar (tanpa jam masuk)    : Rp50.000
                */
                $hasCheckIn = filled($attendance->jam_masuk);
                $hasCheckOut = filled($attendance->jam_keluar);
                $attendanceYmd = Carbon::parse($attendance->tanggal)->format('Y-m-d');

                if (!$hasCheckIn && !$hasCheckOut) {
                    $absentPenalty += 100000;
                    $absentInDates[$attendanceYmd] = self::dateLabel($attendanceYmd);
                    $absentOutDates[$attendanceYmd] = self::dateLabel($attendanceYmd);
                } elseif (!$hasCheckIn) {
                    $absentPenalty += 50000;
                    $absentInDates[$attendanceYmd] = self::dateLabel($attendanceYmd);
                } elseif (!$hasCheckOut) {
                    $absentPenalty += 50000;
                    $absentOutDates[$attendanceYmd] = self::dateLabel($attendanceYmd);
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

            $izinDateSet = PermissionRange::datesWithin($izinList, $startDate, $endDate);

            $izinDates = $izinDateSet
                ->sort()
                ->unique()
                ->values()
                ->map(fn($date) => self::dateLabel($date))
                ->implode("\n");

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

            $cutiDateSet = collect();

            foreach ($leaves as $leave) {
                $cursor = Carbon::parse($leave->start_date)
                    ->max($startDate->copy()->startOfDay());
                $limit = Carbon::parse($leave->end_date)
                    ->min($endDate->copy()->startOfDay());

                while ($cursor->lte($limit)) {
                    $cutiDateSet->push($cursor->format('Y-m-d'));
                    $cursor->addDay();
                }
            }

            $cutiDateSet = $cutiDateSet->sort()->unique()->values();

            $cutiDates = $cutiDateSet
                ->map(fn($date) => self::dateLabel($date))
                ->implode("\n");

            /*
            |----------------------------------------------------------------------
            | ALPHA (ada jadwal kerja tetapi tidak absen)
            |----------------------------------------------------------------------
            | - Pegawai shift : tanggal pada jadwal shift.
            | - Office 5/6    : hari kerja (Senin-Jumat / Senin-Sabtu).
            | Bila pada tanggal tersebut tidak ada attendance, izin approved,
            | maupun cuti approved => alpha, didenda Rp100.000 (tidak absen
            | masuk & keluar). Tanggal setelah hari ini tidak dihitung agar
            | jadwal yang belum berjalan tidak langsung menjadi denda.
            */
            $alphaEnd = $endDate->copy()->startOfDay();
            $today = Carbon::today()->startOfDay();

            if ($alphaEnd->gt($today)) {
                $alphaEnd = $today->copy();
            }

            $workDates = collect();

            if ($startDate->copy()->startOfDay()->lte($alphaEnd)) {
                if ($employee->work_type === 'shift') {
                    $workDates = EmployeeShift::where('user_id', $employee->id)
                        ->whereBetween('shift_date', [
                            $startDate->format('Y-m-d'),
                            $alphaEnd->format('Y-m-d'),
                        ])
                        ->pluck('shift_date')
                        ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                        ->unique();
                } elseif (in_array($employee->work_type, ['office_5', 'office_6'], true)) {
                    $cursor = $startDate->copy()->startOfDay();

                    while ($cursor->lte($alphaEnd)) {
                        $isWorkday = $employee->work_type === 'office_5'
                            ? !$cursor->isWeekend()
                            : $cursor->dayOfWeek !== Carbon::SUNDAY;

                        if ($isWorkday) {
                            $workDates->push($cursor->format('Y-m-d'));
                        }

                        $cursor->addDay();
                    }
                }
            }

            foreach ($workDates as $workDate) {
                if (
                    $attendanceDates->contains($workDate) ||
                    $izinDateSet->contains($workDate) ||
                    $cutiDateSet->contains($workDate)
                ) {
                    continue;
                }

                $absentPenalty += 100000;
                $alphaDates[$workDate] = self::dateLabel($workDate);
            }

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
                ->map(fn($ot) => Carbon::parse($ot->overtime_date)->format('Y-m-d'))
                ->sort()
                ->unique()
                ->values()
                ->map(fn($date) => self::dateLabel($date))
                ->implode("\n");

            $overtimeTotalJam = $overtimeJam->sum(fn($ot) => intval($ot->total_hours));

            // Rincian jam lembur per tanggal sesuai surat lembur: "10/09: 3 Jam; 12/09: 2 Jam".
            $overtimeDetails = $overtimeJam
                ->map(fn($ot) => Carbon::parse($ot->overtime_date)->format('d/m') . ': ' . intval($ot->total_hours) . ' Jam')
                ->implode('; ');

            // Urutkan rincian absen berdasarkan tanggal (key Y-m-d).
            ksort($absentInDates);
            ksort($absentOutDates);
            ksort($alphaDates);

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

                $izinDates,

                $jamIzinText,

                $cutiDates,

                $overtimeDates,

                $overtimeTotalJam . ' Jam',

                // L: rincian telat per hari
                implode('; ', $lateDetails),

                // M: total menit telat (angka, dipakai rumus denda)
                $lateMinutes,

                // N: placeholder formula denda keterlambatan (diisi di AfterSheet)
                null,

                // O: rincian tanggal tanpa jam masuk
                empty($absentInDates) ? '-' : implode("\n", $absentInDates),

                // P: rincian tanggal tanpa jam keluar
                empty($absentOutDates) ? '-' : implode("\n", $absentOutDates),

                // Q: rincian tanggal alpha
                empty($alphaDates) ? '-' : implode("\n", $alphaDates),

                // R: total denda absen periode ini
                $absentPenalty,

                // S: placeholder formula total denda (N + R), diisi di AfterSheet
                null,
            ];
        })->values();

        // Susun ulang sebagai collection bernomor agar baris TOTAL bisa di-append
        // sebagai baris data terakhir (indeks numerik).
        $rows = $rows->values();

        // Placeholder baris TOTAL: rumus E, M, N, R, S diisi di AfterSheet agar
        // referensi baris selalu benar setelah 5 baris header di-insert.
        $totalRow = [
            'TOTAL',
            '',
            '',
            '',
            null, // E: total waktu telat (HH:MM)
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            null, // M: total menit telat
            null, // N: total denda keterlambatan
            '',   // O
            '',   // P
            '',   // Q
            null, // R: total denda absen
            null, // S: total seluruh denda
        ];

        // Total jam lembur semua user (format surat lembur: "X Jam") untuk baris TOTAL kolom K.
        $totalLemburJam = 0;
        foreach ($rows as $r) {
            if (preg_match('/(\d+)\s*Jam/', (string) ($r[10] ?? ''), $m)) {
                $totalLemburJam += (int) $m[1];
            }
        }
        $totalRow[10] = $totalLemburJam . ' Jam';

        return $rows->push($totalRow)->values();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Geser data ke bawah untuk memberi ruang header (Baris 1-5)
                $sheet->insertNewRowBefore(1, 5);

                /*
                |------------------------------------------------------------------
                | HEADER SURAT (disamakan dengan export Rekap Izin/Cuti/Lembur)
                |------------------------------------------------------------------
                | Teks instansi di kolom B:C, logo kiri di A1 dan logo kanan di D1
                | supaya tampilan semua laporan rekap HRD konsisten.
                */
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

                $sheet->mergeCells('B1:C1');
                $sheet->mergeCells('B2:C2');
                $sheet->mergeCells('B3:C3');
                $sheet->mergeCells('B4:C4');
                $sheet->mergeCells('B5:C5');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('5a6ea8');
                $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('5a6ea8');
                $sheet->getStyle('B1:C5')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Tambah Logo (posisi sama seperti export Rekap Izin/Cuti/Lembur)
                $this->addLogo($sheet, 'A1', public_path('images/rsprofile.png'));
                $this->addLogo($sheet, 'D1', public_path('images/Picture1.png'), 180);

                // Styling Heading Tabel (Baris 6) - seluruh kolom A:S
                $sheet->getStyle('A6:S6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(35);

                $lastRow = $sheet->getHighestRow();

                /*
                |--------------------------------------------------------------------------
                | KOLOM M-N : TOTAL MENIT TELAT & FORMULA DENDA KETERLAMBATAN
                |--------------------------------------------------------------------------
                | Ketentuan: bila akumulasi keterlambatan dalam 1 periode melebihi
                | toleransi 30 menit, kelebihannya didenda Rp10.000 untuk SETIAP
                | kelipatan 5 menit (dibulatkan ke atas ke kelipatan terdekat).
                |
                |   30 mnt -> Rp0       |  40 mnt -> Rp20.000
                |   35 mnt -> Rp10.000  |  45 mnt -> Rp30.000
                |   50 mnt -> Rp40.000  |  dst.
                |
                | Sumber: kolom M ("TOTAL RINCIAN TELAT (MENIT)") yang sudah
                | berupa angka, sehingga tidak perlu mem-parsing teks "HH:MM"
                | dan tetap benar untuk akumulasi > 24 jam (mis. 155 menit).
                |--------------------------------------------------------------------------
                */
                for ($row = 7; $row < $lastRow; $row++) {
                    $sheet->setCellValue(
                        'N' . $row,
                        sprintf(
                            '=IF($M%1$d<=30,0,CEILING(($M%1$d-30)/5,1)*10000)',
                            $row
                        )
                    );

                    // Format tampilan Rupiah: nilai tetap numerik (bisa di-SUM),
                    // hanya tampilannya diberi prefix "Rp" dan pemisah ribuan.
                    $sheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode('"Rp"#,##0');

                    // Total denda = denda keterlambatan + denda absen.
                    $sheet->setCellValue('S' . $row, sprintf('=N%d+R%d', $row, $row));
                    $sheet->getStyle('S' . $row)->getNumberFormat()->setFormatCode('"Rp"#,##0');
                }

                // Format kolom menit & denda absen untuk seluruh baris data.
                $sheet->getStyle('M7:M' . $lastRow)->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('R7:R' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');

                // BARIS TOTAL (baris terakhir): total waktu telat, total menit,
                // total denda keterlambatan, total denda absen, dan total denda.
                $sheet->setCellValue('A' . $lastRow, 'TOTAL');
                $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $lastRow . ':S' . $lastRow)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                    'font' => ['bold' => true],
                ]);

                if ($lastRow > 7) {
                    // Kolom E berisi TEKS "HH:MM" (bukan durasi Excel), jadi totalnya
                    // dihitung dari komponen jam & menit (mendukung > 24 jam).
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

                    $sheet->setCellValue('M' . $lastRow, sprintf('=SUM(M7:M%d)', $lastRow - 1));
                    $sheet->setCellValue('N' . $lastRow, sprintf('=SUM(N7:N%d)', $lastRow - 1));
                    $sheet->setCellValue('R' . $lastRow, sprintf('=SUM(R7:R%d)', $lastRow - 1));
                    $sheet->setCellValue('S' . $lastRow, sprintf('=SUM(S7:S%d)', $lastRow - 1));

                    $sheet->getStyle('N' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
                    $sheet->getStyle('S' . $lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
                }

                // Border dan Vertical Alignment untuk data di bawah baris 6
                $sheet->getStyle('A6:S' . $lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                ]);

                // Semua sel data: wrap text (sama seperti export Rekap Izin/Cuti/Lembur)
                $sheet->getStyle('A7:S' . $lastRow)->getAlignment()->setWrapText(true);

                // Kolom rincian (tanggal izin/cuti/lembur, jam, rincian telat/absen): rata tengah
                $sheet->getStyle('G7:S' . $lastRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                /*
                |------------------------------------------------------------------
                | TINGGI BARIS MENGIKUTI JUMLAH TANGGAL
                |------------------------------------------------------------------
                | Kolom G (izin), I (cuti), J (lembur), O/P/Q (rincian absen)
                | menampilkan satu tanggal per baris. Tinggi baris disesuaikan
                | agar seluruh tanggal terlihat tanpa perlu auto-fit manual.
                */
                for ($row = 7; $row < $lastRow; $row++) {
                    $maxLines = 1;

                    foreach (['G', 'I', 'J', 'O', 'P', 'Q'] as $column) {
                        $value = (string) $sheet->getCell($column . $row)->getValue();
                        $maxLines = max($maxLines, substr_count($value, "\n") + 1);
                    }

                    if ($maxLines > 1) {
                        $sheet->getRowDimension($row)->setRowHeight(16 * $maxLines);
                    }
                }

                // Cetak menyesuaikan lebar halaman (sama seperti export Rekap Izin/Cuti/Lembur)
                $sheet->getPageSetup()->setFitToWidth(1);
            },
        ];
    }

    private function addLogo($sheet, $coordinate, $path, ?int $offsetX = null)
    {
        if (file_exists($path)) {
            $drawing = new Drawing();
            $drawing->setPath($path);
            $drawing->setHeight(50);
            $drawing->setCoordinates($coordinate);

            if ($offsetX !== null) {
                $drawing->setOffsetX($offsetX);
            }

            $drawing->setWorksheet($sheet->getDelegate());
        }
    }
}
