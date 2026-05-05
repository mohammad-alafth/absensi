<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'jenis',
        'alasan',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}