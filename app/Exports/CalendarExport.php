<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CalendarExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithColumnWidths
{
    protected $data;
    protected $month;
    protected $role;

    // Tambahkan nilai default null agar tidak error saat dipanggil 1 parameter
    public function __construct($dataOrMonth, $role = null)
    {
        if ($role === null) {
            // Dipanggil dari exportCalendar (hanya kirim $data)
            $this->data = $dataOrMonth;
        } else {
            // Dipanggil dari MultiSheetExport (kirim $month dan $role)
            $this->month = $dataOrMonth;
            $this->role = $role;
            // Query langsung di dalam constructor
            $this->data = \App\Models\EmployeeShift::with(['user', 'shift'])
                ->whereHas('user', fn($q) => $q->where('role', $role))
                ->whereRaw("DATE_FORMAT(shift_date,'%Y-%m') = ?", [$this->month])
                ->get();
        }
    }

    public function collection()
    {
        return $this->data;
    }
    public function title(): string
    {
        return substr(strtoupper($this->role), 0, 31); // Nama sheet maksimal 31 karakter
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Pegawai',
            'Role',
            'Shift',
            'Jadwal Masuk',
            'Jadwal Keluar',
            'Check In',
            'Check Out',
            'Status Kehadiran',
            'Terlambat',
            'Jam Kerja',
            'Lembur',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 35,
            'C' => 20,
            'D' => 25,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 30,
            'J' => 15,
            'K' => 15,
            'L' => 15,
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                // Header
                $sheet->insertNewRowBefore(1, 4);
                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');
                $sheet->setCellValue(
                    'A1',
                    'REKAP JADWAL SHIFT PEGAWAI'
                );
                $sheet->setCellValue(
                    'A2',
                    'HRD MANAGEMENT REPORT'
                );
                $sheet->getStyle('A1')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(16);
                $sheet->getStyle('A2')
                    ->getFont()
                    ->setSize(11);
                $sheet->getStyle('A1:H2')
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );
                // Heading table
                $sheet->getStyle('A5:L5')
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => [
                                'rgb' => 'FFFFFF'
                            ]
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => [
                                'rgb' => '1E40AF'
                            ]
                        ],
                        'alignment' => [
                            'horizontal' =>
                            Alignment::HORIZONTAL_CENTER,
                            'vertical' =>
                            Alignment::VERTICAL_CENTER,
                        ]
                    ]);
                $lastRow = $sheet->getHighestRow();
                // Border semua data
                $sheet->getStyle(
                    'A5:L' . $lastRow
                )->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' =>
                            Border::BORDER_THIN,
                        ]
                    ]
                ]);
                // Tengah kolom jam
                $sheet->getStyle(
                    'A5:H' . $lastRow
                )->getAlignment()
                    ->setVertical(
                        Alignment::VERTICAL_CENTER
                    );
                $sheet->getStyle(
                    'A5:A' . $lastRow
                )->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );
                $sheet->getStyle(
                    'E5:H' . $lastRow
                )->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );
                // Freeze header
                $sheet->freezePane('A6');
                // Auto filter
                $sheet->setAutoFilter(
                    'A5:H' . $lastRow
                );
                // Highlight keterlambatan
                for ($row = 6; $row <= $lastRow; $row++) {
                    $schedule = $sheet->getCell('E' . $row)->getValue();
                    $checkin  = $sheet->getCell('G' . $row)->getValue();
                    if (
                        $schedule !== '-' &&
                        $checkin !== '-'
                    ) {
                        try {
                            $scheduleTime = Carbon::createFromFormat('H:i', $schedule);
                            $checkinTime  = Carbon::createFromFormat('H:i', $checkin);

                            if ($checkinTime->gt($scheduleTime)) {
                                $sheet->getStyle('G' . $row)
                                    ->getFont()
                                    ->getColor()
                                    ->setRGB('DC2626');
                            }
                        } catch (\Exception $e) {
                            // ignore invalid format
                        }
                    }
                }
                // Landscape print
                $sheet->getPageSetup()
                    ->setOrientation(
                        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                    );
                $sheet->getPageSetup()
                    ->setFitToWidth(1);
            }
        ];
    }

    public function map($row): array
    {
        $attendance = Attendance::where(
            'user_id',
            $row->user_id
        )
            ->whereDate(
                'tanggal',
                $row->shift_date
            )
            ->first();

        /*
    |--------------------------------------------------------------------------
    | CEK CUTI / IZIN / LEMBUR DULU
    |--------------------------------------------------------------------------
    */

        $leave = \App\Models\Leave::where(
            'user_id',
            $row->user_id
        )
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $row->shift_date)
            ->whereDate('end_date', '>=', $row->shift_date)
            ->exists();

        $permission = \App\Models\Permission::where(
            'user_id',
            $row->user_id
        )
            ->whereDate('tanggal', $row->shift_date)
            ->where('status', 'approved')
            ->exists();

        $overtime = \App\Models\Overtime::where(
            'user_id',
            $row->user_id
        )
            ->whereDate('overtime_date', $row->shift_date)
            ->where('status', 'approved')
            ->first();

        $statusKehadiran = '-';
        $terlambat = '-';
        $jamKerja = '-';
        $jamLembur = '-';

        /*
    |--------------------------------------------------------------------------
    | PRIORITAS STATUS
    |--------------------------------------------------------------------------
    */

        if ($leave) {

            $statusKehadiran = 'Cuti';
        } elseif ($permission) {

            $statusKehadiran = 'Izin';
        } elseif ($overtime) {

            $statusKehadiran = 'Lembur';
        } elseif ($attendance) {

            $statusKehadiran = 'Hadir';

            /*
        |--------------------------------------------------------------------------
        | TERLAMBAT (>15 MENIT)
        |--------------------------------------------------------------------------
        */
            if (
                $attendance->jam_masuk &&
                $row->start_time
            ) {

                try {

                    $jamMasuk = Carbon::parse(
                        $attendance->jam_masuk
                    );

                    $jadwalMasuk = Carbon::parse(
                        $row->start_time
                    );

                    $selisihMenit = $jadwalMasuk->diffInMinutes(
                        $jamMasuk,
                        false
                    );

                    if ($selisihMenit > 15) {

                        $statusKehadiran = 'Terlambat';

                        $menitTerlambat = $selisihMenit - 15;

                        $terlambat = sprintf(
                            '%02d:%02d',
                            floor($menitTerlambat / 60),
                            $menitTerlambat % 60
                        );
                    }
                } catch (\Exception $e) {
                }
            }

            /*
        |--------------------------------------------------------------------------
        | JAM KERJA
        |--------------------------------------------------------------------------
        */
            if (
                $attendance->jam_masuk &&
                $attendance->jam_keluar
            ) {

                try {

                    $masuk = Carbon::parse(
                        $attendance->jam_masuk
                    );

                    $keluar = Carbon::parse(
                        $attendance->jam_keluar
                    );

                    $menitKerja = $masuk->diffInMinutes(
                        $keluar
                    );

                    $jamKerja = sprintf(
                        '%02d:%02d',
                        floor($menitKerja / 60),
                        $menitKerja % 60
                    );
                } catch (\Exception $e) {
                }
            }
        } else {

            $today = now()->toDateString();

            if ($row->shift_date >= $today) {

                $statusKehadiran = 'Belum Absen';
            } else {

                $statusKehadiran = 'Alpha';
            }
        }

        /*
    |--------------------------------------------------------------------------
    | JAM LEMBUR
    |--------------------------------------------------------------------------
    */

        if (
            $overtime &&
            $overtime->start_time &&
            $overtime->end_time
        ) {

            try {

                $mulai = Carbon::parse(
                    $overtime->start_time
                );

                $selesai = Carbon::parse(
                    $overtime->end_time
                );

                $menitLembur = $mulai->diffInMinutes(
                    $selesai
                );

                $jamLembur = sprintf(
                    '%02d:%02d',
                    floor($menitLembur / 60),
                    $menitLembur % 60
                );
            } catch (\Exception $e) {
            }
        }

        return [

            Carbon::parse(
                $row->shift_date
            )->format('d-m-Y'),

            $row->user->name ?? '-',

            $row->user->role ?? '-',

            $row->shift->name ?? '-',

            $row->start_time,

            $row->end_time,

            $attendance?->jam_masuk
                ? Carbon::parse(
                    $attendance->jam_masuk
                )->format('H:i')
                : '-',

            $attendance?->jam_keluar
                ? Carbon::parse(
                    $attendance->jam_keluar
                )->format('H:i')
                : '-',

            $statusKehadiran,

            $terlambat,

            $jamKerja,

            $jamLembur,
        ];
    }
}
