<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Permission;

class PJPermissionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST PJ
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $divisionRole = $this->getDivisionRole();

        $permissions = Permission::with('user')

            ->where(
                'pj_status',
                'pending'
            )

            ->whereHas(
                'user',
                function ($q) use ($divisionRole) {

                    $q->where(
                        'role',
                        $divisionRole
                    );
                }
            )

            ->latest()

            ->get();

        return view(
            'pj.permission.index',
            compact('permissions')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE PJ
    |--------------------------------------------------------------------------
    */
    public function approve($id)
    {
        $permission = Permission::findOrFail($id);

        $permission->update([

            'pj_status' => 'approved',

            'pj_approved_by' => auth()->id(),

            'pj_approved_at' => now(),

            'status' => 'waiting_hrd'

        ]);

        return back()->with(
            'success',
            'Izin diteruskan ke HRD'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT PJ
    |--------------------------------------------------------------------------
    */
    public function reject($id)
    {
        $permission = Permission::findOrFail($id);

        $permission->update([

            'pj_status' => 'rejected',

            'pj_approved_by' => auth()->id(),

            'pj_approved_at' => now(),

            'status' => 'rejected'

        ]);

        return back()->with(
            'success',
            'Izin ditolak PJ'
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
