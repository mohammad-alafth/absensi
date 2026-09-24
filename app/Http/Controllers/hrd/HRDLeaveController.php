<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use App\Services\ApprovalFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HRDLeaveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST CUTI (PUSAT APPROVAL)
    |--------------------------------------------------------------------------
    | Role approver menyesuaikan stage-nya sendiri:
    | medical_service, kabag_umum, manager_umum,
    | kabag_marketing, manager_finance, director
    */
    public function index()
    {
        $stage = ApprovalFlowService::stageForApproverRole(auth()->user()->role);

        if (!$stage) {
            return back()->with('error', 'Role tidak memiliki akses ke halaman ini.');
        }

        $leaves = Leave::with('user')
            ->whereIn('status', ApprovalFlowService::statusesForStage($stage))
            ->latest()
            ->get();

        $stageLabel = ApprovalFlowService::labelForStage($stage);

        return view('hrd.cuti.cuti', compact('leaves', 'stage', 'stageLabel'));
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE HRD
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'signature' => 'nullable|string'
        ]);

        $leave = Leave::with('user')->findOrFail($id);

        $stage = ApprovalFlowService::stageForApproverRole(auth()->user()->role);

        if (!$stage) {
            return back()->with('error', 'Role Anda tidak berwenang menyetujui pengajuan ini.');
        }

        if (!in_array($leave->status, ApprovalFlowService::statusesForStage($stage), true)) {
            return back()->with('error', 'Pengajuan tidak berada pada tahap '
                . ApprovalFlowService::labelForStage($stage) . '.');
        }

        // Rantai approval mengikuti grup role pengaju
        $chain = ApprovalFlowService::chainFor($leave->user->role ?? null);

        $leave->update(array_merge(
            ['status' => ApprovalFlowService::nextStatus($chain, $stage)],
            ApprovalFlowService::stagePayload($stage, 'approved', $request->signature)
        ));

        $this->regenerateLeavePdf($leave);

        return back()->with('success', 'Cuti disetujui oleh '
            . ApprovalFlowService::labelForStage($stage) . '.');
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT HRD
    |--------------------------------------------------------------------------
    */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|min:2|max:500'
        ]);

        $leave = Leave::findOrFail($id);

        $stage = ApprovalFlowService::stageForApproverRole(auth()->user()->role);

        if (!$stage) {
            return back()->with('error', 'Role Anda tidak berwenang menolak pengajuan ini.');
        }

        if (!in_array($leave->status, ApprovalFlowService::statusesForStage($stage), true)) {

            return back()->with(
                'error',
                'Pengajuan sudah diproses atau bukan tahap Anda.'
            );
        }

        $leave->update(array_merge(
            ['status' => 'rejected'],
            ApprovalFlowService::stagePayload($stage, 'rejected', null, $request->note)
        ));

        $this->regenerateLeavePdf($leave);

        return back()->with(
            'success',
            'Cuti ditolak oleh ' . ApprovalFlowService::labelForStage($stage) . '.'
        );
    }
    private function regenerateLeavePdf($leave)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'pdf.leave-letter',
            [
                'leave' => $leave->fresh([
                    'user',
                    'pjApprover',
                    'hrdApprover',
                    'headApprover',
                    'directorApprover'
                ])
            ]
        );

        $fileName = "leaves/leave_{$leave->id}.pdf";

        Storage::disk('public')->put(
            $fileName,
            $pdf->output()
        );

        $leave->update([
            'pdf_file' => $fileName
        ]);
    }
}
