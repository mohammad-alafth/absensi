<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| ABSEN LEMBUR REALTIME (LOG BUKTI KEHADIRAN)
|--------------------------------------------------------------------------
| Satu baris = satu kali tekan tombol absen (mulai / selesai) atau satu
| koreksi oleh PJ. Baris tidak pernah diubahnilainya; kolom actual_* pada
| model Overtime adalah ringkasan turunan dari log ini.
*/

class OvertimePunch extends Model
{
    public const TYPE_START = 'start';

    public const TYPE_END = 'end';

    public const TYPE_CORRECTION = 'koreksi';

    protected $fillable = [
        'overtime_id',
        'user_id',
        'type',
        'punched_at',
        'latitude',
        'longitude',
        'accuracy',
        'distance_m',
        'photo',
        'source',
        'note',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'distance_m' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI
    |--------------------------------------------------------------------------
    */
    public function overtime()
    {
        return $this->belongsTo(Overtime::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | BUKTI FOTO
    |--------------------------------------------------------------------------
    | Foto disimpan di disk public pada folder overtime-punches sehingga bisa
    | ditampilakan lewat asset('storage/...') di halaman approval.
    */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | LABEL TAMPILAN
    |--------------------------------------------------------------------------
    */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_START => 'Mulai lembur',
            self::TYPE_END => 'Selesai lembur',
            self::TYPE_CORRECTION => 'Koreksi PJ/HRD',
            default => $this->type,
        };
    }
}
