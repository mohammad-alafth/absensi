<?php

namespace App\Services;

final class ProtectedRouteDecision
{
    public const PROTECTED = 'protected_invariant';
    public const METADATA_MISSING = 'metadata_missing';
    public const METADATA_CONFLICT = 'metadata_conflict';

    public function __construct(
        public readonly string $decision,
        public readonly ?string $permissionKey = null,
        public readonly string $reason = '',
    ) {
    }

    public function protected(): bool
    {
        return $this->decision === self::PROTECTED;
    }
}
