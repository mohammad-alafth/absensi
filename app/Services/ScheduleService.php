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
        | OFFICE 5
        |--------------------------------------------------------------------------
        */
        $shiftStart = Carbon::today()->setTimeFromTimeString('08:00:00');

        $shiftEnd = Carbon::today()->setTimeFromTimeString('17:00:00');

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

                'shift_start' => $shiftStart,

                'shift_end' => $shiftEnd,

                'shift_date' => today()->format('Y-m-d'),
            ];
        }
        if ($user->work_type === 'shift') {

            $now = Carbon::now();

            /*
    |--------------------------------------------------------------------------
    | CARI SHIFT HARI INI / KEMARIN
    |--------------------------------------------------------------------------
    */

            $employeeShift = EmployeeShift::with('shift')

                ->where('user_id', $user->id)

                ->where(function ($q) use ($now) {

                    $q->whereDate('shift_date', $now->format('Y-m-d'))

                        ->orWhereDate(
                            'shift_date',
                            $now->copy()->subDay()->format('Y-m-d')
                        );
                })

                ->orderByDesc('shift_date')
                ->orderByDesc('start_time')
                ->first();

            if (!$employeeShift) {

                return null;
            }

            /*
    |--------------------------------------------------------------------------
    | BUILD DATETIME SHIFT
    |--------------------------------------------------------------------------
    */

            $shiftStart = Carbon::parse(
                $employeeShift->shift_date . ' ' .
                    $employeeShift->start_time
            );

            $shiftEnd = Carbon::parse(
                $employeeShift->shift_date . ' ' .
                    $employeeShift->end_time
            );

            /*
    |--------------------------------------------------------------------------
    | SHIFT MALAM
    |--------------------------------------------------------------------------
    */

            if ($employeeShift->is_overnight) {

                $shiftEnd->addDay();
            }

            /*
    |--------------------------------------------------------------------------
    | VALIDASI APAKAH SEKARANG MASUK RANGE SHIFT
    |--------------------------------------------------------------------------
    */

            $maxCheckin = $shiftStart->copy()->subHours(2);

            $maxCheckout = $shiftEnd->copy()->addHours(6);

            $isValidWindow = $now->between(
                $maxCheckin,
                $maxCheckout
            );

            return [

                'type' => 'shift',

                'shift_id' => $employeeShift->shift->id,

                'shift_name' => $employeeShift->shift->name,

                'start_time' => $employeeShift->start_time,

                'end_time' => $employeeShift->end_time,

                'grace_minutes' => $employeeShift->shift->grace_minutes,

                'is_overnight' => $employeeShift->is_overnight,

                'shift_start' => $shiftStart,

                'shift_end' => $shiftEnd,

                'shift_date' => $employeeShift->shift_date,

                'invalid_window' => !$isValidWindow,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | OFFICE 6
        |--------------------------------------------------------------------------
        */
        $shiftStart = Carbon::today()->setTimeFromTimeString('08:00:00');

        $shiftEnd = Carbon::today()->setTimeFromTimeString('16:00:00');

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

                'end_time' => '17:00:00',

                'grace_minutes' => 15,

                'is_overnight' => false,

                'shift_start' => $shiftStart,

                'shift_end' => $shiftEnd,

                'shift_date' => today()->format('Y-m-d'),
            ];
        }

        return null;
    }
}
