<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\CompressesSignatureAttributes;

class Permission extends Model
{
    use CompressesSignatureAttributes;

    /**
     * Jenis izin pulang cepat (pada form bernama "pulang lebih awal").
     *
     * Izin ini berjam-jam: absen pulang dibuka sejak `jam_mulai` izin yang
     * sudah disetujui, dan riwayat absen menampilkan durasinya sesuai surat
     * pengajuan (lihat AttendancePunchService & HistoryController::rekap).
     * Jenis ini hanya memakai SATU tanggal (`tanggal` / tanggal mulai);
     * `tanggal_selesai` selalu dipaksa sama dengan `tanggal` saat disimpan
     * (lihat PermissionController::store & update).
     */
    public const EARLY_LEAVE_TYPES = ['pulang lebih awal', 'pulang cepat'];

    /**
     * Jenis izin terlambat masuk (pada form: "terlambat masuk").
     *
     * Izin ini berjam-jam: absen masuk dibuka sampai `jam_selesai` izin yang
     * sudah disetujui, dan absen pada rentang izin tidak tercatat "terlambat"
     * (lihat AttendancePunchService::checkIn).
     */
    public const LATE_ARRIVAL_TYPES = ['terlambat masuk'];

    protected $fillable = [

        'user_id',

        'tanggal',
        'tanggal_selesai',
        'jenis',

        'jam_mulai',
        'jam_selesai',

        'alasan',
        'lampiran',
        'employee_signature',
        'pj_signature',
        'hrd_signature',
        'pdf_file',

        'status',

        // PJ
        'pj_status',
        'pj_note',
        'pj_approved_by',
        'pj_approved_at',
        'pj_note',
        // HRD
        'hrd_status',
        'hrd_note',
        'hrd_approved_by',
        'hrd_approved_at',

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function pjApprover()
    {
        return $this->belongsTo(
            User::class,
            'pj_approved_by'
        );
    }
    public function hrdApprover()
    {
        return $this->belongsTo(
            User::class,
            'hrd_approved_by'
        );
    }
    public function directorApprover()
    {
        return $this->belongsTo(User::class, 'director_approved_by');
    }
    public function headApprover()
    {
        return $this->belongsTo(User::class, 'head_approved_by');
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

    /** Apakah nama jenis izin ini termasuk pulang cepat (pulang lebih awal)? */
    public static function isEarlyLeaveType(string $jenis): bool
    {
        return in_array(strtolower(trim($jenis)), self::EARLY_LEAVE_TYPES, true);
    }

    /** Apakah izin ini termasuk jenis pulang cepat (pulang lebih awal)? */
    public function isEarlyLeave(): bool
    {
        return self::isEarlyLeaveType((string) $this->jenis);
    }

    /** Apakah izin ini termasuk jenis terlambat masuk? */
    public function isLateArrival(): bool
    {
        return in_array(
            strtolower(trim((string) $this->jenis)),
            self::LATE_ARRIVAL_TYPES,
            true
        );
    }

    /**
     * Label durasi izin dari `jam_mulai` s/d `jam_selesai`
     * ("2 jam", "1 jam 30 menit").
     *
     * Null bila jam belum diisi (mis. izin multi-hari). Rentang yang
     * melewati tengah malam dihitung sampai hari berikutnya.
     */
    public function getDurationLabelAttribute(): ?string
    {
        if (!$this->jam_mulai || !$this->jam_selesai) {
            return null;
        }

        try {
            $start = Carbon::parse($this->jam_mulai);
            $end   = Carbon::parse($this->jam_selesai);
        } catch (\Throwable $e) {
            return null;
        }

        if ($end->lte($start)) {
            $end->addDay();
        }

        $minutes = (int) round($start->diffInMinutes($end));
        $hours   = intdiv($minutes, 60);
        $rest    = $minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' jam';
        }
        if ($rest > 0) {
            $parts[] = $rest . ' menit';
        }

        return $parts === [] ? '0 menit' : implode(' ', $parts);
    }
}
