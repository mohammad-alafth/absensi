<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [

        'user_id',

        'tanggal',
        'jenis',

        'jam_mulai',
        'jam_selesai',

        'alasan',
        'lampiran',

        'status',

        // PJ
        'pj_status',
        'pj_note',
        'pj_approved_by',
        'pj_approved_at',

        // HRD
        'hrd_status',
        'hrd_note',
        'hrd_approved_by',
        'hrd_approved_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function pjApprover()
    {
        return $this->belongsTo(
            User::class,
            'pj_approved_by'
        );
    }


    public function hrdApprover()
    {
        return $this->belongsTo(
            User::class,
            'hrd_approved_by'
        );
    }
}
