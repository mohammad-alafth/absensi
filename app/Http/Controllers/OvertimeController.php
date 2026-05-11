<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Overtime;
use Carbon\Carbon;

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
    | STORE
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([

            'overtime_date' => 'required|date',

            'start_time' => 'required',

            'end_time' => 'required',

            'reason' => 'required|min:5',
        ]);

        /*
    |--------------------------------------------------------------------------
    | HITUNG JAM
    |--------------------------------------------------------------------------
    */
        $start = Carbon::parse($request->start_time);

        $end = Carbon::parse($request->end_time);

        // VALIDASI JAM
        if ($end <= $start) {

            return back()
                ->withErrors([
                    'end_time' => 'Jam selesai harus lebih besar dari jam mulai'
                ])
                ->withInput();
        }

        $hours = $start->diffInHours($end);

        /*
    |--------------------------------------------------------------------------
    | SIMPAN
    |--------------------------------------------------------------------------
    */
        Overtime::create([

            'user_id' => auth()->id(),

            'overtime_date' => $request->overtime_date,

            'start_time' => $request->start_time,

            'end_time' => $request->end_time,

            'total_hours' => $hours,

            'reason' => $request->reason,

            'status' => 'pending',

            'pj_status' => 'pending',

            'hrd_status' => 'pending',
        ]);

        return redirect('/dashboard')
            ->with(
                'success',
                'Pengajuan lembur berhasil dikirim'
            );
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

        return view(
            'lembur-history',
            compact('overtimes')
        );
    }
}
