<?php

namespace Database\Seeders;

use App\Services\ApprovalFlowDefaults;
use Illuminate\Database\Seeder;

/**
 * Mengisi konfigurasi alur approval dari aturan bawaan aplikasi.
 *
 * Jalankan: php artisan db:seed --class=ApprovalFlowSeeder
 *
 * Aman dijalankan berkali-kali (idempotent): alur, langkah, dan pemetaan role
 * dibangun ulang mengikuti konstanta ApprovalFlowService, sehingga hasilnya
 * sama persis dengan perilaku sistem sebelum konfigurasi dipindahkan ke database.
 */
class ApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        $result = ApprovalFlowDefaults::sync();

        $this->command?->info(
            "Alur approval siap: {$result['stages']} tahap, {$result['flows']} alur, {$result['mappings']} pemetaan role."
        );
    }
}