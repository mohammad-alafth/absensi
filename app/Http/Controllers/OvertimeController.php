<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Overtime;
use Carbon\Carbon;
use App\Services\ApprovalFlowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class OvertimeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('lembur');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE (PROSES SIMPAN & GENERATE PDF)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'overtime_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'reason' => 'required|string',
            'day_type' => 'required|string',
            'employee_signature' => 'required|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | HITUNG JAM BERDASARKAN MENIT (DUKUNGAN DESIMAL JAM)
        |--------------------------------------------------------------------------
        | Menghitung total selisih menit terlebih dahulu, kemudian mengonversinya
        | ke satuan jam desimal agar presisi (Misal: 90 menit -> 1.5 Jam).
        */
        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);

        // Validasi jikalau lembur manual melewati tengah malam
        if ($end <= $start) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        $hoursDecimal = $totalMinutes / 60;

        /*
        |--------------------------------------------------------------------------
        | APPROVAL FLOW
        |--------------------------------------------------------------------------
        */
        $userRole = auth()->user()->role;
        $flow = ApprovalFlowService::handle($userRole);

        /*
        |--------------------------------------------------------------------------
        | SIMPAN DATA AWAL
        |--------------------------------------------------------------------------
        */
        $overtime = Overtime::create([
            'user_id' => auth()->id(),
            'department' => auth()->user()->role, // Menyimpan role pengaju sebagai department
            'day_type' => $request->day_type,
            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_hours' => $hoursDecimal, // Menyimpan nilai jam dalam bentuk pecahan desimal murni
            'employee_signature' => $request->employee_signature,
            'reason' => $request->reason,
            'status' => $flow['status'],
            'pj_status' => $flow['pj_status'],
            'hrd_status' => $flow['hrd_status'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | PROSES GENERATE PDF (SINKRONISASI SURAT PERINTAH LEMBUR)
        |--------------------------------------------------------------------------
        */
        // Load template view pdf dengan mempassing data relasi user lengkap
        $pdf = Pdf::loadView('pdf.overtime-letter', [
            'overtime' => $overtime->load('user')
        ]);

        // Menentukan nama & lokasi file pdf jikalau ingin diunduh sewaktu-waktu
        $fileName = 'overtime-pdf/' . $overtime->id . '.pdf';

        // Simpan file biner PDF ke dalam public storage folder
        Storage::disk('public')->put($fileName, $pdf->output());

        // Update data jikalau Anda memiliki field `pdf_file` di tabel overtimes
        if (\Schema::hasColumn('overtimes', 'pdf_file')) {
            $overtime->update([
                'pdf_file' => $fileName
            ]);
        }

        return redirect('/dashboard')
            ->with(
                'success',
                'Pengajuan lembur berhasil dikirim dan berkas PDF telah dibuat.'
            );
    }
    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD PDF FORM LEMBUR
    |--------------------------------------------------------------------------
    */
    public function downloadPdf($id)
    {
        $overtime = Overtime::findOrFail($id);

        if ($overtime->user_id !== auth()->id() && auth()->user()->role !== 'hrd' && auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $filePath = 'storage/' . $overtime->pdf_file;

        if ($overtime->pdf_file && file_exists(public_path($filePath))) {
            return response()->download(public_path($filePath), 'Surat_Lembur_' . $overtime->id . '.pdf');
        }

        $pdf = Pdf::loadView('pdf.overtime-letter', [
            'overtime' => $overtime->load('user')
        ]);

        return $pdf->stream('Surat_Lembur_' . $overtime->id . '.pdf');
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        $overtimes = Overtime::with([
            'pjApprover',
            'hrdApprover'
        ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('lembur-history', compact('overtimes'));
    }
}
