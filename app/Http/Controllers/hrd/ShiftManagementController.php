<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Gunakan explicit assignment (bukan mass assignment) untuk memastikan
        // allowed_roles tersimpan meskipun ada perbedaan versi model di production
        $shift = new Shift();
        $shift->name = $request->name;
        $shift->start_time = $request->start_time;
        $shift->end_time = $request->end_time;
        $shift->is_overnight = $request->has('is_overnight');
        $shift->allowed_roles = [$request->role];
        $shift->save();

        // Fallback: pastikan allowed_roles tersimpan via DB facade
        // (mengatasi kasus OPcache/model version mismatch di production)
        if (empty($shift->allowed_roles) || $shift->allowed_roles !== [$request->role]) {
            Log::warning('ShiftManagement: allowed_roles tidak tersimpan via model, menggunakan DB fallback', [
                'shift_id' => $shift->id,
                'role' => $request->role
            ]);
            DB::table('shifts')
                ->where('id', $shift->id)
                ->update(['allowed_roles' => json_encode([$request->role])]);
        }

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

        // Gunakan explicit assignment (bukan mass assignment) untuk memastikan
        // allowed_roles tersimpan
        $shift->start_time = $request->start_time;
        $shift->end_time = $request->end_time;
        $shift->is_overnight = $request->has('is_overnight');
        $shift->allowed_roles = [$request->role];
        $shift->save();

        // Fallback: pastikan allowed_roles tersimpan via DB facade
        if (empty($shift->allowed_roles) || $shift->allowed_roles !== [$request->role]) {
            Log::warning('ShiftManagement: allowed_roles tidak tersimpan via model, menggunakan DB fallback', [
                'shift_id' => $shift->id,
                'role' => $request->role
            ]);
            DB::table('shifts')
                ->where('id', $shift->id)
                ->update(['allowed_roles' => json_encode([$request->role])]);
        }

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
            'cs', 'pj_cs',
        ];

        // Ambil semua role dari database (tidak hanya work_type=shift)
        $dbRoles = User::whereNotNull('role')
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
            'cs' => 'CUSTOMER SERVICE',
            'pj_cs' => 'PENANGGUNG JAWAB CUSTOMER SERVICE',
            'ipsrs' => 'IPSRS',
            'pj_ipsrs' => 'PENANGGUNG JAWAB IPSRS',
            'it' => 'IT',
            'hrd' => 'HRD',
            'head_pegawai' => 'KEPALA PEGAWAI',
            'pj_casemix' => 'PENANGGUNG JAWAB CASEMIX',
            'casemix' => 'CASEMIX',
            'medical_record' => 'REKAM MEDIS',
            'director' => 'DIREKTUR',
            'medical_service' => 'LAYANAN MEDIK',
            'pipp' => 'PIPP',
            'marketing' => 'MARKETING',
            'pj_marketing' => 'PENANGGUNG JAWAB MARKETING',
            'nutrition' => 'NUTRISI',
            'kasir' => 'KASIR',
            'creator' => 'CREATOR',
            'karyawan' => 'KARYAWAN',
        ];

        return view('hrd.shifts.index', compact('shifts', 'roles', 'roleLabels'));
    }
}
