<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class PermissionRegistry extends Model
{
    protected $table = 'permission_registry';

    protected $fillable = [
        'module_id',
        'key',
        'label',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $permission): void {
            if (!preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)+$/', (string) $permission->key)) {
                throw ValidationException::withMessages([
                    'key' => 'Permission key harus stabil dan menggunakan format identifier.',
                ]);
            }
        });
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(PermissionModule::class, 'module_id');
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'permission_id');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(PermissionChangeLog::class, 'permission_id');
    }
}
