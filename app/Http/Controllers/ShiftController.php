<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\EmployeeShift;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShiftController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HALAMAN SHIFT
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $date = $request->date ?? now()->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | ROLE PJ
        |--------------------------------------------------------------------------
        */

        $role = auth()->user()->role;

        $targetRole = str_replace('pj_', '', $role);

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE
        |--------------------------------------------------------------------------
        */

        $employees = User::whereIn('role', [
            $targetRole,
            $role
        ])
            ->where('work_type', 'shift')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | SHIFT MASTER
        |--------------------------------------------------------------------------
        */

        $shifts = Shift::all();

        /*
        |--------------------------------------------------------------------------
        | PERIODE SHIFT
        |--------------------------------------------------------------------------
        */

        $period = $this->getShiftPeriod($date);

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE SHIFT
        |--------------------------------------------------------------------------
        */

        $employeeShifts = EmployeeShift::with([
            'user',
            'shift'
        ])
            ->whereDate(
                'start_date',
                $period['start_date']
            )
            ->whereDate(
                'end_date',
                $period['end_date']
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | IZIN
        |--------------------------------------------------------------------------
        */

        $approvedPermissions = Permission::whereDate(
            'tanggal',
            $date
        )
            ->where('status', 'approved')
            ->pluck('jenis', 'user_id');

        /*
        |--------------------------------------------------------------------------
        | CUTI
        |--------------------------------------------------------------------------
        */

        $approvedLeaves = Leave::whereDate(
            'start_date',
            '<=',
            $date
        )
            ->whereDate(
                'end_date',
                '>=',
                $date
            )
            ->where('status', 'approved')
            ->pluck('leave_type', 'user_id');

        /*
        |--------------------------------------------------------------------------
        | STATUS ATTENDANCE
        |--------------------------------------------------------------------------
        */

        foreach ($employeeShifts as $shift) {

            $shift->attendance_status = null;

            if (isset($approvedPermissions[$shift->user_id])) {

                $shift->attendance_status = 'izin';
            }

            if (isset($approvedLeaves[$shift->user_id])) {

                $shift->attendance_status = 'cuti';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | POPUP REMINDER SHIFT
        |--------------------------------------------------------------------------
        */

        $showReminder = false;

        $today = Carbon::today();

        $endPeriod = Carbon::parse(
            $period['end_date']
        );

        /*
        |--------------------------------------------------------------------------
        | H-2 SEBELUM PERIODE BERAKHIR
        |--------------------------------------------------------------------------
        */

        if (
            $today->gte(
                $endPeriod->copy()->subDays(2)
            )
        ) {

            /*
            |--------------------------------------------------------------------------
            | CEK APAKAH SHIFT PERIODE SELANJUTNYA SUDAH DIBUAT
            |--------------------------------------------------------------------------
            */

            $nextPeriod = $this->getShiftPeriod(
                $endPeriod->copy()->addDay()
            );

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
            |--------------------------------------------------------------------------
            | JIKA BELUM ADA SHIFT PERIODE SELANJUTNYA
            |--------------------------------------------------------------------------
            */

            if (!$nextShiftExist) {

                $showReminder = true;
            }
        }

        return view('shifts.index', compact(
            'employees',
            'shifts',
            'employeeShifts',
            'date',
            'approvedPermissions',
            'approvedLeaves',
            'showReminder',
            'period'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGN SHIFT
    |--------------------------------------------------------------------------
    */

    public function assign(Request $request)
    {
        $request->validate([

            'user_id' => 'required',
            'shift_id' => 'required',
            'shift_date' => 'required|date'

        ]);

        /*
        |--------------------------------------------------------------------------
        | PERIODE SHIFT
        |--------------------------------------------------------------------------
        */

        $period = $this->getShiftPeriod(
            $request->shift_date
        );

        $startDate = $period['start_date'];

        $endDate = $period['end_date'];

        /*
        |--------------------------------------------------------------------------
        | UPDATE / CREATE
        |--------------------------------------------------------------------------
        */

        EmployeeShift::updateOrCreate(

            [
                'user_id' => $request->user_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],

            [
                'shift_id' => $request->shift_id,
                'shift_date' => $request->shift_date,
            ]
        );

        return response()->json([
            'success' => true
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REALTIME DATA
    |--------------------------------------------------------------------------
    */

    public function data(Request $request)
    {
        $date = $request->date ?? now()->format('Y-m-d');

        $period = $this->getShiftPeriod($date);

        $employeeShifts = EmployeeShift::with([
            'user',
            'shift'
        ])
            ->whereDate(
                'start_date',
                $period['start_date']
            )
            ->whereDate(
                'end_date',
                $period['end_date']
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | IZIN
        |--------------------------------------------------------------------------
        */

        $permissions = Permission::whereDate(
            'tanggal',
            $date
        )
            ->where('status', 'approved')
            ->pluck('jenis', 'user_id');

        /*
        |--------------------------------------------------------------------------
        | CUTI
        |--------------------------------------------------------------------------
        */

        $leaves = Leave::whereDate(
            'start_date',
            '<=',
            $date
        )
            ->whereDate(
                'end_date',
                '>=',
                $date
            )
            ->where('status', 'approved')
            ->pluck('leave_type', 'user_id');

        return response()->json([
            'employeeShifts' => $employeeShifts,
            'permissions' => $permissions,
            'leaves' => $leaves
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET SHIFT PERIOD
    |--------------------------------------------------------------------------
    */

    private function getShiftPeriod($date)
    {
        $date = Carbon::parse($date);

        /*
        |--------------------------------------------------------------------------
        | TANGGAL GAJIAN
        |--------------------------------------------------------------------------
        */

        $payrollDay = 26;

        /*
        |--------------------------------------------------------------------------
        | JIKA >= 26
        |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | JIKA < 26
            |--------------------------------------------------------------------------
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
