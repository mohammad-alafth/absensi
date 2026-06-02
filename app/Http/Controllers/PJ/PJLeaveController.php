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
        $divisionRole = $this->getDivisionRole();

        $leaves = Leave::with('user')
            ->where('pj_status', 'pending')
            ->whereHas('user', function ($q) use ($divisionRole) {
                $q->where('role', $divisionRole);
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

        $leave = Leave::with('user')->findOrFail($id);

        if ($leave->pj_status !== 'pending') {
            return back()->with('error', 'Cuti sudah diproses PJ');
        }

        /*
        |----------------------------------------------------------
        | FLOW DARI SERVICE (SINGLE SOURCE OF TRUTH)
        |----------------------------------------------------------
        */
        $flow = ApprovalFlowService::handle($leave->user->role);

        $leave->update([
            'pj_status' => 'approved',
            'pj_signature' => $request->signature,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            'status' => $flow['status'],
            'hrd_status' => $flow['hrd_status'],
        ]);

        $this->regenerateLeavePdf($leave);

        return back()->with('success', 'Cuti diteruskan sesuai flow');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|min:2|max:500'
        ]);

        $leave = Leave::findOrFail($id);

        if ($leave->pj_status !== 'pending') {
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

    private function getDivisionRole()
    {
        $role = auth()->user()->role;

        return str_starts_with($role, 'pj_')
            ? str_replace('pj_', '', $role)
            : $role;
    }

    private function regenerateLeavePdf($leave)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'pdf.leave-letter',
            [
                'leave' => $leave->fresh([
                    'user',
                    'pjApprover',
                    'hrdApprover'
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
