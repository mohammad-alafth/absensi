<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Exports\HRDRekapExport;
use App\Exports\HRDAbsentExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\EmployeeShift;
use App\Models\Permission;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use App\Exports\CalendarExport;
use App\Exports\CalendarUserExport;
use App\Services\ScheduleService;
use App\Exports\CalendarMultiExport;
use App\Support\PermissionRange;


class HRDController extends Controller
{
    public function index(Request $request)
    {
        /*
            |--------------------------------------------------------------------------
            | FILTER PERIODE (TANGGAL MULAI - TANGGAL SELESAI)
            |--------------------------------------------------------------------------
            | Prioritas parameter: `start_date` & `end_date` (rentang bebas).
            | Fallback: parameter `month` (kompatibilitas link/bookmark lama),
            | lalu bulan berjalan. Input tanggal tidak valid -> bulan berjalan.
            */
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->startOfDay()
                : Carbon::parse(($request->month ?? now()->format('Y-m')) . '-01')->startOfMonth();

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->endOfDay()
                : Carbon::parse(($request->month ?? now()->format('Y-m')) . '-01')->endOfMonth();
        } catch (\Throwable $e) {
            $startDate = now()->startOfMonth();
            $endDate   = now()->endOfMonth();
        }

        // Bila pengguna membalik urutan tanggal, tukar otomatis.
        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // Dipakai fitur berbasis bulan (kalender shift / export jadwal).
        $month = $startDate->format('Y-m');

        /*
            |--------------------------------------------------------------------------
            | EMPLOYEE
            |--------------------------------------------------------------------------
            */
        $employees = User::whereNotIn('role', ['admin'])
            ->select('id', 'name', 'role', 'leave_quota')
            ->get();

        /*
            |--------------------------------------------------------------------------
            | ATTENDANCE BULAN INI (1 QUERY)
            |--------------------------------------------------------------------------
            */
        $attendanceGroups = Attendance::select([
            'user_id',
            'status',
            'late_minutes',
            'overtime_minutes',
            'jam_masuk',
            'jam_keluar',
            'tanggal'
        ])
            ->whereBetween('tanggal', [
                $startDate,
                $endDate
            ])
            ->get()
            ->groupBy('user_id');

        /*
            |--------------------------------------------------------------------------
            | SHIFT BULAN INI (1 QUERY)
            |--------------------------------------------------------------------------
            */
        $employeeShifts = EmployeeShift::select([
            'id',
            'user_id',
            'shift_id',
            'shift_date',
            'start_time',
            'end_time'
        ])
            ->with([
                'user:id,name,role,work_type',
                'shift:id,name'
            ])
            ->whereHas('user', function ($q) {
                $q->where('work_type', 'shift');
            })
            ->whereBetween('shift_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->get();

        $shiftGroups = $employeeShifts->groupBy('user_id');

        /*
            |--------------------------------------------------------------------------
            | REKAP
            |--------------------------------------------------------------------------
            */

        $leaveGroups = Leave::select(
            'user_id',
            'start_date',
            'end_date'
        )
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {

                $q->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            })
            ->get()
            ->groupBy('user_id');

        // Izin multi-hari menempati `tanggal` s/d `tanggal_selesai`, jadi
        // seluruh tanggal dalam rentang ikut dihitung (bukan hanya hari pertama).
        $permissionGroups = PermissionRange::applyOverlapsPeriod(
            Permission::select(
                'user_id',
                'tanggal',
                'tanggal_selesai'
            )->where('status', 'approved'),
            $startDate,
            $endDate
        )
            ->get()
            ->groupBy('user_id');

        $roles = User::select('role')
            ->distinct()
            ->pluck('role')
            ->map(function ($role) {

                $role = strtolower($role);

                // hilangkan prefix pj_
                $role = str_replace('pj_', '', $role);

                // gabungkan nurse_ok ke nurse
                if ($role === 'nurse_ok') {
                    $role = 'nurse';
                }

                return $role;
            })
            ->unique()
            ->values();

        $roleLabels = [
            'nurse' => 'PERAWAT',
            'security' => 'SECURITY',
            'cs' => 'CLEANING SERVICE',
            'administrasi' => 'ADMINISTRASI',
            'ro' => 'REFRAKSIONIS OPTISIEN',
            'finance' => 'KEUANGAN',
            'pharmacist' => 'APOTEKER',
            'casemix' => 'CASEMIX',
            'ipsrs' => 'IPSRS',
            'marketing' => 'MARKETING',
            'it' => 'IT',
            'hrd' => 'HRD',
            'nutrition' => 'GIZI',
            'medical_record' => 'REKAM MEDIS',
            'medical_service' => 'MEDICAL SERVICE',
            'head_pegawai' => 'KEPALA BAGIAN UMUM',
            'director' => 'DIREKTUR',
            'pipp' => 'PIPP',
        ];

        $recaps = [];

