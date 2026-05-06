<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $table = 'leaves';

    protected $fillable = [
        'user_id',
        'recipient',

        'start_date',
        'end_date',
        'return_date',

        'total_days',

        'leave_type',
        'reason',

        'delegate_name',
        'delegate_nik',

        'emergency_contact',

        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
