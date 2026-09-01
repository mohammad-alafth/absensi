<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Leave;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'face_descriptor',
        'finger_id',
        'leave_quota',
        'is_approved',
        'password_reset_request',
        'work_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function shiftChangeRequests()
    {
        return $this->hasMany(ShiftChangeRequest::class);
    }

    public function getRoleLabelAttribute()
    {
        $roles = [
            'admin' => 'ADMIN',
            'hrd' => 'HRD',
            'it' => 'IT',
            'marketing' => 'MARKETING',
            'creator' => 'KONTEN CREATOR',
            'head_pegawai' => 'KEPALA BAGIAN UMUM DAN KEPEGAWAIAN',
            'director' => 'DIREKTUR',
            'medical_service' => 'MEDICAL SERVICE',
            'pipp' => 'PIPP',
            'pj_security' => 'PENANGGUNG JAWAB SECURITY',
            'pj_marketing' => 'PENANGGUNG JAWAB MARKETING',
            'pj_ipsrs' => 'PENANGGUNG JAWAB IPSRS',
            'pj_casemix' => 'PENANGGUNG JAWAB CASEMIX',
            'pj_nurse' => 'PENANGGUNG JAWAB PERAWAT',
            'nurse_ok' => 'PERAWAT OK',
            'nurse' => 'PERAWAT',
            'finance' => 'KEUANGAN',
            'security' => 'SECURITY',
            'ro' => 'REFRAKSIONIS OPTISIEN',
            'pj_ro' => 'PENANGGUNG JAWAB REFRAKSIONIS OPTISIEN',
            'pj_pharmacist' => 'PENANGGUNG JAWAB APOTEKER',
            'pharmacist' => 'APOTEKER',
            'pj_finance' => 'PENANGGUNG JAWAB KEUANGAN',
            'pj_cs' => 'PENANGGUNG JAWAB CLEANING SERVICE',
            'cs' => 'CLEANING SERVICE',
            'pj_administrasi' => 'PENANGGUNG JAWAB ADMINISTRASI',
            'administrasi' => 'ADMINISTRASI',
            'nutrition' => 'GIZI',
            'medical_record' => 'REKAM MEDIS',
            'casemix' => 'CASEMIX',
            'ipsrs' => 'IPSRS',
        ];

        return $roles[$this->role]
            ?? strtoupper(str_replace('_', ' ', $this->role));
    }

    public function shifts()
    {
        return $this->hasMany(\App\Models\EmployeeShift::class, 'user_id');
    }

    public function getUsedLeaveAttribute()
    {
        return $this->leaves()
            ->where('status', 'approved')
            ->sum('total_days');
    }

    public function getRemainingLeaveAttribute()
    {
        return max(
            $this->leave_quota - $this->used_leave,
            0
        );
    }
}
