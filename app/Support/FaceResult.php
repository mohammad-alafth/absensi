<?php

namespace App\Support;

/**
 * Hasil verifikasi wajah untuk satu percobaan scan/punch.
 *
 * Status yang mungkin:
 *  - match        : skor >= ambang match, wajah dikenali.
 *  - gray         : skor di antara ambang abu dan ambang match, perlu review HRD.
 *  - mismatch     : skor di bawah ambang abu, wajah tidak dikenali.
 *  - no_enrollment: pengguna belum terdaftar wajah.
 *  - exempt       : pengguna tidak wajib wajah (mis. manager, direktur, YANMED).
 *  - unavailable  : layanan AI tidak dapat dihubungi (absen tetap dicatat).
 *  - no_face      : tidak ada gambar wajah yang dikirim.
 */
final class FaceResult
{
    public const MATCH = 'match';
    public const GRAY = 'gray';
    public const MISMATCH = 'mismatch';
    public const NO_ENROLLMENT = 'no_enrollment';
    public const EXEMPT = 'exempt';
    public const UNAVAILABLE = 'unavailable';
    public const NO_FACE = 'no_face';
    public const SPOOF = 'spoof';

    /**
     * @param string          $status    salah satu konstanta di atas
     * @param float|null      $score     cosine similarity (0..1), null bila tidak dihitung
     * @param string|null     $message   pesan singkat untuk ditampilkan ke pengguna
     * @param string|null     $method    metode verifikasi (service | exempt | manual_fallback)
     * @param array|null      $embedding vektor wajah 512-d (hanya saat registrasi)
     */
    public function __construct(
        public readonly string $status,
        public readonly ?float $score = null,
        public readonly ?string $message = null,
        public readonly ?string $method = null,
        public readonly ?array $embedding = null,
    ) {
    }

    public function isMatch(): bool
    {
        return $this->status === self::MATCH;
    }

    /** True bila wajah dikenali sehingga boleh absen tanpa review manual. */
    public function isAccepted(): bool
    {
        return in_array($this->status, [self::MATCH, self::EXEMPT], true);
    }

    /**
     * True bila absen tetap boleh dicatat meski wajah tidak dipastikan.
     *
     * Semua status selain `no_face` diperbolehkan:Policy B3 meminta
     * percobaan ulang sampai cocok, dan setelah batasnya habis barulah absen
     * dicatat untuk review HRD (bukan dikunci).
     */
    public function allowsAttendance(): bool
    {
        return $this->status !== self::NO_FACE;
    }

    /** True bila hasil perlu ditinjau HRD (skor & metode). */
    public function needsReview(): bool
    {
        return in_array($this->status, [self::GRAY, self::MISMATCH, self::SPOOF], true);
    }

    /** Nilai kolom `face_match` pada tabel attendances. */
    public function toMatchColumn(): ?bool
    {
        return match ($this->status) {
            self::MATCH => true,
            self::GRAY, self::MISMATCH => false,
            default => null,
        };
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'score' => $this->score,
            'message' => $this->message,
            'method' => $this->method,
        ];
    }

    public static function match(float $score, ?string $method = 'service'): self
    {
        return new self(self::MATCH, $score, 'Wajah dikenali.', $method);
    }

    public static function gray(float $score, ?string $message = null, ?string $method = 'service'): self
    {
        return new self(self::GRAY, $score, $message ?? 'Wajah belum yakin, perlu verifikasi HRD.', $method);
    }

    public static function mismatch(float $score, ?string $message = null, ?string $method = 'service'): self
    {
        return new self(self::MISMATCH, $score, $message ?? 'Wajah tidak dikenali.', $method);
    }

    public static function exempt(?string $message = 'Tidak wajib verifikasi wajah.'): self
    {
        return new self(self::EXEMPT, null, $message, 'exempt');
    }

    public static function unavailable(string $message = 'Layanan wajah tidak tersedia.'): self
    {
        return new self(self::UNAVAILABLE, null, $message, 'manual_fallback');
    }

    public static function noEnrollment(string $message = 'Wajah belum terdaftar.'): self
    {
        return new self(self::NO_ENROLLMENT, null, $message, 'manual_fallback');
    }

    public static function noFace(string $message = 'Foto wajah tidak terkirim.'): self
    {
        return new self(self::NO_FACE, null, $message, 'manual_fallback');
    }

    public static function spoof(?string $message = null, ?float $score = null): self
    {
        return new self(
            self::SPOOF,
            $score,
            $message ?? 'Wajah tidak terbaca sebagai orang sungguhan. Pastikan Anda berada di depan kamera.',
            'service'
        );
    }
}