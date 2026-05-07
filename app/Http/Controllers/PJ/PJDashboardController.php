<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Leave;
use App\Models\Permission;
use App\Models\User;

class PJDashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Ambil role login
        |--------------------------------------------------------------------------
        |
        | contoh:
        | pj_nurse
        | pj_security
        |
        */

        $pjRole = auth()->user()->role;

        /*
        |--------------------------------------------------------------------------
        | Ambil divisi target
        |--------------------------------------------------------------------------
        |
        | pj_nurse => nurse
        |
        */

        $divisionRole = str_replace('pj_', '', $pjRole);

        /*
        |--------------------------------------------------------------------------
        | Ambil user sesuai divisi
        |--------------------------------------------------------------------------
        */

        $userIds = User::where('role', $divisionRole)
            ->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | Statistik
        |--------------------------------------------------------------------------
        */

        $pendingLeave = Leave::whereIn('user_id', $userIds)
            ->where('status', 'pending')
            ->count();

        $approvedLeave = Leave::whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->count();

        $rejectedLeave = Leave::whereIn('user_id', $userIds)
            ->where('status', 'rejected')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Pending izin
        |--------------------------------------------------------------------------
        */

        $pendingPermission = Permission::whereIn('user_id', $userIds)
            ->where('status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent leave
        |--------------------------------------------------------------------------
        */

        $recentLeaves = Leave::with('user')
            ->whereIn('user_id', $userIds)
            ->latest()
            ->take(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Return view
        |--------------------------------------------------------------------------
        */

        return view('pj.dashboard', compact(
            'divisionRole',
            'pendingLeave',
            'approvedLeave',
            'rejectedLeave',
            'pendingPermission',
            'recentLeaves'
        ));
    }
}