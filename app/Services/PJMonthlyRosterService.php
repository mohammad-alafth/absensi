<?php

namespace App\Services;

use App\Http\Controllers\PJ\PJDashboardController;
use App\Models\Attendance;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PJMonthlyRosterException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly array $errors = [],
        public readonly ?string $errorCode = null,
        public readonly ?string $revision = null,
        string $message = 'Roster tidak dapat disimpan.'
    ) {
        parent::__construct($message);
    }
}

class PJMonthlyRosterService
{
    public function calculateRevision(User $employee, int $year, int $month): string
    {
        $periodStart = $this->periodStart($year, $month);
        $rows = $this->revisionRows($employee->id, $periodStart);

        return $this->hashRevision($employee->id, $year, $month, $rows);
    }

    public function save(User $actor, User $routeEmployee, array $payload): array
    {
        $year = (int) $payload['year'];
        $month = (int) $payload['month'];
        $this->authorizeTarget($actor, $routeEmployee, (int) $payload['employee_id']);

        $entries = collect($payload['entries'])->keyBy('date');
        $shiftIds = $entries->pluck('shift_id')->filter()->unique()->values();
        $shifts = Shift::query()->whereIn('id', $shiftIds)->get()->keyBy('id');
        $this->validateShifts($routeEmployee, $entries, $shifts);

        try {
            return DB::transaction(function () use ($actor, $routeEmployee, $payload, $entries, $shifts, $year, $month): array {
                $employee = User::query()->whereKey($routeEmployee->id)->lockForUpdate()->first();
                if (!$employee) {
                    throw new PJMonthlyRosterException(404, [], null, null, 'Pegawai tidak ditemukan.');
                }

                $this->authorizeTarget($actor, $employee, (int) $payload['employee_id']);
                $periodStart = $this->periodStart($year, $month);
                $periodEnd = $periodStart->copy()->endOfMonth();
                $rows = $this->lockedRows($employee->id, $periodStart, $periodEnd);
                $this->rejectDuplicates($rows);

                $currentRevision = $this->hashRevision($employee->id, $year, $month, $this->revisionRowsFromCollection($rows, $periodStart));
                if (!hash_equals((string) $payload['base_revision'], $currentRevision)) {
                    throw new PJMonthlyRosterException(
                        409,
                        [],
                        'roster_revision_conflict',
                        $currentRevision,
                        'Roster berubah setelah halaman dibuka. Muat ulang data sebelum menyimpan.'
                    );
                }

                $this->validateUnchangedEntries($entries, $rows);
                $changedDates = $this->changedDates($entries, $rows);
                $this->rejectAttendanceConflicts($employee->id, $changedDates, $entries, $rows, $shifts);
                $this->rejectAdjacentOverlaps($entries, $rows, $shifts, $changedDates, $periodStart, $periodEnd);

                $savedDates = [];
                $unchangedDates = [];
                foreach ($entries->sortKeys() as $date => $entry) {
                    if ($entry['operation'] === 'unchanged') {
                        $unchangedDates[] = $date;
                        continue;
                    }

                    $shift = $shifts->get((int) $entry['shift_id']);
                    $values = $this->snapshotValues($shift, $actor->id);
                    $existing = $rows->get($date, collect());

                    if ($existing->count() === 1) {
                        $existing->first()->update($values);
                    } else {
                        EmployeeShift::create(array_merge([
                            'user_id' => $employee->id,
                            'shift_date' => $date,
                        ], $values));
                    }
                    $savedDates[] = $date;
                }

                $newRows = $this->revisionRows($employee->id, $periodStart);

                return [
                    'success' => true,
                    'employee_id' => $employee->id,
                    'period' => sprintf('%04d-%02d', $year, $month),
                    'saved_dates' => $savedDates,
                    'unchanged_dates' => $unchangedDates,
                    'revision' => $this->hashRevision($employee->id, $year, $month, $newRows),
                    'errors' => [],
                ];
            });
        } catch (PJMonthlyRosterException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('PJ monthly roster save failed.', [
                'employee_id' => $routeEmployee->id,
                'actor_id' => $actor->id,
                'exception' => $exception,
            ]);

            throw new PJMonthlyRosterException(500, [], null, null, 'Roster tidak dapat disimpan saat ini.');
        }
    }

    private function authorizeTarget(User $actor, User $employee, int $payloadEmployeeId): void
    {
        if (!str_starts_with((string) $actor->role, 'pj_')) {
            throw new PJMonthlyRosterException(403, [], null, null, 'Anda tidak memiliki otoritas untuk menyimpan roster.');
        }

        $roles = PJDashboardController::getDivisionRolesForUser($actor->role);
        if ($employee->id !== $payloadEmployeeId || !in_array($employee->role, $roles, true)) {
            throw new PJMonthlyRosterException(404, [], null, null, 'Target roster tidak ditemukan.');
        }
    }

