<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\Overtime;
use App\Services\ApprovalFlowService;

class HRDDashboardController extends Controller
{
    public function index()
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

        return view('hrd.dashboard_hrd', compact(
            'pendingLeave',
            'pendingPermission',
            'pendingOvertime',
            'recentSubmissions',
            'stage',
            'stageLabel'
        ));
    }
}
