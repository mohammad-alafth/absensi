<?php

namespace App\Services;

use App\Models\FaceConsent;
use App\Support\FaceSettings;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Informasi & bukti persetujuan sebelum biometrik wajah diambil.
 *
 * Prinsip: pengguna HARUS menerima informasi yang jelas lebih dulu, lalu
 * persetujuan dicatat sebagai bukti yang dapat diperiksa (versi informasi, hash
 * dokumen, waktu, cara, tanda tangan), bukan sekadar checkbox `agree = 1`.
 *
 * CATATAN: UU PDP tidak hanya mengenal persetujuan sebagai dasar pemrosesan.
 * Implementasi di sini tidak menjamin maupun menyatakan kepatuhan;
 * pemilihan dasar pemrosesan tetap milik legal/compliance organisasi.
 */
class FaceConsentService
{
    /** Versi dokumen informasi; naikkan bila isi informasi berubah. */
    public static function noticeVersion(): string
    {
        return FaceSettings::str('face.consent_notice_version') ?: 'v1.0';
    }

    /** Teks informasi yang harus dibaca pengguna sebelum memberi persetujuan. */
    public static function noticeText(): string
    {
        return 'Sebelum data biometrik wajah Anda diambil dan disimpan, kami '
            . 'memberikan informasi berikut:'
            . "\n\n"
            . '1. Data yang dikumpulkan: citra wajah (selfie) saat registrasi, '
            . 'dan saat Anda melakukan absen. Citra disimpan sebagaivektor '
            . 'karakteristik wajah (bukan foto yang bisa dilihat orang lain), '
            . 'selama masa registrasi. Gambar selfie absen hanya dipakai sebagai '
            . 'bukti internal dan tidak ditampilkan maupun diunduh lewat tautan.'
            . "\n"
            . '2. Tujuan: memverifikasi identitas saat absen masuk/pulang agar '
            . 'catatan kehadiran akurat.'
            . "\n"
            . '3. Dasar pemrosesan: mengikuti dasar pemrosesan yang ditetapkan '
            . 'organisasi sesuai UU PDP. Persetujuan yang Anda berikan ini adalah '
            . 'salah satu bentuk kesediaan, bukan pernyataan bahwa seluruh '
            . 'pemrosesan otomatis patuh.'
            . "\n"
            . '4. Masa simpan: vektor wajah selama Anda aktif bekerja; foto bukti '
            . 'absen maksimal 2 bulan, lalu dihapus otomatis.'
            . "\n"
            . '5. Hak Anda: meminta salinan data, memperbarui/menarik kembali '
            . 'persetujuan, dan mengajukan permintaan penghapusan kepada HRD. '
            . 'Bila ditarik, Anda dapat tetap absen lewat verifikasi manual oleh HRD.';
    }

    /** Hash teks informasi (bukti dokumen yang benar-benar ditampilkan). */
    public static function noticeHash(): string
    {
        return hash('sha256', self::noticeText());
    }

    /** Apakah pengguna sudah memberi persetujuan untuk versi informasi ini? */
    public static function hasValidConsent(?User $user): bool
    {
        return $user !== null
            && FaceConsent::activeFor($user->id, self::noticeVersion()) !== null;
    }

    /**
     * Simpan bukti persetujuan.
     *
     * @param string $method typed_name | drawn_signature | uploaded_document
     */
    public function grant(
        User $user,
        string $method,
        ?string $evidence,
        ?Request $request = null
    ): FaceConsent {
        $consent = FaceConsent::create([
            'user_id' => $user->id,
            'notice_version' => self::noticeVersion(),
            'notice_hash' => self::noticeHash(),
            'method' => $method,
            'evidence' => $evidence,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255) ?: null,
            'consented_at' => now(),
        ]);

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::CONSENT,
            $user->id,
            'granted',
            ['notice_version' => $consent->notice_version, 'method' => $method],
            $request
        );

        return $consent;
    }

    /** Tarik kembali persetujuan (data tetap boleh dipakai untuk absen manual). */
    public function revoke(User $user, ?string $reason = null, ?Request $request = null): void
    {
        FaceConsent::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'revoke_reason' => $reason]);

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::CONSENT_REVOKE,
            $user->id,
            'revoked',
            ['reason' => $reason],
            $request
        );
    }
}