<?php

namespace App\Support;

/*
|--------------------------------------------------------------------------
| KOMPRESI TANDA TANGAN (DATA-URI BASE64)
|--------------------------------------------------------------------------
| Tanda tangan dari canvas dikirim sebagai data-URI PNG ukuran penuh
| (bisa 100-300 KB per tanda tangan). Karena setiap pengajuan menyimpan
| banyak kolom tanda tangan (11 kolom) dan gambar tersebut juga ditempel
| ke PDF, ukuran database & berkas PDF membengkak.
|
| Helper ini memperkecil dimensi gambar (bukan memotong/mengubah bentuk)
| dengan ekstensi GD yang sudah tersedia di PHP 8.2, lalu mengembalikan
| data-URI PNG dengan transparansi tetap utuh.
|
| Aman dipakai: bila input bukan data-URI gambar, bukan base64 valid,
| GD tidak tersedia, atau hasil kompresi tidak lebih kecil, nilai asli
| dikembalikan apa adanya (tidak pernah mengubah/menghilangkan tanda tangan).
*/
class SignatureCompressor
{
    // Lebar maksimum gambar tanda tangan yang disimpan (piksel).
    public const MAX_WIDTH = 480;

    // Tinggi maksimum gambar tanda tangan yang disimpan (piksel).
    public const MAX_HEIGHT = 240;

    public static function compress(?string $value): ?string
    {
        if (!$value || !is_string($value)) {
            return $value;
        }

        if (stripos($value, 'data:image') !== 0 || stripos($value, ';base64,') === false) {
            // Bukan base64 (mis. hanya nama file) -> biarkan seperti adanya.
            return $value;
        }

        if (!function_exists('imagecreatefromstring')) {
            return $value;
        }

        $payload = substr($value, strpos($value, ',') + 1);
        $binary  = base64_decode($payload, true);

        if ($binary === false || $binary === '') {
            return $value;
        }

        $image = @imagecreatefromstring($binary);

        if (!$image) {
            return $value;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        if ($width <= self::MAX_WIDTH && $height <= self::MAX_HEIGHT) {
            imagedestroy($image);
            return $value;
        }

        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height);

        $newWidth  = max(1, (int) floor($width * $ratio));
        $newHeight = max(1, (int) floor($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagepng($canvas);
        $output = ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        if (!$output) {
            return $value;
        }

        $compressed = 'data:image/png;base64,' . base64_encode($output);

        // Hanya pakai hasil kompresi bila benar-benar lebih kecil.
        return strlen($compressed) < strlen($value) ? $compressed : $value;
    }
}
