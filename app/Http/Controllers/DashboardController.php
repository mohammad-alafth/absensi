<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use App\Services\ScheduleService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        $isHoliday = $this->isHoliday($today);

        /*
    |------------------------------------------------------------------
    | SCHEDULE TODAY (ambil lebih awal untuk dapat shift_date)
    |------------------------------------------------------------------
    */
        $scheduleData = ScheduleService::getTodaySchedule($user);

        /*
    |------------------------------------------------------------------
    | ATTENDANCE TODAY (gunakan shift_date dari schedule untuk cross-day)
    |------------------------------------------------------------------
    */
        $attendanceDate = $scheduleData && isset($scheduleData['shift_date'])
            ? $scheduleData['shift_date']
            : today()->format('Y-m-d');

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $attendanceDate)
            ->first();

        /*
    |------------------------------------------------------------------
    | ATTENDANCE HISTORY
    |------------------------------------------------------------------
    */
        $histories = Attendance::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        /*
    |------------------------------------------------------------------
    | SCHEDULE TODAY (SINKRONISASI & AMANKAN DATA LIBUR)
    |------------------------------------------------------------------
    */
        // PERBAIKAN UTAMA: Pastikan variabel berupa array/object DAN memiliki key 'shift_name'
        if ($scheduleData && isset($scheduleData['shift_name'])) {
            $schedule = $scheduleData['shift_name']
                . ' • ' .
                Carbon::parse($scheduleData['start_time'])->format('H:i')
                . ' - ' .
                Carbon::parse($scheduleData['end_time'])->format('H:i');

            $isWorkingDay = true;
        } else {
            // Jika data null atau tidak punya jadwal (Hari Libur)
            $schedule = 'Hari Libur';
            $isWorkingDay = false;

            // Kita paksa buat array dummy berstruktur agar Blade pilihan Anda tidak bingung/error
            $scheduleData = [
                'shift_name'   => 'Hari Libur / Off',
                'start_time'   => null,
                'end_time'     => null,
                'is_overnight' => false
            ];
        }

        /*
    |------------------------------------------------------------------
    | PERMISSION
    |------------------------------------------------------------------
    */
        $latestPermission = Permission::where('user_id', $user->id)
            ->latest()
            ->first();

        $pendingPermissionCount = Permission::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        /*
    |------------------------------------------------------------------
    | LEAVE
    |------------------------------------------------------------------
    */
        $latestLeave = Leave::where('user_id', $user->id)
            ->latest()
            ->first();

        $pendingLeaveCount = Leave::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        /*
    |------------------------------------------------------------------
    | OVERTIME
    |------------------------------------------------------------------
    */
        $pendingOvertimeCount = Overtime::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        /*
        |------------------------------------------------------------------
        | SHIFT CHANGE REQUEST
        |------------------------------------------------------------------
        */
        $pendingShiftChangeCount = \App\Models\ShiftChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

                return view('dashboard', compact(
            'user',
            'todayAttendance',
            'histories',
            'schedule',
            'scheduleData',
            'isWorkingDay',
            'latestPermission',
            'pendingPermissionCount',
            'latestLeave',
            'pendingLeaveCount',
            'pendingOvertimeCount',
            'pendingShiftChangeCount',
            'isHoliday'
        ));
    }

    private function isHoliday($date)
    {
        $date = Carbon::parse($date);

        // 1. Cek Akhir Pekan (Sabtu & Minggu)
        if ($date->isWeekend()) return true;

        // 2. Cek Tanggal Merah via API (https://api-harilibur.id)
        try {
            $url = "https://api-harilibur.id/api?tanggal=" . $date->format('Y-m-d');
            $response = @file_get_contents($url);
            $data = json_decode($response, true);

            // Jika API mengembalikan data libur
            if (!empty($data)) return true;
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
