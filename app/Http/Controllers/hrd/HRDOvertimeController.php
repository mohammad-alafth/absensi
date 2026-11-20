<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Services\ApprovalFlowService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Storage;

class HRDOvertimeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST HRD
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        // Stage approver ditentukan terpusat di ApprovalFlowService.
        // Mencakup status baru + status lama agar data lama tetap muncul.
        $stage = ApprovalFlowService::stageForApproverRole(auth()->user()->role);

        if (!$stage) {
            return back()->with('error', 'Role tidak memiliki akses ke halaman ini.');
        }

        $overtimes = Overtime::with('user')
            ->where(function ($query) {
                $query->where('status', 'like', 'waiting_%')
                    ->orWhere('status', 'pending');
            })
            ->latest()
            ->get()
            ->filter(function (Overtime $overtime) {
                $resolved = ApprovalFlowService::stageForSubmission($overtime, auth()->user()->role, 'overtime');
                return $resolved !== null
                    && in_array($overtime->status, ApprovalFlowService::statusesForStage($resolved), true);
            });

        $stageLabel = ApprovalFlowService::labelForStage($stage);

        return view('hrd.lembur.lembur', compact('overtimes', 'stage', 'stageLabel'));
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE HRD
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        $request->validate(['signature' => 'nullable|string']);

        $overtime = Overtime::with('user')->findOrFail($id);

        $stage = ApprovalFlowService::stageForSubmission($overtime, auth()->user()->role, 'overtime');

        if (!$stage) {
            return back()->with('error', 'Role Anda tidak berwenang menyetujui pengajuan ini.');
        }

        if (!in_array($overtime->status, ApprovalFlowService::statusesForStage($stage), true)) {
            return back()->with('error', 'Pengajuan tidak berada pada tahap '
                . ApprovalFlowService::labelForStage($stage) . '.');
        }

        // Rantai approval mengikuti grup role pengaju
        $chain = ApprovalFlowService::chainForSubmission($overtime, $overtime->user->role ?? null, 'overtime');

        $overtime->update(array_merge(
            ['status' => ApprovalFlowService::nextStatus($chain, $stage)],
            ApprovalFlowService::stagePayload($stage, 'approved', $request->signature)
        ));

        $this->regeneratePdf($overtime);

        return back()->with('success', 'Lembur disetujui oleh '
            . ApprovalFlowService::labelForStage($stage) . '.');
    }

    private function regeneratePdf($overtime)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'pdf.overtime-letter',
            [
                'overtime' => $overtime->fresh([
                    'user',
                    'pjApprover',
                    'hrdApprover',
                    'headApprover',
                    'directorApprover'
                ])
            ]
        );

        $fileName =
            "overtime/overtime_{$overtime->id}.pdf";

        Storage::disk('public')->put(
            $fileName,
            $pdf->output()
        );

        $overtime->update([
            'pdf_file' => $fileName
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT HRD
    |--------------------------------------------------------------------------
    */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|min:5'
        ]);

        $overtime = Overtime::findOrFail($id);

        $stage = ApprovalFlowService::stageForSubmission($overtime, auth()->user()->role, 'overtime');

        if (!$stage) {
            return back()->with('error', 'Role Anda tidak berwenang menolak pengajuan ini.');
        }

        if (!in_array($overtime->status, ApprovalFlowService::statusesForStage($stage), true)) {
            return back()->with('error', 'Pengajuan sudah diproses atau bukan tahap Anda.');
        }

        $overtime->update(array_merge(
            ['status' => 'rejected'],
            ApprovalFlowService::stagePayload($stage, 'rejected', null, $request->note)
        ));

        $this->regeneratePdf($overtime);

        return back()->with(
            'success',
            'Lembur ditolak oleh ' . ApprovalFlowService::labelForStage($stage) . '.'
        );
    }
}
