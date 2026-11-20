<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role_key',
        'permission_id',
        'granted',
    ];

    protected $casts = [
        'granted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $mapping): void {
            $roleKey = (string) $mapping->role_key;

            if ($roleKey === '' || mb_strlen($roleKey) > 255) {
                throw ValidationException::withMessages([
                    'role_key' => 'Role key wajib menggunakan nilai users.role yang tidak kosong.',
                ]);
            }
        });
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(PermissionRegistry::class, 'permission_id');
    }
}
