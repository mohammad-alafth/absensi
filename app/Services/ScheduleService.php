<?php

namespace App\Services;

use App\Models\EmployeeShift;
use Carbon\Carbon;

class ScheduleService
{
    /*
    |--------------------------------------------------------------------------
    | KONSTANTA WINDOW ABSENSI
    |--------------------------------------------------------------------------
    | Nilai di bawah ini dipakai bersama oleh ScheduleService, AttendanceController,
    | dan FaceController agar batas check-in/check-out selalu konsisten.
    |--------------------------------------------------------------------------
    */

    // Check-in diperbolehkan paling awal 2 jam sebelum jam masuk shift.
    public const EARLY_CHECKIN_HOURS = 2;

    // Check-in paling lambat 2 jam setelah jam masuk shift.
    public const LATE_CHECKIN_HOURS = 2;

    // Resolver shift tetap memakai jadwal sampai 6 jam setelah shift selesai
    // (agar user shift malam masih bisa check-out pagi harinya).
    public const POST_SHIFT_WINDOW_HOURS = 6;

    // Check-out baru bisa dilakukan mulai 5 menit sebelum jam selesai shift.
    public const CHECKOUT_GRACE_MINUTES = 5;

    // Batas toleransi keterlambatan default (15 menit).
    public const DEFAULT_GRACE_MINUTES = 15;

