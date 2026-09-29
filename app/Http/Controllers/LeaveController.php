<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Services\ApprovalFlowService;
use App\Services\LeaveDayCalculator;
use App\Support\SubmissionStatus;

class LeaveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TAMPILKAN FORM CUTI
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | PENGAJUAN SAYA
        |--------------------------------------------------------------------------
        | Ditampilkan langsung di halaman pengajuan agar pengaju bisa memantau
        | status dan merevisi pengajuannya sendiri tanpa membuka menu Riwayat.
        */
        $leaves = Leave::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        // Nama approver diambil sekali untuk semua pengajuan (hindari N+1).
        SubmissionStatus::primeApproverNames($leaves);

        return view('cuti', [
            'usedLeave' => $user->used_leave,
            'remainingLeave' => $user->remaining_leave,
            'leaves' => $leaves,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | USER AJUKAN CUTI
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([

            'recipient' => 'nullable|string|max:100',

            'start_date' => 'required|date',

            'end_date' => 'required|date',

            'return_date' => 'nullable|date',

            'leave_type' => 'required|string',

            'reason' => 'required|string|min:2',

            'delegate_name' => 'nullable|string|max:100',

            'delegate_nik' => 'nullable|string|max:30',

            'emergency_contact' => 'nullable|string|max:30',

            'employee_signature' => 'required|string',

        ]);


        /*
        |--------------------------------------------------------------------------
        | Parse Tanggal
        |--------------------------------------------------------------------------
        */
        $startDate = Carbon::parse(
            $request->start_date
        );

        $endDate = Carbon::parse(
            $request->end_date
        );


        /*
        |--------------------------------------------------------------------------
        | Validasi Tanggal
        |--------------------------------------------------------------------------
        */
        if ($endDate->lt($startDate)) {

            return back()
                ->withErrors([
                    'end_date' =>
                    'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai'
                ])
                ->withInput();
        }


        /*
        |--------------------------------------------------------------------------
        | Hitung Total Hari
        |--------------------------------------------------------------------------
        */
        $user = auth()->user();

        // Hari cuti efektif mengikuti jadwal kerja (lihat LeaveDayCalculator):
        // hari tanpa jadwal (akhir pekan office / tanggal tanpa employee_shifts)
        // tidak mengurangi kuota. Contoh: cuti tgl 1-5 dengan tgl 3 & 4 tanpa
        // jadwal -> total_days = 3.
        $totalDays = LeaveDayCalculator::count($user, $startDate, $endDate);

        if ($totalDays < 1) {
            return back()
                ->withErrors([
                    'start_date' =>
                    'Tidak ada jadwal kerja pada rentang tanggal cuti. Hari libur/tanpa jadwal tidak dihitung sebagai hari cuti.'
                ])
                ->withInput();
        }
        /*
|--------------------------------------------------------------------------
| VALIDASI QUOTA CUTI
|--------------------------------------------------------------------------
*/
        if ($totalDays > $user->remaining_leave) {

            return back()
                ->withErrors([
                    'start_date' => 'Sisa cuti tidak mencukupi'
                ])
                ->withInput();
        }

        /*
|--------------------------------------------------------------------------
| FLOW ROLE
|--------------------------------------------------------------------------
*/
        $userRole = auth()->user()->role;

        $flow = ApprovalFlowService::handle($userRole);

        $status = $flow['status'];

        $pjStatus = $flow['pj_status'];

        $hrdStatus = $flow['hrd_status'];

        /*
        |--------------------------------------------------------------------------
        | Simpan
        |--------------------------------------------------------------------------
        */
        $leave = Leave::create([

            'user_id' => auth()->id(),

            'recipient' => $request->recipient,

            'start_date' => $request->start_date,

            'end_date' => $request->end_date,

            'return_date' => $request->return_date,

            'total_days' => $totalDays,

            'leave_type' => $request->leave_type,

            'reason' => $request->reason,

            'delegate_name' => $request->delegate_name,

            'delegate_nik' => $request->delegate_nik,

            'emergency_contact' => $request->emergency_contact,

            'employee_signature' => $request->employee_signature,

            'status' => $status,

            'pj_status' => $pjStatus,

            'hrd_status' => $hrdStatus,
            // $flow membawa seluruh kolom status stage (medical_service,
            // kabag_umum, manager_umum, kabag_marketing, manager_finance,
            // director) sehingga semua tahap terinisialisasi 'pending'.
        ] + $flow);
        /*
    |--------------------------------------------------------------------------
    | GENERATE PDF
    |--------------------------------------------------------------------------
    */
        $user = $leave->load('user')->user;

        $pdf = Pdf::loadView(
            'pdf.leave-letter',
            [
                'leave' => $leave,
                'usedLeave' => $user->used_leave,
                'remainingLeave' => $user->remaining_leave,
            ]
        );

        $fileName = 'leave-pdf/' . $leave->id . '.pdf';

        Storage::disk('public')->put(
            $fileName,
            $pdf->output()
        );

        $leave->update([
            'pdf_file' => $fileName
        ]);

        return redirect('/dashboard')
            ->with(
                'success',
                'Pengajuan cuti berhasil dikirim.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD PDF FORM CUTI
    |--------------------------------------------------------------------------
    */
    public function downloadPdf($id)
    {
        $leave = Leave::findOrFail($id);

        // Validasi Keamanan: Memastikan pegawai hanya bisa mengunduh berkas miliknya sendiri
        if ($leave->user_id !== auth()->id() && auth()->user()->role !== 'hrd' && auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $filePath = 'storage/' . $leave->pdf_file;

        if ($leave->pdf_file && file_exists(public_path($filePath))) {
            return response()->download(public_path($filePath), 'Surat_Cuti_' . $leave->id . '.pdf');
        }

        // Jika file biner fisik hilang di storage, generate ulang secara instan
        $pdf = Pdf::loadView(
            'pdf.leave-letter',
            [
                'leave' => $leave->load('user'),
                'usedLeave' => $leave->user->used_leave,
                'remainingLeave' => $leave->user->remaining_leave,
            ]
        );

        return $pdf->stream('Surat_Cuti_' . $leave->id . '.pdf');
    }
    /*
    |--------------------------------------------------------------------------
    | DATA CUTI USER LOGIN
    |--------------------------------------------------------------------------
    */
    public function myRequest()
    {
        return Leave::where(
            'user_id',
            auth()->id()
        )
            ->latest()
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | DATA CUTI ADMIN
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        return Leave::with('user')
            ->latest()
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | USER UBAH CUTI (REVISI SAAT PENDING / SETELAH DITOLAK)
    |--------------------------------------------------------------------------
    | Hanya pemilik pengajuan dan hanya selama status `pending` atau `rejected`.
    | Revisi dari pengajuan yang ditolak otomatis dikirim ulang ke tahap awal
    | approval, sedangkan catatan penolakan lama tetap disimpan agar bisa
    | ditampilkan di menu Riwayat.
    */
    public function update(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!SubmissionStatus::isEditable($leave)) {
            return back()->with(
                'error',
                'Pengajuan cuti yang sudah diverifikasi atau disetujui tidak dapat diubah lagi.'
            );
        }

        $request->validate([

            'recipient' => 'nullable|string|max:100',

            'start_date' => 'required|date',

            'end_date' => 'required|date',

            'return_date' => 'nullable|date',

            'leave_type' => 'required|string',

            'reason' => 'required|string|min:2',

            'delegate_name' => 'nullable|string|max:100',

            'delegate_nik' => 'nullable|string|max:30',

            'emergency_contact' => 'nullable|string|max:30',

        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        if ($endDate->lt($startDate)) {

            return back()
                ->withErrors([
                    'end_date' =>
                    'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai'
                ])
                ->withInput();
        }

        $user = auth()->user();

        // Hari cuti efektif mengikuti jadwal kerja; hari tanpa jadwal tidak
        // mengurangi kuota (sama dengan saat pengajuan awal).
        $totalDays = LeaveDayCalculator::count($user, $startDate, $endDate);

        if ($totalDays < 1) {
            return back()
                ->withErrors([
                    'start_date' =>
                    'Tidak ada jadwal kerja pada rentang tanggal cuti. Hari libur/tanpa jadwal tidak dihitung sebagai hari cuti.'
                ])
                ->withInput();
        }

        if ($totalDays > $user->remaining_leave) {

            return back()
                ->withErrors([
                    'start_date' => 'Sisa cuti tidak mencukupi'
                ])
                ->withInput();
        }

        $wasRejected = $leave->status === 'rejected';

        $leave->update([

            'recipient' => $request->recipient,

            'start_date' => $request->start_date,

            'end_date' => $request->end_date,

            'return_date' => $request->return_date,

            'total_days' => $totalDays,

            'leave_type' => $request->leave_type,

            'reason' => $request->reason,

            'delegate_name' => $request->delegate_name,

            'delegate_nik' => $request->delegate_nik,

            'emergency_contact' => $request->emergency_contact,
        ]);

        /*
        |--------------------------------------------------------------------------
        | KIRIM ULANG SETELAH PENOLAKAN
        |--------------------------------------------------------------------------
        */
        if ($wasRejected) {
            $leave->update(ApprovalFlowService::handle($user->role));
        }

        $leave = $leave->fresh();
        $leave->load('user');

        /*
        |--------------------------------------------------------------------------
        | PERBARUI BERKAS PDF SURAT CUTI
        |--------------------------------------------------------------------------
        */
        $pdf = Pdf::loadView(
            'pdf.leave-letter',
            [
                'leave' => $leave,
                'usedLeave' => $user->used_leave,
                'remainingLeave' => $user->remaining_leave,
            ]
        );

        $fileName = 'leave-pdf/' . $leave->id . '.pdf';

        Storage::disk('public')->put(
            $fileName,
            $pdf->output()
        );

        $leave->update([
            'pdf_file' => $fileName
        ]);

        return back()->with(
            'success',
            $wasRejected
                ? 'Perubahan cuti tersimpan dan pengajuan dikirim ulang untuk persetujuan.'
                : 'Perubahan pengajuan cuti berhasil disimpan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY CUTI
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        $leaves = Leave::where('user_id', auth()->id())
            ->latest()
            ->get();

        // Nama approver diambil sekali untuk semua pengajuan (hindari N+1).
        SubmissionStatus::primeApproverNames($leaves);

        return view(
            'cuti-history',
            compact('leaves')
        );
    }
}
