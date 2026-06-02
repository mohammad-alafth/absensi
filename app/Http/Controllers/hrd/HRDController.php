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
        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        $selectedMonth = $startDate->month;
        $selectedYear = $startDate->year;

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEE
        |--------------------------------------------------------------------------
        */
        $employees = User::whereNotIn('role', ['admin'])->get();

        /*
        |--------------------------------------------------------------------------
        | DATA REKAP
        |--------------------------------------------------------------------------
        */
        $recaps = [];

        foreach ($employees as $employee) {
            $attendances = Attendance::where('user_id', $employee->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            /*
            |--------------------------------------------------------------------------
            | TOTAL STATUS
            |--------------------------------------------------------------------------
            */
            $hadir = $attendances->where('status', 'hadir')->count();
            $telat = $attendances->where('status', 'terlambat')->count();
            $izin = $attendances->where('status', 'izin')->count();
            $sakit = $attendances->where('status', 'sakit')->count();
            $alpha = $attendances->where('status', 'alpha')->count();

            $totalLateMinutes = 0;

            foreach ($attendances as $attendance) {
                $late = $attendance->late_minutes ?? 0;

                /*
                |--------------------------------------------------------------------------
                | HITUNG SETELAH 15 MENIT
                |--------------------------------------------------------------------------
                */
                $realLate = max($late - 15, 0);
                $totalLateMinutes += $realLate;
            }

            /*
            |--------------------------------------------------------------------------
            | FORMAT JAM MENIT KETERLAMBATAN
            |--------------------------------------------------------------------------
            */
            $lateHours = floor($totalLateMinutes / 60);
            $lateRemainMinutes = $totalLateMinutes % 60;
            $lateFormatted = '';

            if ($lateHours > 0) {
                $lateFormatted .= $lateHours . ' Jam ';
            }
            $lateFormatted .= $lateRemainMinutes . ' Menit';

            /*
            |--------------------------------------------------------------------------
            | TOTAL JAM KERJA + AMBIL DATA MURNI OVERTIME MINUTES DARI ATTENDANCE
            |--------------------------------------------------------------------------
            */
            $totalJam = 0;
            $totalActualOvertimeMinutes = 0;

            foreach ($attendances as $attendance) {
                // Akumulasi murni data menit lembur dari field 'overtime_minutes' tabel attendance
                $totalActualOvertimeMinutes += $attendance->overtime_minutes ?? 0;

                if ($attendance->jam_masuk && $attendance->jam_keluar) {
                    $masuk = Carbon::parse($attendance->jam_masuk);
                    $keluar = Carbon::parse($attendance->jam_keluar);

                    /*
                    |--------------------------------------------------------------------------
                    | SHIFT MALAM
                    |--------------------------------------------------------------------------
                    */
                    if ($keluar->lt($masuk)) {
                        $keluar->addDay();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL JAM KERJA AKTUAL
                    |--------------------------------------------------------------------------
                    */
                    $workMinutes = $masuk->diffInMinutes($keluar);
                    $totalJam += $workMinutes;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | RATA-RATA JAM
            |--------------------------------------------------------------------------
            */
            $hariKerja = $hadir + $telat;
            $avgJam = $hariKerja > 0
                ? round(($totalJam / 60) / $hariKerja, 1)
                : 0;

            /*
            |--------------------------------------------------------------------------
            | STATUS PERFORMA
            |--------------------------------------------------------------------------
            */
            if ($alpha >= 3 || $telat >= 8) {
                $performance = 'Buruk';
            } elseif ($telat >= 5) {
                $performance = 'Evaluasi';
            } else {
                $performance = 'Baik';
            }

            /*
            |--------------------------------------------------------------------------
            | FORMAT STRING JAM LEMBUR MURNI PRESENSI
            |--------------------------------------------------------------------------
            | Memecah total menit lembur murni dari database attendance 
            | menjadi string format teks "X Jam Y Menit".
            */
            $finalOvertimeHours = floor($totalActualOvertimeMinutes / 60);
            $finalOvertimeMinutes = $totalActualOvertimeMinutes % 60;

            $overtimeFormatted = "";
            if ($finalOvertimeHours > 0) {
                $overtimeFormatted .= $finalOvertimeHours . " Jam ";
            }
            if ($finalOvertimeMinutes > 0 || $finalOvertimeHours == 0) {
                $overtimeFormatted .= $finalOvertimeMinutes . " Menit";
            }

            $recaps[] = [
                'employee' => $employee,
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
                'overtimes' => trim($overtimeFormatted), // Dilempar berupa teks matang ke Blade UI Anda
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | SORTING TELAT TERBANYAK
        |--------------------------------------------------------------------------
        */
        $rankingTelat = collect($recaps)
            ->sortByDesc('telat')
            ->take(5);

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */
        return view('hrd.rekap.index', compact(
            'recaps',
            'month',
            'rankingTelat'
        ));
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

        // 1. Ambil data dengan eager loading 'user' agar tidak terjadi N+1 query
        switch ($type) {
            case 'attendance':
                $data = Attendance::whereDate('tanggal', $date ?? Carbon::today())->with('user')->get();
                break;
            case 'leave':
                // Pastikan 'start_date' sudah benar di tabel leaves
                $data = Leave::where('status', $statusList)->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$month ?? now()->format('Y-m')])->with('user')->get();
                break;
            case 'permission':
                // Cek database Anda, apakah kolomnya 'tanggal' atau 'permission_date'?
                // Jika error 1054 muncul lagi, ganti 'tanggal' dengan nama kolom yang benar
                $data = Permission::where('status', $statusList)->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$month ?? now()->format('Y-m')])->with('user')->get();
                break;
            case 'overtime':
                // PERBAIKAN: Gunakan 'overtime_date' sesuai screenshot Anda
                $data = Overtime::where('status', $statusList)->whereRaw("DATE_FORMAT(overtime_date, '%Y-%m') = ?", [$month ?? now()->format('Y-m')])->with('user')->get();
                break;
            default:
                $data = collect([]);
        }

        if ($data->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk diekspor.');
        }

        // 2. Gunakan class anonim dengan mapping yang lebih fleksibel
        return Excel::download(new class($data, $type) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            protected $data, $type;
            public function __construct($data, $type)
            {
                $this->data = $data;
                $this->type = $type;
            }

            public function collection()
            {
                return $this->data->map(function ($item) {
                    // Pemetaan dinamis berdasarkan tipe
                    return [
                        'Nama Pegawai' => $item->user->name ?? 'N/A',
                        'Tanggal'      => $item->tanggal ?? $item->start_date ?? $item->overtime_date ?? 'N/A',
                        'Status'       => ucwords(str_replace('_', ' ', $item->status ?? 'N/A')),
                        'Keterangan'   => $item->reason ?? $item->hrd_note ?? '-'
                    ];
                });
            }

            public function headings(): array
            {
                return ['Nama Pegawai', 'Tanggal / Waktu', 'Status / Keterangan'];
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
        $today = Carbon::today();
        $presentIds = Attendance::whereDate('tanggal', $today)->pluck('user_id');
        $data = User::whereNotIn('id', $presentIds)->where('work_type', 'shift')->get();
        return view('hrd.reports.template-absent', ['data' => $data, 'title' => 'Pegawai Tidak Hadir Hari Ini']);
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
}
