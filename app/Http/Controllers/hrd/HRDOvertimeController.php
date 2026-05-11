<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Overtime;

class HRDOvertimeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST HRD
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $overtimes = Overtime::with([
            'user',
            'pjApprover'
        ])
            ->where(
                'pj_status',
                'approved'
            )
            ->where(
                'hrd_status',
                'pending'
            )
            ->latest()
            ->get();

        return view(
            'hrd.lembur.lembur',
            compact('overtimes')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE HRD
    |--------------------------------------------------------------------------
    */
    public function approve($id)
    {
        $overtime = Overtime::findOrFail($id);

        $overtime->update([

            'status' => 'approved',

            'hrd_status' => 'approved',

            'hrd_approved_by' => auth()->id(),

            'hrd_approved_at' => now(),
        ]);

        return back()->with(
            'success',
            'Lembur berhasil diapprove HRD'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT HRD
    |--------------------------------------------------------------------------
    */
    public function reject($id)
    {
        $overtime = Overtime::findOrFail($id);

        $overtime->update([

            'status' => 'rejected',

            'hrd_status' => 'rejected',

            'hrd_approved_by' => auth()->id(),

            'hrd_approved_at' => now(),
        ]);

        return back()->with(
            'success',
            'Lembur ditolak HRD'
        );
    }
}
