<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\ApprovalFlowService;
use Barryvdh\DomPDF\Facade\Pdf; // <-- TAMBAHKAN INI
use Illuminate\Support\Facades\Storage; // <-- TAMBAHKAN INI

class PermissionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('permission.create');
    }

    /*
    |--------------------------------------------------------------------------
    | USER AJUKAN IZIN (DENGAN GENERATE PDF)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|string',
            'jam_mulai' => 'nullable|required_with:jam_selesai',
            'jam_selesai' => 'nullable|required_with:jam_mulai',
            'alasan' => 'required|string|min:2',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            // 'employee_signature' => 'required|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDASI JAM (Dipindahkan ke atas agar aman sebelum proses simpan)
        |--------------------------------------------------------------------------
        */
        if ($request->jam_mulai && $request->jam_selesai) {
            $start = Carbon::parse($request->jam_mulai);
            $end = Carbon::parse($request->jam_selesai);

            // Jika melewati tengah malam
            if ($end <= $start) {
                $end->addDay();
            }

            // Maksimal izin misalnya 24 jam
            if ($start->diffInHours($end) > 24) {
                return back()
                    ->withErrors([
                        'jam_selesai' => 'Durasi izin terlalu panjang'
                    ])
                    ->withInput();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Upload lampiran
        |--------------------------------------------------------------------------
        */
        $lampiran = null;
        if ($request->hasFile('lampiran')) {
            $lampiran = $request->file('lampiran')->store('izin', 'public');
        }

        /*
|--------------------------------------------------------------------------
| FLOW APPROVAL
|--------------------------------------------------------------------------
*/
        $flow = ApprovalFlowService::handle(
            auth()->user()->role
        );

        $status = $flow['status'];

        $pjStatus = $flow['pj_status'];

        $hrdStatus = $flow['hrd_status'];


        /*
        |--------------------------------------------------------------------------
        | Simpan Data Izin
        |--------------------------------------------------------------------------
        */
        $permission = Permission::create([
            'user_id' => auth()->id(),
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'alasan' => $request->alasan,
            'lampiran' => $lampiran,
            'status' => $status,
            'pj_status' => $pjStatus,
            // 'employee_signature' => $request->employee_signature,
            'hrd_status' => $hrdStatus,
        ]);

        /*
        |--------------------------------------------------------------------------
        | GENERATE PDF AUTOMATICALLY
        |--------------------------------------------------------------------------
        */
        // Load view template PDF dengan melempar data permissions & relasi user
        $pdf = Pdf::loadView('pdf.permission-letter', [
            'permission' => $permission->load('user')
        ]);

        // Berikan penamaan file yang unik di folder permission-pdf
        $fileName = 'permission-pdf/' . $permission->id . '.pdf';

        // Simpan file biner ke dalam public storage disk
        Storage::disk('public')->put($fileName, $pdf->output());

        // Update database untuk menyimpan path path file PDF jikalau field-nya ada
        if (\Schema::hasColumn('permissions', 'pdf_file')) {
            $permission->update([
                'pdf_file' => $fileName
            ]);
        }

        return redirect('/dashboard')
            ->with(
                'success',
                'Pengajuan izin berhasil dikirim.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY USER
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        $permissions = Permission::with([
            'pjApprover',
            'hrdApprover',
            'headApprover',
            'directorApprover',
        ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('permission.history', compact('permissions'));
    }
    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD PDF FORM IZIN
    |--------------------------------------------------------------------------
    */
    public function downloadPdf($id)
    {
        $permission = Permission::findOrFail($id);

        if ($permission->user_id !== auth()->id() && auth()->user()->role !== 'hrd' && auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $filePath = 'storage/' . $permission->pdf_file;

        if ($permission->pdf_file && file_exists(public_path($filePath))) {
            return response()->download(public_path($filePath), 'Surat_Izin_' . $permission->id . '.pdf');
        }

        $pdf = Pdf::loadView('pdf.permission-letter', [
            'permission' => $permission->load('user')
        ]);

        return $pdf->stream('Surat_Izin_' . $permission->id . '.pdf');
    }
}
