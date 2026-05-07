<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Leave;

class PJLeaveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $userRole = auth()->user()->role;

        /*
        |--------------------------------------------------------------------------
        | HRD
        |--------------------------------------------------------------------------
        */
        if ($userRole == 'hrd') {

            $leaves = Leave::with([
                'user',
                'pjApprover'
            ])

                ->where('pj_status', 'approved')

                ->where('hrd_status', 'pending')

                ->latest()

                ->get();

            return view(
                'hrd.cuti',
                compact('leaves')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PJ
        |--------------------------------------------------------------------------
        */
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


    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */
    public function approve($id)
    {
        $leave = Leave::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | HRD
        |--------------------------------------------------------------------------
        */
        if (auth()->user()->role == 'hrd') {

            $leave->update([

                'hrd_status' => 'approved',

                'hrd_approved_by' => auth()->id(),

                'hrd_approved_at' => now(),

                'status' => 'approved'
            ]);

            return back()->with(
                'success',
                'Cuti berhasil diapprove HRD'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PJ
        |--------------------------------------------------------------------------
        */
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


    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */
    public function reject($id)
    {
        $leave = Leave::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | HRD
        |--------------------------------------------------------------------------
        */
        if (auth()->user()->role == 'hrd') {

            $leave->update([

                'hrd_status' => 'rejected',

                'hrd_approved_by' => auth()->id(),

                'hrd_approved_at' => now(),

                'status' => 'rejected'
            ]);

            return back()->with(
                'success',
                'Cuti ditolak HRD'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PJ
        |--------------------------------------------------------------------------
        */
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


    /*
    |--------------------------------------------------------------------------
    | DIVISION
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
