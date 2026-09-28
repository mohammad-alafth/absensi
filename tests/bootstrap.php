<?php

/*
|--------------------------------------------------------------------------
| BOOTSTRAP PHPUNIT
|--------------------------------------------------------------------------
| Menghapus config cache Laravel sebelum pengujian berjalan.
|
| Alasan: bila `php artisan config:cache` pernah dijalankan, nilai `DB_DATABASE`
| di phpunit.xml TIDAK lagi dipakai (config dibaca dari cache) sehingga
| `RefreshDatabase` bisa menyasar dan MENGHAPUS database dev (`absensi_rs`).
| Dengan menghapus cache ini, pengujian selalu memakai `absensi_rs_test`.
|
| Lapisan kedua ada di tests/TestCase.php (pengujian dihentikan bila koneksi
| ternyata bukan database test).
*/
$cachedConfig = __DIR__ . '/../bootstrap/cache/config.php';

if (is_file($cachedConfig)) {
    @unlink($cachedConfig);
}

require __DIR__ . '/../vendor/autoload.php';
