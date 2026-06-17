<?php

namespace App\Http\Controllers\PJ;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Services\ApprovalFlowService;
use Illuminate\Support\Facades\Storage;

class PJPermissionController extends Controller
{
    public function index()
    {
        $divisionRole = $this->getDivisionRole();

        $permissions = Permission::with('user')
            ->where('pj_status', 'pending')
            ->whereHas('user', function ($q) use ($divisionRole) {
                $q->where('role', $divisionRole);
            })
            ->latest()
            ->get();

        return view('pj.permission.index', compact('permissions'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'signature' => 'required|string'
        ]);

        $permission = Permission::with('user')->findOrFail($id);

        if ($permission->pj_status !== 'pending') {
            return back()->with('error', 'Izin sudah diproses PJ');
        }

        /*
        |----------------------------------------------------------
        | FLOW DARI SERVICE (SINGLE SOURCE OF TRUTH)
        |----------------------------------------------------------
        */
        $flow = ApprovalFlowService::handle($permission->user->role);

        $permission->update([
            'pj_status' => 'approved',
            'pj_signature' => $request->signature,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            'status' => 'waiting_hrd',
            'hrd_status' => $flow['hrd_status'],
        ]);

        $this->regeneratePdf($permission);

        return back()->with('success', 'Izin diteruskan sesuai flow');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|min:2'
        ]);

        $permission = Permission::findOrFail($id);

        if ($permission->pj_status !== 'pending') {
            return back()->with('error', 'Izin sudah diproses PJ');
        }

        $permission->update([
            'pj_status' => 'rejected',
            'pj_note' => $request->note,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            'status' => 'rejected',
        ]);

        return back()->with('success', 'Izin ditolak PJ');
    }

    private function getDivisionRole()
    {
        $role = auth()->user()->role;

        return str_starts_with($role, 'pj_')
            ? str_replace('pj_', '', $role)
            : $role;
    }

    private function regeneratePdf($permission)
    {
        // Tambahkan 'headApprover' dan 'directorApprover' di sini agar tidak error saat dipanggil di view
        $permission = $permission->fresh([
            'user',
            'pjApprover',
            'hrdApprover',
            'headApprover',
            'directorApprover'
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.permission-letter', [
            'permission' => $permission
        ]);

        $fileName = "permission/permission_{$permission->id}.pdf";

        \Storage::disk('public')->put($fileName, $pdf->output());

        $permission->update(['pdf_file' => $fileName]);
    }
}