    private function validateShifts(User $employee, Collection $entries, Collection $shifts): void
    {
        $errors = [];
        foreach ($entries as $date => $entry) {
            $shift = $shifts->get((int) ($entry['shift_id'] ?? 0));
            if ($entry['operation'] === 'unchanged' && ($entry['shift_id'] ?? null) === null) {
                continue;
            }
            if (!$shift) {
                $errors[$date][] = 'Shift yang dipilih tidak ditemukan.';
                continue;
            }

            $allowedRoles = $shift->allowed_roles ?? [];
            if (!in_array($employee->role, $allowedRoles, true)) {
                $errors[$date][] = 'Shift tidak diizinkan untuk pegawai ini.';
            }
        }

        if ($errors) {
            throw new PJMonthlyRosterException(422, $errors);
        }
    }

    private function validateUnchangedEntries(Collection $entries, Collection $rows): void
    {
        $errors = [];
        foreach ($entries as $date => $entry) {
            if ($entry['operation'] !== 'unchanged') {
                continue;
            }

            $dateRows = $rows->get($date, collect());
            $shiftId = $entry['shift_id'] ?? null;
            if ($dateRows->count() === 0 && $shiftId === null) {
                continue;
            }

            if ($dateRows->count() === 1 && $shiftId !== null && (int) $dateRows->first()->shift_id === (int) $shiftId) {
                continue;
            }

            if ($dateRows->count() === 1 && $shiftId === null) {
                $errors[$date][] = 'Pengosongan roster yang sudah tersimpan belum didukung.';
                continue;
            }

            if ($dateRows->count() !== 1 || (int) $dateRows->first()->shift_id !== (int) $shiftId) {
                $errors[$date][] = 'Entry unchanged tidak sesuai dengan roster tersimpan.';
            }
        }

        if ($errors) {
            throw new PJMonthlyRosterException(422, $errors);
        }
    }

    private function rejectDuplicates(Collection $rows): void
    {
        $errors = [];
        foreach ($rows as $date => $dateRows) {
            if ($dateRows->count() > 1) {
                $errors[$date][] = 'Terdapat lebih dari satu roster pada tanggal ini.';
            }
        }

        if ($errors) {
            throw new PJMonthlyRosterException(422, $errors, 'duplicate_roster');
        }
    }

    private function rejectAttendanceConflicts(int $employeeId, array $changedDates, Collection $entries, Collection $rows, Collection $shifts): void
    {
        $attendanceDates = [];
        foreach ($changedDates as $date) {
            $attendanceDates[$date] = true;
            $old = $rows->get($date, collect())->first();
            $new = $shifts->get((int) ($entries->get($date)['shift_id'] ?? 0));
            if ($this->isOvernight($old) || $this->isOvernight($new)) {
                $attendanceDates[Carbon::parse($date)->addDay()->toDateString()] = true;
            }
        }

        if (!$attendanceDates) {
            return;
        }

        $query = Attendance::query()->where('user_id', $employeeId)->where(function ($query) use ($attendanceDates): void {
            foreach (array_keys($attendanceDates) as $date) {
                $query->orWhereDate('tanggal', $date);
            }
        });

        if ($query->exists()) {
            $errors = [];
            foreach (array_keys($attendanceDates) as $date) {
                $errors[$date][] = 'Tanggal sudah memiliki attendance dan tidak dapat diubah.';
            }
            throw new PJMonthlyRosterException(422, $errors);
        }
    }

    private function rejectAdjacentOverlaps(Collection $entries, Collection $rows, Collection $shifts, array $changedDates, Carbon $periodStart, Carbon $periodEnd): void
    {
        $errors = [];
        foreach ($changedDates as $date) {
            $current = $shifts->get((int) $entries->get($date)['shift_id']);
            $nextDate = Carbon::parse($date)->addDay()->toDateString();
            $previousDate = Carbon::parse($date)->subDay()->toDateString();
            $next = $this->effectiveShift($nextDate, $entries, $rows, $shifts);
            $previous = $this->effectiveShift($previousDate, $entries, $rows, $shifts);

            if ($next && $this->intervalsOverlap($date, $current, $nextDate, $next)) {
                $errors[$date][] = 'Jadwal bertabrakan dengan roster pada tanggal berikutnya.';
            }
            if ($previous && $this->intervalsOverlap($previousDate, $previous, $date, $current)) {
                $errors[$date][] = 'Jadwal bertabrakan dengan roster pada tanggal sebelumnya.';
            }
        }

        if ($errors) {
            throw new PJMonthlyRosterException(422, $errors);
        }
    }

