<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class CalendarWorkTypeSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithColumnWidths
{
    protected $data;
    protected $type;
    protected $month;

    public function __construct($data, $type, $month)
    {
        $this->data = $data;
        $this->type = $type;
        $this->month = $month;
    }

    public function title(): string
    {
        return strtoupper($this->type);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Pegawai',
            'Work Type',
            'Shift',
            'Jadwal Masuk',
            'Jadwal Keluar',
            'Check In',
            'Check Out',
        ];
    }

    public function map($row): array
    {
        $attendance = Attendance::where('user_id', $row->user_id)
            ->whereDate('tanggal', $row->shift_date)
            ->first();

        return [
            Carbon::parse($row->shift_date)->format('d-m-Y'),
            $row->user->name ?? '-',
            $row->user->work_type ?? '-',
            $row->shift->name ?? '-',
            $row->start_time,
            $row->end_time,
            $attendance?->jam_masuk ? Carbon::parse($attendance->jam_masuk)->format('H:i') : '-',
            $attendance?->jam_keluar ? Carbon::parse($attendance->jam_keluar)->format('H:i') : '-',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 25,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
        ];
    }
}