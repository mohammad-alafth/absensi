<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;

class LeaveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | USER AJUKAN IZIN / CUTI
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:izin,sakit,cuti',
            'alasan' => 'required'
        ]);

        $leave = LeaveRequest::create([
            'user_id' => auth()->id(),
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'alasan' => $request->alasan,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Pengajuan berhasil',
            'data' => $leave
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN APPROVE / REJECT
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, $id)
    {
        $leave = LeaveRequest::findOrFail($id);

        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $leave->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Status berhasil diupdate'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LIST
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        return LeaveRequest::with('user')
            ->latest()
            ->get();
    }
}
