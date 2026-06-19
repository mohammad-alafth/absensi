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
        $divisionRole = $this->getDivisionRole();

        $overtimes = Overtime::with('user')
            ->where('pj_status', 'pending')
            ->whereHas('user', function ($q) use ($divisionRole) {
                $q->where('role', $divisionRole);
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

        $overtime = Overtime::with('user')->findOrFail($id);

        if ($overtime->pj_status !== 'pending') {
            return back()->with('error', 'Lembur sudah diproses PJ');
        }

        /*
        |----------------------------------------------------------
        | FLOW DARI SERVICE (SINGLE SOURCE OF TRUTH)
        |----------------------------------------------------------
        */
        $flow = ApprovalFlowService::handle($overtime->user->role);

        $overtime->update([
            'pj_status' => 'approved',
            'pj_signature' => $request->signature,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            'status' => 'waiting_hrd',
            'hrd_status' => $flow['hrd_status'],
        ]);

        $this->regeneratePdf($overtime);

        return back()->with('success', 'Lembur berhasil diproses');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|min:2'
        ]);

        $overtime = Overtime::findOrFail($id);

        if ($overtime->pj_status !== 'pending') {
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

    private function getDivisionRole()
    {
        $role = auth()->user()->role;

        return str_starts_with($role, 'pj_')
            ? str_replace('pj_', '', $role)
            : $role;
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

        $fileName = "overtime/overtime_{$overtime->id}.pdf";

        Storage::disk('public')->put(
            $fileName,
            $pdf->output()
        );

        $overtime->update([
            'pdf_file' => $fileName
        ]);
    }
}
