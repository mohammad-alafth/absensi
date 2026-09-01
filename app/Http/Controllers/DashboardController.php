<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use App\Services\ScheduleService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        $isHoliday = $this->isHoliday($today);

        /*
    |------------------------------------------------------------------
    | ATTENDANCE TODAY
    |------------------------------------------------------------------
    */
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('tanggal', today())
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
        $scheduleData = ScheduleService::getTodaySchedule($user);

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

        /*
    |------------------------------------------------------------------
    | REMINDER SHIFT UNTUK PJ
    |------------------------------------------------------------------
    */
        $showShiftReminder = false;
        $shiftReminderMessage = null;

        if (str_starts_with($user->role, 'pj_')) {
            $today = Carbon::today();
            $period = $this->getShiftPeriod($today);
            $periodEnd = Carbon::parse($period['end_date']);
            $reminderStart = $periodEnd->copy()->subDays(6); // Tanggal 20

            $nextPeriod = $this->getShiftPeriod($periodEnd->copy()->addDay());

            // 1. Cek jumlah data shift untuk periode depan
            $shiftCount = EmployeeShift::whereDate('start_date', $nextPeriod['start_date'])
                ->where('assigned_by', $user->id)
                ->count();

            // 2. Ambil waktu update terakhir dari periode depan
            $lastUpdated = EmployeeShift::whereDate('start_date', $nextPeriod['start_date'])
                ->where('assigned_by', $user->id)
                ->max('updated_at');

            // 3. Cek apakah sudah diupdate di rentang reminder (tgl 20 ke atas)
            $isUpdated = $lastUpdated && Carbon::parse($lastUpdated)->gte($reminderStart);

            // 4. Munculkan notif jika dalam rentang 20-26 dan shift belum ada atau belum diupdate
            if ($today->gte($reminderStart) && $today->lte($periodEnd)) {
                if ($shiftCount === 0 || !$isUpdated) {
                    $showShiftReminder = true;
                    $shiftReminderMessage = 'Periode shift akan berakhir pada ' . $periodEnd->translatedFormat('d F Y') . '. Segera update jadwal shift periode berikutnya.';
                }
                
            }
            
        }
        

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
            'showShiftReminder',
            'shiftReminderMessage',
            'isHoliday'
        ));
    }

    /*
    |------------------------------------------------------------------
    | GET SHIFT PERIOD
    |------------------------------------------------------------------
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

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date'   => $endDate->format('Y-m-d'),
        ];
    }
    // Tambahkan di dalam DashboardController.php

    private function isHoliday($date)
    {
        $date = Carbon::parse($date);

        // 1. Cek Akhir Pekan (Sabtu & Minggu)
        if ($date->isWeekend()) return true;

        // 2. Cek Tanggal Merah via API (https://api-harilibur.id)
        try {
            $url = "https://api-harilibur.id/api?tanggal=" . $date->format('Y-m-d');
            $response = @file_get_contents($url);
            $data = json_decode($response, true);

            // Jika API mengembalikan data libur
            if (!empty($data)) return true;
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
