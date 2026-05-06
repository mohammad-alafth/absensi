<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Permission;

class HistoryController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        /*
        |--------------------------------------------------------------------------
        | CUTI
        |--------------------------------------------------------------------------
        */
        $leaves = Leave::where(
            'user_id',
            $userId
        )
            ->orderBy('start_date', 'desc')
            ->get()
            ->groupBy(function ($item) {

                return date(
                    'Y',
                    strtotime(
                        $item->start_date
                    )
                );
            });


        /*
        |--------------------------------------------------------------------------
        | IZIN
        |--------------------------------------------------------------------------
        */
        $permissions = Permission::where(
            'user_id',
            $userId
        )
            ->orderBy('tanggal', 'desc')
            ->get()
            ->groupBy(function ($item) {

                return date(
                    'Y',
                    strtotime(
                        $item->tanggal
                    )
                );
            });


        /*
        |--------------------------------------------------------------------------
        | Ambil semua tahun yang ada
        |--------------------------------------------------------------------------
        */
        $years = collect(
            array_merge(
                $leaves->keys()->toArray(),
                $permissions->keys()->toArray()
            )
        )
            ->unique()
            ->sortDesc()
            ->values();


        return view(
            'history.index',
            compact(
                'years',
                'leaves',
                'permissions'
            )
        );
    }
}
