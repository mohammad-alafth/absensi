<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\ApprovalFlowService;
use App\Support\SubmissionStatus;
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
        /*
        |--------------------------------------------------------------------------
        | PENGAJUAN SAYA
        |--------------------------------------------------------------------------
        | Ditampilkan langsung di halaman pengajuan agar pengaju bisa memantau
        | status dan merevisi pengajuannya sendiri tanpa membuka menu Riwayat.
        */
        $permissions = Permission::where('user_id', auth()->id())
            ->latest()
            ->limit(5)
            ->get();

        // Nama approver diambil sekali untuk semua pengajuan (hindari N+1).
        SubmissionStatus::primeApproverNames($permissions);

        return view('permission.create', compact('permissions'));
    }

    /*
    |--------------------------------------------------------------------------
    | USER AJUKAN IZIN (DENGAN GENERATE PDF)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'jenis' => 'required|string',
            'tanggal' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal',
            'alasan' => 'required|string|min:2',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            // 'employee_signature' => 'required|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | RENTANG TANGGAL IZIN
        |--------------------------------------------------------------------------
        | Tanggal selesai kosong / sama dengan tanggal mulai = izin 1 hari.
        | Izin 1 hari  -> wajib mengisi jam mulai & jam selesai.
        | Izin >1 hari -> jam tidak digunakan (dikosongkan).
        */
        $tanggalSelesai = $request->filled('tanggal_selesai')
            ? $request->tanggal_selesai
            : $request->tanggal;

        $isSingleDay = $tanggalSelesai === $request->tanggal;

        $jamMulai = null;
        $jamSelesai = null;

        if ($isSingleDay) {

            $request->validate([
                'jam_mulai' => 'required',
                'jam_selesai' => 'required',
            ]);

            /*
            |--------------------------------------------------------------------------
            | VALIDASI JAM (Dipindahkan ke atas agar aman sebelum proses simpan)
            |--------------------------------------------------------------------------
            */
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

            $jamMulai = $request->jam_mulai;
            $jamSelesai = $request->jam_selesai;
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
            'tanggal_selesai' => $tanggalSelesai,
            'jenis' => $request->jenis,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'alasan' => $request->alasan,
            'lampiran' => $lampiran,
            'status' => $status,
            'pj_status' => $pjStatus,
            // 'employee_signature' => $request->employee_signature,
            'hrd_status' => $hrdStatus,
            // $flow membawa seluruh kolom status stage approval
        ] + $flow);

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
    | USER UBAH IZIN (REVISI SAAT PENDING / SETELAH DITOLAK)
    |--------------------------------------------------------------------------
    | Hanya boleh diubah oleh pemilik pengajuan dan hanya selama status masih
    | `pending` (menunggu PJ) atau `rejected`. Bila pengajuan sebelumnya ditolak,
    | penyimpanan otomatis MENGIRIM ULANG pengajuan ke tahap approval awal.
    | Kolom catatan approval tidak dihapus supaya alasan penolakan sebelumnya
    | tetap terlihat di menu Riwayat.
    */
    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        if ($permission->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!SubmissionStatus::isEditable($permission)) {
            return back()->with(
                'error',
                'Pengajuan izin yang sudah diverifikasi atau disetujui tidak dapat diubah lagi.'
            );
        }

        $request->validate([
            'jenis' => 'required|string',
            'tanggal' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal',
            'alasan' => 'required|string|min:2',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        /*
        |--------------------------------------------------------------------------
        | RENTANG TANGGAL (sama seperti store)
        |--------------------------------------------------------------------------
        */
        $tanggalSelesai = $request->filled('tanggal_selesai')
            ? $request->tanggal_selesai
            : $request->tanggal;

        $isSingleDay = $tanggalSelesai === $request->tanggal;

        $jamMulai = null;
        $jamSelesai = null;

        if ($isSingleDay) {

            $request->validate([
                'jam_mulai' => 'required',
                'jam_selesai' => 'required',
            ]);

            $start = Carbon::parse($request->jam_mulai);
            $end = Carbon::parse($request->jam_selesai);

            if ($end <= $start) {
                $end->addDay();
            }

            if ($start->diffInHours($end) > 24) {
                return back()
                    ->withErrors([
                        'jam_selesai' => 'Durasi izin terlalu panjang'
                    ])
                    ->withInput();
            }

            $jamMulai = $request->jam_mulai;
            $jamSelesai = $request->jam_selesai;
        }

        $wasRejected = $permission->status === 'rejected';

        $permission->update([
            'tanggal' => $request->tanggal,
            'tanggal_selesai' => $tanggalSelesai,
            'jenis' => $request->jenis,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'alasan' => $request->alasan,
        ]);

        if ($request->hasFile('lampiran')) {
            $permission->update([
                'lampiran' => $request->file('lampiran')->store('izin', 'public'),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | KIRIM ULANG SETELAH PENOLAKAN
        |--------------------------------------------------------------------------
        */
        if ($wasRejected) {
            $permission->update(ApprovalFlowService::handle(auth()->user()->role));
        }

        $permission = $permission->fresh();
        $permission->load('user');

        /*
        |--------------------------------------------------------------------------
        | PERBARUI BERKAS PDF SURAT IZIN
        |--------------------------------------------------------------------------
        */
        $pdf = Pdf::loadView('pdf.permission-letter', [
            'permission' => $permission,
        ]);

        $fileName = 'permission-pdf/' . $permission->id . '.pdf';

        Storage::disk('public')->put($fileName, $pdf->output());

        if (\Schema::hasColumn('permissions', 'pdf_file')) {
            $permission->update([
                'pdf_file' => $fileName
            ]);
        }

        return back()->with(
            'success',
            $wasRejected
                ? 'Perubahan izin tersimpan dan pengajuan dikirim ulang untuk persetujuan.'
                : 'Perubahan pengajuan izin berhasil disimpan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY USER
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        $permissions = Permission::where('user_id', auth()->id())
            ->latest()
            ->get();

        // Nama approver diambil sekali untuk semua pengajuan (hindari N+1).
        SubmissionStatus::primeApproverNames($permissions);

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
