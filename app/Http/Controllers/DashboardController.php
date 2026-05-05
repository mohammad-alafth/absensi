<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $todayAttendance = Attendance::where(
            'user_id',
            $user->id
        )
            ->whereDate('tanggal', today())
            ->first();

        $histories = Attendance::where(
            'user_id',
            $user->id
        )
            ->latest()
            ->limit(10)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Schedule Logic
        |--------------------------------------------------------------------------
        */

        $today = Carbon::now();

        // ISO day:
        // 1 = Monday
        // 5 = Friday
        // 6 = Saturday
        // 7 = Sunday

        $dayNumber = $today->dayOfWeekIso;

        if ($dayNumber >= 1 && $dayNumber <= 5) {

            $schedule = '08:00 - 17:00 WIB';

            $isWorkingDay = true;
        } else {

            $schedule = 'Hari Libur';

            $isWorkingDay = false;
        }


        return view('dashboard', compact(
            'user',
            'todayAttendance',
            'histories',
            'schedule',
            'isWorkingDay'
        ));
    }
}
