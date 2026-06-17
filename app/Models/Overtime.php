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
        'employee_signature',
        'pj_signature',
        'hrd_signature',
        'pdf_file',

        'reason',
        'department',
        'day_type',
        'status',

        'pj_status',
        'pj_approved_by',
        'pj_approved_at',

        'hrd_status',
        'hrd_approved_by',
        'hrd_approved_at',
        'pj_note',
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
    // App\Models\Overtime.php

    public function directorApprover()
    {
        return $this->belongsTo(User::class, 'director_approved_by');
    }

    public function headApprover()
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function hrdApprover()
    {
        return $this->belongsTo(User::class, 'hrd_approved_by');
    }
    
}
