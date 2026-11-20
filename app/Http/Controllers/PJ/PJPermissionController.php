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
        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);

        $permissions = Permission::with('user')
            ->where('pj_status', 'pending')
            ->whereHas('user', function ($q) use ($divisionRoles) {
                $q->whereIn('role', $divisionRoles);
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

        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);
        $permission = Permission::with('user')
            ->whereKey($id)
            ->whereHas('user', fn ($query) => $query->whereIn('role', $divisionRoles))
            ->firstOrFail();

        if (!ApprovalFlowService::needsPjForSubmission($permission, $permission->user->role, 'permission') || $permission->pj_status !== 'pending') {
            return back()->with('error', 'Izin sudah diproses PJ');
        }

        $permission->update([
            'pj_status' => 'approved',
            'pj_signature' => $request->signature,
            'pj_approved_by' => auth()->id(),
            'pj_approved_at' => now(),
            // Tahap PJ selesai -> masuk stage approver pertama sesuai grup role
            'status' => ApprovalFlowService::statusAfterPjSubmission($permission, $permission->user->role, 'permission'),
        ]);

        $this->regeneratePdf($permission);

        return back()->with('success', 'Izin diteruskan sesuai flow');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|min:2'
        ]);

        $divisionRoles = PJDashboardController::getDivisionRolesForUser(auth()->user()->role);
        $permission = Permission::whereKey($id)
            ->whereHas('user', fn ($query) => $query->whereIn('role', $divisionRoles))
            ->firstOrFail();

        if (!ApprovalFlowService::needsPjForSubmission($permission, $permission->user->role, 'permission') || $permission->pj_status !== 'pending') {
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

    private function regeneratePdf($permission)
    {
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
