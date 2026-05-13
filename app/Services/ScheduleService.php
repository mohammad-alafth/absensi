<?php

namespace App\Services;

use App\Models\EmployeeShift;
use Carbon\Carbon;

class ScheduleService
{
    public static function getTodaySchedule($user)
    {
        $today = now()->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | SHIFT EMPLOYEE
        |--------------------------------------------------------------------------
        */

        if ($user->work_type === 'shift') {

            $employeeShift = EmployeeShift::with('shift')

                ->where('user_id', $user->id)

                ->whereDate('start_date', '<=', $today)

                ->whereDate('end_date', '>=', $today)

                ->first();

            if (!$employeeShift) {

                return null;
            }

            return [

                'type' => 'shift',

                'shift_id' => $employeeShift->shift->id,

                'shift_name' => $employeeShift->shift->name,

                'start_time' => $employeeShift->shift->start_time,

                'end_time' => $employeeShift->shift->end_time,

                'grace_minutes' => $employeeShift->shift->grace_minutes,

                'is_overnight' => $employeeShift->shift->is_overnight,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | OFFICE 5
        |--------------------------------------------------------------------------
        */

        if ($user->work_type === 'office_5') {

            $day = Carbon::now()->dayOfWeekIso;

            // sabtu minggu libur
            if ($day >= 6) {

                return null;
            }

            return [

                'type' => 'office',

                'shift_name' => 'Office',

                'start_time' => '08:00:00',

                'end_time' => '17:00:00',

                'grace_minutes' => 15,

                'is_overnight' => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | OFFICE 6
        |--------------------------------------------------------------------------
        */

        if ($user->work_type === 'office_6') {

            $day = Carbon::now()->dayOfWeekIso;

            // minggu libur
            if ($day == 7) {

                return null;
            }

            return [

                'type' => 'office',

                'shift_name' => 'Office',

                'start_time' => '08:00:00',

                'end_time' => '16:00:00',

                'grace_minutes' => 15,

                'is_overnight' => false,
            ];
        }

        return null;
    }
}
