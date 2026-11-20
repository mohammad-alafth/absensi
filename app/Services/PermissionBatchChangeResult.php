<?php

namespace App\Services;

final class PermissionBatchChangeResult
{
    /** @param list<array{permission_key:string,before_granted:bool|null,after_granted:bool,audit_id:int}> $changes */
    public function __construct(
        public readonly string $roleKey,
        public readonly int $version,
        public readonly int $previousVersion,
        public readonly array $changes,
    ) {
    }

    public function changedCount(): int
    {
        return count($this->changes);
    }
}
