<?php

namespace App\Support;

use App\Models\User;

/**
 * Kebijakan "wajib absen pakai wajah" per pengguna.
 *
 * Aturan:
 *  1. `face.enabled` = false  -> tidak ada verifikasi wajah sama sekali
 *     (seluruh sistem absensi berjalan seperti biasa).
 *  2. Role yang ada di `face.exempt_roles` -> bebas, seperti manager, direktur,
 *     dan YANMED.
 *  3. Selain itu -> wajib verifikasi wajah.
 */
class FacePolicy
{
    public static function enabled(): bool
    {
        return FaceSettings::bool('face.enabled');
    }

    /** Apakah pengguna ini bebas dari verifikasi wajah? */
    public static function isExempt(?User $user): bool
    {
        if ($user === null) {
            return true;
        }

        $exempt = FaceSettings::exemptRoles();

        if ($exempt === []) {
            return false;
        }

        $role = strtolower(trim((string) $user->role));

        // Role persis atau role turunan (mis. 'pj_gizi' ikut bebas bila 'gizi' bebas).
        return in_array($role, $exempt, true)
            || in_array(preg_replace('/^pj_/', '', $role) ?? '', $exempt, true);
    }

    /** Apakah pengguna wajib absen dengan verifikasi wajah? */
    public static function isRequired(?User $user): bool
    {
        if (!self::enabled()) {
            return false;
        }

        return !self::isExempt($user);
    }

    /** Pengguna yang sudah terdaftar wajahnya. */
    public static function isEnrolled(?User $user): bool
    {
        return $user !== null
            && $user->face_status === 'enrolled'
            && filled($user->face_embedding);
    }
}