<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Overtime extends Model
{
    protected $fillable = [

        'user_id',

        'overtime_date',

        'start_time',
        'end_time',

        'total_hours',

        'reason',

        'status',

        'pj_status',
        'pj_approved_by',
        'pj_approved_at',

        'hrd_status',
        'hrd_approved_by',
        'hrd_approved_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | PJ APPROVER
    |--------------------------------------------------------------------------
    */
    public function pjApprover()
    {
        return $this->belongsTo(
            User::class,
            'pj_approved_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HRD APPROVER
    |--------------------------------------------------------------------------
    */
    public function hrdApprover()
    {
        return $this->belongsTo(
            User::class,
            'hrd_approved_by'
        );
    }
}