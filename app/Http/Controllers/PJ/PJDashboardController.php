<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\User;

class PJDashboardController extends Controller
{
    public function index()
    {
        /*
        |------------------------------------------------------------------
        | ROLE LOGIN
        |------------------------------------------------------------------
        */

        $pjRole = auth()->user()->role;

        /*
        |------------------------------------------------------------------
        | HRD DASHBOARD
        |------------------------------------------------------------------
        */

        if ($pjRole == 'hrd') {

            $pendingLeave = Leave::where(
                'pj_status',
                'approved'
            )
                ->where(
                    'hrd_status',
                    'pending'
                )
                ->count();

            $pendingPermission = Permission::where(
                'pj_status',
                'approved'
            )
                ->where(
                    'hrd_status',
                    'pending'
                )
                ->count();

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
            |------------------------------------------------------------------
            | DATA LEMBUR HRD
            |------------------------------------------------------------------
            */

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
                'hrd.dashboard_hrd',
                compact(
                    'pendingLeave',
                    'pendingPermission',
                    'pendingOvertime',
                    'overtimes'
                )
            );
        }

        /*
        |------------------------------------------------------------------
        | PJ DASHBOARD
        |------------------------------------------------------------------
        */

        $divisionRole = str_replace(
            'pj_',
            '',
            $pjRole
        );

        /*
        |------------------------------------------------------------------
        | USER DIVISI
        |------------------------------------------------------------------
        */

        $userIds = User::where(
            'role',
            $divisionRole
        )->pluck('id');

        /*
        |------------------------------------------------------------------
        | STATISTIK
        |------------------------------------------------------------------
        */

        $pendingLeave = Leave::whereIn(
            'user_id',
            $userIds
        )
            ->where(
                'pj_status',
                'pending'
            )
            ->count();

        $approvedLeave = Leave::whereIn(
            'user_id',
            $userIds
        )
            ->where(
                'pj_status',
                'approved'
            )
            ->count();

        $rejectedLeave = Leave::whereIn(
            'user_id',
            $userIds
        )
            ->where(
                'pj_status',
                'rejected'
            )
            ->count();

        $pendingPermission = Permission::whereIn(
            'user_id',
            $userIds
        )
            ->where(
                'pj_status',
                'pending'
            )
            ->count();

        $pendingOvertime = Overtime::whereIn(
            'user_id',
            $userIds
        )
            ->where(
                'pj_status',
                'pending'
            )
            ->count();

        /*
        |------------------------------------------------------------------
        | RECENT LEAVE
        |------------------------------------------------------------------
        */

        $recentLeaves = Leave::with('user')
            ->whereIn(
                'user_id',
                $userIds
            )
            ->latest()
            ->take(10)
            ->get();

        /*
        |------------------------------------------------------------------
        | LIST LEMBUR PJ
        |------------------------------------------------------------------
        */

        $overtimes = Overtime::with('user')

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

        /*
        |------------------------------------------------------------------
        | RETURN VIEW
        |------------------------------------------------------------------
        */

        return view(
            'pj.dashboard',
            compact(
                'divisionRole',
                'pendingLeave',
                'approvedLeave',
                'rejectedLeave',
                'pendingPermission',
                'pendingOvertime',
                'recentLeaves',
                'overtimes'
            )
        );
    }
}
