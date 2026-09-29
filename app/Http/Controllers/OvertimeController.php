<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Overtime;
use Carbon\Carbon;
use App\Services\ApprovalFlowService;
use App\Support\SubmissionStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class OvertimeController extends Controller
{
    /**
     * Durasi minimum pengajuan lembur (menit).
     * Jam lembur dihitung per 60 menit penuh, jadi di bawah ini
     * pengajuan akan menghasilkan surat tanpa jam lembur.
     */
    private const MIN_OVERTIME_MINUTES = 60;

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
        $overtimes = Overtime::where('user_id', auth()->id())
            ->latest()
            ->limit(5)
            ->get();

        // Nama approver diambil sekali untuk semua pengajuan (hindari N+1).
        SubmissionStatus::primeApproverNames($overtimes);

        return view('lembur', compact('overtimes'));
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
        | HITUNG JAM LEMBUR (PEMBULATAN KE BAWAH PER 60 MENIT)
        |--------------------------------------------------------------------------
        | Total selisih menit dihitung dulu, lalu setiap 60 menit penuh dihitung
        | 1 jam dan sisa menit di bawah 60 tidak dihitung:
        | 60 menit -> 1 jam, 110 menit -> 1 jam, 120 menit -> 2 jam.
        | Durasi di bawah 60 menit ditolak agar surat tidak bernilai 0 jam.
        */
        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);

        // Validasi jikalau lembur manual melewati tengah malam
        if ($end <= $start) {
            $end->addDay();
        }

        $totalMinutes = (int) $start->diffInMinutes($end);

        if ($totalMinutes < self::MIN_OVERTIME_MINUTES) {
            return back()
                ->withErrors([
                    'end_time' => 'Durasi lembur minimal ' . self::MIN_OVERTIME_MINUTES . ' menit',
                ])
                ->withInput();
        }

        $totalHours = intdiv($totalMinutes, 60);

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
            'total_hours' => $totalHours, // Jam penuh; sisa menit di bawah 60 tidak dihitung
            'employee_signature' => $request->employee_signature,
            'reason' => $request->reason,
            // $flow membawa status global + seluruh kolom status stage approval
        ] + $flow);

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
    | USER UBAH LEMBUR (REVISI SAAT PENDING / SETELAH DITOLAK)
    |--------------------------------------------------------------------------
    | Hanya pemilik pengajuan dan hanya selama status `pending` atau `rejected`.
    | Revisi dari pengajuan yang ditolak otomatis dikirim ulang ke tahap awal
    | approval; catatan penolakan lama tetap tersimpan untuk menu Riwayat.
    */
    public function update(Request $request, $id)
    {
        $overtime = Overtime::findOrFail($id);

        if ($overtime->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!SubmissionStatus::isEditable($overtime)) {
            return back()->with(
                'error',
                'Pengajuan lembur yang sudah diverifikasi atau disetujui tidak dapat diubah lagi.'
            );
        }

        $request->validate([
            'overtime_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'reason' => 'required|string',
            'day_type' => 'required|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | HITUNG ULANG VOLUME JAM (sama seperti store)
        |--------------------------------------------------------------------------
        | Setiap 60 menit penuh dihitung 1 jam; durasi di bawah 60 menit ditolak.
        |--------------------------------------------------------------------------
        */
        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);

        if ($end <= $start) {
            $end->addDay();
        }

        $totalMinutes = (int) $start->diffInMinutes($end);

        if ($totalMinutes < self::MIN_OVERTIME_MINUTES) {
            return back()
                ->withErrors([
                    'end_time' => 'Durasi lembur minimal ' . self::MIN_OVERTIME_MINUTES . ' menit',
                ])
                ->withInput();
        }

        $totalHours = intdiv($totalMinutes, 60);

        $wasRejected = $overtime->status === 'rejected';

        $overtime->update([
            'day_type' => $request->day_type,
            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_hours' => $totalHours,
            'reason' => $request->reason,
        ]);

        /*
        |--------------------------------------------------------------------------
        | KIRIM ULANG SETELAH PENOLAKAN
        |--------------------------------------------------------------------------
        */
        if ($wasRejected) {
            $overtime->update(ApprovalFlowService::handle(auth()->user()->role));
        }

        $overtime = $overtime->fresh();
        $overtime->load('user');

        /*
        |--------------------------------------------------------------------------
        | PERBARUI BERKAS PDF SURAT PERINTAH LEMBUR
        |--------------------------------------------------------------------------
        */
        $pdf = Pdf::loadView('pdf.overtime-letter', [
            'overtime' => $overtime
        ]);

        $fileName = 'overtime-pdf/' . $overtime->id . '.pdf';

        Storage::disk('public')->put($fileName, $pdf->output());

        if (\Schema::hasColumn('overtimes', 'pdf_file')) {
            $overtime->update([
                'pdf_file' => $fileName
            ]);
        }

        return back()->with(
            'success',
            $wasRejected
                ? 'Perubahan lembur tersimpan dan pengajuan dikirim ulang untuk persetujuan.'
                : 'Perubahan pengajuan lembur berhasil disimpan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        $overtimes = Overtime::where('user_id', auth()->id())
            ->latest()
            ->get();

        // Nama approver diambil sekali untuk semua pengajuan (hindari N+1).
        SubmissionStatus::primeApproverNames($overtimes);

        return view('lembur-history', compact('overtimes'));
    }
}
