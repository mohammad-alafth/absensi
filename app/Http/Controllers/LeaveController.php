<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Services\ApprovalFlowService;

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

        return view('cuti', [
            'usedLeave' => $user->used_leave,
            'remainingLeave' => $user->remaining_leave,
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
        $totalDays =
            $startDate->diffInDays(
                $endDate
            ) + 1;
        /*
|--------------------------------------------------------------------------
| VALIDASI QUOTA CUTI
|--------------------------------------------------------------------------
*/
        $user = auth()->user();

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
        ]);
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
    | HISTORY CUTI
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        $leaves = Leave::with([
            'pjApprover',
            'hrdApprover',
            'headApprover',
            'directorApprover'
        ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view(
            'cuti-history',
            compact('leaves')
        );
    }
}
