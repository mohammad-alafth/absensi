<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Storage;

class HRDPermissionController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;

        if ($role === 'hrd') {
            $permissions = Permission::where('status', 'waiting_hrd')->get();
        }

        if ($role === 'head_pegawai') {
            $permissions = Permission::where('status', 'waiting_head')->get();
        }

        if ($role === 'director') {
            $permissions = Permission::where('status', 'waiting_director')->get();
        }

        return view('hrd.permission.izin', compact('permissions'));
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'signature' => 'required'
        ]);

        $permission = Permission::findOrFail($id);
        $role = auth()->user()->role;

        /*
    |--------------------------------------------------------------------------
    | FLOW BERJENJANG
    |--------------------------------------------------------------------------
    */

        if ($role === 'head_pegawai') {

            $permission->update([
                'status' => 'waiting_director',
            ]);

            return back()->with('success', 'Diteruskan ke Direktur');
        }

        if ($role === 'director') {

            $permission->update([
                'status' => 'approved',
                'hrd_status' => 'approved', // Opsional: set agar PDF terbaca final
                'hrd_approved_at' => now(), // Catat waktu persetujuan final
            ]);
            $this->regeneratePdf($permission);

            return back()->with('success', 'Disetujui Final oleh Direktur');
        }

        if ($role === 'hrd') {

            $permission->update([
                'status' => 'approved',
                'hrd_status' => 'approved',
                'hrd_signature' => $request->signature,
                'hrd_approved_by' => auth()->id(),
                'hrd_approved_at' => now(),
            ]);

            $this->regeneratePdf($permission);

            return back()->with('success', 'Final Approved oleh HRD');
        }

        return back()->with('error', 'Unauthorized');
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|min:5'
        ]);

        $permission = Permission::findOrFail($id);

        $permission->update([

            'status' => 'rejected',

            'hrd_status' => 'rejected',

            'hrd_note' => $request->note,

            'hrd_approved_by' => auth()->id(),

            'hrd_approved_at' => now(),
        ]);

        $this->regeneratePdf($permission);

        return back()->with(
            'success',
            'Izin ditolak HRD'
        );
    }


    private function regeneratePdf($permission)
    {
        $pdf = Pdf::loadView(
            'pdf.permission-letter',
            [
                'permission' => $permission->fresh([
                    'user',
                    'pjApprover',
                    'hrdApprover'
                ])
            ]
        );

        $fileName =
            "permission/permission_{$permission->id}.pdf";

        Storage::disk('public')->put(
            $fileName,
            $pdf->output()
        );

        $permission->update([
            'pdf_file' => $fileName
        ]);
    }
}
