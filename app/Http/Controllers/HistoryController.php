<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\Permission;
use App\Models\Overtime;

class HistoryController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        /*
        |--------------------------------------------------------------------------
        | Ambil semua tahun
        |--------------------------------------------------------------------------
        */

        $leaveYears = Leave::where('user_id', $userId)
            ->selectRaw('YEAR(start_date) as year')
            ->pluck('year');

        $permissionYears = Permission::where('user_id', $userId)
            ->selectRaw('YEAR(tanggal) as year')
            ->pluck('year');

        $overtimeYears = Overtime::where('user_id', $userId)
            ->selectRaw('YEAR(overtime_date) as year')
            ->pluck('year');

        $years = $leaveYears
            ->merge($permissionYears)
            ->merge($overtimeYears)
            ->unique()
            ->sortDesc()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | LEAVES
        |--------------------------------------------------------------------------
        */

        $leaves = [];

        foreach ($years as $year) {

            $leaves[$year] = Leave::where('user_id', $userId)
                ->whereYear('start_date', $year)
                ->latest()
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | PERMISSIONS
        |--------------------------------------------------------------------------
        */

        $permissions = [];

        foreach ($years as $year) {

            $permissions[$year] = Permission::where('user_id', $userId)
                ->whereYear('tanggal', $year)
                ->latest()
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | OVERTIMES
        |--------------------------------------------------------------------------
        */

        $overtimes = [];

        foreach ($years as $year) {

            $overtimes[$year] = Overtime::where('user_id', $userId)
                ->whereYear('overtime_date', $year)
                ->latest()
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view('history.index', compact(
            'years',
            'leaves',
            'permissions',
            'overtimes'
        ));
    }
}
