<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | TAMPILKAN FORM CUTI
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $usedLeave = Leave::where(
            'user_id',
            auth()->id()
        )
            ->where('status', 'approved')
            ->sum('total_days');

        $remainingLeave = 12 - $usedLeave;

        if ($remainingLeave < 0) {
            $remainingLeave = 0;
        }

        return view('cuti', compact(
            'usedLeave',
            'remainingLeave'
        ));
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

            'leave_type' =>
            'required|in:Tahunan,Sakit,Melahirkan,Menikah,Keluarga,Khusus',

            'reason' => 'required|string|min:5',

            'delegate_name' => 'nullable|string|max:100',

            'delegate_nik' => 'nullable|string|max:30',

            'emergency_contact' => 'nullable|string|max:30',

        ]);


        /*
        |--------------------------------------------------------------------------
        | Parse tanggal
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
        | Validasi tanggal
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
        | Hitung total hari
        |--------------------------------------------------------------------------
        */
        $totalDays =
            $startDate->diffInDays(
                $endDate
            ) + 1;


        /*
        |--------------------------------------------------------------------------
        | Simpan
        |--------------------------------------------------------------------------
        */
        Leave::create([

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

            'status' => 'pending'

        ]);


        return redirect('/dashboard')
            ->with(
                'success',
                'Pengajuan cuti berhasil dikirim dan sedang menunggu approval.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE / REJECT
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $leave->update([
            'status' => $request->status
        ]);

        return back()->with(
            'success',
            'Status cuti berhasil diperbarui'
        );
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
        $leaves = Leave::where(
            'user_id',
            auth()->id()
        )
            ->latest()
            ->get();

        return view(
            'cuti-history',
            compact('leaves')
        );
    }
}
