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
use App\Models\Leave;
use App\Models\Permission;
use App\Models\Overtime;

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
                $sheet->mergeCells("B{$row}:K{$row}");
                $sheet->setCellValue("B{$row}", "JADWAL PEGAWAI ROLE " . strtoupper($this->role));

                $sheet->getStyle("B{$row}:K{$row}")->applyFromArray([
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
                        $employeeShift = EmployeeShift::with('shift')
                            ->where('user_id', $user->id)
                            ->whereDate('shift_date', $date->format('Y-m-d'))
                            ->first();

                        $attendance = Attendance::where('user_id', $user->id)
                            ->whereDate('tanggal', $date->format('Y-m-d'))
                            ->first();

                        $dayOfWeek = $date->dayOfWeek;

                        /*
                        |--------------------------------------------------------------------------
                        | TENTUKAN SHIFT
                        |--------------------------------------------------------------------------
                        */
                        if ($user->work_type === 'shift') {

                            $shiftName = $employeeShift?->shift?->name ?? '-';
                            $startTime = $employeeShift?->shift?->start_time ?? '-';
                            $endTime   = $employeeShift?->shift?->end_time ?? '-';
                        } elseif ($user->work_type === 'office_5') {

                            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {

                                $shiftName = 'Office 5';
                                $startTime = '08:00';
                                $endTime   = '17:00';
                            } else {

                                $shiftName = 'Libur';
                                $startTime = '-';
                                $endTime   = '-';
                            }
                        } elseif ($user->work_type === 'office_6') {

                            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {

                                $shiftName = 'Office 6';
                                $startTime = '08:00';
                                $endTime   = '16:00';
                            } elseif ($dayOfWeek == 6) {

                                $shiftName = 'Office 6';
                                $startTime = '08:00';
                                $endTime   = '13:00';
                            } else {

                                $shiftName = 'Libur';
                                $startTime = '-';
                                $endTime   = '-';
                            }
                        } else {

                            $shiftName = '-';
                            $startTime = '-';
                            $endTime   = '-';
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | DEFAULT VALUE
                        |--------------------------------------------------------------------------
                        */
                        $status     = '-';
                        $terlambat  = '-';
                        $jamKerja   = '-';
                        $jamLembur  = '-';

                        /*
                        |--------------------------------------------------------------------------
                        | CEK CUTI / IZIN
                        |--------------------------------------------------------------------------
                        */
                        $leave = Leave::where('user_id', $user->id)
                            ->where('status', 'approved')
                            ->whereDate('start_date', '<=', $date->format('Y-m-d'))
                            ->whereDate('end_date', '>=', $date->format('Y-m-d'))
                            ->exists();

                        $permission = Permission::where('user_id', $user->id)
                            ->whereDate('tanggal', $date->format('Y-m-d'))
                            ->where('status', 'approved')
                            ->exists();
                        $overtime = Overtime::where('user_id', $user->id)
                            ->whereDate('overtime_date', $date->format('Y-m-d'))
                            ->where('status', 'approved')
                            ->exists();
                        /*
|--------------------------------------------------------------------------
| PRIORITAS STATUS
|--------------------------------------------------------------------------
| CUTI > IZIN > LEMBUR > TERLAMBAT > HADIR
|--------------------------------------------------------------------------
*/

                        if ($leave) {

                            $status = 'Cuti';
                        } elseif ($permission) {

                            $status = 'Izin';
                        } elseif ($overtime) {

                            $status = 'Lembur';
                        } elseif ($attendance) {

                            $status = 'Hadir';

                            /*
    |--------------------------------------------------------------------------
    | TERLAMBAT
    |--------------------------------------------------------------------------
    */
                            if (
                                $attendance->jam_masuk &&
                                $startTime !== '-'
                            ) {

                                $jadwalMasuk = Carbon::parse(
                                    $date->format('Y-m-d') . ' ' . $startTime
                                );

                                $jamMasuk = Carbon::parse(
                                    $attendance->jam_masuk
                                );

                                $selisihMenit = $jadwalMasuk
                                    ->diffInMinutes($jamMasuk, false);

                                if ($selisihMenit > 15) {

                                    $status = 'Terlambat';

                                    $menitTerlambat = $selisihMenit - 15;

                                    $jam = floor($menitTerlambat / 60);
                                    $menit = $menitTerlambat % 60;

                                    $terlambat = sprintf(
                                        '%02d:%02d',
                                        $jam,
                                        $menit
                                    );
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

                                $checkIn = Carbon::parse(
                                    $attendance->jam_masuk
                                );

                                $checkOut = Carbon::parse(
                                    $attendance->jam_keluar
                                );

                                if ($checkOut->lt($checkIn)) {
                                    $checkOut->addDay();
                                }

                                $totalMenit = $checkIn
                                    ->diffInMinutes($checkOut);

                                $jam = floor($totalMenit / 60);
                                $menit = $totalMenit % 60;

                                $jamKerja = sprintf(
                                    '%02d:%02d',
                                    $jam,
                                    $menit
                                );
                            }
                        } else {

                            if ($shiftName === 'Libur') {

                                $status = 'Libur';
                            } elseif ($date->isFuture()) {

                                $status = '-';
                            } elseif ($date->isToday()) {

                                $status = 'Belum Absen';
                            } else {

                                $status = 'Alpha';
                            }
                        }
                        $days->push([
                            'tanggal'   => $date->format('d-m-Y'),
                            'shift'     => $shiftName,
                            'masuk'     => $startTime,
                            'keluar'    => $endTime,

                            'check_in'  => $attendance?->jam_masuk
                                ? Carbon::parse($attendance->jam_masuk)->format('H:i')
                                : '-',

                            'check_out' => $attendance?->jam_keluar
                                ? Carbon::parse($attendance->jam_keluar)->format('H:i')
                                : '-',

                            'status'     => $status,
                            'terlambat'  => $terlambat,
                            'jam_kerja'  => $jamKerja,
                            'jam_lembur' => $jamLembur,
                        ]);
                    }

                    /*
                    | USER HEADER
                    */
                    $sheet->mergeCells("B{$row}:K{$row}");
                    $sheet->setCellValue(
                        "B{$row}",
                        "👤 USER: {$user->name} | ROLE: {$user->role} | TYPE: {$user->work_type}"
                    );

                    $sheet->getStyle("B{$row}:K{$row}")->applyFromArray([
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
                        'Check Out',
                        'Status',
                        'Terlambat',
                        'Jam Kerja',
                        'Jam Lembur'
                    ], null, "B{$row}");

                    $sheet->getStyle("B{$row}:K{$row}")->applyFromArray([
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
                            $day['status'],
                            $day['terlambat'],
                            $day['jam_kerja'],
                            $day['jam_lembur'],
                        ], null, "B{$row}");

                        $row++;
                    }

                    $lastRow = $row - 1;

                    /*
                    | BORDER TABLE
                    */
                    $sheet->getStyle("B{$headerRow}:K{$lastRow}")
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
                foreach (range('B', 'K') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->freezePane('B6');
            }
        ];
    }
}
