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
        $role = auth()->user()->role;

        /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    */
        $leaves = Leave::query();
        $permissions = Permission::query();
        $overtimes = Overtime::query();

        /*
    |--------------------------------------------------------------------------
    | FILTER BERDASARKAN ROLE
    |--------------------------------------------------------------------------
    */

        if ($role === 'hrd') {

            $leaves->where('status', 'waiting_hrd');
            $permissions->where('status', 'waiting_hrd');
            $overtimes->where('status', 'waiting_hrd');
        }

        if ($role === 'head_pegawai') {

            $leaves->where('status', 'waiting_head');
            $permissions->where('status', 'waiting_head');
            $overtimes->where('status', 'waiting_head');
        }

        if ($role === 'director') {

            $leaves->where('status', 'waiting_director');
            $permissions->where('status', 'waiting_director');
            $overtimes->where('status', 'waiting_director');
        }

        /*
    |--------------------------------------------------------------------------
    | COUNTS
    |--------------------------------------------------------------------------
    */
        $pendingLeave = $leaves->count();
        $pendingPermission = $permissions->count();
        $pendingOvertime = $overtimes->count();

        /*
    |--------------------------------------------------------------------------
    | RECENT (ROLE FILTERED)
    |--------------------------------------------------------------------------
    */
        $leaves = $leaves->with('user')->latest()->take(5)->get()->map(function ($item) {
            $item->type = 'Cuti';
            return $item;
        });

        $permissions = $permissions->with('user')->latest()->take(5)->get()->map(function ($item) {
            $item->type = 'Izin';
            return $item;
        });

        $overtimes = $overtimes->with('user')->latest()->take(5)->get()->map(function ($item) {
            $item->type = 'Lembur';
            return $item;
        });

        $recentSubmissions = $leaves
            ->concat($permissions)
            ->concat($overtimes)
            ->sortByDesc('created_at')
            ->take(10);

        return view('hrd.dashboard_hrd', compact(
            'pendingLeave',
            'pendingPermission',
            'pendingOvertime',
            'recentSubmissions'
        ));
    }
}
