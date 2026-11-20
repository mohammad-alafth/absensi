<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PermissionChangeLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'permission_change_logs';

    protected $fillable = [
        'actor_id',
        'target_role_key',
        'permission_id',
        'before_granted',
        'after_granted',
        'correlation_id',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'before_granted' => 'boolean',
        'after_granted' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Permission change logs are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Permission change logs are append-only.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(PermissionRegistry::class, 'permission_id');
    }
}
