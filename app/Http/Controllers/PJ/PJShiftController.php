<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\User;
use App\Http\Requests\PJ\StoreMonthlyRosterRequest;
use App\Services\PJMonthlyRosterException;
use App\Services\PJMonthlyRosterService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PJShiftController extends Controller
{
    /**
     * Read-only monthly roster view for one employee under the current PJ.
     *
     * Draft edits are deliberately kept in the Blade/Alpine layer. This
     * action does not write schedules and does not call any write endpoint.
     */
    public function show(Request $request, User $user, int $year, int $month, PJMonthlyRosterService $rosterService)
    {
        $data = $this->monthlyMatrixData($request, $user, $year, $month, $rosterService);

        return view('pj.shifts.employee', [
            'employee' => $user,
            ...$data,
        ]);
    }

    public function data(Request $request, User $user, int $year, int $month, PJMonthlyRosterService $rosterService)
    {
        $data = $this->monthlyMatrixData($request, $user, $year, $month, $rosterService);

        return response()->json([
            'success' => true,
            'employee_id' => $user->id,
            'period' => sprintf('%04d-%02d', $year, $month),
            'calendarDays' => $data['calendarDays'],
            'initialDraft' => $data['initialDraft'],
            'revision' => $data['baseRevision'],
        ]);
    }

    private function monthlyMatrixData(Request $request, User $user, int $year, int $month, PJMonthlyRosterService $rosterService): array
    {
        $actor = $request->user();

        if (!$actor || !str_starts_with((string) $actor->role, 'pj_')) {
            abort(403);
        }

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            abort(404);
        }

        $divisionRoles = PJDashboardController::getDivisionRolesForUser($actor->role);

        // Route model binding must never be the only authorization boundary.
        if (!in_array($user->role, $divisionRoles, true)) {
            abort(404);
        }

        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth()->endOfDay();

        $rows = EmployeeShift::with('shift')
            ->where('user_id', $user->id)
            ->whereBetween('shift_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->orderBy('shift_date')
            ->orderBy('id')
            ->get();

        $rowsByDate = $rows->groupBy(fn (EmployeeShift $row) => Carbon::parse($row->shift_date)->toDateString());

        $attendanceByDate = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('tanggal', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->select(['tanggal', 'jam_masuk', 'jam_keluar'])
            ->get()
            ->keyBy(fn (Attendance $attendance) => Carbon::parse($attendance->tanggal)->toDateString());

        // Only the immediately preceding date can carry an overnight shift
        // into the selected month. No record is created or duplicated here.
        $previousDate = $periodStart->copy()->subDay()->toDateString();
        $previousRows = EmployeeShift::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $previousDate)
            ->orderBy('id')
            ->get()
            ->filter(fn (EmployeeShift $row) => $this->isOvernight($row));

        $calendarDays = [];
        $initialDraft = [];
        $cursor = $periodStart->copy()->startOfDay();

        while ($cursor->lte($periodEnd)) {
            $date = $cursor->toDateString();
            $dateRows = $rowsByDate->get($date, collect());
            $duplicate = $dateRows->count() > 1;
            $record = $duplicate ? null : $dateRows->first();
            $carryOverRows = $date === $periodStart->toDateString() ? $previousRows : collect();
            $carryOver = $carryOverRows->first();

            $status = $record
                ? 'scheduled'
                : ($carryOver ? 'carry_over' : 'unassigned');

            $attendance = $attendanceByDate->get($date);
            $shift = $record?->shift;
            $initialDraft[$date] = $record?->shift_id;

            $calendarDays[] = [
                'date' => $date,
                'day' => $cursor->day,
                'weekday' => $cursor->isoFormat('ddd'),
                'status' => $status,
                'shift_id' => $record?->shift_id,
                'shift_name' => $shift?->name,
                'start_time' => $record?->start_time,
                'end_time' => $record?->end_time,
                'is_overnight' => $record ? $this->isOvernight($record) : false,
                'carry_over' => $carryOver ? $this->serializeCarryOver($carryOver) : null,
                'duplicate_count' => $duplicate ? $dateRows->count() : 0,
                'attendance' => $attendance ? [
                    'check_in' => $attendance->jam_masuk
                        ? Carbon::parse($attendance->jam_masuk)->format('H:i')
                        : null,
                    'check_out' => $attendance->jam_keluar
                        ? Carbon::parse($attendance->jam_keluar)->format('H:i')
                        : null,
                ] : null,
            ];

            $cursor->addDay();
        }

        $leadingBlankCount = $periodStart->dayOfWeekIso - 1;
        $cellCount = $leadingBlankCount + count($calendarDays);
        $trailingBlankCount = (7 - ($cellCount % 7)) % 7;

        return [
            'year' => $year,
            'month' => $month,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'calendarDays' => $calendarDays,
            'initialDraft' => $initialDraft,
            'shiftOptions' => Shift::query()
                ->allowedForRoles($divisionRoles)
                ->orderBy('name')
                ->get()
                ->map(fn (Shift $shift) => $this->serializeShift($shift))
                ->values(),
            'leadingBlankCount' => $leadingBlankCount,
            'trailingBlankCount' => $trailingBlankCount,
            'storedShiftCount' => $rows->count(),
            'duplicateDates' => $rowsByDate
                ->filter(fn ($dateRows) => $dateRows->count() > 1)
                ->keys()
                ->values(),
            'baseRevision' => $rosterService->calculateRevision($user, $year, $month),
        ];
    }

    public function save(
        StoreMonthlyRosterRequest $request,
        User $user,
        int $year,
        int $month,
        PJMonthlyRosterService $rosterService
    ) {
        try {
            return response()->json($rosterService->save($request->user(), $user, $request->validated()));
        } catch (PJMonthlyRosterException $exception) {
            $body = [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors,
            ];

            if ($exception->errorCode !== null) {
                $body['code'] = $exception->errorCode;
            }
            if ($exception->revision !== null) {
                $body['revision'] = $exception->revision;
            } elseif ($exception->status === 422) {
                $body['revision'] = $rosterService->calculateRevision($user, $year, $month);
            }

            return response()->json($body, $exception->status);
        }
    }

    private function isOvernight(EmployeeShift $row): bool
    {
        if ($row->is_overnight) {
            return true;
        }

        if (!$row->start_time || !$row->end_time) {
            return false;
        }

        return Carbon::parse($row->end_time)->lessThan(Carbon::parse($row->start_time));
    }

    private function serializeShift(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'name' => $shift->name,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'is_overnight' => (bool) $shift->is_overnight
                || ($shift->start_time && $shift->end_time
                    && Carbon::parse($shift->end_time)->lessThan(Carbon::parse($shift->start_time))),
        ];
    }

    private function serializeCarryOver(EmployeeShift $row): array
    {
        return [
            'source_date' => Carbon::parse($row->shift_date)->toDateString(),
            'shift_name' => $row->shift?->name,
            'start_time' => $row->start_time,
            'end_time' => $row->end_time,
        ];
    }
}
