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
        if (!str_starts_with($user->role, 'pj_') || $user->work_type !== 'shift') {
            abort(403, 'Anda tidak memiliki otoritas untuk mengakses halaman ini.');
        }

        $date = $request->date ?? now()->format('Y-m-d');
        $role = $user->role;
        $targetRole = str_replace('pj_', '', $role);

        $employees = User::whereIn('role', [$targetRole, $role])->where('work_type', 'shift')->get();
        $shifts = Shift::whereJsonContains('allowed_roles', $role)->get();
        $period = $this->getShiftPeriod($date);

        $employeeShifts = EmployeeShift::with('user')
            ->whereDate('start_date', $period['start_date'])
            ->whereDate('end_date', $period['end_date'])
            ->whereHas('user', function ($query) use ($targetRole) {
                $query->where('role', $targetRole);
            })
            ->get();

        $approvedPermissions = Permission::whereDate('tanggal', $date)->where('status', 'approved')->pluck('jenis', 'user_id');
        $approvedLeaves = Leave::whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->where('status', 'approved')->pluck('leave_type', 'user_id');

        $showReminder = false;
        if (Carbon::today()->gte(Carbon::parse($period['end_date'])->copy()->subDays(2))) {
            $nextPeriod = $this->getShiftPeriod(Carbon::parse($period['end_date'])->copy()->addDay());
            $nextShiftExist = EmployeeShift::whereDate('start_date', $nextPeriod['start_date'])
                ->whereDate('end_date', $nextPeriod['end_date'])->exists();
            if (!$nextShiftExist) {
                $showReminder = true;
            }
        }

        return view('shifts.index', compact('employees', 'shifts', 'employeeShifts', 'date', 'approvedPermissions', 'approvedLeaves', 'showReminder', 'period'));
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGN SHIFT (DENGAN CUSTOM JAM)
    |--------------------------------------------------------------------------
    */
    public function assign(Request $request)
    {
        \Log::info('Data diterima:', $request->all());

        // 1. Aksi Hapus (Tetap di atas)
        if ($request->boolean('is_delete')) {
            EmployeeShift::where('user_id', $request->user_id)
                ->where('shift_date', $request->shift_date)
                ->delete();
            return response()->json(['success' => true]);
        }

        // 2. Validasi ketat agar tidak ada data yang kosong/undefined
        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'shift_date' => 'required|date',
            'shift_id'   => 'required|exists:shifts,id',
            'start_time' => 'required',
            'end_time'   => 'required',
        ]);

        try {
            $period = $this->getShiftPeriod($request->shift_date);

            // Bersihkan format waktu menjadi H:i:s
            $startTime = \Carbon\Carbon::parse($request->start_time)->format('H:i:s');
            $endTime   = \Carbon\Carbon::parse($request->end_time)->format('H:i:s');

            // 3. Update atau Create
            // Menggunakan updateOrCreate sudah cukup, tidak perlu if event_id 
            // kecuali Anda benar-benar perlu menangani ID secara manual
            if ($request->event_id) {

                $shift = EmployeeShift::findOrFail($request->event_id);

                $shift->update([
                    'shift_id'      => $request->shift_id,
                    'shift_date'    => $request->shift_date,
                    'start_date'    => $period['start_date'],
                    'end_date'      => $period['end_date'],
                    'start_time'    => $startTime,
                    'end_time'      => $endTime,
                    'is_overnight'  => $request->is_overnight ?? 0,
                ]);
            } else {

                EmployeeShift::create([
                    'user_id'       => $request->user_id,
                    'shift_id'      => $request->shift_id,
                    'shift_date'    => $request->shift_date,
                    'start_date'    => $period['start_date'],
                    'end_date'      => $period['end_date'],
                    'assigned_by'   => auth()->id(),
                    'start_time'    => $startTime,
                    'end_time'      => $endTime,
                    'is_overnight'  => $request->is_overnight ?? 0,
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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

        $employeeShifts = EmployeeShift::with('user')
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
        $payrollDay = 26;
        if ($date->day >= $payrollDay) {
            $startDate = $date->copy()->day($payrollDay);
            $endDate = $startDate->copy()->addMonth()->subDay();
        } else {
            $startDate = $date->copy()->subMonth()->day($payrollDay);
            $endDate = $startDate->copy()->addMonth()->subDay();
        }
        return ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')];
    }


    public function calendarEvents(Request $request)
    {
        $user = auth()->user();

        if (!str_starts_with($user->role, 'pj_')) {
            return response()->json([], 403);
        }

        $targetRole = str_replace('pj_', '', $user->role);
        $rolesAllowed = [$targetRole, $user->role];

        $start = Carbon::parse($request->start)->format('Y-m-d');
        $end   = Carbon::parse($request->end)->format('Y-m-d');

        $employeeShifts = EmployeeShift::with(['user', 'shift'])
            ->whereBetween('shift_date', [$start, $end])
            ->whereHas('user', function ($query) use ($rolesAllowed) {
                $query->whereIn('role', $rolesAllowed);
            })
            ->get();

        $events = [];

        foreach ($employeeShifts as $es) {

            $startTime = Carbon::parse($es->start_time)->format('H:i');
            $endTime   = Carbon::parse($es->end_time)->format('H:i');

            $endDateTime = $es->is_overnight
                ? Carbon::parse($es->shift_date)
                ->addDay()
                ->format('Y-m-d') . 'T' . $endTime
                : $es->shift_date . 'T' . $endTime;

            $events[] = [
                'id' => $es->id,
                'title' => $es->user->name . ' - ' . ($es->shift->name ?? 'Shift'),
                'start' => $es->shift_date . 'T' . $startTime,
                'end' => $endDateTime,

                'extendedProps' => [
                    'user_id' => $es->user_id,
                    'shift_id' => $es->shift_id,
                    'shift_name' => $es->shift->name ?? null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_overnight' => (bool) $es->is_overnight,
                ]
            ];
        }

        return response()->json($events);
    }
}