    /**
     * Ambil jadwal kerja hari ini (atau pada tanggal $forDate bila diisi,
     * mis. untuk keperluan export kalender per user di modul HRD).
     *
     * @param \App\Models\User|\stdClass $user user dengan properti work_type
     * @param string|\Carbon\Carbon|null $forDate tanggal jadwal yang diminta (opsional)
     */
    public static function getTodaySchedule($user, $forDate = null)
    {
        $base = $forDate === null
            ? Carbon::today()
            : Carbon::parse($forDate)->startOfDay();

        /*
        |--------------------------------------------------------------------------
        | OFFICE 5 (SENIN - JUMAT)
        |--------------------------------------------------------------------------
        */
        if ($user->work_type === 'office_5') {

            $day = $base->dayOfWeekIso;

            // sabtu minggu libur
            if ($day >= 6) {
                return null;
            }

            $shiftStart = $base->copy()->setTimeFromTimeString('08:00:00');
            $shiftEnd   = $base->copy()->setTimeFromTimeString('17:00:00');

            return [
                'type' => 'office',
                'shift_name' => 'Office',

                // jadwal masuk & keluar
                'start_time' => '08:00:00',
                'end_time'   => '17:00:00',

                'grace_minutes' => 15,
                'is_overnight' => false,

                'shift_start' => $shiftStart,
                'shift_end'   => $shiftEnd,
                'shift_date'  => $base->format('Y-m-d'),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | SHIFT (DYNAMIC)
        |--------------------------------------------------------------------------
        */
        if ($user->work_type === 'shift') {

            $now = Carbon::now();

            /*
            |------------------------------------------------------------------
            | PRIORITAS 1: Cek shift lintas hari (overnight) dari kemarin
            |------------------------------------------------------------------
            | Jika user punya shift lintas hari kemarin (mis. 22:00 - 06:00),
            | dan sekarang masih dalam window shift (2 jam sebelum mulai
            | sampai 6 jam setelah selesai), return shift tersebut.
            | Ini memastikan user bisa check-out pagi hari.
            |
            | Catatan: flag is_overnight TIDAK SELALU tersimpan pada baris
            | employee_shifts (data lama / bulk assign / perubahan shift oleh PJ
            | sering tidak menyertakannya). Karena itu baris juga dicocokkan
            | dari jam: bila end_time < start_time berarti shift melewati
            | tengah malam (overnight) walau flag-nya 0.
            |------------------------------------------------------------------
            */
            $yesterdayShift = EmployeeShift::with('shift')
                ->where('user_id', $user->id)
                ->whereDate('shift_date', $now->copy()->subDay()->format('Y-m-d'))
                ->get()
                ->first(function ($es) {
                    return (bool) $es->is_overnight
                        || self::isOvernightByTime($es->start_time, $es->end_time);
                });

            if ($yesterdayShift) {
                $isOvernight = (bool) $yesterdayShift->is_overnight
                    || self::isOvernightByTime($yesterdayShift->start_time, $yesterdayShift->end_time);

                $shiftStart = Carbon::parse(
                    $yesterdayShift->shift_date . ' ' . $yesterdayShift->start_time
                );
                $shiftEnd = Carbon::parse(
                    $yesterdayShift->shift_date . ' ' . $yesterdayShift->end_time
                );

                // Shift melewati tengah malam -> jam selesai jatuh di hari berikutnya.
                if (self::isOvernightByTime($yesterdayShift->start_time, $yesterdayShift->end_time)) {
                    $shiftEnd->addDay();
                }

                $maxCheckin  = $shiftStart->copy()->subHours(self::EARLY_CHECKIN_HOURS);
                $maxCheckout = $shiftEnd->copy()->addHours(self::POST_SHIFT_WINDOW_HOURS);

                if ($now->between($maxCheckin, $maxCheckout)) {
                    return [
                        'type' => 'shift',
                        'shift_id' => $yesterdayShift->shift->id,
                        'shift_name' => optional($yesterdayShift->shift)->name ?? 'Shift',
                        'start_time' => $yesterdayShift->start_time,
                        'end_time'   => $yesterdayShift->end_time,
                        'grace_minutes' => self::DEFAULT_GRACE_MINUTES,
                        'is_overnight' => $isOvernight,
                        'shift_start' => $shiftStart,
                        'shift_end'   => $shiftEnd,
                        'shift_date'  => $yesterdayShift->shift_date,
                        'invalid_window' => false,
                    ];
                }
            }

            /*
            |------------------------------------------------------------------
            | PRIORITAS 2: Cek shift hari ini
            |------------------------------------------------------------------
            */
            $todayShift = EmployeeShift::with('shift')
                ->where('user_id', $user->id)
                ->whereDate('shift_date', $now->format('Y-m-d'))
                ->first();

            if ($todayShift) {
                $shiftStart = Carbon::parse(
                    $todayShift->shift_date . ' ' . $todayShift->start_time
                );
                $shiftEnd = Carbon::parse(
                    $todayShift->shift_date . ' ' . $todayShift->end_time
                );

                // Deteksi lintas hari dari flag ATAU dari jam (end < start).
                // Pengaman untuk data yang flag is_overnight-nya tidak tersimpan.
                $isOvernight = (bool) $todayShift->is_overnight
                    || self::isOvernightByTime($todayShift->start_time, $todayShift->end_time);

                if (self::isOvernightByTime($todayShift->start_time, $todayShift->end_time)) {
                    $shiftEnd->addDay();
                }

                $maxCheckin  = $shiftStart->copy()->subHours(self::EARLY_CHECKIN_HOURS);
                $maxCheckout = $shiftEnd->copy()->addHours(self::POST_SHIFT_WINDOW_HOURS);

                $isValidWindow = $now->between($maxCheckin, $maxCheckout);

                return [
                    'type' => 'shift',
                    'shift_id' => $todayShift->shift->id,
                    'shift_name' => optional($todayShift->shift)->name ?? 'Shift',
                    'start_time' => $todayShift->start_time,
                    'end_time'   => $todayShift->end_time,
                    'grace_minutes' => self::DEFAULT_GRACE_MINUTES,
                    'is_overnight' => $isOvernight,
                    'shift_start' => $shiftStart,
                    'shift_end'   => $shiftEnd,
                    'shift_date'  => $todayShift->shift_date,
                    'invalid_window' => !$isValidWindow,
                ];
            }

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | OFFICE 6 (SENIN - SABTU)
        |--------------------------------------------------------------------------
        | Senin - Jumat : 08.00 - 16.00
        | Sabtu         : 08.00 - 13.00 (setengah hari)
        |--------------------------------------------------------------------------
        */
        if ($user->work_type === 'office_6') {

            $day = $base->dayOfWeekIso;

            // Minggu libur
            if ($day == 7) {
                return null;
            }

            // Sabtu jam kerja setengah hari 08.00 - 13.00
            $endTime = ($day == 6) ? '13:00:00' : '16:00:00';

            $shiftStart = $base->copy()->setTimeFromTimeString('08:00:00');
            $shiftEnd   = $base->copy()->setTimeFromTimeString($endTime);

            return [
                'type' => 'office',
                'shift_name' => 'Office',

                // jadwal masuk & keluar
                'start_time' => '08:00:00',
                'end_time'   => $endTime,

                'grace_minutes' => 15,
                'is_overnight' => false,

                'shift_start' => $shiftStart,
                'shift_end'   => $shiftEnd,
                'shift_date'  => $base->format('Y-m-d'),
            ];
        }

        return null;
    }

    /**
     * Deteksi shift lintas hari (overnight) berdasarkan jam.
     *
     * Bila jam selesai (end_time) lebih kecil dari jam mulai (start_time),
     * artinya shift melewati tengah malam, mis. 22:00 -> 06:00.
     * Digunakan sebagai pengaman karena flag is_overnight di employee_shifts
     * tidak selalu tersimpan (data lama / bulk assign / perubahan shift PJ).
     */
    private static function isOvernightByTime($startTime, $endTime): bool
    {
        if (empty($startTime) || empty($endTime)) {
            return false;
        }

        $start = Carbon::parse($startTime);
        $end   = Carbon::parse($endTime);

        return $end->lessThan($start);
    }
}
