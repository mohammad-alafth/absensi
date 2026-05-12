<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\EmployeeShift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HALAMAN SHIFT PJ
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $date = $request->date ?? now()->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | ROLE PJ
        |--------------------------------------------------------------------------
        */

        $role = auth()->user()->role;

        /*
        |--------------------------------------------------------------------------
        | AMBIL STAFF BERDASARKAN ROLE PJ
        |--------------------------------------------------------------------------
        */

        // contoh:
        // pj_nurse => nurse

        $targetRole = str_replace('pj_', '', $role);

        $employees = User::where('role', $targetRole)->get();

        /*
        |--------------------------------------------------------------------------
        | SHIFT MASTER
        |--------------------------------------------------------------------------
        */

        $shifts = Shift::all();

        /*
        |--------------------------------------------------------------------------
        | JADWAL HARI INI
        |--------------------------------------------------------------------------
        */

        $employeeShifts = EmployeeShift::with('user', 'shift')
            ->where('shift_date', $date)
            ->get();

        return view('shifts.index', compact(
            'employees',
            'shifts',
            'employeeShifts',
            'date'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE SHIFT AJAX
    |--------------------------------------------------------------------------
    */

    public function assign(Request $request)
    {
        $request->validate([

            'user_id' => 'required',

            'shift_id' => 'required',

            'shift_date' => 'required|date'
        ]);

        EmployeeShift::updateOrCreate(

            [
                'user_id' => $request->user_id,

                'shift_date' => $request->shift_date,
            ],

            [
                'shift_id' => $request->shift_id,

                'assigned_by' => auth()->id(),
            ]
        );

        return response()->json([

            'success' => true
        ]);
    }
}
