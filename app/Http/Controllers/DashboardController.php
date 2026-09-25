<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use App\Services\ScheduleService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        $isHoliday = $this->isHoliday($today);

        /*
    |------------------------------------------------------------------
    | SCHEDULE TODAY (ambil lebih awal untuk dapat shift_date)
    |------------------------------------------------------------------
    */
        $scheduleData = ScheduleService::getTodaySchedule($user);

        /*
    |------------------------------------------------------------------
    | ATTENDANCE TODAY (gunakan shift_date dari schedule untuk cross-day)
    |------------------------------------------------------------------
    */
        $attendanceDate = $scheduleData && isset($scheduleData['shift_date'])
            ? $scheduleData['shift_date']
            : today()->format('Y-m-d');

        // `where` (bukan `whereDate`) agar index (user_id, tanggal) terpakai.
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->where('tanggal', $attendanceDate)
            ->first();

        /*
    |------------------------------------------------------------------
    | ATTENDANCE HISTORY
    |------------------------------------------------------------------
    */
        $histories = Attendance::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        /*
    |------------------------------------------------------------------
    | SCHEDULE TODAY (SINKRONISASI & AMANKAN DATA LIBUR)
    |------------------------------------------------------------------
    */
        // PERBAIKAN UTAMA: Pastikan variabel berupa array/object DAN memiliki key 'shift_name'
        if ($scheduleData && isset($scheduleData['shift_name'])) {
            $schedule = $scheduleData['shift_name']
                . ' • ' .
                Carbon::parse($scheduleData['start_time'])->format('H:i')
                . ' - ' .
                Carbon::parse($scheduleData['end_time'])->format('H:i');

            $isWorkingDay = true;
        } else {
            // Jika data null atau tidak punya jadwal (Hari Libur)
            $schedule = 'Hari Libur';
            $isWorkingDay = false;

            // Kita paksa buat array dummy berstruktur agar Blade pilihan Anda tidak bingung/error
            $scheduleData = [
                'shift_name'   => 'Hari Libur / Off',
                'start_time'   => null,
                'end_time'     => null,
                'is_overnight' => false
            ];
        }

        /*
    |------------------------------------------------------------------
    | PERMISSION
    |------------------------------------------------------------------
    */
        $latestPermission = Permission::where('user_id', $user->id)
            ->latest()
            ->first();

        $pendingPermissionCount = Permission::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        /*
    |------------------------------------------------------------------
    | LEAVE
    |------------------------------------------------------------------
    */
        $latestLeave = Leave::where('user_id', $user->id)
            ->latest()
            ->first();

        $pendingLeaveCount = Leave::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        /*
    |------------------------------------------------------------------
    | OVERTIME
    |------------------------------------------------------------------
    */
        $pendingOvertimeCount = Overtime::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        /*
        |------------------------------------------------------------------
        | SHIFT CHANGE REQUEST
        |------------------------------------------------------------------
        */
        $pendingShiftChangeCount = \App\Models\ShiftChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

                return view('dashboard', compact(
            'user',
            'todayAttendance',
            'histories',
            'schedule',
            'scheduleData',
            'isWorkingDay',
            'latestPermission',
            'pendingPermissionCount',
            'latestLeave',
            'pendingLeaveCount',
            'pendingOvertimeCount',
            'pendingShiftChangeCount',
            'isHoliday'
        ));
    }

    private function isHoliday($date)
    {
        $date = Carbon::parse($date);

        // 1. Cek Akhir Pekan (Sabtu & Minggu)
        if ($date->isWeekend()) return true;

        /*
        |--------------------------------------------------------------------------
        | 2. Cek Tanggal Merah via API (https://api-harilibur.id)
        |--------------------------------------------------------------------------
        | Hasil disimpan di cache agar dashboard tidak memanggil API eksternal
        | pada setiap request (sumber lag ketika banyak user membuka dashboard).
        | Nilai & arti kembalian tetap sama seperti sebelumnya:
        |   - ada data libur  -> true
        |   - api error/kosong -> false
        | Saat API gagal, cache ditahan hanya 5 menit agar bisa dicoba lagi,
        | dan kegagalan cache (mis. Redis mati) tidak membuat dashboard error.
        */
        $cacheKey = 'hari_libur:' . $date->format('Y-m-d');

        try {
            if (Cache::has($cacheKey)) {
                return (bool) Cache::get($cacheKey);
            }
        } catch (\Throwable $e) {
            // cache tidak tersedia -> lanjut memanggil API seperti biasa
        }

        $isHoliday = false;
        $fromApi   = false;

        try {
            $response = Http::timeout(3)
                ->get('https://api-harilibur.id/api', ['tanggal' => $date->format('Y-m-d')]);

            if ($response->successful()) {
                $isHoliday = !empty($response->json());
                $fromApi   = true;
            }
        } catch (\Throwable $e) {
            $isHoliday = false;
            $fromApi   = false;
        }

        try {
            Cache::put($cacheKey, $isHoliday, $fromApi ? now()->addHours(12) : now()->addMinutes(5));
        } catch (\Throwable $e) {
            // abaikan kegagalan cache
        }

        return $isHoliday;
    }
}
