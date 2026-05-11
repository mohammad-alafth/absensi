<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

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
    | USER AJUKAN IZIN
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([

            'tanggal' => 'required|date',

            'jenis' => 'required|string',

            'jam_mulai' => 'nullable',

            'jam_selesai' => 'nullable',

            'alasan' => 'required|string|min:5',

            'lampiran' =>
            'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'

        ]);


        /*
        |--------------------------------------------------------------------------
        | Upload lampiran
        |--------------------------------------------------------------------------
        */
        $lampiran = null;

        if ($request->hasFile('lampiran')) {

            $lampiran = $request
                ->file('lampiran')
                ->store(
                    'izin',
                    'public'
                );
        }



        /*
        |--------------------------------------------------------------------------
        | Flow Role
        |--------------------------------------------------------------------------
        */
        $userRole =
            auth()->user()->role;


        // default user biasa
        $status = 'pending';

        $pjStatus = 'pending';

        $hrdStatus = 'pending';



        /*
        |--------------------------------------------------------------------------
        | Kalau submit dari PJ
        |--------------------------------------------------------------------------
        */
        if (
            str_starts_with(
                $userRole,
                'pj_'
            )
        ) {

            $status = 'waiting_hrd';

            $pjStatus = 'approved';
        }



        /*
        |--------------------------------------------------------------------------
        | Kalau submit dari HRD
        |--------------------------------------------------------------------------
        */
        if (
            $userRole == 'hrd'
        ) {

            $status = 'approved';

            $pjStatus = 'approved';

            $hrdStatus = 'approved';
        }



        /*
        |--------------------------------------------------------------------------
        | Simpan
        |--------------------------------------------------------------------------
        */
        Permission::create([

            'user_id' => auth()->id(),

            'tanggal' => $request->tanggal,

            'jenis' => $request->jenis,

            'jam_mulai' => $request->jam_mulai,

            'jam_selesai' => $request->jam_selesai,

            'alasan' => $request->alasan,

            'lampiran' => $lampiran,


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */
            'status' => $status,

            'pj_status' => $pjStatus,

            'hrd_status' => $hrdStatus,

        ]);


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
        $permissions =
            Permission::with([

                'pjApprover',

                'hrdApprover'

            ])

            ->where(
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
