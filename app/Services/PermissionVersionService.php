<?php

namespace App\Services;

use App\Models\PermissionStateVersion;
use RuntimeException;

class PermissionVersionService
{
    public const STATE_KEY = 'permission';

    public function current(): ?int
    {
        $state = PermissionStateVersion::query()
            ->where('state_key', self::STATE_KEY)
            ->first();

        return $state?->version;
    }

    /** Must be called from an existing database transaction. */
    public function incrementWithinTransaction(): int
    {
        $state = PermissionStateVersion::query()
            ->where('state_key', self::STATE_KEY)
            ->lockForUpdate()
            ->first();

        if ($state === null) {
            throw new RuntimeException('Permission state version row is missing.');
        }

        $state->version++;
        $state->save();

        return $state->version;
    }
}