        foreach ($employees as $employee) {
            $normalizedRole = strtolower($employee->role);

            $normalizedRole = str_replace(
                'pj_',
                '',
                $normalizedRole
            );

            if ($normalizedRole === 'nurse_ok') {
                $normalizedRole = 'nurse';
            }

            $attendances = $attendanceGroups[$employee->id]
                ?? collect();

            $userShifts = $shiftGroups[$employee->id]
                ?? collect();

            $userLeaves =
                $leaveGroups[$employee->id]
                ?? collect();

            $userPermissions =
                $permissionGroups[$employee->id]
                ?? collect();

            /*
                |--------------------------------------------------------------------------
                | TANGGAL SHIFT
                |--------------------------------------------------------------------------
                */
            $shiftDates = $userShifts
                ->pluck('shift_date')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->unique()
                ->values();

            /*
                |--------------------------------------------------------------------------
                | TANGGAL HADIR/TELAT/SAKIT
                |--------------------------------------------------------------------------
                */
            $attendanceDates = $attendances
                ->pluck('tanggal')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->unique();

            /*
                |--------------------------------------------------------------------------
                | TANGGAL IZIN APPROVED
                |--------------------------------------------------------------------------
                */
            $permissionDates = PermissionRange::datesWithin(
                $userPermissions,
                $startDate,
                $endDate
            );

            /*
                |--------------------------------------------------------------------------
                | TANGGAL CUTI APPROVED
                |--------------------------------------------------------------------------
                */
            $leaveDates = collect();

            foreach ($userLeaves as $leave) {

                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd   = Carbon::parse($leave->end_date);

                /*
                |--------------------------------------------------------------------------
                | Batasi hanya tanggal dalam bulan rekap
                |--------------------------------------------------------------------------
                */
                $effectiveStart = $leaveStart->copy()->max($startDate);
                $effectiveEnd   = $leaveEnd->copy()->min($endDate);

                while ($effectiveStart->lte($effectiveEnd)) {

                    $leaveDates->push(
                        $effectiveStart->format('Y-m-d')
                    );

                    $effectiveStart->addDay();
                }
            }

            $leaveDates = $leaveDates->unique();

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */
            $hadir = $attendances->where('status', 'hadir')->count();

            $telat = $attendances->where('status', 'terlambat')->count();

            $izin = $attendances->where('status', 'izin')->count();

            $sakit = $attendances->where('status', 'sakit')->count();

            /*
            |--------------------------------------------------------------------------
            | TOTAL HARI SHIFT
            |--------------------------------------------------------------------------
            */
            $totalShiftDays = $userShifts
                ->pluck('shift_date')
                ->unique()
                ->count();
            /*
            |--------------------------------------------------------------------------
            | HITUNG ALPHA BERDASARKAN HARI SHIFT
            |--------------------------------------------------------------------------
            */
            $alpha = 0;

            foreach ($shiftDates as $shiftDate) {

                $isAttendance =
                    $attendanceDates->contains($shiftDate);

                $isPermission =
                    $permissionDates->contains($shiftDate);

                $isLeave =
                    $leaveDates->contains($shiftDate);

                /*
                |--------------------------------------------------------------------------
                | Jika pada hari shift tidak ada
                | attendance, izin, ataupun cuti
                |--------------------------------------------------------------------------
                */
                if (
                    !$isAttendance &&
                    !$isPermission &&
                    !$isLeave
                ) {
                    $alpha++;
                }
            }
            /*
        |--------------------------------------------------------------------------
        | TOTAL KETERLAMBATAN
        |--------------------------------------------------------------------------
        */
            $totalLateMinutes = 0;

            foreach ($attendances as $attendance) {

                $late = $attendance->late_minutes ?? 0;

                $realLate = max($late - 15, 0);

                $totalLateMinutes += $realLate;
            }

            $lateHours = floor($totalLateMinutes / 60);

            $lateRemainMinutes = $totalLateMinutes % 60;

            $lateFormatted = '';

            if ($lateHours > 0) {
                $lateFormatted .= $lateHours . ' Jam ';
            }

            $lateFormatted .= $lateRemainMinutes . ' Menit';

            /*
        |--------------------------------------------------------------------------
        | TOTAL JAM KERJA
        |--------------------------------------------------------------------------
        */
            $totalJam = 0;

            $totalActualOvertimeMinutes = 0;

            foreach ($attendances as $attendance) {

                $totalActualOvertimeMinutes +=
                    ($attendance->overtime_minutes ?? 0);

                if (
                    !$attendance->jam_masuk ||
                    !$attendance->jam_keluar
                ) {
                    continue;
                }

                $masuk = Carbon::parse(
                    $attendance->jam_masuk
                );

                $keluar = Carbon::parse(
                    $attendance->jam_keluar
                );

                /*
            |--------------------------------------------------------------------------
            | SHIFT MALAM
            |--------------------------------------------------------------------------
            */
                if ($keluar->lt($masuk)) {
                    $keluar->addDay();
                }

                $totalJam +=
                    $masuk->diffInMinutes($keluar);
            }

            /*
        |--------------------------------------------------------------------------
        | RATA-RATA JAM KERJA
        |--------------------------------------------------------------------------
        */
            $hariKerja = $hadir + $telat;

            $avgJam = $hariKerja > 0
                ? round(($totalJam / 60) / $hariKerja, 1)
                : 0;

            /*
        |--------------------------------------------------------------------------
        | PERFORMANCE
        |--------------------------------------------------------------------------
        */
            if ($alpha >= 3) {

                $performance = 'Buruk';
            } elseif ($alpha >= 1 || $telat >= 5) {

                $performance = 'Evaluasi';
            } else {

                $performance = 'Baik';
            }

            /*
        |--------------------------------------------------------------------------
        | FORMAT OVERTIME
        |--------------------------------------------------------------------------
        */
            $finalOvertimeHours =
                floor($totalActualOvertimeMinutes / 60);

            $finalOvertimeMinutes =
                $totalActualOvertimeMinutes % 60;

            $overtimeFormatted = '';

            if ($finalOvertimeHours > 0) {
                $overtimeFormatted .=
                    $finalOvertimeHours . ' Jam ';
            }

            if (
                $finalOvertimeMinutes > 0 ||
                $finalOvertimeHours == 0
            ) {
                $overtimeFormatted .=
                    $finalOvertimeMinutes . ' Menit';
            }

            /*
        |--------------------------------------------------------------------------
        | RATA-RATA JAM MASUK (UNTUK RANKING ABSEN TERCEPAT)
        |--------------------------------------------------------------------------
        */
            $checkIns = $attendances->filter(fn($a) => $a->jam_masuk);

            $avgCheckin = null;

            if ($checkIns->isNotEmpty()) {
                $totalCheckinMinutes = $checkIns->sum(
                    fn($a) =>
                    (int) Carbon::parse($a->jam_masuk)->format('H') * 60
                        + (int) Carbon::parse($a->jam_masuk)->format('i')
                );

                $avgCheckinMinutes = (int) round(
                    $totalCheckinMinutes / $checkIns->count()
                );

                $avgCheckin = sprintf(
                    '%02d:%02d',
                    intdiv($avgCheckinMinutes, 60),
                    $avgCheckinMinutes % 60
                );
            }

            $recaps[] = [
                'employee' => $employee,
                'role' => $normalizedRole,
                'hadir' => $hadir,
                'telat' => $telat,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'late_minutes' => $totalLateMinutes,
                'late_formatted' => $lateFormatted,
                'total_jam' => round($totalJam / 60, 1),
                'avg_jam' => $avgJam,
                'performance' => $performance,
                'leave_quota' => $employee->leave_quota ?? 0,
                'overtimes' => trim($overtimeFormatted),
                'total_shift' => $totalShiftDays,
                'avg_checkin' => $avgCheckin,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RANKING ABSEN TERCEPAT (RATA-RATA JAM MASUK PALING AWAL)
        |--------------------------------------------------------------------------
        */
        $rankingCepat = collect($recaps)
            ->filter(fn($item) => !empty($item['avg_checkin']))
            ->sortBy('avg_checkin')
            ->take(5)
            ->values();


        /*
        |--------------------------------------------------------------------------
        | TIMELINE SHIFT
        |--------------------------------------------------------------------------
        */
        $calendarEvents = [];

        /*
        |--------------------------------------------------------------------------
        | QUERY ATTENDANCE SEKALI (OPTIMASI)
        |--------------------------------------------------------------------------
        | Sebelumnya query Attendance berada DI DALAM loop foreach ($employeeShifts)
        | sehingga dijalankan ulang untuk SETIAP baris shift (N+1 query).
        | Dengan ratusan/ribuan shift per bulan, halaman rekap menjadi sangat lambat.
        | Sekarang data attendance dimuat SEKALI di luar loop.
        */
        $attendanceMap = Attendance::whereBetween('tanggal', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->get()
            ->keyBy(fn($a) => $a->user_id . '_' . $a->tanggal);

        foreach ($employeeShifts as $shift) {
            if (!$shift->user) {
                continue;
            }
            $key = $shift->user_id . '_' .
                Carbon::parse($shift->shift_date)->format('Y-m-d');
            $attendance = $attendanceMap->get($key);
            $role = strtolower($shift->user->role);

            if (str_starts_with($role, 'pj_')) {
                $role = substr($role, 3);
            }
            if ($role === 'nurse_ok') {
                $role = 'nurse';
            }
            $start = Carbon::parse(
                $shift->shift_date . ' ' . $shift->start_time
            );

            $end = Carbon::parse(
                $shift->shift_date . ' ' . $shift->end_time
            );

            if ($end->lt($start)) {
                $end->addDay();
            }
            $roleColors = [
                'superadmin'     => '#dc2626', // red-600
                'admin'          => '#2563eb', // blue-600
                'nurse'          => '#10b981', // emerald
                'doctor'         => '#0ea5e9', // sky
                'pharmacist'     => '#8b5cf6', // violet
                'security'       => '#ef4444', // red
                'cs'             => '#06b6d4', // cyan
                'administrasi'   => '#6366f1', // indigo
                'finance'        => '#f59e0b', // amber
                'kasir'          => '#ec4899', // pink
                'ipsrs'          => '#64748b', // slate

                'lab'            => '#14b8a6', // teal
                'radiologi'      => '#f97316', // orange
                'gizi'           => '#84cc16', // lime
                'rekam_medis'    => '#3b82f6', // blue
                'it'             => '#1e293b', // slate dark
                'marketing'      => '#e11d48', // rose
                'manager'        => '#7c3aed', // purple
                'direktur'       => '#991b1b', // dark red
            ];
            $calendarEvents[] = [
                'title' => $shift->user->name,
                'start' => $start->toDateTimeString(),
                'end'   => $end->toDateTimeString(),

                'backgroundColor' => $roleColors[$role] ?? '#1E40AF',
                'borderColor'     => $roleColors[$role] ?? '#1E40AF',

                'extendedProps' => [
                    'role'      => $role,
                    'shift'     => $shift->shift->name ?? '-',
                    'date'      => $shift->shift_date,
                    'user_id' => $shift->user_id,
                    'shift_in'  => $shift->start_time,
                    'shift_out' => $shift->end_time,

                    'check_in'  => $attendance?->jam_masuk
                        ? Carbon::parse($attendance->jam_masuk)->format('H:i')
                        : '-',

                    'check_out' => $attendance?->jam_keluar
                        ? Carbon::parse($attendance->jam_keluar)->format('H:i')
                        : '-',
                ]
            ];
        }
        return view(
            'hrd.rekap.index',
            compact(
                'recaps',
                'month',
                'startDate',
                'endDate',
                'rankingCepat',
                'calendarEvents',
                'roles',
                'roleLabels'
            )
        );
    }

    public function calendarEmployee($userId)
    {
        $month = now()->month;
        $year  = now()->year;

        $data = EmployeeShift::with('shift')
            ->where('user_id', $userId)
            ->whereMonth('shift_date', $month)
            ->whereYear('shift_date', $year)
            ->orderBy('shift_date')
            ->get()
            ->map(function ($item) {

                $attendance = Attendance::where('user_id', $item->user_id)
                    ->whereDate('tanggal', $item->shift_date)
                    ->first();

                return [
                    'tanggal'    => Carbon::parse($item->shift_date)->format('d-m-Y'),
                    'shift'      => $item->shift->name ?? '-',
                    'jam_masuk'  => $item->start_time,
                    'jam_keluar' => $item->end_time,

                    'check_in' => $attendance?->jam_masuk
                        ? Carbon::parse($attendance->jam_masuk)->format('H:i')
                        : '-',

                    'check_out' => $attendance?->jam_keluar
                        ? Carbon::parse($attendance->jam_keluar)->format('H:i')
                        : '-',
                ];
            });

        return response()->json($data);
    }

    public function exportCalendarUser(
        Request $request,
        User $user
    ) {
        $month = $request->month;

        $year  = Carbon::parse($month)->year;
        $monthNumber = Carbon::parse($month)->month;

        $user = $user;

        $startDate = Carbon::createFromDate($year, $monthNumber, 1);
        $endDate   = $startDate->copy()->endOfMonth();

        $days = collect();

        for ($date = $startDate; $date <= $endDate; $date->addDay()) {

            $schedule = \App\Services\ScheduleService::getTodaySchedule(
                $user,
                $date->format('Y-m-d')
            );

            if (!$schedule) continue;

            $days->push((object)[
                'user_id' => $user->id,
                'shift_date' => $date->format('Y-m-d'),
                'shift' => (object)[
                    'name' => $schedule['shift_name']
                ],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
            ]);
        }

        $data = $days;

        return Excel::download(
            new CalendarUserExport(
                $data,
                $user,
                $month
            ),
            'shift-' . $user->name . '.xlsx'
        );
    }

    public function exportCalendar(Request $request)
    {
        $month = $request->month;
        $role = $request->role;

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        $data = EmployeeShift::with(['user', 'shift'])
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->whereHas('user', function ($q) use ($role) {
                if ($role !== 'all') {
                    $q->whereRaw("LOWER(REPLACE(role,'pj_','')) = ?", [strtolower($role)]);
                }
            })
            ->orderBy('shift_date')
            ->get();

        return Excel::download(
            new CalendarExport($data),
            "calendar-{$role}-{$month}.xlsx"
        );
    }
    public function exportCalendarAll(Request $request)
    {
        return Excel::download(
            new CalendarMultiExport($request->month),
            "calendar-all-{$request->month}.xlsx"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT EXCEL
    |--------------------------------------------------------------------------
    */
    public function exportExcel(Request $request)
    {
        $role = $request->role ?? 'all';
        $month = $request->month ?? now()->format('Y-m');

        /*
        | Rentang tanggal (start_date & end_date) dipakai bila keduanya/ salah
        | satunya dikirim; tanpa itu, fallback ke parameter `month` (mode lama).
        */
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->startOfDay()
                : null;
            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->endOfDay()
                : null;
        } catch (\Throwable $e) {
            $startDate = null;
            $endDate = null;
        }

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $export = new HRDRekapExport($month, $role, $startDate, $endDate);

        $suffix = ($startDate && $endDate)
            ? $startDate->format('Ymd') . '-' . $endDate->format('Ymd')
            : $month;

        return Excel::download(
            $export,
            "rekap-$role-$suffix.xlsx"
        );
    }

    public function updateLeaveQuota(Request $request, User $user)
    {
        $request->validate([
            'leave_quota' => 'required|integer|min:0|max:365',
        ]);

        $user->update([
            'leave_quota' => $request->leave_quota,
        ]);

        return back()->with(
            'success',
            'Quota cuti berhasil diperbarui'
        );
    }

    public function exportReport(Request $request)
    {
        $type = $request->type;
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->end_date ?? Carbon::now()->format('Y-m-d');
        
        $statusList = [
            'pending', 'waiting_head', 'waiting_director',
            'waiting_medical_service', 'waiting_hrd', 'approved', 'rejected'
        ];
        
        $data = collect();
        switch ($type) {
            case 'attendance':
                $data = Attendance::whereBetween('tanggal', [$startDate, $endDate])->with('user')->get();
                break;
            case 'leave':
                $data = Leave::whereIn('status', $statusList)
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $endDate)
                          ->where('end_date', '>=', $startDate);
                    })->with('user')->latest()->get();
                break;
            case 'permission':
                $data = Permission::whereIn('status', $statusList)
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->with('user')->latest()->get();
                break;
            case 'overtime':
                $data = Overtime::whereIn('status', $statusList)
                    ->whereBetween('overtime_date', [$startDate, $endDate])
                    ->with('user')->latest()->get();
                break;
        }
        
        if ($data->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk diekspor.');
        }        
        // Pre-process: group per user, jabarkan per tanggal/durasi
        $processedData = collect();
        
        switch ($type) {
            // Format: Nama, Tanggal Mulai, Tanggal Selesai, Durasi, Keterangan, Total Hari
            case 'leave':
                foreach ($data->groupBy('user_id') as $userId => $userRows) {
                    $userName = $userRows->first()->user->name ?? 'N/A';
                    $totalDays = 0;
                    foreach ($userRows as $row) {
                        $start = Carbon::parse($row->start_date);
                        $end = Carbon::parse($row->end_date);
                        $days = $start->diffInDays($end) + 1;
                        $totalDays += $days;
                        $processedData->push([
                            'name' => $userName,
                            'start_date' => $start->format('d/m/Y'),
                            'end_date' => $end->format('d/m/Y'),
                            'durasi' => $days . ' Hari',
                            'keterangan' => $row->reason ?? $row->alasan ?? '-',
                            'total_hari' => ''
                        ]);
                    }
                    $processedData->push([
                        'name' => $userName,
                        'start_date' => '', 'end_date' => '',
                        'durasi' => 'TOTAL', 'keterangan' => '',
                        'total_hari' => $totalDays . ' Hari'
                    ]);
                }
                break;                
            // Format: Nama, Tanggal, Jam Mulai, Jam Selesai, Total Jam/Hari, Keterangan, Total Jam
            case 'overtime':
                foreach ($data->groupBy('user_id') as $userId => $userRows) {
                    $userName = $userRows->first()->user->name ?? 'N/A';
                    $totalHours = 0;
                    foreach ($userRows as $row) {
                        $start = Carbon::parse($row->overtime_date);
                        $jamMulai = $row->start_time ?? '-';
                        $jamSelesai = $row->end_time_label;
                        $hours = floatval($row->total_hours ?? 0);
                        $totalHours += $hours;
                        $hoursFormatted = floor($hours) . ' Jam ' . round(($hours - floor($hours)) * 60) . ' Menit';
                        $processedData->push([
                            'name' => $userName,
                            'tanggal' => $start->format('d/m/Y'),
                            'jam_mulai' => $jamMulai,
                            'jam_selesai' => $jamSelesai,
                            'total_jam_hari' => $hoursFormatted,
                            'keterangan' => $row->reason ?? $row->hrd_note ?? '-',
                            'total_jam' => ''
                        ]);
                    }
                    $totalHoursFormatted = floor($totalHours) . ' Jam ' . round(($totalHours - floor($totalHours)) * 60) . ' Menit';
                    $processedData->push([
                        'name' => $userName,
                        'tanggal' => '', 'jam_mulai' => '', 'jam_selesai' => '',
                        'total_jam_hari' => 'TOTAL', 'keterangan' => '',
                        'total_jam' => $totalHoursFormatted
                    ]);
                }
                break;                
            // Format: Nama, Tanggal Mulai, Tanggal Selesai, Jam Mulai, Jam Selesai, Durasi, Keterangan, Total Jam
            case 'permission':
                foreach ($data->groupBy('user_id') as $userId => $userRows) {
                    $userName = $userRows->first()->user->name ?? 'N/A';
                    $totalMinutes = 0;
                    foreach ($userRows as $row) {
                        $start = Carbon::parse($row->tanggal);
                        $end = isset($row->tanggal_selesai) ? Carbon::parse($row->tanggal_selesai) : $start->copy();
                        $jamMulai = $row->jam_mulai ?? '-';
                        $jamSelesai = $row->jam_selesai ?? '-';
                        $rowMinutes = 0;
                        if ($row->jam_mulai && $row->jam_selesai) {
                            $begin = Carbon::parse($row->jam_mulai);
                            $finish = Carbon::parse($row->jam_selesai);
                            $rowMinutes = $finish->diffInMinutes($begin);
                            $totalMinutes += $rowMinutes;
                        }
                        $hoursFormatted = floor($rowMinutes / 60) . ' Jam ' . ($rowMinutes % 60) . ' Menit';
                        $processedData->push([
                            'name' => $userName,
                            'start_date' => $start->format('d/m/Y'),
                            'end_date' => $end->isSameDay($start) ? '' : $end->format('d/m/Y'),
                            'jam_mulai' => $jamMulai,
                            'jam_selesai' => $jamSelesai,
                            'durasi' => $hoursFormatted,
                            'keterangan' => $row->alasan ?? $row->reason ?? '-',
                            'total_jam' => ''
                        ]);
                    }
                    $totalHoursFormatted = floor($totalMinutes / 60) . ' Jam ' . ($totalMinutes % 60) . ' Menit';
                    $processedData->push([
                        'name' => $userName, 'start_date' => '', 'end_date' => '',
                        'jam_mulai' => '', 'jam_selesai' => '', 'durasi' => 'TOTAL',
                        'keterangan' => '', 'total_jam' => $totalHoursFormatted
                    ]);
                }
                break;                
            default:
                // attendance - format lama
                $processedData = $data;
        }
        
        // Export dengan Class Anonim yang sudah Disamakan Formatnya
        return Excel::download(new class($processedData, $type) implements FromCollection, WithHeadings, WithMapping, WithEvents, WithColumnWidths {
            protected $data;
            protected $type;
            public function __construct($data, $type)
            {
                $this->data = $data;
                $this->type = $type;
            }
            public function collection()
            {
                return $this->data;
            }
            
            public function headings(): array
            {
                switch ($this->type) {
                    case 'leave':
                        return ['NAMA PEGAWAI', 'TANGGAL MULAI', 'TANGGAL SELESAI', 'DURASI', 'KETERANGAN', 'TOTAL HARI'];
                    case 'overtime':
                        return ['NAMA PEGAWAI', 'TANGGAL', 'JAM MULAI', 'JAM SELESAI', 'TOTAL JAM/HARI', 'KETERANGAN', 'TOTAL JAM'];
                    case 'permission':
                        return ['NAMA PEGAWAI', 'TANGGAL MULA', 'TANGGAL SELESAI', 'JAM MULA', 'JAM SELESAI', 'DURASI', 'KETERANGAN', 'TOTAL JAM'];
                    default:
                        return ['NAMA PEGAWAI', 'TANGGAL / WAKTU', 'STATUS', 'KETERANGAN'];
                }
            }            
            public function map($item): array
            {
                $name = $item['name'] ?? $item->user->name ?? 'N/A';
                
                if ($this->type === 'leave') {
                    return [$name, $item['start_date'], $item['end_date'], $item['durasi'], $item['keterangan'], $item['total_hari']];
                } elseif ($this->type === 'overtime') {
                    return [$name, $item['tanggal'], $item['jam_mulai'], $item['jam_selesai'], $item['total_jam_hari'], $item['keterangan'], $item['total_jam']];
                } elseif ($this->type === 'permission') {
                    return [$name, $item['start_date'], $item['end_date'], $item['jam_mulai'], $item['jam_selesai'], $item['durasi'], $item['keterangan'], $item['total_jam']];
                } else {
                    return [
                        $item->user->name ?? 'N/A',
                        $item->tanggal ?? $item->start_date ?? $item->overtime_date ?? 'N/A',
                        ucwords(str_replace('_', ' ', $item->status ?? 'N/A')),
                        $item->reason ?? $item->hrd_note ?? $item->alasan ?? '-'
                    ];
                }
            }
            
            public function columnWidths(): array
            {
                switch ($this->type) {
                    case 'leave': return ['A' => 30, 'B' => 20, 'C' => 20, 'D' => 15, 'E' => 45, 'F' => 15];
                    case 'overtime': return ['A' => 30, 'B' => 18, 'C' => 15, 'D' => 15, 'E' => 18, 'F' => 40, 'G' => 18];
                    case 'permission': return ['A' => 30, 'B' => 18, 'C' => 18, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 40, 'H' => 18];
                    default: return ['A' => 30, 'B' => 20, 'C' => 30, 'D' => 45];
                }
            }            
            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet;
                        
                        $sheet->insertNewRowBefore(1, 5);
                        
                        $sheet->setCellValue('B1', 'RS MATA Pekanbaru Eye Center');
                        $sheet->setCellValue('B2', 'Jl. Soekarno Hatta No. 236 – Pekanbaru – Riau');
                        $sheet->setCellValue('B3', 'Telp. (0761) 7875191, 0811 7605191 Fax. (0761) 7875195');
                        $sheet->setCellValue('B4', 'www.pekanbarueyecenter.com');
                        
                        $sheet->mergeCells('B1:C1');
                        $sheet->mergeCells('B2:C2');
                        $sheet->mergeCells('B3:C3');
                        $sheet->mergeCells('B4:C4');
                        
                        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('5a6ea8');
                        $sheet->getStyle('B1:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('B1:C4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        
                        if (file_exists(public_path('images/rsprofile.png'))) {
                            $drawing1 = new Drawing();
                            $drawing1->setPath(public_path('images/rsprofile.png'));
                            $drawing1->setHeight(50);
                            $drawing1->setCoordinates('A1');
                            $drawing1->setWorksheet($sheet->getDelegate());
                        }
                        if (file_exists(public_path('images/Picture1.png'))) {
                            $drawing2 = new Drawing();
                            $drawing2->setPath(public_path('images/Picture1.png'));
                            $drawing2->setHeight(50);
                            $drawing2->setOffsetX(180);
                            $drawing2->setCoordinates('D1');
                            $drawing2->setWorksheet($sheet->getDelegate());
                        }
                        
                        $sheet->getRowDimension(6)->setRowHeight(35);
                        
                        $colCount = 'D';
                        switch ($this->type) {
                            case 'leave': $colCount = 'F'; break;
                            case 'overtime': $colCount = 'G'; break;
                            case 'permission': $colCount = 'H'; break;
                            default: $colCount = 'D';
                        }
                        $sheet->getStyle('A6:' . $colCount . '6')->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        ]);
                        
                        $lastRow = $sheet->getHighestRow();
                        $sheet->getStyle('A6:' . $colCount . $lastRow)->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                        ]);
                        
