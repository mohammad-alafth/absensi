<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionStateVersion extends Model
{
    protected $table = 'permission_state_versions';

    protected $fillable = [
        'state_key',
        'version',
    ];

    protected $casts = [
        'version' => 'integer',
    ];
}
