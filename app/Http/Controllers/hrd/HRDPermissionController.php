<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Permission;

class HRDPermissionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST HRD
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $permissions = Permission::with([

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
            'hrd.permission.izin',
            compact('permissions')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE HRD
    |--------------------------------------------------------------------------
    */
    public function approve($id)
    {
        $permission = Permission::findOrFail($id);

        $permission->update([

            'hrd_status' => 'approved',

            'hrd_approved_by' => auth()->id(),

            'hrd_approved_at' => now(),

            'status' => 'approved'

        ]);

        return back()->with(
            'success',
            'Izin berhasil diapprove HRD'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT HRD
    |--------------------------------------------------------------------------
    */
    public function reject($id)
    {
        $permission = Permission::findOrFail($id);

        $permission->update([

            'hrd_status' => 'rejected',

            'hrd_approved_by' => auth()->id(),

            'hrd_approved_at' => now(),

            'status' => 'rejected'

        ]);

        return back()->with(
            'success',
            'Izin ditolak HRD'
        );
    }
}