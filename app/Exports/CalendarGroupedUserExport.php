<?php

namespace App\Exports;

use App\Models\User;
use App\Models\EmployeeShift;
use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;


class CalendarGroupedUserExport implements FromCollection, WithEvents
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection()
    {
        return User::with(['shifts' => function ($q) {
            $q->whereMonth('shift_date', Carbon::parse($this->month)->month)
                ->whereYear('shift_date', Carbon::parse($this->month)->year)
                ->orderBy('shift_date');
        }])->get();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                $row = 1;

                $users = User::with(['shifts.shift'])->get();

                foreach ($users as $user) {

                    $shifts = EmployeeShift::with('shift')
                        ->where('user_id', $user->id)
                        ->whereMonth('shift_date', Carbon::parse($this->month)->month)
                        ->whereYear('shift_date', Carbon::parse($this->month)->year)
                        ->get();

                    if ($shifts->isEmpty()) continue;

                    // HEADER USER
                    $sheet->setCellValue(
                        "A{$row}",
                        "USER: {$user->name} | ROLE: {$user->role}"
                    );
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $row++;

                    // HEADER TABLE
                    $sheet->fromArray([
                        'Tanggal',
                        'Shift',
                        'Masuk',
                        'Keluar',
                        'Check In',
                        'Check Out'
                    ], null, "A{$row}");

                    $row++;

                    foreach ($shifts as $shift) {

                        $attendance = Attendance::where('user_id', $user->id)
                            ->whereDate('tanggal', $shift->shift_date)
                            ->first();

                        $sheet->fromArray([
                            Carbon::parse($shift->shift_date)->format('d-m-Y'),
                            $shift->shift->name ?? '-',
                            $shift->start_time,
                            $shift->end_time,
                            $attendance?->jam_masuk ?? '-',
                            $attendance?->jam_keluar ?? '-',
                        ], null, "A{$row}");

                        $row++;
                    }

                    $row += 2; // spacing antar user
                }
            }
        ];
    }
}
