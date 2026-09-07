<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Services\ScheduleService;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | SCHEDULE
        |--------------------------------------------------------------------------
        */
        $schedule = ScheduleService::getTodaySchedule($user);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Hari ini anda libur'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | BUILD SHIFT DATETIME (dari ScheduleService - sudah handle cross-day)
        |--------------------------------------------------------------------------
        */
        $shiftStart = $schedule['shift_start'];
        $shiftEnd = $schedule['shift_end'];
        $shiftDate = $schedule['shift_date'];

        /*
        |--------------------------------------------------------------------------
        | GRACE PERIOD
        |--------------------------------------------------------------------------
        */
        $graceMinutes = $schedule['grace_minutes'] ?? 15;
        $lateLimit = $shiftStart->copy()->addMinutes($graceMinutes);

        /*
        |--------------------------------------------------------------------------
        | ATTENDANCE - Cari berdasarkan shift_date (bukan today)
        |--------------------------------------------------------------------------
        | Untuk cross-day shift, shift_date adalah tanggal mulai shift (kemarin).
        | Jadi kita cari attendance berdasarkan shift_date dari schedule.
        |--------------------------------------------------------------------------
        */
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', $shiftDate)
            ->orderByDesc('jam_masuk')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | CHECK IN
        |--------------------------------------------------------------------------
        */
        if (!$attendance) {
            $status = 'hadir';
            $lateMinutes = 0;

            /*
            |--------------------------------------------------------------------------
            | CEK KETERLAMBATAN
            |--------------------------------------------------------------------------
            */
            if ($now->gt($lateLimit)) {
                $status = 'terlambat';

                /*
                |--------------------------------------------------------------------------
                | HITUNG TELAT SETELAH BATAS TOLERANSI
                |--------------------------------------------------------------------------
                */
                $lateMinutes = $lateLimit->diffInMinutes($now);
            }

            // Check-in diperbolehkan paling awal 2 jam sebelum jam masuk shift
            // dan paling lambat 2 jam setelah jam masuk shift.
            // (diubah dari 1 jam -> 2 jam agar karyawan yang datang lebih awal
            // tidak ditolak / kena error "diluar jam absensi" saat jadwal aktif)
            $checkinStart = $shiftStart->copy()->subHours(ScheduleService::EARLY_CHECKIN_HOURS);
            $checkinEnd   = $shiftStart->copy()->addHours(ScheduleService::LATE_CHECKIN_HOURS);

            if ($now->lt($checkinStart)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum masuk jam absensi (absensi dibuka mulai '
                        . ScheduleService::EARLY_CHECKIN_HOURS . ' jam sebelum jam masuk)'
                ], 403);
            }

            if ($now->gt($checkinEnd)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Diluar jam checkin (maksimal '
                        . ScheduleService::LATE_CHECKIN_HOURS . ' jam setelah jam masuk)'
                ], 403);
            }

            Attendance::create([
                'user_id' => $user->id,
                'tanggal' => $shiftDate,
                'jam_masuk' => $now,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'scheduled_checkin' => $shiftStart,
                'scheduled_checkout' => $shiftEnd
            ]);

            return response()->json([
                'success' => true,
                'type' => 'checkin',
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'message' => $status === 'terlambat'
                    ? 'Check in berhasil (Terlambat ' . $lateMinutes . ' menit)'
                    : 'Check in berhasil'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SUDAH CHECKOUT
        |--------------------------------------------------------------------------
        */
        if ($attendance->jam_keluar) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah checkout'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | BELUM WAKTU PULANG
        |--------------------------------------------------------------------------
        | Check-out baru diperbolehkan mulai 5 menit sebelum jam selesai shift.
        */
        $checkoutLimit = $shiftEnd->copy()->subMinutes(ScheduleService::CHECKOUT_GRACE_MINUTES);

        if ($now->lt($checkoutLimit)) {
            return response()->json([
                'success' => false,
                'message' => 'Belum waktu checkout (baru bisa 5 menit sebelum jam pulang)'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | REALITAS OVERTIME MURNI PRESENSI
        |--------------------------------------------------------------------------
        | Dihitung murni sejak menit pertama kelebihan setelah jadwal pulang shift selesai.
        */
        $overtimeMinutes = 0;

        if ($now->gt($shiftEnd)) {
            $overtimeMinutes = $shiftEnd->diffInMinutes($now);
        }

        /*
        |--------------------------------------------------------------------------
        | PROCESS CHECKOUT
        |--------------------------------------------------------------------------
        */
        $attendance->update([
            'jam_keluar' => $now,
            'overtime_minutes' => $overtimeMinutes
        ]);

        return response()->json([
            'success' => true,
            'type' => 'checkout',
            'message' => 'Checkout berhasil',
            'overtime_minutes' => $overtimeMinutes
        ]);
    }
}