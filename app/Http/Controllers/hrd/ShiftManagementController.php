<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use App\Models\User;

class ShiftManagementController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required',
            'start_time' => 'required',
            'end_time'   => 'required',
            'role'       => 'required|string'
        ]);

        Shift::create([
            'name'          => $request->name,
            'start_time'    => $request->start_time,
            'end_time'      => $request->end_time,
            'is_overnight'  => $request->has('is_overnight'),
            'allowed_roles' => [$request->role]
        ]);

        return back()->with('success', 'Shift baru berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'required',
            'end_time'   => 'required',
            'role'       => 'required|string'
        ]);

        $shift = Shift::findOrFail($id);

        $shift->update([
            'start_time'    => $request->start_time,
            'end_time'      => $request->end_time,
            'is_overnight'  => $request->has('is_overnight'),
            'allowed_roles' => [$request->role]
        ]);

        return back()->with('success', 'Shift berhasil diupdate');
    }

    public function index()
    {
        $shifts = Shift::all();

        $shiftRoles = [
            'nurse', 'pj_nurse',
            'nurse_ok', 'pj_nurse_ok',
            'ro', 'pj_ro',
            'security', 'pj_security',
            'pharmacist', 'pj_pharmacist',
            'finance', 'pj_finance',
            'administrasi', 'pj_administrasi',
        ];

        $dbRoles = User::where('work_type', 'shift')
            ->whereNotNull('role')
            ->distinct()
            ->pluck('role')
            ->toArray();

        $roles = array_values(array_unique(array_merge($shiftRoles, $dbRoles)));

        $roleLabels = [
            'nurse' => 'PERAWAT',
            'pj_nurse' => 'PENANGGUNG JAWAB PERAWAT',
            'nurse_ok' => 'PERAWAT OK',
            'pj_nurse_ok' => 'PENANGGUNG JAWAB PERAWAT OK',
            'ro' => 'REFRAKSIONIS OPTISIEN',
            'pj_ro' => 'PENANGGUNG JAWAB REFRAKSIONIS OPTISIEN',
            'security' => 'SECURITY',
            'pj_security' => 'PENANGGUNG JAWAB SECURITY',
            'pharmacist' => 'APOTEKER',
            'pj_pharmacist' => 'PENANGGUNG JAWAB APOTEKER',
            'finance' => 'KEUANGAN',
            'pj_finance' => 'PENANGGUNG JAWAB KEUANGAN',
            'administrasi' => 'ADMINISTRASI',
            'pj_administrasi' => 'PENANGGUNG JAWAB ADMINISTRASI',
        ];

        return view('hrd.shifts.index', compact('shifts', 'roles', 'roleLabels'));
    }
}