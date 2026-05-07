<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

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

        /*
        |--------------------------------------------------------------------------
        | GLOBAL STATUS
        |--------------------------------------------------------------------------
        */
        'status',

        /*
        |--------------------------------------------------------------------------
        | PJ
        |--------------------------------------------------------------------------
        */
        'pj_status',
        'pj_note',
        'pj_approved_by',
        'pj_approved_at',

        /*
        |--------------------------------------------------------------------------
        | HRD
        |--------------------------------------------------------------------------
        */
        'hrd_status',
        'hrd_note',
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
