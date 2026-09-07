<?php

namespace App\Http\Controllers;

use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftChangeController extends Controller
{
    /**
     * Halaman form pengajuan perubahan shift
     */
    public function create()
    {
        $user = auth()->user();

        if ($user->work_type !== 'shift') {
            return redirect()->route('dashboard')->with('error', 'Fitur ini hanya untuk karyawan tipe kerja Shift.');
        }

        // Ambil shift yang diizinkan untuk role user ini
        $availableShifts = Shift::all()->filter(function ($shift) use ($user) {
            if (empty($shift->allowed_roles)) return true;
            return in_array($user->role, $shift->allowed_roles) ||
                   in_array('pj_' . $user->role, $shift->allowed_roles);
        });

        // Jika filter kosong, tampilkan semua shift sebagai fallback
        if ($availableShifts->isEmpty()) {
            $availableShifts = Shift::all();
        }

        return view('shift-change.create', compact('availableShifts'));
    }

    /**
     * API / Helper untuk mendapatkan shift user pada tanggal tertentu
     */
    public function getShiftByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $user = auth()->user();
        $employeeShift = EmployeeShift::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $request->date)
            ->first();

        if ($employeeShift) {
            return response()->json([
                'has_shift' => true,
                'shift_id' => $employeeShift->shift_id,
                'shift_name' => $employeeShift->shift->name ?? 'Custom Shift',
                'start_time' => Carbon::parse($employeeShift->start_time)->format('H:i'),
                'end_time' => Carbon::parse($employeeShift->end_time)->format('H:i'),
            ]);
        }

        return response()->json([
            'has_shift' => false,
            'shift_name' => 'Tidak Ada Shift / Off',
        ]);
    }

    /**
     * Simpan pengajuan perubahan shift
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->work_type !== 'shift') {
            return redirect()->route('dashboard')->with('error', 'Fitur ini hanya untuk karyawan tipe kerja Shift.');
        }

        $request->validate([
            'shift_date' => 'required|date|after_or_equal:today',
            'requested_shift_id' => 'required',
            'reason' => 'required|string|min:5',
        ], [
            'shift_date.required' => 'Tanggal shift wajib diisi.',
            'shift_date.after_or_equal' => 'Tanggal shift minimal hari ini.',
            'requested_shift_id.required' => 'Pilih shift yang diinginkan.',
            'reason.required' => 'Alasan perubahan shift wajib diisi.',
            'reason.min' => 'Alasan minimal 5 karakter.',
        ]);

        // Cek apakah ada pengajuan pending pada tanggal yang sama
        $existingPending = ShiftChangeRequest::where('user_id', $user->id)
            ->whereDate('shift_date', $request->shift_date)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return back()->with('error', 'Anda sudah memiliki pengajuan perubahan shift yang pending pada tanggal tersebut.')->withInput();
        }

        // Nilai 'off' = minta libur / tidak ada shift (jadwal kosong), disimpan sebagai NULL
        $requestedShiftId = $request->input('requested_shift_id');
        if ($requestedShiftId !== 'off' && !Shift::whereKey($requestedShiftId)->exists()) {
            return back()->withErrors(['requested_shift_id' => 'Shift yang dipilih tidak valid.'])->withInput();
        }

        // Cari shift saat ini
        $currentShift = EmployeeShift::where('user_id', $user->id)
            ->whereDate('shift_date', $request->shift_date)
            ->first();

        ShiftChangeRequest::create([
            'user_id' => $user->id,
            'shift_date' => $request->shift_date,
            'current_shift_id' => $currentShift ? $currentShift->shift_id : null,
            'requested_shift_id' => $requestedShiftId === 'off' ? null : $requestedShiftId,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('shift-change.history')->with('success', 'Pengajuan perubahan shift berhasil dikirim. Menunggu persetujuan Penanggung Jawab (PJ).');
    }

    /**
     * Riwayat pengajuan perubahan shift karyawan
     */
    public function history()
    {
        $user = auth()->user();

        $requests = ShiftChangeRequest::with(['currentShift', 'requestedShift', 'pjApprover'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return view('shift-change.history', compact('requests'));
    }
}
