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
        |--------------------------------------------------------------------------
        | ROLE LOGIN
        |--------------------------------------------------------------------------
        */
        $pjRole = auth()->user()->role;

        /*
        |--------------------------------------------------------------------------
        | DIVISION ROLE
        |--------------------------------------------------------------------------
        */
        $divisionRole = str_replace(
            'pj_',
            '',
            $pjRole
        );

        /*
        |--------------------------------------------------------------------------
        | USER DIVISI
        |--------------------------------------------------------------------------
        */
        $userIds = User::where(
            'role',
            $divisionRole
        )->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | STATISTIK CUTI
        |--------------------------------------------------------------------------
        */
        $pendingLeave =
            Leave::whereIn('user_id', $userIds)
            ->where('pj_status', 'pending')
            ->count()

            +

            Permission::whereIn('user_id', $userIds)
            ->where('pj_status', 'pending')
            ->count()

            +

            Overtime::whereIn('user_id', $userIds)
            ->where('pj_status', 'pending')
            ->count();

        $approvedLeave =
            Leave::whereIn('user_id', $userIds)
            ->where('pj_status', 'approved')
            ->count()

            +

            Permission::whereIn('user_id', $userIds)
            ->where('pj_status', 'approved')
            ->count()

            +

            Overtime::whereIn('user_id', $userIds)
            ->where('pj_status', 'approved')
            ->count();

        $rejectedLeave =
            Leave::whereIn('user_id', $userIds)
            ->where('pj_status', 'rejected')
            ->count()

            +

            Permission::whereIn('user_id', $userIds)
            ->where('pj_status', 'rejected')
            ->count()

            +

            Overtime::whereIn('user_id', $userIds)
            ->where('pj_status', 'rejected')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | STATISTIK IZIN
        |--------------------------------------------------------------------------
        */
        $pendingPermission = Permission::whereIn(
            'user_id',
            $userIds
        )
            ->where('status', 'pending')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | STATISTIK LEMBUR
        |--------------------------------------------------------------------------
        */
        $pendingOvertime = Overtime::whereIn(
            'user_id',
            $userIds
        )
            ->where('status', 'pending')
            ->count();

        /*
|--------------------------------------------------------------------------
| RECENT LEAVE
|--------------------------------------------------------------------------
*/
        $leaves = Leave::with('user')
            ->whereIn(
                'user_id',
                $userIds
            )
            ->latest()
            ->get()
            ->map(function ($item) {

                $item->type = 'cuti';

                return $item;
            });

        /*
|--------------------------------------------------------------------------
| RECENT PERMISSION
|--------------------------------------------------------------------------
*/
        $permissions = Permission::with('user')
            ->whereIn(
                'user_id',
                $userIds
            )
            ->latest()
            ->get()
            ->map(function ($item) {

                $item->type = 'izin';

                return $item;
            });

        /*
|--------------------------------------------------------------------------
| RECENT OVERTIME
|--------------------------------------------------------------------------
*/
        $overtimesHistory = Overtime::with('user')
            ->whereIn(
                'user_id',
                $userIds
            )
            ->latest()
            ->get()
            ->map(function ($item) {

                $item->type = 'lembur';

                return $item;
            });

        /*
|--------------------------------------------------------------------------
| GABUNG SEMUA
|--------------------------------------------------------------------------
*/
        $recentSubmissions = collect()

            ->merge($leaves)

            ->merge($permissions)

            ->merge($overtimesHistory)

            ->sortByDesc('created_at')

            ->values();

        /*
        |--------------------------------------------------------------------------
        | LIST LEMBUR MENUNGGU PJ
        |--------------------------------------------------------------------------
        */
        $overtimes = Overtime::with('user')

            ->where('status', 'pending')

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
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
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
                'recentSubmissions',
                'overtimes'
            )
        );
    }
}
