<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HRDLeaveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST CUTI HRD
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $role = auth()->user()->role;

        if ($role === 'hrd') {
            $leaves = Leave::where('status', 'waiting_hrd')->get();
        }

        if ($role === 'head_pegawai') {
            $leaves = Leave::where('status', 'waiting_head')->get();
        }

        if ($role === 'director') {
            $leaves = Leave::where('status', 'waiting_director')->get();
        }

        return view('hrd.cuti.cuti', compact('leaves'));
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE HRD
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $role = auth()->user()->role;

        if ($role === 'head_pegawai') {
            $leave->update([
                'status' => 'waiting_director',
                'head_status' => 'approved',
                'head_signature' => $request->signature, // Menyimpan TTD Head
                'head_approved_by' => auth()->id(),
                'head_approved_at' => now(),
            ]);
        }

        if ($role === 'director') {
            $leave->update([
                'status' => 'approved',
                'director_status' => 'approved',
                'director_signature' => $request->signature, // Menyimpan TTD Direktur
                'director_approved_by' => auth()->id(),
                'director_approved_at' => now(),
            ]);
            $this->regenerateLeavePdf($leave);

            return back()->with('success', 'Disetujui Final oleh Direktur');
        }

        if ($role === 'hrd') {
            $leave->update([
                'status' => 'approved',
                'hrd_status' => 'approved',
                'hrd_approved_by' => auth()->id(),
                'hrd_approved_at' => now(),
            ]);
            $this->regenerateLeavePdf($leave);
        }

        return back()->with('success', 'Disetujui oleh HRD');
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
        if ($leave->hrd_status != 'pending') {

            return back()->with(
                'error',
                'Cuti sudah diproses HRD'
            );
        }

        $leave->update([

            'hrd_status' => 'rejected',

            'hrd_note' => $request->note,

            'hrd_approved_by' => auth()->id(),

            'hrd_approved_at' => now(),

            'status' => 'rejected'
        ]);
        $this->regenerateLeavePdf($leave);

        return back()->with(
            'success',
            'Cuti ditolak HRD'
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
