<?php

namespace App\Support;



/**
 * Penyimpanan vektor wajah pada kolom `users.face_embedding`.
 *
 * Format: `v1:<base64(float32 array)>`
 * - float32 hemat ruang (512 dimensi = 2 KB per orang),
 * - awalan versi untuk memudahkan migrasi model di masa depan,
 * - kolom tetap bertipe longText, jadi aman untuk MySQL lama.
 */
class FaceEmbedding
{
    private const PREFIX = 'v1:';

    /**
     * @param array<int, float> $vector
     */
    public static function encode(array $vector): string
    {
        if ($vector === []) {
            return '';
        }

        $binary = '';

        foreach ($vector as $value) {
            $binary .= pack('g', (float) $value);
        }

        return self::PREFIX . base64_encode($binary);
    }

    /**
     * @return array<int, float>
     */
    public static function decode(?string $payload): array
    {
        if (!is_string($payload) || trim($payload) === '') {
            return [];
        }

        $payload = trim($payload);

        if (!str_starts_with($payload, self::PREFIX)) {
            return [];
        }

        $binary = base64_decode(substr($payload, strlen(self::PREFIX)), true);

        if ($binary === false || $binary === '') {
            return [];
        }

        $count = intdiv(strlen($binary), 4);
        $vector = [];

        for ($i = 0; $i < $count; $i++) {
            /** @var array{1: float} $unpacked */
            $unpacked = unpack('g', substr($binary, $i * 4, 4));
            $vector[] = (float) $unpacked[1];
        }

        return $vector;
    }
}