<?php

namespace App\Services;

use App\Models\EmployeeShift;
use Carbon\Carbon;

/**
 * Menghitung hari cuti efektif berdasarkan jadwal kerja.
 *
 * Hari tanpa jadwal TIDAK mengurangi kuota cuti:
 *   - office_5 : Senin - Jumat (Sabtu & Minggu libur)
 *   - office_6 : Senin - Sabtu (Minggu libur)
 *   - shift    : tanggal yang memiliki baris employee_shifts
 *
 * Contoh: cuti tgl 1 - 5 dengan jadwal hanya di tgl 1, 2, dan 5
 * (tgl 3 & 4 tanpa jadwal) -> terhitung 3 hari cuti.
 */
class LeaveDayCalculator
{
    /**
     * Hitung jumlah hari cuti efektif pada rentang tanggal.
     *
     * @param  \App\Models\User|\stdClass  $user  user dengan properti work_type & id
     * @param  \Carbon\Carbon|string  $startDate
     * @param  \Carbon\Carbon|string  $endDate
     * @return int
     */
    public static function count($user, $startDate, $endDate): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------
        | SHIFT: satu query untuk seluruh rentang
        |--------------------------------------------------------------------
        | Baris employee_shifts = jadwal kerja. Tanggal tanpa baris berarti
        | hari libur pegawai tersebut sehingga tidak dihitung sebagai cuti.
        */
        if ($user->work_type === 'shift') {
            return EmployeeShift::where('user_id', $user->id)
                ->whereBetween('shift_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ])
                ->pluck('shift_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->count();
        }

        $days = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (self::hasSchedule($user, $date)) {
                $days++;
            }
        }

        return $days;
    }

    /**
     * Apakah $date adalah hari kerja (punya jadwal) bagi $user?
     *
     * @param  \App\Models\User|\stdClass  $user  user dengan properti work_type & id
     * @param  \Carbon\Carbon|string  $date
     */
    public static function hasSchedule($user, $date): bool
    {
        $date = Carbon::parse($date);

        switch ($user->work_type) {
            case 'office_5':
                // Senin - Jumat; Sabtu & Minggu libur.
                return $date->dayOfWeekIso <= 5;

            case 'office_6':
                // Senin - Sabtu; Minggu libur.
                return $date->dayOfWeekIso <= 6;

            case 'shift':
                // Hari kerja = tanggal yang punya jadwal employee_shifts.
                return EmployeeShift::where('user_id', $user->id)
                    ->whereDate('shift_date', $date->toDateString())
                    ->exists();

            default:
                // work_type tidak dikenal -> perilaku lama: semua tanggal dihitung.
                return true;
        }
    }
}
