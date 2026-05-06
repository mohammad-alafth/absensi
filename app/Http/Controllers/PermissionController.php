<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function create()
    {
        return view('permission.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required',
            'jam_mulai' => 'nullable',
            'jam_selesai' => 'nullable',
            'alasan' => 'required|min:5',
            'lampiran' => 'nullable|file|mimes:jpg,png,pdf|max:2048'
        ]);

        $lampiran = null;

        if ($request->hasFile('lampiran')) {
            $lampiran = $request
                ->file('lampiran')
                ->store('izin', 'public');
        }

        Permission::create([
            'user_id' => auth()->id(),
            'tanggal' => $request->tanggal,
            'jenis' => $request->jenis,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'alasan' => $request->alasan,
            'lampiran' => $lampiran,
            'status' => 'pending'
        ]);

        return redirect('/dashboard')
            ->with(
                'success',
                'Pengajuan izin berhasil dikirim dan sedang menunggu approval.'
            );
    }

    public function history()
    {
        $permissions = Permission::where(
            'user_id',
            auth()->id()
        )
            ->latest()
            ->get();

        return view(
            'permission.history',
            compact('permissions')
        );
    }
}
