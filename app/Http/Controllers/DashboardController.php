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

        /*
        |------------------------------------------------------------------
        | ATTENDANCE TODAY
        |------------------------------------------------------------------
        */

        $todayAttendance = Attendance::where(
            'user_id',
            $user->id
        )
            ->whereDate('tanggal', today())
            ->first();

        /*
        |------------------------------------------------------------------
        | ATTENDANCE HISTORY
        |------------------------------------------------------------------
        */

        $histories = Attendance::where(
            'user_id',
            $user->id
        )
            ->latest()
            ->limit(10)
            ->get();

        /*
        |------------------------------------------------------------------
        | SCHEDULE TODAY
        |------------------------------------------------------------------
        */

        $scheduleData = ScheduleService::getTodaySchedule($user);

        if ($scheduleData) {

            $schedule =
                $scheduleData['shift_name']
                . ' • ' .
                Carbon::parse($scheduleData['start_time'])->format('H:i')
                . ' - ' .
                Carbon::parse($scheduleData['end_time'])->format('H:i');

            $isWorkingDay = true;
        } else {

            $schedule = 'Hari Libur';

            $isWorkingDay = false;
        }

        /*
        |------------------------------------------------------------------
        | PERMISSION
        |------------------------------------------------------------------
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
        |------------------------------------------------------------------
        | LEAVE
        |------------------------------------------------------------------
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

        /*
        |------------------------------------------------------------------
        | OVERTIME
        |------------------------------------------------------------------
        */

        $pendingOvertimeCount = Overtime::where(
            'user_id',
            $user->id
        )
            ->where('status', 'pending')
            ->count();

        /*
        |------------------------------------------------------------------
        | REMINDER SHIFT UNTUK PJ
        |------------------------------------------------------------------
        */

        $showShiftReminder = false;

        $shiftReminderMessage = null;

        if (str_starts_with($user->role, 'pj_')) {

            $today = Carbon::today();

            /*
            |------------------------------------------------------------------
            | PERIODE SEKARANG
            |------------------------------------------------------------------
            */

            $period = $this->getShiftPeriod($today);

            $periodStart = Carbon::parse(
                $period['start_date']
            );

            $periodEnd = Carbon::parse(
                $period['end_date']
            );

            /*
            |------------------------------------------------------------------
            | H-2 SEBELUM SHIFT HABIS
            |------------------------------------------------------------------
            */

            $reminderStart = $periodEnd
                ->copy()
                ->subDays(2);

            /*
            |------------------------------------------------------------------
            | NEXT PERIOD
            |------------------------------------------------------------------
            */

            $nextPeriod = $this->getShiftPeriod(
                $periodEnd->copy()->addDay()
            );

            /*
            |------------------------------------------------------------------
            | CEK SHIFT PERIODE BERIKUTNYA
            |------------------------------------------------------------------
            */

            $nextShiftExist = EmployeeShift::whereDate(
                'start_date',
                $nextPeriod['start_date']
            )
                ->whereDate(
                    'end_date',
                    $nextPeriod['end_date']
                )
                ->exists();

            /*
            |------------------------------------------------------------------
            | TAMPILKAN POPUP
            |------------------------------------------------------------------
            */

            if (
                $today->gte($reminderStart)
                &&
                !$nextShiftExist
            ) {

                $showShiftReminder = true;

                $shiftReminderMessage =
                    'Periode shift akan berakhir pada '
                    . $periodEnd->translatedFormat('d F Y')
                    . '. Silakan atur ulang jadwal shift untuk periode berikutnya.';
            }

            /*
            |------------------------------------------------------------------
            | JIKA SUDAH LEWAT PERIODE
            |------------------------------------------------------------------
            */

            if (
                $today->gt($periodEnd)
                &&
                !$nextShiftExist
            ) {

                $showShiftReminder = true;

                $shiftReminderMessage =
                    'Periode shift telah habis. Segera atur ulang jadwal shift pegawai.';
            }
        }

        /*
        |------------------------------------------------------------------
        | RETURN
        |------------------------------------------------------------------
        */

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
            'showShiftReminder',
            'shiftReminderMessage'
        ));
    }

    /*
    |------------------------------------------------------------------
    | GET SHIFT PERIOD
    |------------------------------------------------------------------
    */

    private function getShiftPeriod($date)
    {
        $date = Carbon::parse($date);

        $payrollDay = 26;

        /*
        |------------------------------------------------------------------
        | JIKA >= 26
        |------------------------------------------------------------------
        */

        if ($date->day >= $payrollDay) {

            $startDate = $date
                ->copy()
                ->day($payrollDay);

            $endDate = $startDate
                ->copy()
                ->addMonth()
                ->subDay();
        } else {

            /*
            |------------------------------------------------------------------
            | JIKA < 26
            |------------------------------------------------------------------
            */

            $startDate = $date
                ->copy()
                ->subMonth()
                ->day($payrollDay);

            $endDate = $startDate
                ->copy()
                ->addMonth()
                ->subDay();
        }

        return [

            'start_date' => $startDate
                ->format('Y-m-d'),

            'end_date' => $endDate
                ->format('Y-m-d'),
        ];
    }
}
