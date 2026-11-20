<?php

namespace App\Services;

final class PermissionGrantChangeResult
{
    public function __construct(
        public readonly int $mappingId,
        public readonly int $auditId,
        public readonly int $version,
        public readonly string $roleKey,
        public readonly string $permissionKey,
        public readonly bool $granted,
        public readonly ?bool $beforeGranted,
    ) {
    }
}
