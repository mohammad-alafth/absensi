<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Leave;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Attendance Today
        |--------------------------------------------------------------------------
        */
        $todayAttendance = Attendance::where(
            'user_id',
            $user->id
        )
            ->whereDate('tanggal', today())
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Attendance History
        |--------------------------------------------------------------------------
        */
        $histories = Attendance::where(
            'user_id',
            $user->id
        )
            ->latest()
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Schedule
        |--------------------------------------------------------------------------
        */
        $dayNumber = Carbon::now()->dayOfWeekIso;

        $isWorkingDay = $dayNumber <= 5;

        $schedule = $isWorkingDay
            ? '08:00 - 17:00 WIB'
            : 'Hari Libur';

        /*
        |--------------------------------------------------------------------------
        | Permission (Izin)
        |--------------------------------------------------------------------------
        */
        $latestPermission = Permission::where(
            'user_id',
            $user->id
        )
            ->latest()
            ->first();

        $pendingPermissionCount = Permission::where(
            'user_id',
            $user->id
        )
            ->where('status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Leave (Cuti)
        |--------------------------------------------------------------------------
        */
        $latestLeave = Leave::where(
            'user_id',
            $user->id
        )
            ->latest()
            ->first();

        $pendingLeaveCount = Leave::where(
            'user_id',
            $user->id
        )
            ->where('status', 'pending')
            ->count();

        return view('dashboard', compact(
            'user',
            'todayAttendance',
            'histories',
            'schedule',
            'isWorkingDay',
            'latestPermission',
            'pendingPermissionCount',
            'latestLeave',
            'pendingLeaveCount'
        ));
    }
}
