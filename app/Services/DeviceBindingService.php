<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\DeviceBindingAudit;
use App\Support\FaceSettings;
use Illuminate\Http\Request;

/**
 * Satu-satunya tempat logika device binding.
 *
 * Alur:
 *   1. Browser membuat UUID sendiri (crypto.randomUUID) dan menyimpannya di
 *      localStorage.
 *   2. `bind()` mencatat/memperbarui perangkat lewat endpoint
 *      POST /api/device/bind.
 *   3. `evaluate()` dipakai alur absen (fase berikutnya) untuk mengetahui
 *      apakah perangkat dikenal.
 *
 * PENTING (keputusan D1): service ini TIDAK PERNAH menjadi gerbang absensi.
 * Semua kegagalan (UUID kosong/tidak valid, perangkat tidak dikenal, limit
 * terlampaui, fitur dimatikan) hanya dilaporkan lewat status, tidak melempar
 * exception dan tidak mengembalikan error yang menghentikan punch.
 */
class DeviceBindingService
{
    /** Pola UUID v1-v5 (cukup ketat, tanpa bergantung pada validasi ketat versi). */
    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public function isEnabled(): bool
    {
        return FaceSettings::bool('device.enabled');
    }

    public function maxPerUser(): int
    {
        return max(1, FaceSettings::int('device.max_per_user'));
    }

    public function requireTrust(): bool
    {
        return FaceSettings::bool('device.require_trust');
    }

    /** Normalisasi UUID; null bila kosong atau formatnya tidak sah. */
    public function normalizeUuid(?string $uuid): ?string
    {
        $uuid = strtolower(trim((string) $uuid));

        return preg_match(self::UUID_PATTERN, $uuid) === 1 ? $uuid : null;
    }

    /**
     * Catat perangkat (idempotent per user + UUID).
     *
     * @param array{label?: ?string, platform?: ?string} $meta
     * @return array{status: string, device: ?UserDevice, max_per_user: int}
     */
    public function bind(User $user, ?string $uuid, array $meta = [], ?Request $request = null): array
    {
        $max = $this->maxPerUser();

        if (!$this->isEnabled()) {
            $this->audit($user, $uuid, 'disabled', null, $request);
            return ['status' => 'disabled', 'device' => null, 'max_per_user' => $max];
        }

        $uuid = $this->normalizeUuid($uuid);

        if ($uuid === null) {
            $this->audit($user, null, 'invalid', null, $request);
            return ['status' => 'invalid', 'device' => null, 'max_per_user' => $max];
        }

        $device = UserDevice::query()
            ->where('user_id', $user->id)
            ->where('device_uuid', $uuid)
            ->first();

        if ($device) {
            $device->fill([
                'label' => $this->cleanLabel($meta['label'] ?? null) ?? $device->label,
                'platform' => $this->cleanPlatform($meta['platform'] ?? null) ?? $device->platform,
                'user_agent' => $this->cleanUserAgent($request?->userAgent()) ?? $device->user_agent,
                'last_seen_at' => now(),
            ]);
            $device->save();

            $this->audit($user, $uuid, $device->isRevoked() ? 'revoked' : 'bound', $device, $request);

            return [
                'status' => $device->isRevoked() ? 'revoked' : 'bound',
                'device' => $device,
                'max_per_user' => $max,
            ];
        }

        // Perangkat yang sudah dicabut tetap dihitung sebagai riwayat, bukan
        // perangkat aktif, sehingga tidak menghabiskan kuota.
        $activeCount = UserDevice::query()
            ->where('user_id', $user->id)
            ->active()
            ->count();

        if ($activeCount >= $max) {
            $this->audit($user, $uuid, 'limit_reached', null, $request);
            return ['status' => 'limit_reached', 'device' => null, 'max_per_user' => $max];
        }

        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => $uuid,
            'label' => $this->cleanLabel($meta['label'] ?? null),
            'platform' => $this->cleanPlatform($meta['platform'] ?? null),
            'user_agent' => $this->cleanUserAgent($request?->userAgent()),
            'first_ip' => $request?->ip(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'status' => $this->requireTrust()
                ? UserDevice::STATUS_PENDING
                : UserDevice::STATUS_TRUSTED,
        ]);

        $this->audit($user, $uuid, 'bound', $device, $request);

        return ['status' => 'bound', 'device' => $device, 'max_per_user' => $max];
    }

    private function audit(User $user, ?string $uuid, string $outcome, ?UserDevice $device, ?Request $request): void
    {
        DeviceBindingAudit::query()->create([
            'actor_id' => $user->id,
            'target_user_id' => $user->id,
            'device_id' => $device?->id,
            'device_uuid_hash' => $uuid === null ? null : hash('sha256', $uuid),
            'action' => 'bind',
            'outcome' => $outcome,
            'ip_address' => $request?->ip(),
        ]);
    }

    /**
     * Nilai perangkat untuk satu permintaan absen.
     *
     * Tidak pernah melempar exception: alur absen harus tetap berjalan
     * walaupun pencatatan perangkat gagal.
     *
     * @return array{
     *     checked: bool,
     *     known: bool,
     *     verified: bool,
     *     status: string,
     *     device_id: ?int,
     *     uuid: ?string
     * }
     */
    public function evaluate(User $user, ?string $uuid): array
    {
        $result = [
            'checked' => false,
            'known' => false,
            'verified' => false,
            'status' => 'disabled',
            'device_id' => null,
            'uuid' => null,
        ];

        if (!$this->isEnabled()) {
            return $result;
        }

        $uuid = $this->normalizeUuid($uuid);

        if ($uuid === null) {
            // Tidak ada UUID: tidak ada yang bisa dievaluasi. Bukan error.
            return $result;
        }

        $result['checked'] = true;
        $result['uuid'] = $uuid;

        $device = UserDevice::query()
            ->where('user_id', $user->id)
            ->where('device_uuid', $uuid)
            ->first();

        if (!$device) {
            $result['status'] = 'unknown';

            return $result;
        }

        $result['known'] = true;
        $result['device_id'] = $device->id;
        $result['status'] = $device->status;
        $result['verified'] = $device->isTrusted();

        return $result;
    }

    private function cleanLabel(?string $label): ?string
    {
        $label = trim((string) $label);

        return $label === '' ? null : mb_substr($label, 0, 120);
    }

    private function cleanPlatform(?string $platform): ?string
    {
        $platform = trim((string) $platform);

        return $platform === '' ? null : mb_substr($platform, 0, 60);
    }

    private function cleanUserAgent(?string $userAgent): ?string
    {
        $userAgent = trim((string) $userAgent);

        return $userAgent === '' ? null : mb_substr($userAgent, 0, 255);
    }
}
