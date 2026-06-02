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
        $user = auth()->user();
        if (
            !str_starts_with($user->role, 'pj_') ||
            $user->work_type !== 'shift'
        ) {
            abort(403, 'Anda tidak memiliki otoritas untuk mengakses halaman ini.');
        }
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

        $shifts = Shift::whereJsonContains('allowed_roles', $role)->get();

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
            ->whereHas('user', function ($query) use ($targetRole) {
                $query->where('role', $targetRole);
            })
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
        // 1. Ubah validasi: shift_id dibuat nullable agar bisa menerima perintah hapus
        $request->validate([
            'user_id' => 'required',
            'shift_id' => 'nullable|exists:shifts,id',
            'shift_date' => 'required|date'
        ]);

        $period = $this->getShiftPeriod($request->shift_date);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];

        // 2. LOGIKA BARU: Jika shift_id tidak ada, maka lakukan penghapusan (Unassign)
        if (!$request->shift_id) {
            EmployeeShift::where('user_id', $request->user_id)
                ->where('start_date', $startDate)
                ->where('end_date', $endDate)
                ->delete();

            return response()->json(['success' => true]);
        }

        // 3. LOGIKA LAMA (Tetap dipertahankan): Update atau Create jadwal
        $shift = Shift::findOrFail($request->shift_id);

        $employeeShift = EmployeeShift::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            [
                'shift_id' => $request->shift_id,
                'shift_date' => $request->shift_date,
                'assigned_by' => auth()->id(),
                'start_time' => $shift->start_time,
                'end_time' => $shift->end_time,
                'is_overnight' => $shift->is_overnight,
            ]
        );

        $employeeShift->touch();

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
        $user = auth()->user();

        if (
            !str_starts_with($user->role, 'pj_') ||
            $user->work_type !== 'shift'
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        $date = $request->date ?? now()->format('Y-m-d');
        $userRole = auth()->user()->role;
        $targetRole = str_replace('pj_', '', $userRole);
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
            ->whereHas('user', function ($query) use ($targetRole) {
                $query->where('role', $targetRole);
            })
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
