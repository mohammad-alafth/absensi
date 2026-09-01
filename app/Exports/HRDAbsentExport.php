<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HRDAbsentExport implements FromCollection, WithEvents, WithHeadings, WithColumnWidths
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate   = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
    }

    public function headings(): array
    {
        return ['NO', 'NAMA PEGAWAI', 'UNIT / ROLE', 'PERIODE LAPORAN', 'TOTAL HARI TIDAK HADIR', 'RINCIAN TANGGAL TIDAK HADIR'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 35,
            'C' => 35,
            'D' => 28,
            'E' => 28,
            'F' => 45,
        ];
    }

    public function collection()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end   = Carbon::parse($this->endDate)->endOfDay();

        $employees = User::whereNotIn('role', ['admin'])->get();

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

        $no = 1;
        $result = collect();

        foreach ($employees as $employee) {
            $absentData = $this->calculateAbsentDetails($employee, $start, $end);

            // Hanya sertakan pegawai yang memiliki minimal 1 hari tidak hadir pada rentang tanggal terpilih
            if ($absentData['total_absent'] > 0) {
                $roleName = $roleLabels[$employee->role] ?? strtoupper(str_replace('_', ' ', $employee->role));
                $periodeText = $start->format('d/m/Y') . ' s/d ' . $end->format('d/m/Y');
                $datesList = implode(', ', $absentData['absent_dates']);

                $result->push([
                    $no++,
                    $employee->name,
                    $roleName,
                    $periodeText,
                    $absentData['total_absent'] . ' Hari',
                    $datesList,
                ]);
            }
        }

        return $result;
    }

    private function calculateAbsentDetails(User $employee, Carbon $start, Carbon $end)
    {
        $attendanceDates = Attendance::where('user_id', $employee->id)
            ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->pluck('tanggal')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique();

        $permissionDates = Permission::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->pluck('tanggal')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique();

        $leaveDates = collect();
        $leaves = Leave::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<=', $end->format('Y-m-d'))
                    ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->get();

        foreach ($leaves as $leave) {
            $lStart = Carbon::parse($leave->start_date);
            $lEnd   = Carbon::parse($leave->end_date);
            while ($lStart->lte($lEnd)) {
                if ($lStart->between($start, $end)) {
                    $leaveDates->push($lStart->format('Y-m-d'));
                }
                $lStart->addDay();
            }
        }
        $leaveDates = $leaveDates->unique();

        $absentDates = [];

        if ($employee->work_type === 'shift') {
            $shiftDates = EmployeeShift::where('user_id', $employee->id)
                ->whereBetween('shift_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->pluck('shift_date')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->unique();

            foreach ($shiftDates as $date) {
                if (
                    !$attendanceDates->contains($date) &&
                    !$permissionDates->contains($date) &&
                    !$leaveDates->contains($date)
                ) {
                    $absentDates[] = Carbon::parse($date)->format('d/m/Y');
                }
            }
        } elseif ($employee->work_type === 'office_5') {
            $curr = $start->copy();
            while ($curr->lte($end)) {
                if (!$curr->isWeekend()) {
                    $day = $curr->format('Y-m-d');
                    if (
                        !$attendanceDates->contains($day) &&
                        !$permissionDates->contains($day) &&
                        !$leaveDates->contains($day)
                    ) {
                        $absentDates[] = $curr->format('d/m/Y');
                    }
                }
                $curr->addDay();
            }
        } elseif ($employee->work_type === 'office_6') {
            $curr = $start->copy();
            while ($curr->lte($end)) {
                if ($curr->dayOfWeek !== Carbon::SUNDAY) {
                    $day = $curr->format('Y-m-d');
                    if (
                        !$attendanceDates->contains($day) &&
                        !$permissionDates->contains($day) &&
                        !$leaveDates->contains($day)
                    ) {
                        $absentDates[] = $curr->format('d/m/Y');
                    }
                }
                $curr->addDay();
            }
        }

        return [
            'total_absent' => count($absentDates),
            'absent_dates' => $absentDates,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Geser data ke bawah untuk memberi ruang header Kop Surat (Baris 1-5)
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

                // Tambah Logo Kiri (A1) & Logo Kanan (F1)
                $this->addLogo($sheet, 'A1', public_path('images/rsprofile.png'));
                $this->addLogo($sheet, 'F1', public_path('images/Picture1.png'));

                // Styling Heading Tabel (Baris 6)
                $sheet->getStyle('A6:F6')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(35);

                // Border & Alignment untuk sel data (Baris 7 ke bawah)
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A6:F' . $lastRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                ]);

                $sheet->getStyle('A7:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D7:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
