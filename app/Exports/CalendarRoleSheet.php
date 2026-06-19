<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CalendarRoleSheet implements WithTitle, WithEvents
{
    protected $role;
    protected $users;
    protected $start;
    protected $end;

    public function __construct($role, $users, $start, $end)
    {
        $this->role  = $role;
        $this->users = $users;
        $this->start = Carbon::parse($start);
        $this->end   = Carbon::parse($end);
    }

    public function title(): string
    {
        return strtoupper($this->role);
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $row = 2;

                /*
                | HEADER SHEET
                */
                $sheet->mergeCells("B{$row}:G{$row}");
                $sheet->setCellValue("B{$row}", "JADWAL PEGAWAI ROLE " . strtoupper($this->role));

                $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ]
                ]);

                $row += 3;

                /*
                | LOOP USERS
                */
                foreach ($this->users as $user) {

                    $days = collect();

                    for (
                        $date = $this->start->copy();
                        $date->lte($this->end);
                        $date->addDay()
                    ) {

                        $attendance = Attendance::where('user_id', $user->id)
                            ->whereDate('tanggal', $date->format('Y-m-d'))
                            ->first();

                        $schedule = \App\Services\ScheduleService::getTodaySchedule($user);

                        if (!$schedule) {
                            $shiftName = '-';
                            $startTime = '-';
                            $endTime   = '-';
                        } else {
                            $shiftName = $schedule['shift_name'] ?? '-';
                            $startTime = $schedule['start_time'] ?? '-';
                            $endTime   = $schedule['end_time'] ?? '-';
                        }

                        $days->push([
                            'tanggal'   => $date->format('d-m-Y'),
                            'shift'     => $shiftName,
                            'masuk'     => $startTime,
                            'keluar'    => $endTime,
                            'check_in'  => $attendance?->jam_masuk ?? '-',
                            'check_out' => $attendance?->jam_keluar ?? '-',
                        ]);
                    }

                    /*
                    | USER HEADER
                    */
                    $sheet->mergeCells("B{$row}:G{$row}");
                    $sheet->setCellValue(
                        "B{$row}",
                        "👤 USER: {$user->name} | ROLE: {$user->role} | TYPE: {$user->work_type}"
                    );

                    $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'size' => 12,
                            'color' => ['rgb' => '0F172A']
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'DBEAFE']
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN
                            ]
                        ]
                    ]);

                    $row++;

                    /*
                    | TABLE HEADER
                    */
                    $headerRow = $row;

                    $sheet->fromArray([
                        'Tanggal',
                        'Shift',
                        'Jam Masuk',
                        'Jam Keluar',
                        'Check In',
                        'Check Out'
                    ], null, "B{$row}");

                    $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF']
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '2563EB']
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN
                            ]
                        ]
                    ]);

                    $row++;

                    /*
                    | DATA
                    */
                    foreach ($days as $day) {

                        $sheet->fromArray([
                            $day['tanggal'],
                            $day['shift'],
                            $day['masuk'],
                            $day['keluar'],
                            $day['check_in'],
                            $day['check_out'],
                        ], null, "B{$row}");

                        $row++;
                    }

                    $lastRow = $row - 1;

                    /*
                    | BORDER TABLE
                    */
                    $sheet->getStyle("B{$headerRow}:G{$lastRow}")
                        ->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN
                                ]
                            ]
                        ]);

                    $row += 2;
                }

                /*
                | AUTO SIZE
                */
                foreach (range('B', 'G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->freezePane('B6');
            }
        ];
    }
}
