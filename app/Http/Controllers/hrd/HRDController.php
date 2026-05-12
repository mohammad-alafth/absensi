<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\EmployeeShift;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HRDController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTER BULAN
        |--------------------------------------------------------------------------
        */

        $month = $request->month ?? now()->format('Y-m');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();

        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE
        |--------------------------------------------------------------------------
        */

        $employees = User::whereNotIn('role', [
            'admin',
            'hrd'
        ])->get();

        /*
        |--------------------------------------------------------------------------
        | DATA REKAP
        |--------------------------------------------------------------------------
        */

        $recaps = [];

        foreach ($employees as $employee) {

            $attendances = Attendance::where('user_id', $employee->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            $hadir = $attendances
                ->where('status', 'hadir')
                ->count();

            $telat = $attendances
                ->where('status', 'terlambat')
                ->count();

            $totalJam = 0;

            foreach ($attendances as $attendance) {

                if (
                    $attendance->jam_masuk &&
                    $attendance->jam_keluar
                ) {

                    $masuk = Carbon::parse($attendance->jam_masuk);

                    $keluar = Carbon::parse($attendance->jam_keluar);

                    $totalJam += $keluar->diffInMinutes($masuk);
                }
            }

            $recaps[] = [

                'employee' => $employee,

                'hadir' => $hadir,

                'telat' => $telat,

                'total_jam' => round($totalJam / 60, 1)
            ];
        }

        return view('hrd.rekap.index', compact(
            'recaps',
            'month'
        ));
    }
}