    private function effectiveShift(string $date, Collection $entries, Collection $rows, Collection $shifts): ?Shift
    {
        $entry = $entries->get($date);
        if ($entry && $entry['operation'] === 'upsert') {
            return $shifts->get((int) $entry['shift_id']);
        }

        return $rows->get($date, collect())->count() === 1
            ? $rows->get($date)->first()->shift
            : null;
    }

    private function intervalsOverlap(string $leftDate, EmployeeShift|Shift $left, string $rightDate, EmployeeShift|Shift $right): bool
    {
        if (!$left->start_time || !$left->end_time || !$right->start_time || !$right->end_time) {
            return false;
        }

        $leftStart = Carbon::parse($leftDate . ' ' . $left->start_time);
        $leftEnd = Carbon::parse($leftDate . ' ' . $left->end_time);
        if ($this->isOvernight($left)) {
            $leftEnd->addDay();
        }

        $rightStart = Carbon::parse($rightDate . ' ' . $right->start_time);
        $rightEnd = Carbon::parse($rightDate . ' ' . $right->end_time);
        if ($this->isOvernight($right)) {
            $rightEnd->addDay();
        }

        return $leftStart->lt($rightEnd) && $rightStart->lt($leftEnd);
    }

    private function changedDates(Collection $entries, Collection $rows): array
    {
        return $entries->filter(function (array $entry, string $date) use ($rows): bool {
            if ($entry['operation'] !== 'upsert') {
                return false;
            }
            $existing = $rows->get($date, collect());
            return $existing->count() !== 1 || (int) $existing->first()->shift_id !== (int) $entry['shift_id'];
        })->keys()->values()->all();
    }

    private function snapshotValues(Shift $shift, int $actorId): array
    {
        return [
            'shift_id' => $shift->id,
            'assigned_by' => $actorId,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'is_overnight' => $this->isOvernight($shift),
        ];
    }

    private function lockedRows(int $employeeId, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $previousDate = $periodStart->copy()->subDay()->toDateString();
        return EmployeeShift::query()
            ->where('user_id', $employeeId)
            ->whereBetween('shift_date', [$previousDate, $periodEnd->copy()->addDay()->toDateString()])
            ->with('shift')
            ->orderBy('shift_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->groupBy(fn (EmployeeShift $row) => Carbon::parse($row->shift_date)->toDateString());
    }

    private function revisionRows(int $employeeId, Carbon $periodStart): Collection
    {
        $previousDate = $periodStart->copy()->subDay()->toDateString();
        $periodEnd = $periodStart->copy()->endOfMonth()->toDateString();
        $rows = EmployeeShift::query()
            ->where('user_id', $employeeId)
            ->whereBetween('shift_date', [$previousDate, $periodEnd])
            ->orderBy('shift_date')
            ->orderBy('id')
            ->get();

        return $rows->filter(function (EmployeeShift $row) use ($periodStart): bool {
            return Carbon::parse($row->shift_date)->gte($periodStart) || $this->isOvernight($row);
        })->values();
    }

    private function revisionRowsFromCollection(Collection $rows, Carbon $periodStart): Collection
    {
        return $rows->flatten(1)->filter(function (EmployeeShift $row) use ($periodStart): bool {
            return Carbon::parse($row->shift_date)->gte($periodStart) || $this->isOvernight($row);
        })->sortBy(fn (EmployeeShift $row) => [$row->shift_date, $row->id])->values();
    }

    private function hashRevision(int $employeeId, int $year, int $month, Collection $rows): string
    {
        $normalized = $rows->map(fn (EmployeeShift $row) => [
            'id' => (int) $row->id,
            'shift_date' => Carbon::parse($row->shift_date)->toDateString(),
            'shift_id' => $row->shift_id === null ? null : (int) $row->shift_id,
            'start_time' => $this->normalizeTime($row->start_time),
            'end_time' => $this->normalizeTime($row->end_time),
            'is_overnight' => (bool) $row->is_overnight,
            'updated_at' => $row->updated_at?->toISOString(),
        ])->values()->all();

        return 'sha256:' . hash('sha256', json_encode([
            'employee_id' => $employeeId,
            'year' => $year,
            'month' => $month,
            'rows' => $normalized,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function normalizeTime(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse((string) $value)->format('H:i:s');
    }

    private function periodStart(int $year, int $month): Carbon
    {
        return Carbon::create($year, $month, 1)->startOfDay();
    }

    private function isOvernight(EmployeeShift|Shift|null $row): bool
    {
        if (!$row) {
            return false;
        }
        if ((bool) $row->is_overnight) {
            return true;
        }
        if (!$row->start_time || !$row->end_time) {
            return false;
        }
        return Carbon::parse($row->end_time)->lessThan(Carbon::parse($row->start_time));
    }
}
