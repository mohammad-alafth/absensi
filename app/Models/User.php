<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


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
        return $this->hasMany(LeaveRequest::class);
    }

    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
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

            'nurse' => 'PERAWAT',

            'finance' => 'KEUANGAN',
        ];

        return $roles[$this->role]
            ?? strtoupper(str_replace('_', ' ', $this->role));
    }
}
