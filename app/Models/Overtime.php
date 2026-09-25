<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Concerns\CompressesSignatureAttributes;

class Overtime extends Model
{
    use CompressesSignatureAttributes;

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

        // APPROVAL STAGE (konvensi: nama stage == prefix kolom)
        'yanmed_status',
        'yanmed_signature',
        'yanmed_note',
        'yanmed_approved_by',
        'yanmed_approved_at',

        'medical_service_status',
        'medical_service_signature',
        'medical_service_note',
        'medical_service_approved_by',
        'medical_service_approved_at',

        'kabag_umum_status',
        'kabag_umum_signature',
        'kabag_umum_note',
        'kabag_umum_approved_by',
        'kabag_umum_approved_at',

        'manager_umum_status',
        'manager_umum_signature',
        'manager_umum_note',
        'manager_umum_approved_by',
        'manager_umum_approved_at',

        'kabag_marketing_status',
        'kabag_marketing_signature',
        'kabag_marketing_note',
        'kabag_marketing_approved_by',
        'kabag_marketing_approved_at',

        'manager_finance_status',
        'manager_finance_signature',
        'manager_finance_note',
        'manager_finance_approved_by',
        'manager_finance_approved_at',
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

    public function medicalServiceApprover()
    {
        return $this->belongsTo(User::class, 'medical_service_approved_by');
    }

    public function yanmedApprover()
    {
        return $this->belongsTo(User::class, 'yanmed_approved_by');
    }

    public function kabagUmumApprover()
    {
        return $this->belongsTo(User::class, 'kabag_umum_approved_by');
    }

    public function managerUmumApprover()
    {
        return $this->belongsTo(User::class, 'manager_umum_approved_by');
    }

    public function kabagMarketingApprover()
    {
        return $this->belongsTo(User::class, 'kabag_marketing_approved_by');
    }

    public function managerFinanceApprover()
    {
        return $this->belongsTo(User::class, 'manager_finance_approved_by');
    }
    
}
