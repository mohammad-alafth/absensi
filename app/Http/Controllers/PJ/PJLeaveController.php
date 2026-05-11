<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Leave;

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

        return view(
            'pj.cuti.index',
            compact('leaves')
        );
    }

    public function approve($id)
    {
        $leave = Leave::findOrFail($id);

        $leave->update([

            'pj_status' => 'approved',

            'pj_approved_by' => auth()->id(),

            'pj_approved_at' => now(),

            'status' => 'waiting_hrd'
        ]);

        return back()->with(
            'success',
            'Cuti diteruskan ke HRD'
        );
    }

    public function reject($id)
    {
        $leave = Leave::findOrFail($id);

        $leave->update([

            'pj_status' => 'rejected',

            'pj_approved_by' => auth()->id(),

            'pj_approved_at' => now(),

            'status' => 'rejected'
        ]);

        return back()->with(
            'success',
            'Cuti ditolak PJ'
        );
    }

    private function getDivisionRole()
    {
        return str_replace(
            'pj_',
            '',
            auth()->user()->role
        );
    }
}
