<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Exports\HRDRekapExport;
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


class HRDController extends Controller
{
    public function index(Request $request)
    {
        /*
            |--------------------------------------------------------------------------
            | FILTER BULAN
            |--------------------------------------------------------------------------
            */
        $month = $request->month ?? now()->format('Y-m');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate   = Carbon::parse($month . '-01')->endOfMonth();

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
                'user:id,name,role',
                'shift:id,name'
            ])
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

        $permissionGroups = Permission::select(
            'user_id',
            'tanggal'
        )
            ->where('status', 'approved')
            ->whereBetween('tanggal', [
                $startDate,
                $endDate
            ])
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
            $permissionDates = $userPermissions
                ->pluck('tanggal')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->unique();

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
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RANKING TELAT
        |--------------------------------------------------------------------------
        */
        $rankingTelat = collect($recaps)
            ->filter(fn($item) => $item['telat'] > 0)
            ->sortByDesc('telat')
            ->take(5)
            ->values();


        /*
        |--------------------------------------------------------------------------
        | TIMELINE SHIFT
        |--------------------------------------------------------------------------
        */
        $calendarEvents = [];

        foreach ($employeeShifts as $shift) {

            if (!$shift->user) {
                continue;
            }
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
                'nurse' => '#10b981',       // emerald
                'security' => '#ef4444',    // red
                'cs' => '#06b6d4',          // cyan
                'administrasi' => '#6366f1', // indigo
                'finance' => '#f59e0b',     // amber
                'kasir' => '#ec4899',       // pink
                'ipsrs' => '#64748b',       // slate
                'pharmacist' => '#8b5cf6',  // violet
            ];
            $calendarEvents[] = [
                'title' => $shift->user->name,
                'start' => $start->toDateTimeString(),
                'end'   => $end->toDateTimeString(),

                'backgroundColor' => $roleColors[$role] ?? '#1E40AF',
                'borderColor'     => $roleColors[$role] ?? '#1E40AF',

                'extendedProps' => [
                    'role'  => $role,
                    'shift' => $shift->shift->name ?? '-'
                ]
            ];
        }
        return view(
            'hrd.rekap.index',
            compact(
                'recaps',
                'month',
                'rankingTelat',
                'calendarEvents',
                'roles',
                'roleLabels'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT EXCEL
    |--------------------------------------------------------------------------
    */
    public function exportExcel(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');
        $role = $request->role ?? 'all';

        return Excel::download(
            new HRDRekapExport($month, $role),
            "rekap-$role-$month.xlsx"
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
        $date = $request->date;
        $month = $request->month;
        $statusList = [
            'pending',
            'waiting_head',
            'waiting_director',
            'waiting_medical_service',
            'waiting_hrd',
            'approved',
            'rejected'
        ];

        // 1. Ambil data
        switch ($type) {
            case 'attendance':
                $data = Attendance::whereDate('tanggal', $date ?? Carbon::today())->with('user')->get();
                break;
            case 'leave':
                $data = Leave::whereIn('status', $statusList)->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$month ?? now()->format('Y-m')])->with('user')->get();
                break;
            case 'permission':
                $data = Permission::whereIn('status', $statusList)->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$month ?? now()->format('Y-m')])->with('user')->get();
                break;
            case 'overtime':
                $data = Overtime::whereIn('status', $statusList)->whereRaw("DATE_FORMAT(overtime_date, '%Y-%m') = ?", [$month ?? now()->format('Y-m')])->with('user')->get();
                break;
            default:
                $data = collect([]);
        }

        if ($data->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk diekspor.');
        }

        // 2. Ekspor dengan Class Anonim yang sudah Disamakan Formatnya
        return Excel::download(new class($data) implements FromCollection, WithHeadings, WithMapping, WithEvents, WithColumnWidths {
            protected $data;
            public function __construct($data)
            {
                $this->data = $data;
            }
            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return ['NAMA PEGAWAI', 'TANGGAL / WAKTU', 'STATUS', 'KETERANGAN'];
            }

            public function map($item): array
            {
                return [
                    $item->user->name ?? 'N/A',
                    $item->tanggal ?? $item->start_date ?? $item->overtime_date ?? 'N/A',
                    ucwords(str_replace('_', ' ', $item->status ?? 'N/A')),
                    $item->reason ?? $item->hrd_note ?? $item->alasan ?? '-'
                ];
            }

            public function columnWidths(): array
            {
                // Disamakan lebar kolomnya agar proporsional
                return [
                    'A' => 30, // Nama
                    'B' => 20, // Tanggal
                    'C' => 30, // Status
                    'D' => 45, // Keterangan (Dibuat lebar)
                ];
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet;

                        // 1. Geser ke bawah 5 baris untuk Kop Surat
                        $sheet->insertNewRowBefore(1, 5);

                        // 2. Teks Header (Ditaruh di B dan C agar di tengah A dan D)
                        $sheet->setCellValue('B1', 'RS MATA Pekanbaru Eye Center');
                        $sheet->setCellValue('B2', 'Jl. Soekarno Hatta No. 236 – Pekanbaru – Riau');
                        $sheet->setCellValue('B3', 'Telp. (0761) 7875191, 0811 7605191 Fax. (0761) 7875195');
                        $sheet->setCellValue('B4', 'www.pekanbarueyecenter.com');

                        $sheet->mergeCells('B1:C1');
                        $sheet->mergeCells('B2:C2');
                        $sheet->mergeCells('B3:C3');
                        $sheet->mergeCells('B4:C4');

                        // 3. Styling Kop Surat
                        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('5a6ea8');
                        $sheet->getStyle('B1:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('B1:C4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                        // 4. Logo Kiri (A1) dan Logo Kanan (D1)
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

                        // 5. Styling Heading Tabel (Baris 6)
                        $sheet->getRowDimension(6)->setRowHeight(35);
                        $sheet->getStyle('A6:D6')->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                        // 6. Styling Data, Border, dan Wrap Text
                        $lastRow = $sheet->getHighestRow();
                        $sheet->getStyle('A6:D' . $lastRow)->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
                        ]);

                        $sheet->getStyle('A7:D' . $lastRow)->getAlignment()->setWrapText(true);

                        // 7. Fit to screen
                        $sheet->getPageSetup()->setFitToWidth(1);
                    },
                ];
            }
        }, "Laporan_" . ucfirst($type) . "_" . now()->format('Ymd_His') . ".xlsx");
    }

    public function reportAttendanceDaily(Request $request)
    {
        $date = $request->date ?? Carbon::today()->format('Y-m-d');
        $data = Attendance::whereDate('tanggal', $date)->with('user')->get();

        // Simpan filter untuk digunakan di tombol export
        return view('hrd.reports.template', [
            'data' => $data,
            'title' => 'Laporan Hadir ' . $date,
            'filter_date' => $date
        ]);
    }

    public function reportAbsentDaily()
    {
        $today = Carbon::today()->format('Y-m-d');

        $attendanceIds = Attendance::whereDate('tanggal', $today)
            ->pluck('user_id');

        $leaveIds = Leave::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('user_id');

        $permissionIds = Permission::where('status', 'approved')
            ->whereDate('tanggal', $today)
            ->pluck('user_id');

        $excludedIds = $attendanceIds
            ->merge($leaveIds)
            ->merge($permissionIds)
            ->unique();

        $data = User::whereNotIn('id', $excludedIds)
            ->whereNotIn('role', ['admin'])
            ->get()
            ->map(function ($employee) {

                $alphaDays = Attendance::where(
                    'user_id',
                    $employee->id
                )
                    ->whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)
                    ->where('status', 'alpha')
                    ->count();

                $employee->alpha_days = $alphaDays;

                return $employee;
            });
        foreach ($data as $employee) {

            $employee->alpha_days =
                $this->calculateAlphaDays(
                    $employee,
                    now()->format('Y-m')
                );
        }
        return view('hrd.reports.template-absent', [
            'data' => $data,
            'title' => 'Pegawai Tidak Hadir Hari Ini'
        ]);
    }

    public function reportLeave(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        $data = Leave::whereIn('status', ['pending', 'waiting_head', 'waiting_director', 'waiting_medical_service', 'waiting_hrd', 'approved', 'rejected'])
            ->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$month])
            ->with('user')
            ->latest()
            ->get();

        return view('hrd.reports.template-status', [
            'data' => $data,
            'title' => 'Rekap Cuti ' . $month,
            'filter_month' => $month,
            'reportType' => 'leave'
        ]);
    }

    public function reportPermission(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        // Pastikan nama kolom 'tanggal' sesuai dengan database Anda
        $data = Permission::whereIn('status', ['pending', 'waiting_head', 'waiting_director', 'waiting_medical_service', 'waiting_hrd', 'approved', 'rejected'])
            ->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$month])
            ->with('user')
            ->latest()
            ->get();

        return view('hrd.reports.template-status', [
            'data' => $data,
            'title' => 'Rekap Izin ' . $month,
            'filter_month' => $month,
            'reportType' => 'permission'
        ]);
    }

    public function reportOvertime(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        // Gunakan 'status' untuk filter waiting_...
        // Gunakan 'overtime_date' untuk filter bulan
        $data = Overtime::whereIn('status', ['pending', 'waiting_head', 'waiting_director', 'waiting_medical_service', 'waiting_hrd', 'approved', 'rejected'])
            ->whereRaw("DATE_FORMAT(overtime_date, '%Y-%m') = ?", [$month])
            ->with('user')
            ->latest()
            ->get();

        return view('hrd.reports.template-status', [
            'data' => $data,
            'title' => 'Rekap Lembur ' . $month,
            'filter_month' => $month,
            'reportType' => 'overtime'
        ]);
    }

    public function tracking()
    {
        $leaves = \App\Models\Leave::with('user')->latest()->get();
        $permissions = \App\Models\Permission::with('user')->latest()->get();
        $overtimes = \App\Models\Overtime::with('user')->latest()->get();

        return view('hrd.tracking', compact('leaves', 'permissions', 'overtimes'));
    }

    public function employeeShifts(
        Request $request,
        User $user
    ) {

        $month =
            $request->month
            ??
            now()->format('Y-m');

        $shifts = EmployeeShift::with('shift')
            ->where('user_id', $user->id)
            ->whereRaw(
                "DATE_FORMAT(shift_date,'%Y-%m') = ?",
                [$month]
            )
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->get();

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
        $permissionDates = Permission::where('user_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->pluck('tanggal')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique();

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
