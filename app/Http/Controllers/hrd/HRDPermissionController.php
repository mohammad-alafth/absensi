<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\ApprovalFlowService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Storage;

class HRDPermissionController extends Controller
{
    public function index()
    {
        // Stage approver ditentukan terpusat di ApprovalFlowService.
        // Mencakup status baru + status lama agar data lama tetap muncul.
        $stage = ApprovalFlowService::stageForApproverRole(auth()->user()->role);

        if (!$stage) {
            return back()->with('error', 'Role tidak memiliki akses ke halaman ini.');
        }

        $permissions = Permission::with('user')
            ->whereIn('status', ApprovalFlowService::statusesForStage($stage))
            ->latest()
            ->get();

        $stageLabel = ApprovalFlowService::labelForStage($stage);

        return view('hrd.permission.izin', compact('permissions', 'stage', 'stageLabel'));
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'signature' => 'nullable|string'
        ]);

        $permission = Permission::with('user')->findOrFail($id);

        $stage = ApprovalFlowService::stageForApproverRole(auth()->user()->role);

        if (!$stage) {
            return back()->with('error', 'Role Anda tidak berwenang menyetujui pengajuan ini.');
        }

        if (!in_array($permission->status, ApprovalFlowService::statusesForStage($stage), true)) {
            return back()->with('error', 'Pengajuan tidak berada pada tahap '
                . ApprovalFlowService::labelForStage($stage) . '.');
        }

        /*
    |--------------------------------------------------------------------------
    | FLOW BERJENJANG (rantai mengikuti grup role pengaju)
    |--------------------------------------------------------------------------
    */
        $chain = ApprovalFlowService::chainFor($permission->user->role ?? null);

        $permission->update(array_merge(
            ['status' => ApprovalFlowService::nextStatus($chain, $stage)],
            ApprovalFlowService::stagePayload($stage, 'approved', $request->signature)
        ));

        $this->regeneratePdf($permission);

        return back()->with('success', 'Izin disetujui oleh '
            . ApprovalFlowService::labelForStage($stage) . '.');
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|min:5'
        ]);

        $permission = Permission::findOrFail($id);

        $stage = ApprovalFlowService::stageForApproverRole(auth()->user()->role);

        if (!$stage) {
            return back()->with('error', 'Role Anda tidak berwenang menolak pengajuan ini.');
        }

        if (!in_array($permission->status, ApprovalFlowService::statusesForStage($stage), true)) {
            return back()->with('error', 'Pengajuan sudah diproses atau bukan tahap Anda.');
        }

        $permission->update(array_merge(
            ['status' => 'rejected'],
            ApprovalFlowService::stagePayload($stage, 'rejected', null, $request->note)
        ));

        $this->regeneratePdf($permission);

        return back()->with(
            'success',
            'Izin ditolak oleh ' . ApprovalFlowService::labelForStage($stage) . '.'
        );
    }


    private function regeneratePdf($permission)
    {
        $pdf = Pdf::loadView(
            'pdf.permission-letter',
            [
                'permission' => $permission->fresh([
                    'user',
                    'pjApprover',
                    'hrdApprover',
                    'headApprover',
                    'directorApprover'
                ])
            ]
        );

        $fileName =
            "permission/permission_{$permission->id}.pdf";

        Storage::disk('public')->put(
            $fileName,
            $pdf->output()
        );

        $permission->update([
            'pdf_file' => $fileName
        ]);
    }
}
