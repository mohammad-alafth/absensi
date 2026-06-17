<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
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
        $role = auth()->user()->role;

        if ($role === 'hrd') {
            $overtimes = Overtime::where('status', 'waiting_hrd')->get();
        }

        if ($role === 'head_pegawai') {
            $overtimes = Overtime::where('status', 'waiting_head')->get();
        }

        if ($role === 'director') {
            $overtimes = Overtime::where('status', 'waiting_director')->get();
        }

        return view('hrd.lembur.lembur', compact('overtimes'));
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE HRD
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        $request->validate(['signature' => 'required']);
        $overtime = Overtime::findOrFail($id);
        $role = auth()->user()->role;

        if ($role === 'head_pegawai') {
            $overtime->update([
                'status' => 'waiting_director',
                'head_status' => 'approved',
                'head_signature' => $request->signature,
                'head_approved_by' => auth()->id(),
                'head_approved_at' => now(),
            ]);
            $this->regeneratePdf($overtime);
            return back()->with('success', 'Disetujui Head, diteruskan ke Direktur');
        }

        if ($role === 'director') {
            $overtime->update([
                'status' => 'approved',
                'director_status' => 'approved',
                'director_signature' => $request->signature,
                'director_approved_by' => auth()->id(),
                'director_approved_at' => now(),
            ]);
            $this->regeneratePdf($overtime);
            return back()->with('success', 'Disetujui sesuai alur');
        }



        if ($role === 'hrd') {
            $overtime->update([
                'status' => 'approved',
                'hrd_status' => 'approved',
            ]);

            $this->regeneratePdf($overtime);
        }

        return back()->with('success', 'Disetujui oleh HRD');
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

        $overtime->update([

            'status' => 'rejected',

            'hrd_status' => 'rejected',

            'hrd_note' => $request->note,

            'hrd_approved_by' => auth()->id(),

            'hrd_approved_at' => now(),
        ]);

        $this->regeneratePdf($overtime);

        return back()->with(
            'success',
            'Lembur ditolak HRD'
        );
    }
}
