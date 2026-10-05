<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal semua parameter absensi & face recognition.
 *
 * Nilai disimpan di tabel `attendance_settings` (bukan konstanta) sehingga
 * HR / admin dapat menyesuaikannya dari UI tanpa mengubah source code.
 * Nilai bawaan dipakai bila baris kosong, jadi sistem tetap aman walau tabel
 * Setting belum diisi.
 *
 * Kunci yang tersedia:
 *   face.enabled              : master switch verifikasi wajah (default false)
 *   face.match_threshold      : skor minimum dianggap cocok (default 0.62)
 *   face.gray_threshold       : skor minimum zona abu (default 0.45)
 *   face.max_attempts         : maksimal percobaan scan per sesi absen (default 3)
 *   face.attempt_timeout_ms   : batas waktu satu percobaan (default 3000)
 *   face.min_samples          : minimal sampel saat registrasi (default 5)
 *   face.min_quality          : minimal kualitas sampel yang diterima (0..1)
 *   face.retention_days       : retensi foto punch dalam hari (default 60)
 *   face.service_url          : URL microservice face recognition
 *   face.service_token        : token API microservice
 *   face.exempt_roles         : role yang TIDAK wajib absen pakai wajah
 *   office.radius_meters      : radius GPS kantor (default 200)
 *   office.latitude           : latitude kantor
 *   office.longitude          : longitude kantor
 */
class FaceSettings
{
    /** Nilai bawaan bila setting belum diisi di database. */
    public const DEFAULTS = [
        'face.enabled' => false,
        'face.match_threshold' => 0.62,
        'face.gray_threshold' => 0.45,
        'face.max_attempts' => 5,
        'face.attempt_timeout_ms' => 3000,
        'face.min_samples' => 5,
        'face.min_quality' => 0.40,
        'face.retention_days' => 60,
        'face.audit_retention_days' => 60,
        'face.spoof_enabled' => false,
        'face.reject_action' => 'keep',
        'face.consent_notice_version' => 'v1.0',
        'telegram.bot_token' => '',
        'telegram.chat_id' => '',
        'telegram.alert_mismatch_threshold' => 5,
        'face.service_url' => 'http://127.0.0.1:8100',
        'face.service_token' => '',
        'face.exempt_roles' => 'manager_umum,manager_finance,director,medical_service',
        'office.radius_meters' => 200,
        'office.latitude' => 0.4761258,
        'office.longitude' => 101.4190600,
    ];

    /**
     * Berapa detik cache settings bertahan sebelum dibaca ulang.
     *
     * Bawaan 0 (tanpa cache) dengan sengaja: pengaturan ini jarang berubah,
     * sedangkan cache yang basi membuat admin mengira simpanannya gagal ketika
     * sebenarnya sudah tersimpan. Setel FACE_SETTINGS_CACHE_TTL bila memang
     * perlu cache (mis. 60) dan aplikasi dijalankan di bawah Octane.
     */
    private static function cacheTtl(): int
    {
        return max(0, (int) env('FACE_SETTINGS_CACHE_TTL', 0));
    }

    private static ?array $cache = null;
    private static int $cachedAt = 0;

    /** Buang cache (dipanggil setiap kali admin menyimpan pengaturan). */
    public static function flush(): void
    {
        self::$cache = null;
        self::$cachedAt = 0;
    }

    /**
     * Peta nama variabel .env ke kunci pengaturan.
     *
     * Semua parameter tetap bisa diubah dari UI (/admin/face-settings) dan
     * nilai di database SELALU menang. Env hanya dipakai sebagai nilai
     * Awal / cadangan bila baris database belum ada.
     *
     * @return array<string, string>
     */
    public const ENV_MAP = [
        'face.enabled' => 'FACE_SERVICE_ENABLED',
        'face.service_url' => 'FACE_SERVICE_URL',
        'face.service_token' => 'FACE_SERVICE_TOKEN',
        'face.spoof_enabled' => 'FACE_ANTISPOOF_ENABLED',
        'face.match_threshold' => 'FACE_MATCH_THRESHOLD',
        'face.gray_threshold' => 'FACE_GRAY_THRESHOLD',
    ];

    /**
     * Nilai bawaan setelah digabung dengan .env.
     *
     * @return array<string, mixed>
     */
    public static function baseDefaults(): array
    {
        $values = self::DEFAULTS;

        foreach (self::ENV_MAP as $key => $envName) {
            $raw = env($envName);

            if ($raw === null || $raw === '') {
                continue;
            }

            if (is_bool(self::DEFAULTS[$key])) {
                $values[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_int(self::DEFAULTS[$key])) {
                $values[$key] = (int) $raw;
            } elseif (is_float(self::DEFAULTS[$key])) {
                $values[$key] = (float) $raw;
            } else {
                $values[$key] = trim((string) $raw);
            }
        }

        return $values;
    }

    /** Semua setting (dari database, dilengkapi nilai bawaan + .env). */
    public static function all(): array
    {
        $now = time();

        $ttl = self::cacheTtl();

        if ($ttl > 0 && self::$cache !== null && ($now - self::$cachedAt) < $ttl) {
            return self::$cache;
        }

        $values = self::baseDefaults();

        try {
            foreach (DB::table('attendance_settings')->pluck('svalue', 'skey') as $key => $value) {
                if (array_key_exists($key, $values)) {
                    $values[$key] = $value;
                }
            }
        } catch (\Throwable $e) {
            // Tabel belum ada / database bermasalah: pakai nilai bawaan saja.
        }

        self::$cache = $values;
        self::$cachedAt = $now;

        return $values;
    }

    public static function get(string $key): mixed
    {
        $values = self::all();

        return $values[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    public static function bool(string $key): bool
    {
        return filter_var(self::get($key), FILTER_VALIDATE_BOOLEAN);
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    public static function float(string $key): float
    {
        return (float) self::get($key);
    }

    public static function str(string $key): string
    {
        return trim((string) self::get($key));
    }

    /**
     * Daftar role yang bebas dari verifikasi wajah.
     *
     * @return array<int, string>
     */
    public static function exemptRoles(): array
    {
        $raw = self::str('face.exempt_roles');

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $role): string => strtolower(trim($role)),
            explode(',', $raw)
        )));
    }

    /** Simpan satu setting (dipakai controller admin). */
    public static function put(string $key, mixed $value): void
    {
        if (!array_key_exists($key, self::DEFAULTS)) {
            return;
        }

        DB::table('attendance_settings')->updateOrInsert(
            ['skey' => $key],
            ['svalue' => is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'updated_at' => now()]
        );

        self::flush();
    }

    /** Kembalikan ke nilai bawaan sistem. */
    public static function reset(): void
    {
        DB::table('attendance_settings')->whereIn('skey', array_keys(self::DEFAULTS))->delete();

        self::flush();
    }

    /** Ambang khusus untuk satu orang, jatuh ke ambang global bila kosong. */
    public static function matchThresholdFor(?float $personal = null): float
    {
        return $personal !== null && $personal > 0 ? (float) $personal : self::float('face.match_threshold');
    }
}
