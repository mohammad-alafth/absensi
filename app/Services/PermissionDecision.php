<?php

namespace App\Services;

final class PermissionDecision
{
    public const GRANTED = 'granted';
    public const DENIED = 'denied';
    public const UNMAPPED = 'unmapped';
    public const PROTECTED_INVARIANT = 'protected_invariant';

    public function __construct(
        public readonly string $decision,
        public readonly string $roleKey,
        public readonly string $permissionKey,
        public readonly string $reason,
        public readonly bool $permissionRegistered = false,
        public readonly bool $permissionActive = false,
        public readonly bool $moduleActive = false,
        public readonly ?bool $grant = null,
    ) {
    }

    public function granted(): bool
    {
        return $this->decision === self::GRANTED;
    }

    /** @return array<string, bool|int|null|string> */
    public function toArray(): array
    {
        return [
            'decision' => $this->decision,
            'role_key' => $this->roleKey,
            'permission_key' => $this->permissionKey,
            'reason' => $this->reason,
            'permission_registered' => $this->permissionRegistered,
            'permission_active' => $this->permissionActive,
            'module_active' => $this->moduleActive,
            'grant' => $this->grant,
        ];
    }
}
