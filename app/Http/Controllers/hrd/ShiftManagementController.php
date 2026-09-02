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

        // Daftar semua role yang ada di sistem
        $allRoles = [
            'admin', 'hrd', 'it', 'marketing', 'creator',
            'head_pegawai', 'director', 'medical_service', 'pipp',
            'pj_security', 'security',
            'pj_marketing',
            'pj_ipsrs', 'ipsrs',
            'pj_casemix', 'casemix',
            'pj_nurse', 'nurse', 'nurse_ok',
            'finance', 'pj_finance',
            'ro', 'pj_ro',
            'pharmacist', 'pj_pharmacist',
            'cs', 'pj_cs',
            'administrasi', 'pj_administrasi',
            'nutrition', 'medical_record',
        ];

        // Ambil role unik dari database juga
        $dbRoles = User::whereNotNull('role')
            ->distinct()
            ->pluck('role')
            ->toArray();

        $roles = array_values(array_unique(array_merge($allRoles, $dbRoles)));

        // Label untuk tampilan
        $roleLabels = [
            'admin' => 'ADMIN',
            'hrd' => 'HRD',
            'it' => 'IT',
            'marketing' => 'MARKETING',
            'creator' => 'KONTEN CREATOR',
            'head_pegawai' => 'KEPALA BAGIAN UMUM DAN KEPEGAWAIAN',
            'director' => 'DIREKTUR',
            'medical_service' => 'MEDICAL SERVICE',
            'pipp' => 'PIPP',
            'pj_security' => 'PENANGGUNG JAWAB SECURITY',
            'security' => 'SECURITY',
            'pj_marketing' => 'PENANGGUNG JAWAB MARKETING',
            'pj_ipsrs' => 'PENANGGUNG JAWAB IPSRS',
            'ipsrs' => 'IPSRS',
            'pj_casemix' => 'PENANGGUNG JAWAB CASEMIX',
            'casemix' => 'CASEMIX',
            'pj_nurse' => 'PENANGGUNG JAWAB PERAWAT',
            'nurse' => 'PERAWAT',
            'nurse_ok' => 'PERAWAT OK',
            'finance' => 'KEUANGAN',
            'pj_finance' => 'PENANGGUNG JAWAB KEUANGAN',
            'ro' => 'REFRAKSIONIS OPTISIEN',
            'pj_ro' => 'PENANGGUNG JAWAB REFRAKSIONIS OPTISIEN',
            'pharmacist' => 'APOTEKER',
            'pj_pharmacist' => 'PENANGGUNG JAWAB APOTEKER',
            'cs' => 'CLEANING SERVICE',
            'pj_cs' => 'PENANGGUNG JAWAB CLEANING SERVICE',
            'administrasi' => 'ADMINISTRASI',
            'pj_administrasi' => 'PENANGGUNG JAWAB ADMINISTRASI',
            'nutrition' => 'GIZI',
            'medical_record' => 'REKAM MEDIS',
        ];

        return view('hrd.shifts.index', compact('shifts', 'roles', 'roleLabels'));
    }
}