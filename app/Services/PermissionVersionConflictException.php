<?php

namespace App\Services;

use RuntimeException;

class PermissionVersionConflictException extends RuntimeException
{
    public function __construct(
        public readonly int $expectedVersion,
        public readonly int $actualVersion,
    ) {
        parent::__construct('Permission data changed since this editor was opened. Reload before saving.');
    }
}
