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
        'employee_signature',
        'pj_signature',
        'hrd_signature',
        'pdf_file',

        'status',

        // PJ
        'pj_status',
        'pj_note',
        'pj_approved_by',
        'pj_approved_at',
        'pj_note',
        // HRD
        'hrd_status',
        'hrd_note',
        'hrd_approved_by',
        'hrd_approved_at',

        'hrd_note',
        // Tambahkan kolom baru di bawah ini:
        'head_status',
        'director_status',
        'head_note',
        'director_note',
        'head_signature',
        'director_signature',
        'head_approved_by',
        'director_approved_by',
        'head_approved_at',
        'director_approved_at',
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
    public function directorApprover()
    {
        return $this->belongsTo(User::class, 'director_approved_by');
    }
    public function headApprover()
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }
}
