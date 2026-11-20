<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionLegacyException extends Model
{
    protected $table = 'permission_legacy_exceptions';

    protected $fillable = [
        'http_method',
        'uri_pattern',
        'route_name',
        'controller_action',
        'reason',
        'existing_guard',
        'risk',
        'migration_status',
        'owner_module',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
