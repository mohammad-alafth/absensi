<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HRDLateReportService
{
    public function rows(Carbon $startDate, Carbon $endDate): Collection
    {
        $employees = User::whereNotIn('role', ['admin'])
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $attendances = Attendance::whereBetween('tanggal', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ])->get()->groupBy('user_id');

        return $employees->map(function (User $employee) use ($attendances) {
            $lateMinutes = 0;

            foreach ($attendances->get($employee->id, collect()) as $attendance) {
                // Match the existing HRD recap: stored lateness includes the
                // 15-minute grace period, which is excluded from the report.
                $lateMinutes += max((int) ($attendance->late_minutes ?? 0) - 15, 0);
            }

            return [
                'name' => $employee->name,
                'total_minutes' => $lateMinutes,
                'total_fine' => self::lateFine($lateMinutes),
            ];
        });
    }

    /**
     * Same policy as HRDRekapSheet: 30 minutes tolerance, then Rp10.000
     * for every started 5-minute block.
     */
    public static function lateFine(int $lateMinutes): int
    {
        return $lateMinutes <= 30
            ? 0
            : (int) ceil(($lateMinutes - 30) / 5) * 10000;
    }
}
