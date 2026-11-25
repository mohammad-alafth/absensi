<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\EmployeeShift;
use App\Http\Controllers\PJ\PJDashboardController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!str_starts_with($user->role, 'pj_')) {
            abort(403, 'Anda tidak memiliki otoritas untuk mengakses halaman ini.');
        }

        $date = $request->date ?? now()->format('Y-m-d');
        $role = $user->role;
        $divisionRoles = PJDashboardController::getDivisionRolesForUser($role);

        $employees = User::whereIn('role', $divisionRoles)->get();

        // Filter shift: hanya tampilkan shift yang allowed_roles-nya cocok
        // dengan salah satu role divisi PJ (termasuk alias pj_ dan wildcard pj_*).
        $shifts = Shift::allowedForRoles($divisionRoles)->get();

        return view('shifts.index', compact('employees', 'shifts', 'date'));
    }

    public function assign(Request $request)
    {
        $actor = $this->authorizePjActor();

        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'shift_date' => 'required|date',
        ]);

        $targets = $this->authorizeTargets([$request->integer('user_id')], $actor);

        if ($request->boolean('is_delete')) {
            EmployeeShift::where('user_id', $request->user_id)
                ->where('shift_date', $request->shift_date)
                ->delete();
            return response()->json(['success' => true]);
        }

        $request->validate([
            'shift_id'   => 'required|exists:shifts,id',
        ]);

        $shift = Shift::findOrFail($request->shift_id);
        $this->assertShiftAllowedForTargets($shift, $targets);

        try {
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

        $actor = $this->authorizePjActor();
        $targets = $this->authorizeTargets($request->input('user_ids', []), $actor);

        $shift = Shift::findOrFail($request->shift_id);
        $this->assertShiftAllowedForTargets($shift, $targets);

        try {
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
                            'is_overnight' => (bool) $shift->is_overnight,
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

        $actor = $this->authorizePjActor();
        $targets = $this->authorizeTargets($request->input('user_ids', []), $actor);

        $shifts = Shift::query()
            ->whereIn('id', array_values(array_filter($request->input('schedule', []))))
            ->get()
            ->keyBy('id');

        foreach (array_values(array_filter($request->input('schedule', []))) as $shiftId) {
            $shift = $shifts->get((int) $shiftId);
            if (!$shift) {
                throw ValidationException::withMessages([
                    'schedule' => 'Salah satu shift yang dipilih tidak ditemukan.',
                ]);
            }

            $this->assertShiftAllowedForTargets($shift, $targets);
        }

        try {
            foreach ($request->user_ids as $userId) {
                foreach ($request->schedule as $dateStr => $shiftId) {
                    if (!$shiftId) continue;

                    $shift = $shifts->get((int) $shiftId);

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
                            'is_overnight' => (bool) $shift->is_overnight,
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
        $actor = auth()->user();
        if (!$actor || !str_starts_with((string) $actor->role, 'pj_')) {
            abort(403);
        }

        $pjRole = $actor->role;
        $divisionRoles = PJDashboardController::getDivisionRolesForUser($pjRole);

        $rangeStart = $request->start
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfMonth()->startOfDay();
        // FullCalendar sends an exclusive end boundary.
        $rangeEnd = $request->end
            ? Carbon::parse($request->end)->startOfDay()->subDay()->endOfDay()
            : now()->endOfMonth()->endOfDay();

        $userIds = User::whereIn('role', $divisionRoles)->pluck('id');

        $employeeShifts = EmployeeShift::with(['user', 'shift'])
            ->whereIn('user_id', $userIds)
            ->whereBetween('shift_date', [
                $rangeStart->copy()->subDay()->toDateString(),
                $rangeEnd->toDateString(),
            ])
            ->orderBy('shift_date')
            ->orderBy('id')
            ->get();

        $events = [];

        foreach ($employeeShifts as $es) {
            $rosterDate = Carbon::parse($es->shift_date)->startOfDay();
            $isOvernight = (bool) $es->is_overnight
                || ($es->start_time && $es->end_time
                    && Carbon::parse($es->end_time)->lessThan(Carbon::parse($es->start_time)));
            $shiftEnd = $es->end_time
                ? Carbon::parse($rosterDate->toDateString() . ' ' . $es->end_time)
                : null;
            if ($shiftEnd && $isOvernight) {
                $shiftEnd->addDay();
            }

            $isCarryOver = $rosterDate->lt($rangeStart);
            if ($isCarryOver && (!$isOvernight || !$shiftEnd || $shiftEnd->lt($rangeStart))) {
                continue;
            }

            $eventStart = $isCarryOver ? $rangeStart->toDateString() : $rosterDate->toDateString();
            $shiftName = $es->shift->name ?? 'Shift';
            $events[] = [
                'id' => $isCarryOver ? $es->id . '-carry-over' : $es->id,
                'user_id' => $es->user_id,
                'title' => ($es->user->name ?? 'N/A') . ' (' . $shiftName . ')',
                'start' => $eventStart,
                'backgroundColor' => $this->getShiftColor($shiftName),
                'borderColor' => 'transparent',
                'extendedProps' => [
                    'employee_name' => $es->user->name ?? 'N/A',
                    'user_id' => $es->user_id,
                    'roster_id' => $es->id,
                    'roster_date' => $rosterDate->toDateString(),
                    'shift_id' => $es->shift_id,
                    'shift_name' => $shiftName,
                    'start_time' => $es->start_time,
                    'end_time' => $es->end_time,
                    'is_overnight' => $isOvernight,
                    'carry_over' => $isCarryOver,
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

    public function weeklySchedules(Request $request)
    {
        $actor = $this->authorizePjActor();
        $userIds = $request->input('user_ids', []);
        $startDate = $request->input('start_date');

        if (!is_array($userIds)) {
            throw ValidationException::withMessages([
                'user_ids' => 'Daftar user tidak valid.',
            ]);
        }

        if (empty($userIds) || empty($startDate)) {
            return response()->json([
                'success' => true,
                'schedules' => [],
            ]);
        }

        $targets = $this->authorizeTargets($userIds, $actor);
        $userIds = $targets->pluck('id')->all();

        $start = Carbon::parse($startDate)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $rows = EmployeeShift::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('shift_date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->get();

        $schedules = [];

        for ($i = 0; $i < 7; $i++) {

            $date = $start->copy()
                ->addDays($i)
                ->toDateString();

            $dayRows = $rows->filter(function ($row) use ($date) {
                return Carbon::parse($row->shift_date)
                    ->toDateString() === $date;
            });

            if ($dayRows->isEmpty()) {
                $schedules[$date] = '';
                continue;
            }

            $shiftIds = $dayRows
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values();

            if (
                $shiftIds->count() === 1 &&
                $dayRows->pluck('user_id')->unique()->count() === count($userIds)
            ) {
                $schedules[$date] = (string) $shiftIds->first();
            } else {
                $schedules[$date] = '';
            }
        }

        return response()->json([
            'success' => true,
            'schedules' => $schedules,
        ]);
    }

    /**
     * Assignment legacy endpoints use the same PJ contract as the P14 roster
     * service: exact pj_* actor and division scope from ApprovalFlowService.
     */
    private function authorizePjActor(): User
    {
        $actor = auth()->user();

        if (!$actor || !str_starts_with((string) $actor->role, 'pj_')) {
            abort(403, 'Anda tidak memiliki otoritas untuk mengatur shift.');
        }

        return $actor;
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function authorizeTargets(array $userIds, User $actor)
    {
        $uniqueIds = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $targets = User::query()->whereIn('id', $uniqueIds->all())->get();
        $divisionRoles = PJDashboardController::getDivisionRolesForUser($actor->role);

        if (
            $targets->count() !== $uniqueIds->count()
            || $targets->contains(fn (User $target) => !in_array($target->role, $divisionRoles, true))
        ) {
            abort(404, 'Target shift tidak ditemukan.');
        }

        return $targets;
    }

    private function assertShiftAllowedForTargets(Shift $shift, $targets): void
    {
        // Null/empty allowed_roles retains the existing unrestricted behavior.
        foreach ($targets as $target) {
            if (!$shift->allowsRole($target->role)) {
                throw ValidationException::withMessages([
                    'shift_id' => "Shift tidak diizinkan untuk role target {$target->role}.",
                ]);
            }
        }
    }
}
