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

        /*
        |---------------------------------------------------------------------
        | ABSEN LEMBUR REALTIME (BUKTI KEHADIRAN)
        |---------------------------------------------------------------------
        | planned_hours = jam rencana dari form (snapshot, tidak berubah)
        | total_hours   = volume sah yang disahkan, otomatis mengikuti jam
        |                 nyata bila bukti absen tersedia
        */
        'planned_hours',
        'actual_start_at',
        'actual_end_at',
        'actual_minutes',
        'actual_hours',
        'proof_type',
        'proof_note',
        'proof_corrected_by',
        'needs_review',

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

    /*
    |--------------------------------------------------------------------------
    | ABSEN LEMBUR REALTIME
    |--------------------------------------------------------------------------
    | Log absen (bukti) per pengajuan. Kolom actual_* adalah ringkasan
    | turunannya; lihat App\Services\OvertimePunchService.
    */
    protected $casts = [
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'needs_review' => 'boolean',
    ];

    public function punches()
    {
        return $this->hasMany(OvertimePunch::class)->orderBy('punched_at');
    }

    /** Petugas PJ/HRD yang mengoreksi jam nyata. */
    public function proofCorrector()
    {
        return $this->belongsTo(User::class, 'proof_corrected_by');
    }

    /** Sedang berjalan: sudah absen mulai tapi belum absen selesai. */
    public function getIsRunningAttribute(): bool
    {
        return $this->actual_start_at !== null
            && $this->actual_end_at === null;
    }

    /** Rentang jam nyata, mis. "10:00 - 14:10"; null bila belum ada absen. */
    public function getActualRangeLabelAttribute(): ?string
    {
        if (!$this->actual_start_at) {
            return null;
        }

        return $this->actual_start_at->format('H:i')
            . ' - '
            . ($this->actual_end_at
                ? $this->actual_end_at->format('H:i')
                : 'berjalan');
    }

    /**
     * Label jam selesai pada SPL. Jam selesai boleh kosong: lembur hari libur
     * mode "sampai selesai" tidak merencanakan jam pulang, jam nyatanya diambil
     * dari absen pulang (lihat App\Services\OvertimePunchService).
     */
    public function getEndTimeLabelAttribute(): string
    {
        return $this->end_time
            ? substr((string) $this->end_time, 0, 5)
            : 'sampai selesai';
    }

    /** Rentang jam rencana, mis. "10:00 - 14:00" atau "10:00 - sampai selesai". */
    public function getPlannedRangeLabelAttribute(): string
    {
        return ($this->start_time ? substr((string) $this->start_time, 0, 5) : '-')
            . ' - '
            . $this->end_time_label;
    }

    /**
     * Label volume jam untuk daftar: angka resmi, atau keterangan bahwa volume
     * masih menunggu absen pulang pada lembur "sampai selesai".
     */
    public function getHoursLabelAttribute(): string
    {
        if (blank($this->end_time) && !$this->actual_end_at) {
            return 'menunggu absen pulang';
        }

        return (int) $this->total_hours . ' Jam';
    }

    /** Lembur tanpa Jam Berakhir: volume sepenuhnya ditentukan absen. */
    public function getIsOpenEndedAttribute(): bool
    {
        return blank($this->end_time);
    }

    /** Durasi nyata, mis. "4 jam 10 menit". */
    public function getActualDurationLabelAttribute(): ?string
    {
        if ($this->actual_minutes === null) {
            return null;
        }

        $minutes = (int) $this->actual_minutes;

        return intdiv($minutes, 60) . ' jam ' . ($minutes % 60) . ' menit';
    }

    /**
     * Label bukti untuk badge pada daftar & layar approval.
     */
    public function getProofLabelAttribute(): string
    {
        return match ($this->proof_type) {
            'realtime' => 'Absen realtime',
            'dari_absen' => 'Dari absen harian',
            'koreksi' => 'Koreksi PJ/HRD',
            default => 'Tanpa absen (manual)',
        };
    }

    /** Kelas warna badge bukti (Tailwind), senada dengan SubmissionStatus. */
    public function getProofToneAttribute(): string
    {
        return match ($this->proof_type) {
            'realtime', 'dari_absen' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
            'koreksi' => 'text-amber-700 bg-amber-50 border-amber-200',
            default => 'text-slate-500 bg-slate-50 border-slate-200',
        };
    }
}

