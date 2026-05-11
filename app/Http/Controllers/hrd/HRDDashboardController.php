<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\Overtime;

class HRDDashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Pending Leave
        |--------------------------------------------------------------------------
        */

        $pendingLeave = Leave::where(
            'pj_status',
            'approved'
        )
            ->where(
                'hrd_status',
                'pending'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Pending Permission
        |--------------------------------------------------------------------------
        */

        $pendingPermission = Permission::where(
            'pj_status',
            'approved'
        )
            ->where(
                'hrd_status',
                'pending'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Pending Overtime
        |--------------------------------------------------------------------------
        */

        $pendingOvertime = Overtime::where(
            'pj_status',
            'approved'
        )
            ->where(
                'hrd_status',
                'pending'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent Leave
        |--------------------------------------------------------------------------
        */

        $recentLeaves = Leave::with('user')
            ->latest()
            ->take(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view(
            'hrd.dashboard_hrd',
            compact(
                'pendingLeave',
                'pendingPermission',
                'pendingOvertime',
                'recentLeaves'
            )
        );
    }
}
