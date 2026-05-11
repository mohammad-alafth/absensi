<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Overtime;

class PJOvertimeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST PJ
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $divisionRole = $this->getDivisionRole();

        $overtimes = Overtime::with('user')

            ->where('pj_status', 'pending')

            ->whereHas('user', function ($q) use ($divisionRole) {

                $q->where(
                    'role',
                    $divisionRole
                );
            })

            ->latest()

            ->get();

        return view(
            'pj.lembur.index',
            compact('overtimes')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE PJ
    |--------------------------------------------------------------------------
    */
    public function approve($id)
    {
        $overtime = Overtime::findOrFail($id);

        $overtime->update([

            'status' => 'waiting_hrd',

            'pj_status' => 'approved',

            'pj_approved_by' => auth()->id(),

            'pj_approved_at' => now(),
        ]);

        return back()->with(
            'success',
            'Lembur diteruskan ke HRD'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT PJ
    |--------------------------------------------------------------------------
    */
    public function reject($id)
    {
        $overtime = Overtime::findOrFail($id);

        $overtime->update([

            'status' => 'rejected',

            'pj_status' => 'rejected',

            'pj_approved_by' => auth()->id(),

            'pj_approved_at' => now(),
        ]);

        return back()->with(
            'success',
            'Lembur ditolak PJ'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DIVISION ROLE
    |--------------------------------------------------------------------------
    */
    private function getDivisionRole()
    {
        return str_replace(
            'pj_',
            '',
            auth()->user()->role
        );
    }
}
