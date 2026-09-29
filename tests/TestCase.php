<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | PENGAMAN DATABASE PENGUJIAN
        |--------------------------------------------------------------------------
        | `RefreshDatabase` / `migrate:fresh` pada test dapat MENGHAPUS isi
        | database yang sedang dipakai. Karena itu pengujian hanya boleh berjalan
        | pada database khusus `absensi_rs_test`.
        |
        | Dua kondisi yang bisa membuat test menyasar database dev:
        |   1. `php artisan config:cache` (nilai phpunit.xml tidak dipakai lagi)
        |   2. variabel environment `DB_DATABASE` yang sudah ada di shell
        |      (phpunit.xml tidak menimpanya kecuali force="true")
        | Bila terdeteksi, pengujian dihentikan sebelum ada data yang terhapus.
        */
        $connection = (string) config('database.default');
        $database   = (string) config("database.connections.{$connection}.database");

        if ($database !== 'absensi_rs_test') {
            $this->fail(
                'Pengujian dihentikan untuk keamanan data: koneksi database menunjuk ke "'
                . $database . '" (bukan "absensi_rs_test"). Jalankan `php artisan config:clear` '
                . 'dan hapus variabel environment DB_DATABASE sebelum `php artisan test`.'
            );
        }
    }
}
