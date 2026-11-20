<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAnnouncement extends Model
{
    protected $table = 'system_announcements';

    protected $fillable = [
        'title',
        'message',
        'level',
        'created_by_chat_id',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /** Ambil pengumuman yang sedang aktif (terbaru). */
    public static function current(): ?self
    {
        return self::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();
    }

    /** Nonaktifkan semua pengumuman aktif. */
    public static function dismissAll(): int
    {
        return self::where('is_active', true)->update(['is_active' => false]);
    }
}
