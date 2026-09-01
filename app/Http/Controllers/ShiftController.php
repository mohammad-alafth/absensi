<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\EmployeeShift;
use App\Http\Controllers\PJ\PJDashboardController;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!str_starts_with($user->role, 'pj_') && $user->work_type !== 'shift') {
            abort(403, 'Anda tidak memiliki otoritas untuk mengakses halaman ini.');
        }

        $date = $request->date ?? now()->format('Y-m-d');
        $role = $user->role;
        $divisionRoles = PJDashboardController::getDivisionRolesForUser($role);

        $employees = User::whereIn('role', $divisionRoles)->get();
        $shifts = Shift::all();

        return view('shifts.index', compact('employees', 'shifts', 'date'));
    }

    public function assign(Request $request)
    {
        if ($request->boolean('is_delete')) {
            EmployeeShift::where('user_id', $request->user_id)
                ->where('shift_date', $request->shift_date)
                ->delete();
            return response()->json(['success' => true]);
        }

        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'shift_date' => 'required|date',
            'shift_id'   => 'required|exists:shifts,id',
        ]);

        try {
            $shift = Shift::findOrFail($request->shift_id);
            $shiftDate = Carbon::parse($request->shift_date)->format('Y-m-d');
            $year = Carbon::parse($shiftDate)->year;
            $month = Carbon::parse($shiftDate)->month;

            $startTime = $request->start_time ?? $shift->start_time;
            $endTime   = $request->end_time ?? $shift->end_time;

            EmployeeShift::updateOrCreate(
                [
                    'user_id'    => $request->user_id,
                    'shift_date' => $shiftDate,
                ],
                [
                    'shift_id'     => $shift->id,
                    'month'        => $month,
                    'year'         => $year,
                    'assigned_by'  => auth()->id(),
                    'start_time'   => $startTime,
                    'end_time'     => $endTime,
                    'is_overnight' => $request->is_overnight ?? 0,
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'shift_id'   => 'required|exists:shifts,id',
        ]);

        try {
            $shift = Shift::findOrFail($request->shift_id);
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            foreach ($request->user_ids as $userId) {
                $curr = $startDate->copy();
                while ($curr->lte($endDate)) {
                    $shiftDate = $curr->format('Y-m-d');
                    EmployeeShift::updateOrCreate(
                        [
                            'user_id'    => $userId,
                            'shift_date' => $shiftDate,
                        ],
                        [
                            'shift_id'     => $shift->id,
                            'month'        => $curr->month,
                            'year'         => $curr->year,
                            'assigned_by'  => auth()->id(),
                            'start_time'   => $shift->start_time,
                            'end_time'     => $shift->end_time,
                        ]
                    );
                    $curr->addDay();
                }
            }

            return response()->json(['success' => true, 'message' => 'Jadwal shift berhasil diterapkan masal.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function weeklyAssign(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'schedule' => 'required|array', // ['YYYY-MM-DD' => shift_id]
        ]);

        try {
            foreach ($request->user_ids as $userId) {
                foreach ($request->schedule as $dateStr => $shiftId) {
                    if (!$shiftId) continue;

                    $shift = Shift::find($shiftId);
                    if (!$shift) continue;

                    $currDate = Carbon::parse($dateStr);

                    EmployeeShift::updateOrCreate(
                        [
                            'user_id'    => $userId,
                            'shift_date' => $currDate->format('Y-m-d'),
                        ],
                        [
                            'shift_id'     => $shift->id,
                            'month'        => $currDate->month,
                            'year'         => $currDate->year,
                            'assigned_by'  => auth()->id(),
                            'start_time'   => $shift->start_time,
                            'end_time'     => $shift->end_time,
                        ]
                    );
                }
            }

            return response()->json(['success' => true, 'message' => 'Jadwal mingguan berhasil disimpan!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function calendarEvents(Request $request)
    {
        $pjRole = auth()->user()->role;
        $divisionRoles = PJDashboardController::getDivisionRolesForUser($pjRole);

        $start = $request->start ? Carbon::parse($request->start)->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
        $end = $request->end ? Carbon::parse($request->end)->format('Y-m-d') : now()->endOfMonth()->format('Y-m-d');

        $userIds = User::whereIn('role', $divisionRoles)->pluck('id');

        $employeeShifts = EmployeeShift::with(['user', 'shift'])
            ->whereIn('user_id', $userIds)
            ->whereBetween('shift_date', [$start, $end])
            ->get();

        $events = [];

        foreach ($employeeShifts as $es) {
            $events[] = [
                'id' => $es->id,
                'user_id' => $es->user_id,
                'title' => ($es->user->name ?? 'N/A') . ' (' . ($es->shift->name ?? 'Shift') . ')',
                'start' => $es->shift_date,
                'backgroundColor' => $this->getShiftColor($es->shift->name ?? ''),
                'borderColor' => 'transparent',
                'extendedProps' => [
                    'shift_name' => $es->shift->name ?? '-',
                    'start_time' => $es->start_time,
                    'end_time' => $es->end_time,
                ]
            ];
        }

        return response()->json($events);
    }

    private function getShiftColor($name)
    {
        $name = strtolower($name);
        if (str_contains($name, 'pagi')) return '#10b981'; // Green/Emerald
        if (str_contains($name, 'siang')) return '#f59e0b'; // Amber/Orange
        if (str_contains($name, 'malam')) return '#6366f1'; // Indigo/Purple
        if (str_contains($name, 'libur')) return '#ef4444'; // Red
        return '#1E40AF'; // Default Blue
    }
}
