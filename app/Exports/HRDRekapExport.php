<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;


class HRDRekapExport implements FromArray
{
    protected $month;

    protected $role;
    public function __construct($month, $role = 'all')
    {
        $this->month = $month;

        $this->role = $role;
    }

    public function array(): array
    {
        $startDate = Carbon::parse($this->month . '-01')->startOfMonth();
        $endDate   = Carbon::parse($this->month . '-01')->endOfMonth();

        $employees = User::query()

            ->whereNotIn('role', ['admin']);

        if ($this->role != 'all') {

            $employees->where(function ($q) {

                $q->where('role', $this->role)

                    ->orWhere('role', 'pj_' . $this->role);
            });
        }

        $employees = $employees->get();

        $data = [];

        // header
        $data[] = [
            'Nama',
            'Role',
            'Hadir',
            'Telat',
            'Total Keterlambatan',
            'Total Jam'
        ];

        foreach ($employees as $employee) {

            $attendances = Attendance::where('user_id', $employee->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            $hadir = $attendances->where('status', 'hadir')->count();
            $telat = $attendances->where('status', 'terlambat')->count();
            /*
|--------------------------------------------------------------------------
| TOTAL KETERLAMBATAN
|--------------------------------------------------------------------------
*/
            $lateMinutes = 0;

            foreach ($attendances as $attendance) {

                $late = (int) ($attendance->late_minutes ?? 0);

                /*
    |--------------------------------------------------------------------------
    | TOLERANSI 15 MENIT
    |--------------------------------------------------------------------------
    */
                $realLate = max($late - 15, 0);

                $lateMinutes += $realLate;
            }

            /*
|--------------------------------------------------------------------------
| FORMAT JAM MENIT
|--------------------------------------------------------------------------
*/
            $lateHours = floor($lateMinutes / 60);

            $lateRemainMinutes = $lateMinutes % 60;

            $lateFormatted = '';

            if ($lateHours > 0) {

                $lateFormatted .= $lateHours . ' Jam ';
            }

            $lateFormatted .= $lateRemainMinutes . ' Menit';

            $totalJam = 0;
            foreach ($attendances as $attendance) {
                if ($attendance->jam_masuk && $attendance->jam_keluar) {
                    $masuk  = Carbon::parse($attendance->jam_masuk);
                    $keluar = Carbon::parse($attendance->jam_keluar);
                    $totalJam += abs($masuk->diffInMinutes($keluar));
                }
            }

            $data[] = [
                $employee->name,
                $employee->role,
                $hadir,
                $telat,
                $lateFormatted,
                round($totalJam / 60, 1),
            ];
        }

        return $data;
    }
}
