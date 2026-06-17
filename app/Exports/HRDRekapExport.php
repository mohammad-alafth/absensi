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
        return ['NAMA PEGAWAI', 'UNIT / ROLE', 'TOTAL HADIR', 'TOTAL TELAT', 'TOTAL WAKTU TERLAMBAT', 'TOTAL JAM KERJA'];
    }

    // Gunakan ukuran yang sudah pas menurut Anda
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
            'C' => 15,
            'D' => 15,
            'E' => 25,
            'F' => 20
        ];
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

            $employees->where(function ($q) {

                $q->where('role', $this->role)
                    ->orWhere('role', 'pj_' . $this->role);

                if ($this->role === 'nurse') {

                    $q->orWhere('role', 'nurse_ok')
                        ->orWhere('role', 'pj_nurse_ok');
                }
            });
        }

        return $employees->get()->map(function ($employee) use ($startDate, $endDate, $roleLabels) {
            $attendances = Attendance::where('user_id', $employee->id)->whereBetween('tanggal', [$startDate, $endDate])->get();
            $hadir = $attendances->where('status', 'hadir')->count();
            $telat = $attendances->where('status', 'terlambat')->count();
            $lateMinutes = 0;
            $totalJam = 0;
            foreach ($attendances as $attendance) {
                $lateMinutes += max((int)($attendance->late_minutes ?? 0) - 15, 0);
                if ($attendance->jam_masuk && $attendance->jam_keluar) {
                    $masuk = Carbon::parse($attendance->jam_masuk);
                    $keluar = Carbon::parse($attendance->jam_keluar);
                    $totalJam += abs($masuk->diffInMinutes($keluar));
                }
            }
            return [
                $employee->name,
                $roleLabels[$employee->role]
                    ?? strtoupper(str_replace('_', ' ', $employee->role)),
                $hadir,
                $telat,
                floor($lateMinutes / 60) . ' Jam ' . ($lateMinutes % 60) . ' Menit',
                round($totalJam / 60, 1),
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

                $sheet->mergeCells('B1:E1');
                $sheet->mergeCells('B2:E2');
                $sheet->mergeCells('B3:E3');
                $sheet->mergeCells('B4:E4');
                $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('5a6ea8');
                $sheet->getStyle('B1:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Tambah Logo
                $this->addLogo($sheet, 'A1', public_path('images/rsprofile.png'));
                $this->addLogo($sheet, 'F1', public_path('images/Picture1.png'));

                // Styling Heading Tabel (Baris 6)
                $sheet->getStyle('A6:F6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(35);

                // Border dan Vertical Alignment untuk data di bawah baris 6
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A6:F' . $lastRow)->applyFromArray([
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
