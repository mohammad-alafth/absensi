<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\EmployeeShift;
use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PJShiftChangeController extends Controller
{
    /**
     * Daftar pengajuan perubahan shift untuk PJ divisi
     */
    public function index()
    {
        $pjRole = auth()->user()->role;
        $divisionRoles = PJDashboardController::getDivisionRolesForUser($pjRole);
        $divisionRole = $pjRole;

        $shiftChangeRequests = ShiftChangeRequest::with(['user', 'currentShift', 'requestedShift'])
            ->where('status', 'pending')
            ->whereHas('user', function ($q) use ($divisionRoles) {
                $q->whereIn('role', $divisionRoles);
            })
            ->latest()
            ->get();

        // Filter shift: hanya tampilkan shift yang allowed_roles-nya
        // mengandung salah satu role divisi PJ yang login
        $shifts = Shift::where(function ($query) use ($divisionRoles) {
            foreach ($divisionRoles as $divRole) {
                $query->orWhereJsonContains('allowed_roles', $divRole);
            }
        })->get();

        return view('pj.shift-change.index', compact('shiftChangeRequests', 'divisionRoles', 'divisionRole', 'shifts'));
    }

    /**
     * Approve pengajuan perubahan shift oleh PJ
     */
    public function approve(Request $request, $id)
    {
        $shiftChangeRequest = ShiftChangeRequest::with(['user', 'requestedShift'])->findOrFail($id);

        if ($shiftChangeRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan perubahan shift sudah diproses.');
        }

        $targetDate = Carbon::parse($shiftChangeRequest->shift_date)->format('Y-m-d');
        $requestedValue = $request->input('requested_shift_id', $shiftChangeRequest->requested_shift_id);

        // Nilai 'off' / tanpa pilihan pada pengajuan libur => hapus jadwal (jadwal kosong)
        $isOffRequest = $requestedValue === 'off'
            || (in_array($requestedValue, ['', null], true) && $shiftChangeRequest->requested_shift_id === null);

        DB::beginTransaction();
        try {
            $shiftChangeRequest->update([
                'requested_shift_id' => $isOffRequest ? null : (int) ($requestedValue ?: $shiftChangeRequest->requested_shift_id),
                'status' => 'approved',
                'pj_approved_by' => auth()->id(),
                'pj_approved_at' => now(),
            ]);

            if ($isOffRequest) {
                // Libur / jadwal kosong: hapus jadwal shift karyawan pada tanggal tersebut
                EmployeeShift::where('user_id', $shiftChangeRequest->user_id)
                    ->where('shift_date', $targetDate)
                    ->delete();

                DB::commit();
                return back()->with('success', 'Pengajuan disetujui. Jadwal shift pada tanggal tersebut dihapus (Libur / Tidak Ada Shift).');
            }

            $requestedShift = Shift::find((int) $requestedValue);
            if (!$requestedShift) {
                throw new \Exception('Shift tujuan tidak ditemukan.');
            }

            $year = Carbon::parse($targetDate)->year;
            $month = Carbon::parse($targetDate)->month;

            $employeeShift = EmployeeShift::where('user_id', $shiftChangeRequest->user_id)
                ->where('shift_date', $targetDate)
                ->first();

            if ($employeeShift) {
                $employeeShift->update([
                    'shift_id' => $requestedShift->id,
                    'start_time' => $requestedShift->start_time,
                    'end_time' => $requestedShift->end_time,
                    'is_overnight' => (bool) $requestedShift->is_overnight,
                ]);
            } else {
                EmployeeShift::create([
                    'user_id' => $shiftChangeRequest->user_id,
                    'shift_id' => $requestedShift->id,
                    'month' => $month,
                    'year' => $year,
                    'shift_date' => $targetDate,
                    'start_time' => $requestedShift->start_time,
                    'end_time' => $requestedShift->end_time,
                    'is_overnight' => (bool) $requestedShift->is_overnight,
                ]);
            }

            DB::commit();
            return back()->with('success', 'Pengajuan perubahan shift berhasil disetujui & jadwal shift diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memperbarui shift: ' . $e->getMessage());
        }
    }

    /**
     * Reject pengajuan perubahan shift oleh PJ
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|max:500'
        ]);

        $shiftChangeRequest = ShiftChangeRequest::findOrFail($id);

        if ($shiftChangeRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan perubahan shift sudah diproses.');
        }

        $shiftChangeRequest->update([
            'status' => 'rejected',
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            'pj_note' => $request->note,
        ]);

        return back()->with('success', 'Pengajuan perubahan shift ditolak.');
    }
}
