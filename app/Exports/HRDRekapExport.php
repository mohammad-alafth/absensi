<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths; // Pastikan ini tetap ada
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HRDRekapExport implements FromCollection, WithEvents, WithHeadings, WithColumnWidths
{
    protected $month, $role;

    public function __construct($month, $role = 'all')
    {
        $this->month = $month;
        $this->role = $role;
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
        ];
    }

    // Gunakan ukuran yang sudah pas menurut Anda
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
        ];
    }
    private function normalizeRole($role)
    {
        $role = strtolower($role);

        $role = str_replace('pj_', '', $role);
        $role = str_replace('_ok', '', $role);

        return $role;
    }
    public function collection()
    {
        $startDate = Carbon::parse($this->month . '-01')->startOfMonth();
        $endDate   = Carbon::parse($this->month . '-01')->endOfMonth();

        $employees = User::whereNotIn('role', ['admin']);

        $roleLabels = [
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

        if ($this->role != 'all') {

            $employees = $employees->get()->filter(function ($user) {

                return $this->normalizeRole($user->role)
                    == $this->normalizeRole($this->role);
            });
        } else {

            $employees = $employees->get();
        }

        return $employees->map(function (
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

            foreach ($attendances as $attendance) {

                if ($attendance->jam_masuk) {
                    $hadir++;
                }

                $shift = \App\Models\EmployeeShift::where(
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
            ];
        });
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

                $sheet->mergeCells('B1:F1');
                $sheet->mergeCells('B2:F2');
                $sheet->mergeCells('B3:F3');
                $sheet->mergeCells('B4:F4');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('5a6ea8');
                $sheet->getStyle('B1:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Tambah Logo
                $this->addLogo($sheet, 'A1', public_path('images/rsprofile.png'));
                $this->addLogo($sheet, 'G1', public_path('images/Picture1.png'));

                // Styling Heading Tabel (Baris 6)
                $sheet->getStyle('A6:G6')->applyFromArray([
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
                for ($row = 7; $row <= $lastRow; $row++) {
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

                // Border dan Vertical Alignment untuk data di bawah baris 6
                $sheet->getStyle('A6:G' . $lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
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