                        $sheet->getStyle('A7:' . $colCount . $lastRow)->getAlignment()->setWrapText(true);
                        $sheet->getPageSetup()->setFitToWidth(1);
                    },
                ];
            }
        }, "Laporan_" . ucfirst($type) . "_" . $startDate . "_sd_" . $endDate . ".xlsx");
    }
    public function reportAttendanceDaily(Request $request)
    {
        $date = $request->date ?? Carbon::today()->format('Y-m-d');
        // `where` (bukan `whereDate`) agar index (tanggal, status) terpakai,
        // dan hanya kolom yang ditampilkan template yang diambil.
        $data = Attendance::where('tanggal', $date)
            ->select('id', 'user_id', 'tanggal', 'jam_masuk', 'jam_keluar', 'status', 'late_minutes', 'overtime_minutes')
            ->with('user:id,name')
            ->get();

        // Simpan filter untuk digunakan di tombol export
        return view('hrd.reports.template', [
            'data' => $data,
            'title' => 'Laporan Hadir ' . $date,
            'filter_date' => $date
        ]);
    }

    public function reportAbsentDaily(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->end_date ?? Carbon::now()->format('Y-m-d');

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        // Hanya kolom yang diperlukan + tanpa kolom signature (base64) agar hemat memori.
        $employees = User::whereNotIn('role', ['admin'])
            ->select('id', 'name', 'work_type')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | AMBIL DATA PERIODE SEKALI SAJA (mengganti query per pegawai / N+1)
        |--------------------------------------------------------------------------
        | Hasil akhir identik dengan sebelumnya, hanya jumlah query berkurang
        | dari 3-4 query x jumlah pegawai menjadi 4 query total.
        */
        $attendanceByUser = Attendance::whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->select('user_id', 'tanggal')
            ->get()
            ->groupBy('user_id');

        // Rentang izin (tanggal .. tanggal_selesai) harus dibaca lengkap,
        // bukan hanya hari pertama, agar hari ke-2 dst tidak dihitung alpha.
        $permissionByUser = PermissionRange::applyOverlapsPeriod(
            Permission::where('status', 'approved'),
            $start,
            $end
        )
            ->select('user_id', 'tanggal', 'tanggal_selesai')
            ->get()
            ->groupBy('user_id');

        $leaveByUser = Leave::where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<=', $end->format('Y-m-d'))
                    ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->select('user_id', 'start_date', 'end_date')
            ->get()
            ->groupBy('user_id');

        $shiftByUser = EmployeeShift::whereBetween('shift_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->select('user_id', 'shift_date')
            ->get()
            ->groupBy('user_id');

        $data = collect();

        foreach ($employees as $employee) {
            $attendanceDates = ($attendanceByUser[$employee->id] ?? collect())
                ->pluck('tanggal')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->unique();

            $permissionDates = PermissionRange::datesWithin(
                $permissionByUser[$employee->id] ?? collect(),
                $start,
                $end
            );

            $leaveDates = collect();

            foreach (($leaveByUser[$employee->id] ?? collect()) as $leave) {
                $lStart = Carbon::parse($leave->start_date);
                $lEnd   = Carbon::parse($leave->end_date);
                while ($lStart->lte($lEnd)) {
                    if ($lStart->between($start, $end)) {
                        $leaveDates->push($lStart->format('Y-m-d'));
                    }
                    $lStart->addDay();
                }
            }
            $leaveDates = $leaveDates->unique();

            $absentCount = 0;

            if ($employee->work_type === 'shift') {
                $shiftDates = ($shiftByUser[$employee->id] ?? collect())
                    ->pluck('shift_date')
                    ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                    ->unique();

                foreach ($shiftDates as $sDate) {
                    if (
                        !$attendanceDates->contains($sDate) &&
                        !$permissionDates->contains($sDate) &&
                        !$leaveDates->contains($sDate)
                    ) {
                        $absentCount++;
                    }
                }
            } elseif ($employee->work_type === 'office_5') {
                $curr = $start->copy();
                while ($curr->lte($end)) {
                    if (!$curr->isWeekend()) {
                        $day = $curr->format('Y-m-d');
                        if (
                            !$attendanceDates->contains($day) &&
                            !$permissionDates->contains($day) &&
                            !$leaveDates->contains($day)
                        ) {
                            $absentCount++;
                        }
                    }
                    $curr->addDay();
                }
            } elseif ($employee->work_type === 'office_6') {
                $curr = $start->copy();
                while ($curr->lte($end)) {
                    if ($curr->dayOfWeek !== Carbon::SUNDAY) {
                        $day = $curr->format('Y-m-d');
                        if (
                            !$attendanceDates->contains($day) &&
                            !$permissionDates->contains($day) &&
                            !$leaveDates->contains($day)
                        ) {
                            $absentCount++;
                        }
                    }
                    $curr->addDay();
                }
            }

            if ($absentCount > 0) {
                $employee->alpha_days = $absentCount;
                $data->push($employee);
            }
        }

        $title = 'Pegawai Tidak Hadir (' . Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($endDate)->format('d/m/Y') . ')';

        return view('hrd.reports.template-absent', [
            'data' => $data,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function reportLeave(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->end_date ?? Carbon::now()->format('Y-m-d');

        // Kolom seperlunya saja (tanpa kolom signature base64) agar hemat memori.
        $data = Leave::whereIn('status', ['pending', 'waiting_head', 'waiting_director', 'waiting_medical_service', 'waiting_hrd', 'approved', 'rejected'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            })
            ->select('id', 'user_id', 'start_date', 'end_date', 'leave_type', 'reason', 'status', 'created_at')
            ->with('user:id,name')
            ->latest()
            ->get();

        $title = 'Rekap Cuti (' . Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($endDate)->format('d/m/Y') . ')';

        return view('hrd.reports.template-status', [
            'data' => $data,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reportType' => 'leave'
        ]);
    }

    public function reportPermission(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->end_date ?? Carbon::now()->format('Y-m-d');

        // Kolom seperlunya saja (tanpa kolom signature base64) agar hemat memori.
        $data = Permission::whereIn('status', ['pending', 'waiting_head', 'waiting_director', 'waiting_medical_service', 'waiting_hrd', 'approved', 'rejected'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select('id', 'user_id', 'tanggal', 'tanggal_selesai', 'jenis', 'jam_mulai', 'jam_selesai', 'alasan', 'status', 'created_at')
            ->with('user:id,name')
            ->latest()
            ->get();

        $title = 'Rekap Izin (' . Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($endDate)->format('d/m/Y') . ')';

        return view('hrd.reports.template-status', [
            'data' => $data,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reportType' => 'permission'
        ]);
    }

    public function reportOvertime(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate   = $request->end_date ?? Carbon::now()->format('Y-m-d');

        // Kolom seperlunya saja (tanpa kolom signature base64) agar hemat memori.
        $data = Overtime::whereIn('status', ['pending', 'waiting_head', 'waiting_director', 'waiting_medical_service', 'waiting_hrd', 'approved', 'rejected'])
            ->whereBetween('overtime_date', [$startDate, $endDate])
            ->select('id', 'user_id', 'overtime_date', 'start_time', 'end_time', 'total_hours', 'day_type', 'reason', 'status', 'created_at')
            ->with('user:id,name')
            ->latest()
            ->get();

        $title = 'Rekap Lembur (' . Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($endDate)->format('d/m/Y') . ')';

        return view('hrd.reports.template-status', [
            'data' => $data,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reportType' => 'overtime'
        ]);
    }

    public function tracking()
    {
        /*
        |--------------------------------------------------------------------------
        | DIBATASI + KOLOM SE PERLUNYA
        |--------------------------------------------------------------------------
        | Sebelumnya memuat SELURUH baris 3 tabel beserta kolom signature (base64)
        | dan data user lengkap sehingga memori server melonjak.
        | Halaman ini tetap menampilkan data terbaru lebih dulu.
        */
        $leaves = \App\Models\Leave::select('id', 'user_id', 'start_date', 'end_date', 'leave_type', 'reason', 'status', 'created_at')
            ->with('user:id,name')
            ->latest()
            ->limit(200)
            ->get();

        $permissions = \App\Models\Permission::select('id', 'user_id', 'tanggal', 'tanggal_selesai', 'jenis', 'alasan', 'status', 'created_at')
            ->with('user:id,name')
            ->latest()
            ->limit(200)
            ->get();

        $overtimes = \App\Models\Overtime::select('id', 'user_id', 'overtime_date', 'total_hours', 'reason', 'status', 'created_at')
            ->with('user:id,name')
            ->latest()
            ->limit(200)
            ->get();

        return view('hrd.tracking', compact('leaves', 'permissions', 'overtimes'));
    }

    public function employeeShifts(Request $request, User $user)
    {
        $month = $request->month ?? now()->format('Y-m');

        $year = Carbon::parse($month)->year;
        $monthNumber = Carbon::parse($month)->month;

        $periodStart = Carbon::parse($month)->startOfMonth()->format('Y-m-d');
        $periodEnd   = Carbon::parse($month)->endOfMonth()->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | ABSENSI PERIODE INI DIAMBIL SEKALI SAJA
        |--------------------------------------------------------------------------
        | Sebelumnya: 1 query Attendance per baris shift (N+1). Sekarang 1 query.
        | Hasil tampilan tetap sama karena tanggal absensi dipetakan per hari.
        */
        $attendanceByDate = Attendance::where('user_id', $user->id)
            ->whereBetween('tanggal', [$periodStart, $periodEnd])
            ->select('user_id', 'tanggal', 'jam_masuk', 'jam_keluar')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->tanggal)->format('Y-m-d'));

        $shifts = EmployeeShift::with('shift')
            ->where('user_id', $user->id)
            ->whereBetween('shift_date', [$periodStart, $periodEnd])
            ->orderBy('shift_date')
            ->get()
            ->map(function ($item) use ($user, $attendanceByDate) {

                $attendance = $attendanceByDate[
                    Carbon::parse($item->shift_date)->format('Y-m-d')
                ] ?? null;

                return [
                    'shift_date' => Carbon::parse($item->shift_date)->format('d-m-Y'),
                    'shift_name' => $item->shift->name ?? '-',

                    // SHIFT DATA
                    'start_time' => $item->start_time,
                    'end_time'   => $item->end_time,

                    // OFFICE FIX (ambil dari schedule service kalau kosong)
                    'office_start' => $item->start_time,
                    'office_end'   => $item->end_time,

                    'check_in' => $attendance?->jam_masuk
                        ? Carbon::parse($attendance->jam_masuk)->format('H:i')
                        : '-',

                    'check_out' => $attendance?->jam_keluar
                        ? Carbon::parse($attendance->jam_keluar)->format('H:i')
                        : '-',
                ];
            });

        /**
         * 🔥 TAMBAHAN: kalau user OFFICE, inject schedule hari ini
         */
        if (in_array($user->work_type, ['office_5', 'office_6'])) {

            $today = Carbon::now()->format('Y-m-d');

            $schedule = ScheduleService::getTodaySchedule($user);

            if ($schedule) {

                // Memakai map absensi periode di atas (tanpa query tambahan).
                $attendance = $attendanceByDate[$today] ?? null;

                $shifts->push([
                    'shift_date' => Carbon::now()->format('d-m-Y'),
                    'shift_name' => 'Office Schedule',

                    // INI YANG KAMU MAU
                    'start_time' => $schedule['start_time'],
                    'end_time'   => $schedule['end_time'],

                    'check_in' => $attendance?->jam_masuk
                        ? Carbon::parse($attendance->jam_masuk)->format('H:i')
                        : '-',

                    'check_out' => $attendance?->jam_keluar
                        ? Carbon::parse($attendance->jam_keluar)->format('H:i')
                        : '-',
                ]);
            }
        }

        return response()->json([
            'month' => $month,
            'data' => $shifts
        ]);
    }

    private function calculateAlphaDays(User $employee, $month = null)
    {
        $month = $month ?? now()->format('Y-m');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = Carbon::parse($month . '-01')->endOfMonth();

        /*
    |--------------------------------------------------------------------------
    | TANGGAL HADIR
    |--------------------------------------------------------------------------
    */
        $attendanceDates = Attendance::where('user_id', $employee->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->pluck('tanggal')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique();

        /*
    |--------------------------------------------------------------------------
    | TANGGAL IZIN
    |--------------------------------------------------------------------------
    */
        $permissionDates = PermissionRange::datesWithin(
            PermissionRange::applyOverlapsPeriod(
                Permission::where('user_id', $employee->id)
                    ->where('status', 'approved'),
                $startDate,
                $endDate
            )->get(['tanggal', 'tanggal_selesai']),
            $startDate,
            $endDate
        );

        /*
    |--------------------------------------------------------------------------
    | TANGGAL CUTI
    |--------------------------------------------------------------------------
    */
        $leaveDates = collect();

        $leaves = Leave::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $startDate);
            })
            ->get();

        foreach ($leaves as $leave) {

            $start = Carbon::parse($leave->start_date);
            $end   = Carbon::parse($leave->end_date);

            while ($start->lte($end)) {

                if ($start->between($startDate, $endDate)) {
                    $leaveDates->push(
                        $start->format('Y-m-d')
                    );
                }

                $start->addDay();
            }
        }

        $leaveDates = $leaveDates->unique();

        /*
    |--------------------------------------------------------------------------
    | SHIFT EMPLOYEE
    |--------------------------------------------------------------------------
    */
        if ($employee->work_type === 'shift') {

            $shiftDates = EmployeeShift::where('user_id', $employee->id)
                ->whereBetween('shift_date', [$startDate, $endDate])
                ->pluck('shift_date')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->unique();

            $alpha = 0;

            foreach ($shiftDates as $date) {

                if (
                    !$attendanceDates->contains($date) &&
                    !$permissionDates->contains($date) &&
                    !$leaveDates->contains($date)
                ) {
                    $alpha++;
                }
            }

            return $alpha;
        }

        /*
    |--------------------------------------------------------------------------
    | OFFICE_5 (SENIN - JUMAT)
    |--------------------------------------------------------------------------
    */
        if ($employee->work_type === 'office_5') {

            $alpha = 0;

            $date = $startDate->copy();

            while ($date->lte($endDate)) {

                if (!$date->isWeekend()) {

                    $day = $date->format('Y-m-d');

                    if (
                        !$attendanceDates->contains($day) &&
                        !$permissionDates->contains($day) &&
                        !$leaveDates->contains($day)
                    ) {
                        $alpha++;
                    }
                }

                $date->addDay();
            }

            return $alpha;
        }

        /*
    |--------------------------------------------------------------------------
    | OFFICE_6 (SENIN - SABTU)
    |--------------------------------------------------------------------------
    */
        if ($employee->work_type === 'office_6') {

            $alpha = 0;

            $date = $startDate->copy();

            while ($date->lte($endDate)) {

                // Minggu libur
                if ($date->dayOfWeek !== Carbon::SUNDAY) {

                    $day = $date->format('Y-m-d');

                    if (
                        !$attendanceDates->contains($day) &&
                        !$permissionDates->contains($day) &&
                        !$leaveDates->contains($day)
                    ) {
                        $alpha++;
                    }
                }

                $date->addDay();
            }

            return $alpha;
        }

        return 0;
    }
}