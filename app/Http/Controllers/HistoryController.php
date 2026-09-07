<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\Overtime;
use App\Models\EmployeeShift;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        /*
        |--------------------------------------------------------------------------
        | Ambil semua tahun
        |--------------------------------------------------------------------------
        */

        $leaveYears = Leave::where('user_id', $userId)
            ->selectRaw('YEAR(start_date) as year')
            ->pluck('year');

        $permissionYears = Permission::where('user_id', $userId)
            ->selectRaw('YEAR(tanggal) as year')
            ->pluck('year');

        $overtimeYears = Overtime::where('user_id', $userId)
            ->selectRaw('YEAR(overtime_date) as year')
            ->pluck('year');

        $years = $leaveYears
            ->merge($permissionYears)
            ->merge($overtimeYears)
            ->unique()
            ->sortDesc()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | LEAVES
        |--------------------------------------------------------------------------
        */

        $leaves = [];

        foreach ($years as $year) {

            $leaves[$year] = Leave::where('user_id', $userId)
                ->whereYear('start_date', $year)
                ->latest()
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | PERMISSIONS
        |--------------------------------------------------------------------------
        */

        $permissions = [];

        foreach ($years as $year) {

            $permissions[$year] = Permission::where('user_id', $userId)
                ->whereYear('tanggal', $year)
                ->latest()
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | OVERTIMES
        |--------------------------------------------------------------------------
        */

        $overtimes = [];

        foreach ($years as $year) {

            $overtimes[$year] = Overtime::where('user_id', $userId)
                ->whereYear('overtime_date', $year)
                ->latest()
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view('history.index', compact(
            'years',
            'leaves',
            'permissions',
            'overtimes'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | REKAP ABSEN PRIBADI (PER BULAN + FILTER HARI INI / TANGGAL SEBELUMNYA)
    |--------------------------------------------------------------------------
    */
    public function rekap(Request $request)
    {
        $user  = auth()->user();
        $today = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | FILTER BULAN (Y-m) - default bulan berjalan
        |--------------------------------------------------------------------------
        */
        $month = (string) $request->query('bulan', $today->format('Y-m'));

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = $today->format('Y-m');
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL (mode=tanggal) -> hari ini / tanggal sebelumnya
        |--------------------------------------------------------------------------
        */
        $selectedDay = null;

        if ($request->query('mode') === 'tanggal') {
            try {
                $selectedDay = Carbon::parse(
                    $request->query('tanggal', $today->format('Y-m-d'))
                )->startOfDay();

                // Tidak boleh menampilkan tanggal masa depan
                if ($selectedDay->gt($today)) {
                    $selectedDay = $today->copy();
                }
            } catch (\Throwable $e) {
                $selectedDay = null;
            }

            // Tanggal dipilih => ikutkan bulan pada tanggal tsb.
            if ($selectedDay) {
                $month = $selectedDay->format('Y-m');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RENTANG PERIODE BULAN & RENTANG DATA YANG DITAMPILKAN
        |--------------------------------------------------------------------------
        */
        $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        if ($selectedDay) {
            $rangeStart = $selectedDay->copy();
            $rangeEnd   = $selectedDay->copy();
        } else {
            $rangeStart = $periodStart->copy();
            $rangeEnd   = $periodEnd->copy();
        }

        $dateStart = $rangeStart->format('Y-m-d');
        $dateEnd   = $rangeEnd->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | KUMPULKAN DATA ABSEN / JADWAL / CUTI / IZIN
        |--------------------------------------------------------------------------
        */
        $attendanceList = Attendance::where('user_id', $user->id)
            ->whereBetween('tanggal', [$dateStart, $dateEnd])
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $employeeShifts = EmployeeShift::with('shift:id,name')
            ->where('user_id', $user->id)
            ->whereBetween('shift_date', [$dateStart, $dateEnd])
            ->get()
            ->keyBy('shift_date');

        $approvedLeaves = Leave::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $dateEnd)
            ->where('end_date', '>=', $dateStart)
            ->get();

        $approvedPermissions = Permission::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('tanggal', [$dateStart, $dateEnd])
            ->get()
            ->keyBy('tanggal');

        // Jenis izin yang dianggap "sehari penuh tidak masuk"
        $fullDayPermitTypes = [
            'izin pribadi',
            'sakit',
            'keperluan keluarga',
            'dinas luar',
        ];

        /*
        |--------------------------------------------------------------------------
        | BANGUN REKAP HARIAN
        |--------------------------------------------------------------------------
        */
        $counts = [
            'hadir'     => 0,
            'terlambat' => 0,
            'cuti'      => 0,
            'izin'      => 0,
            'alpa'      => 0,
            'off'       => 0,
            'terjadwal' => 0,
        ];

        $rows   = [];
        $cursor = $rangeStart->copy();

        while ($cursor->lte($rangeEnd)) {
            $dateStr  = $cursor->format('Y-m-d');
            $isFuture = $cursor->gt($today);
            $isToday  = $cursor->isSameDay($today);

            $attendance = $attendanceList->get($dateStr);
            $shiftRow   = $employeeShifts->get($dateStr);
            $permit     = $approvedPermissions->get($dateStr);

            // Cuti disetujui yang menutup tanggal ini
            $leaveCover = null;

            foreach ($approvedLeaves as $leave) {
                if ($dateStr >= $leave->start_date && $dateStr <= $leave->end_date) {
                    $leaveCover = $leave;
                    break;
                }
            }

            // Jadwal kerja tanggal ini (null = tidak ada jadwal / libur-off)
            $schedule = $this->resolveDaySchedule($user, $cursor, $shiftRow);

            $isPermitFullDay = $permit
                && in_array(strtolower($permit->jenis), $fullDayPermitTypes);

            /*
            |--------------------------------------------------------------------------
            | Tentukan status hari tsb
            |--------------------------------------------------------------------------
            */
            $kind = 'off';
            $note = null;

            if ($attendance) {
                if ($attendance->status === 'terlambat') {
                    $kind = 'terlambat';
                    $note = 'Terlambat ' . (int) $attendance->late_minutes . ' menit';
                } else {
                    $kind = 'hadir';
                }

                // Anotasi izin/dinas luar walau tetap hadir
                if ($permit) {
                    $note = ($note ? $note . ' • ' : '') . ucwords($permit->jenis);
                }
            } elseif ($leaveCover) {
                $kind = 'cuti';
                $note = $leaveCover->leave_type;
            } elseif ($isPermitFullDay) {
                $kind = 'izin';
                $note = ucwords($permit->jenis);
            } elseif ($schedule) {
                if ($isFuture) {
                    $kind = 'terjadwal';
                    $note = 'Shift terjadwal';
                } elseif ($isToday) {
                    $kind = 'belum_absen';
                    $note = 'Belum melakukan absen';
                } else {
                    $kind = 'alpa';
                    $note = 'Tanpa keterangan';
                }
            } else {
                if ($isFuture) {
                    $kind = 'off_future';
                } else {
                    $kind = 'off';
                    $note = 'Tidak ada jadwal (Libur/Off)';
                }
            }

            // Jam masuk / keluar
            $masuk = $attendance?->jam_masuk
                ? Carbon::parse($attendance->jam_masuk)->format('H:i')
                : null;

            $keluar = $attendance?->jam_keluar
                ? Carbon::parse($attendance->jam_keluar)->format('H:i')
                : null;

            $checkoutPending = $attendance
                && !$attendance->jam_keluar
                && !$isFuture;

            // Hitung summary hanya untuk tanggal <= hari ini
            if (!$isFuture && $kind !== 'belum_absen') {
                if (isset($counts[$kind])) {
                    $counts[$kind]++;
                }
            }

            $rows[] = [
                'date_str'         => $dateStr,
                'date_label'       => $cursor->format('j') . ' ' . $this->indoMonthName($cursor->month),
                'day_name'         => $this->indoDayName($cursor),
                'is_today'         => $isToday,
                'is_future'        => $isFuture,
                'schedule'         => $schedule,
                'status'           => $kind,
                'status_label'     => $this->statusLabel($kind),
                'note'             => $note,
                'masuk'            => $masuk,
                'keluar'           => $keluar,
                'checkout_pending' => $checkoutPending,
            ];

            $cursor->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | PERSENTASE KEHADIRAN (dari hari kerja tanpa cuti/izin)
        |--------------------------------------------------------------------------
        */
        $masukTotal    = $counts['hadir'] + $counts['terlambat'];
        $wajibHadir    = $masukTotal + $counts['alpa'];
        $presentPercent = $wajibHadir > 0
            ? (int) round($masukTotal / $wajibHadir * 100)
            : null;

        /*
        |--------------------------------------------------------------------------
        | NAVIGASI BULAN
        |--------------------------------------------------------------------------
        */
        $prevMonth = $periodStart->copy()->subMonthNoOverflow()->format('Y-m');
        $nextMonth = $periodStart->copy()->addMonthNoOverflow()->format('Y-m');

        $periodLabel = $this->indoMonthName($periodStart->month)
            . ' ' . $periodStart->format('Y');

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */
        return view('history.rekap', compact(
            'user',
            'today',
            'month',
            'selectedDay',
            'periodStart',
            'periodEnd',
            'rangeStart',
            'rangeEnd',
            'prevMonth',
            'nextMonth',
            'periodLabel',
            'rows',
            'counts',
            'presentPercent'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Tentukan jadwal kerja untuk satu tanggal tertentu.
    | - work_type 'shift'    : bersumber dari tabel employee_shifts.
    | - work_type 'office_5' : Senin-Jumat 08.00-17.00.
    | - work_type 'office_6' : Senin-Sabtu 08.00-16.00.
    | - null berarti libur / off (tidak ada jadwal).
    |--------------------------------------------------------------------------
    */
    private function resolveDaySchedule($user, Carbon $date, $employeeShift)
    {
        if ($user->work_type === 'shift') {
            if (!$employeeShift) {
                return null;
            }

            $shift = $employeeShift->shift;

            return [
                'name'      => optional($shift)->name ?? 'Shift',
                'start'     => $employeeShift->start_time ?? optional($shift)->start_time,
                'end'       => $employeeShift->end_time ?? optional($shift)->end_time,
                'type'      => 'shift',
                'overnight' => (bool) $employeeShift->is_overnight,
            ];
        }

        $dayOfWeekIso = (int) $date->dayOfWeekIso; // 1=Senin ... 7=Minggu

        if ($user->work_type === 'office_5') {
            if ($dayOfWeekIso > 5) {
                return null;
            }

            return [
                'name'      => 'Office',
                'start'     => '08:00:00',
                'end'       => '17:00:00',
                'type'      => 'office',
                'overnight' => false,
            ];
        }

        if ($user->work_type === 'office_6') {
            if ($dayOfWeekIso === 7) {
                return null;
            }

            return [
                'name'      => 'Office',
                'start'     => '08:00:00',
                'end'       => '16:00:00',
                'type'      => 'office',
                'overnight' => false,
            ];
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS LABEL BAHASA INDONESIA
    |--------------------------------------------------------------------------
    */
    private function indoDayName(Carbon $date): string
    {
        $days = [
            'Minggu',
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu',
        ];

        return $days[$date->dayOfWeek] ?? '';
    }

    private function indoMonthName(int $month): string
    {
        $months = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $months[$month] ?? $month;
    }

    private function statusLabel(string $kind): string
    {
        return match ($kind) {
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'cuti'        => 'Cuti',
            'izin'        => 'Izin',
            'alpa'        => 'Alpa',
            'belum_absen' => 'Belum Absen',
            'terjadwal'   => 'Terjadwal',
            'off'         => 'Libur / Off',
            default       => 'Libur / Off',
        };
    }
}
