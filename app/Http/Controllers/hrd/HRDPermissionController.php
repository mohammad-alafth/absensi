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

        // 1. Definisikan mapping status yang dicari berdasarkan role
        // Ini menggantikan banyak 'if'
        $statusMapping = [
            'hrd'          => 'waiting_hrd',
            'head_pegawai' => 'waiting_head',
            'director'     => 'waiting_director',
        ];

        // 2. Ambil status yang sesuai, jika role tidak ada di mapping, gunakan array kosong
        $targetStatus = $statusMapping[$role] ?? null;

        if (!$targetStatus) {
            return back()->with('error', 'Role tidak memiliki akses ke halaman ini.');
        }

        // 3. Query dinamis
        $permissions = Permission::with('user')
            ->where('status', $targetStatus)
            ->latest()
            ->get();

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
                'head_status' => 'approved',
                'head_signature' => $request->signature,
                'head_approved_by' => auth()->id(),
                'head_approved_at' => now(),
            ]);
            $this->regeneratePdf($permission);
            return back()->with('success', 'Diteruskan ke Direktur');
        }

        if ($role === 'director') {
            $permission->update([
                'status' => 'approved',
                'director_status' => 'approved',
                'director_signature' => $request->signature,
                'director_approved_by' => auth()->id(),
                'director_approved_at' => now(),
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
                    'hrdApprover',
                    'headApprover',
                    'directorApprover'
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
