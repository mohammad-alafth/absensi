<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;
use App\Services\ApprovalFlowService;
use Illuminate\Support\Facades\Storage;

class PJLeaveController extends Controller
{
    public function index()
    {
        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);

        $leaves = Leave::with('user')
            ->where('pj_status', 'pending')
            ->whereHas('user', function ($q) use ($divisionRoles) {
                $q->whereIn('role', $divisionRoles);
            })
            ->latest()
            ->get();

        return view('pj.cuti.index', compact('leaves'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'signature' => 'required|string'
        ]);

        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);
        $leave = Leave::with('user')
            ->whereKey($id)
            ->whereHas('user', fn ($query) => $query->whereIn('role', $divisionRoles))
            ->firstOrFail();

        if (!ApprovalFlowService::needsPjForSubmission($leave, $leave->user->role, 'leave') || $leave->pj_status !== 'pending') {
            return back()->with('error', 'Cuti sudah diproses PJ');
        }

        $leave->update([
            'pj_status' => 'approved',
            'pj_signature' => $request->signature,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            // Tahap PJ selesai -> masuk stage approver pertama sesuai grup role
            'status' => ApprovalFlowService::statusAfterPjSubmission($leave, $leave->user->role, 'leave'),
        ]);

        $this->regenerateLeavePdf($leave);

        return back()->with('success', 'Cuti diteruskan sesuai flow');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|min:2|max:500'
        ]);

        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);
        $leave = Leave::whereKey($id)
            ->whereHas('user', fn ($query) => $query->whereIn('role', $divisionRoles))
            ->firstOrFail();

        if (!ApprovalFlowService::needsPjForSubmission($leave, $leave->user->role, 'leave') || $leave->pj_status !== 'pending') {
            return back()->with('error', 'Cuti sudah diproses PJ');
        }

        $leave->update([
            'pj_status' => 'rejected',
            'pj_note' => $request->note,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            'status' => 'rejected',
        ]);

        $this->regenerateLeavePdf($leave);

        return back()->with('success', 'Cuti ditolak PJ');
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
