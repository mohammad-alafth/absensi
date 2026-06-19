<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithColumnWidths
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\{
    Alignment,
    Border,
    Fill
};

class CalendarUserExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithColumnWidths
{
    protected $data;
    protected $user;
    protected $month;

    public function __construct($data, $user, $month)
    {
        $this->data = $data;
        $this->user = $user;
        $this->month = $month;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Shift',
            'Jadwal Masuk',
            'Jadwal Keluar',
            'Check In',
            'Check Out',
        ];
    }

    public function map($row): array
    {
        $attendance = Attendance::where(
            'user_id',
            $row->user_id
        )
            ->whereDate('tanggal', $row->shift_date)
            ->first();

        return [
            Carbon::parse($row->shift_date)->format('d-m-Y'),
            $row->shift->name ?? '-',
            $row->start_time,
            $row->end_time,

            $attendance?->jam_masuk
                ? Carbon::parse($attendance->jam_masuk)->format('H:i')
                : '-',

            $attendance?->jam_keluar
                ? Carbon::parse($attendance->jam_keluar)->format('H:i')
                : '-',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 25,
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function ($event) {

                $sheet = $event->sheet;

                $sheet->insertNewRowBefore(1, 4);

                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');

                $sheet->setCellValue(
                    'A1',
                    'LAPORAN SHIFT PEGAWAI'
                );

                $sheet->setCellValue(
                    'A2',
                    $this->user->name .
                        ' | ' .
                        Carbon::parse(
                            $this->month
                        )->translatedFormat('F Y')
                );

                $sheet->getStyle('A1')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(16);

                $sheet->getStyle('A1:A2')
                    ->getAlignment()
                    ->setHorizontal(
                        Alignment::HORIZONTAL_CENTER
                    );

                $sheet->getStyle('A5:F5')
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
                        ]
                    ]);

                $lastRow =
                    $sheet->getHighestRow();

                $sheet->getStyle(
                    'A5:F' . $lastRow
                )->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' =>
                            Border::BORDER_THIN
                        ]
                    ]
                ]);

                foreach (range(6, $lastRow) as $row) {

                    if ($sheet->getCell('E' . $row)->getValue() === '-') {
                        continue;
                    }

                    $checkIn = $sheet->getCell('E' . $row)->getValue();
                    $schedule = $sheet->getCell('C' . $row)->getValue();

                    if (
                        $checkIn !== '-' &&
                        $schedule !== '-'
                    ) {
                        try {
                            $checkInTime = Carbon::createFromFormat('H:i', $checkIn);
                            $scheduleTime = Carbon::createFromFormat('H:i', $schedule);

                            if ($checkInTime->gt($scheduleTime)) {
                                $sheet->getStyle('E' . $row)
                                    ->getFont()
                                    ->getColor()
                                    ->setRGB('DC2626');
                            }
                        } catch (\Exception $e) {
                            // skip kalau format error
                        }
                    }
                }
            }
        ];
    }
}
