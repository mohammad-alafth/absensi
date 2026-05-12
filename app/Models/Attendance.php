<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

class Attendance extends Model
{
    protected $fillable = [

        'user_id',

        'shift_id',

        'tanggal',

        'jam_masuk',

        'jam_keluar',

        'latitude',

        'longitude',

        'status',

        'late_minutes',

        'overtime_minutes',

        'scheduled_checkin',

        'scheduled_checkout'
    ];

    protected $casts = [

        'jam_masuk' => 'datetime',

        'jam_keluar' => 'datetime',

        'scheduled_checkin' => 'datetime',

        'scheduled_checkout' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
