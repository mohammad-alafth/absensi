<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }


        $now = Carbon::now();


        $employeeShift = EmployeeShift::with('shift')

            ->where('user_id', $user->id)

            ->whereDate('shift_date', today())

            ->first();

        if (!$employeeShift) {

            return response()->json([

                'success' => false,

                'message' => 'Anda belum memiliki jadwal shift hari ini'

            ], 403);
        }

        $shift = $employeeShift->shift;

        /*
        |--------------------------------------------------------------------------
        | BUILD SHIFT DATETIME
        |--------------------------------------------------------------------------
        */

        $shiftStart = Carbon::parse(

            today()->format('Y-m-d') . ' ' .
                $shift->start_time
        );

        $shiftEnd = Carbon::parse(

            today()->format('Y-m-d') . ' ' .
                $shift->end_time
        );

        /*
        |--------------------------------------------------------------------------
        | SHIFT MALAM
        |--------------------------------------------------------------------------
        */

        if ($shift->is_overnight) {

            $shiftEnd->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | TOLERANSI
        |--------------------------------------------------------------------------
        */

        $lateLimit = $shiftStart
            ->copy()
            ->addMinutes(
                $shift->grace_minutes
            );

        /*
        |--------------------------------------------------------------------------
        | ATTENDANCE TODAY
        |--------------------------------------------------------------------------
        */

        $attendance = Attendance::where(

            'user_id',
            $user->id

        )

            ->whereDate(
                'tanggal',
                today()
            )

            ->first();

        /*
        |--------------------------------------------------------------------------
        | CHECK IN
        |--------------------------------------------------------------------------
        */

        if (!$attendance) {

            $status = 'hadir';

            $lateMinutes = 0;

            if ($now->gt($lateLimit)) {

                $status = 'terlambat';

                $lateMinutes =
                    $lateLimit->diffInMinutes($now);
            }

            Attendance::create([

                'user_id' => $user->id,

                'shift_id' => $shift->id,

                'tanggal' => today(),

                'jam_masuk' => $now,

                'latitude' => $request->latitude,

                'longitude' => $request->longitude,

                'status' => $status,

                'late_minutes' => $lateMinutes,

                'scheduled_checkin' => $shiftStart,

                'scheduled_checkout' => $shiftEnd
            ]);

            return response()->json([

                'success' => true,

                'type' => 'checkin',

                'message' => 'Check in berhasil'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SUDAH CHECKOUT
        |--------------------------------------------------------------------------
        */

        if ($attendance->jam_keluar) {

            return response()->json([

                'success' => false,

                'message' => 'Anda sudah checkout'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | BELUM WAKTU PULANG
        |--------------------------------------------------------------------------
        */

        if ($now->lt($shiftEnd)) {

            return response()->json([

                'success' => false,

                'message' => 'Belum jam pulang'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | OVERTIME
        |--------------------------------------------------------------------------
        */

        $overtime = 0;

        if ($now->gt($shiftEnd)) {

            $overtime =
                $shiftEnd->diffInMinutes($now);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECKOUT
        |--------------------------------------------------------------------------
        */

        $attendance->update([

            'jam_keluar' => $now,

            'overtime_minutes' => $overtime
        ]);

        return response()->json([

            'success' => true,

            'type' => 'checkout',

            'message' => 'Checkout berhasil'
        ]);
    }
}
