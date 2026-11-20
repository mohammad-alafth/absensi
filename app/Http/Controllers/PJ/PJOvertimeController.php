<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use Illuminate\Http\Request;
use App\Services\ApprovalFlowService;
use Illuminate\Support\Facades\Storage;

class PJOvertimeController extends Controller
{
    public function index()
    {
        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);

        $overtimes = Overtime::with('user')
            ->where('pj_status', 'pending')
            ->whereHas('user', function ($q) use ($divisionRoles) {
                $q->whereIn('role', $divisionRoles);
            })
            ->latest()
            ->get();

        return view('pj.lembur.index', compact('overtimes'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'signature' => 'required|string'
        ]);

        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);
        $overtime = Overtime::with('user')
            ->whereKey($id)
            ->whereHas('user', fn ($query) => $query->whereIn('role', $divisionRoles))
            ->firstOrFail();

        if (!ApprovalFlowService::needsPjForSubmission($overtime, $overtime->user->role, 'overtime') || $overtime->pj_status !== 'pending') {
            return back()->with('error', 'Lembur sudah diproses PJ');
        }

        $overtime->update([
            'pj_status' => 'approved',
            'pj_signature' => $request->signature,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            // Tahap PJ selesai -> masuk stage approver pertama sesuai grup role
            'status' => ApprovalFlowService::statusAfterPjSubmission($overtime, $overtime->user->role, 'overtime'),
        ]);

        $this->regeneratePdf($overtime);

        return back()->with('success', 'Lembur diteruskan sesuai flow');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|min:2'
        ]);

        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);
        $overtime = Overtime::whereKey($id)
            ->whereHas('user', fn ($query) => $query->whereIn('role', $divisionRoles))
            ->firstOrFail();

        if (!ApprovalFlowService::needsPjForSubmission($overtime, $overtime->user->role, 'overtime') || $overtime->pj_status !== 'pending') {
            return back()->with('error', 'Lembur sudah diproses PJ');
        }

        $overtime->update([
            'pj_status' => 'rejected',
            'pj_note' => $request->note,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            'status' => 'rejected',
        ]);

        return back()->with('success', 'Lembur ditolak PJ');
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

        $fileName = "overtimes/overtime_{$overtime->id}.pdf";

        Storage::disk('public')->put($fileName, $pdf->output());

        $overtime->update(['pdf_file' => $fileName]);
    }
}
