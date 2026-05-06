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
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}