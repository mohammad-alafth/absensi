<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\Overtime;
use App\Models\OvertimePunch;
use App\Services\ApprovalFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HRDDashboardController extends Controller
{
    public function index(Request $request)
    {
        $role = auth()->user()->role;

        /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */
        $leaves = Leave::query();
        $permissions = Permission::query();
        $overtimes = Overtime::query();

        /*
    |--------------------------------------------------------------------------
    | FILTER BERDASARKAN STAGE APPROVER
    |--------------------------------------------------------------------------
    | Setiap role approver hanya melihat pengajuan yang berada pada tahapnya:
    | medical_service (+alias lama medical_service), kabag_umum, manager_umum,
    | kabag_marketing, manager_finance, director.
    | Status lama (mis. waiting_medical_service) ikut dicocokkan agar
    | data lama tetap muncul di antrean.
    */
        $stage = ApprovalFlowService::stageForApproverRole($role);
        $stageLabel = $stage ? ApprovalFlowService::labelForStage($stage) : null;

        if ($stage) {

            $targetStatuses = ApprovalFlowService::statusesForStage($stage);

            $leaves->whereIn('status', $targetStatuses);
            $permissions->whereIn('status', $targetStatuses);
            $overtimes->whereIn('status', $targetStatuses);
        } else {

            // Role tanpa tahap approval (mis. HRD): tidak ada data pengajuan
            $leaves->whereRaw('1 = 0');
            $permissions->whereRaw('1 = 0');
            $overtimes->whereRaw('1 = 0');
        }

        /*
    |--------------------------------------------------------------------------
    | COUNTS
    |--------------------------------------------------------------------------
    */
        $pendingLeave = $leaves->count();
        $pendingPermission = $permissions->count();
        $pendingOvertime = $overtimes->count();

        /*
    |--------------------------------------------------------------------------
    | RECENT (ROLE FILTERED)
    |--------------------------------------------------------------------------
    */
        $leaves = $leaves->with('user:id,name')->latest()->take(5)->get()->map(function ($item) {
            $item->type = 'Cuti';
            return $item;
        });

        $permissions = $permissions->with('user:id,name')->latest()->take(5)->get()->map(function ($item) {
            $item->type = 'Izin';
            return $item;
        });

        $overtimes = $overtimes->with('user:id,name')->latest()->take(5)->get()->map(function ($item) {
            $item->type = 'Lembur';
            return $item;
        });

        $recentSubmissions = $leaves
            ->concat($permissions)
            ->concat($overtimes)
            ->sortByDesc('created_at')
            ->take(10);

        $historyStatus = $request->input('history_status');
        $historyStatus = is_string($historyStatus) ? $historyStatus : '';

        $historySearch = $request->input('history_search');
        $historySearch = is_string($historySearch) ? trim($historySearch) : '';

        $historyBase = $stage
            ? $this->approvalHistoryQuery($stage, (int) auth()->id())
            : DB::query()->fromSub(
                DB::table('leaves')
                    ->whereRaw('1 = 0')
                    ->selectRaw("NULL as id, NULL as employee_name, NULL as employee_role, NULL as type, NULL as submitted_at, NULL as request_date, NULL as period_end, NULL as duration, NULL as category, NULL as reason, NULL as time_start, NULL as time_end, NULL as decision_status, NULL as action_at, NULL as note, NULL as employee_signature, NULL as pdf_file, NULL as attachment, NULL as proof_type, NULL as proof_note, NULL as actual_start_at, NULL as actual_end_at, NULL as actual_minutes"),
                'approval_history'
            );

        $historyCounts = (clone $historyBase)
            ->select('decision_status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('decision_status')
            ->pluck('aggregate', 'decision_status');

        $historyQuery = clone $historyBase;

        if (in_array($historyStatus, ['approved', 'rejected'], true)) {
            $historyQuery->where('decision_status', $historyStatus);
        } else {
            $historyStatus = 'all';
        }

        if ($historySearch !== '') {
            $historyQuery->where('employee_name', 'like', '%' . $historySearch . '%');
        }

        $approvalHistory = $historyQuery
            ->orderByDesc('action_at')
            ->paginate(8, ['*'], 'history_page')
            ->withQueryString();

        if ($stage) {
            // Pertahankan panel history saat pengguna berpindah halaman.
            $approvalHistory->appends(['history_open' => 1]);
        }

        /*
        |----------------------------------------------------------------------
        | BUKTI ABSEN LEMBUR UNTUK DETAIL HISTORY
        |----------------------------------------------------------------------
        | Query history memakai union DB (bukan model), sehingga log absen
        | lembur dimuat terpisah hanya untuk baris lembur pada halaman aktif.
        */
        $historyOvertimeIds = collect($approvalHistory->items())
            ->where('type', 'Lembur')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $historyPunches = empty($historyOvertimeIds)
            ? collect()
            : OvertimePunch::query()
                ->whereIn('overtime_id', $historyOvertimeIds)
                ->oldest('punched_at')
                ->get()
                ->groupBy('overtime_id');

        $historyOpen = $stage !== null && (
            $request->boolean('history_open')
            || $historySearch !== ''
            || $historyStatus !== 'all'
            || $approvalHistory->currentPage() > 1
        );

        return view('hrd.dashboard_hrd', compact(
            'pendingLeave',
            'pendingPermission',
            'pendingOvertime',
            'recentSubmissions',
            'stage',
            'stageLabel',
            'approvalHistory',
            'historyCounts',
            'historyStatus',
            'historySearch',
            'historyOpen',
            'historyPunches'
        ));
    }

    /**
     * Riwayat hanya berisi keputusan yang dibuat oleh approver aktif pada
     * stage-nya sendiri. Query union menjaga satu pagination lintas tiga
     * jenis pengajuan tanpa mengubah data atau alur approval.
     */
    private function approvalHistoryQuery(string $stage, int $approverId)
    {
        $statusColumn = $stage . '_status';
        $approvedByColumn = $stage . '_approved_by';
        $approvedAtColumn = $stage . '_approved_at';
        $noteColumn = $stage . '_note';

        $leave = DB::table('leaves as submissions')
            ->join('users as employees', 'employees.id', '=', 'submissions.user_id')
            ->where('submissions.' . $approvedByColumn, $approverId)
            ->whereIn('submissions.' . $statusColumn, ['approved', 'rejected'])
            ->whereNotNull('submissions.' . $approvedAtColumn)
            ->select([
                'submissions.id',
                'employees.name as employee_name',
                'employees.role as employee_role',
                'submissions.created_at as submitted_at',
                'submissions.start_date as request_date',
                'submissions.end_date as period_end',
                'submissions.total_days as duration',
                'submissions.leave_type as category',
                'submissions.reason as reason',
                DB::raw('NULL as time_start'),
                DB::raw('NULL as time_end'),
                'submissions.' . $approvedAtColumn . ' as action_at',
                'submissions.' . $statusColumn . ' as decision_status',
                'submissions.' . $noteColumn . ' as note',
                'submissions.employee_signature as employee_signature',
                'submissions.pdf_file as pdf_file',
                DB::raw('NULL as attachment'),
                DB::raw('NULL as proof_type'),
                DB::raw('NULL as proof_note'),
                DB::raw('NULL as actual_start_at'),
                DB::raw('NULL as actual_end_at'),
                DB::raw('NULL as actual_minutes'),
            ])
            ->selectRaw("'Cuti' as type");

        $permission = DB::table('permissions as submissions')
            ->join('users as employees', 'employees.id', '=', 'submissions.user_id')
            ->where('submissions.' . $approvedByColumn, $approverId)
            ->whereIn('submissions.' . $statusColumn, ['approved', 'rejected'])
            ->whereNotNull('submissions.' . $approvedAtColumn)
            ->select([
                'submissions.id',
                'employees.name as employee_name',
                'employees.role as employee_role',
                'submissions.created_at as submitted_at',
                'submissions.tanggal as request_date',
                'submissions.tanggal_selesai as period_end',
                DB::raw('NULL as duration'),
                'submissions.jenis as category',
                'submissions.alasan as reason',
                'submissions.jam_mulai as time_start',
                'submissions.jam_selesai as time_end',
                'submissions.' . $approvedAtColumn . ' as action_at',
                'submissions.' . $statusColumn . ' as decision_status',
                'submissions.' . $noteColumn . ' as note',
                'submissions.employee_signature as employee_signature',
                'submissions.pdf_file as pdf_file',
                'submissions.lampiran as attachment',
                DB::raw('NULL as proof_type'),
                DB::raw('NULL as proof_note'),
                DB::raw('NULL as actual_start_at'),
                DB::raw('NULL as actual_end_at'),
                DB::raw('NULL as actual_minutes'),
            ])
            ->selectRaw("'Izin' as type");

        $overtime = DB::table('overtimes as submissions')
            ->join('users as employees', 'employees.id', '=', 'submissions.user_id')
            ->where('submissions.' . $approvedByColumn, $approverId)
            ->whereIn('submissions.' . $statusColumn, ['approved', 'rejected'])
            ->whereNotNull('submissions.' . $approvedAtColumn)
            ->select([
                'submissions.id',
                'employees.name as employee_name',
                'employees.role as employee_role',
                'submissions.created_at as submitted_at',
                'submissions.overtime_date as request_date',
                'submissions.overtime_date as period_end',
                'submissions.total_hours as duration',
                'submissions.day_type as category',
                'submissions.reason as reason',
                'submissions.start_time as time_start',
                'submissions.end_time as time_end',
                'submissions.' . $approvedAtColumn . ' as action_at',
                'submissions.' . $statusColumn . ' as decision_status',
                'submissions.' . $noteColumn . ' as note',
                'submissions.employee_signature as employee_signature',
                'submissions.pdf_file as pdf_file',
                DB::raw('NULL as attachment'),
                'submissions.proof_type as proof_type',
                'submissions.proof_note as proof_note',
                'submissions.actual_start_at as actual_start_at',
                'submissions.actual_end_at as actual_end_at',
                'submissions.actual_minutes as actual_minutes',
            ])
            ->selectRaw("'Lembur' as type");

        return DB::query()->fromSub(
            $leave->unionAll($permission)->unionAll($overtime),
            'approval_history'
        );
    }
}
