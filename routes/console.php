<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| JADWAL PEMBERSIHAN (AGAR DATABASE TIDAK MEMBENGKAK)
|--------------------------------------------------------------------------
| Menjalankan: php artisan schedule:work (development) atau
| php artisan schedule:run setiap menit via Windows Task Scheduler.
| Tidak mengubah alur aplikasi, hanya membersihkan data teknis yang kadaluarsa.
*/
Schedule::command('queue:prune-failed --hours=168')->weekly();
Schedule::command('queue:prune-batches --hours=48')->daily();
Schedule::command('sanctum:prune-expired --hours=48')->daily();
Schedule::command('cache:prune-stale-tags')->hourly();

/*
|--------------------------------------------------------------------------
| PENGINGAT ABSEN LEMBUR
|--------------------------------------------------------------------------
| Setiap pagi daftar pengajuan lembur yang lewat jam rencananya tetapi tidak
| memiliki absen selesai, supaya PJ/HRD bisa mengoreksi sebelum rekap dibuat.
| Menjalankan: php artisan schedule:run (Laravel 12: tidak perlu cron tambahan
| bila schedule:work sudah berjalan).
*/
Schedule::command('lembur:ingatkan-absen')->dailyAt('07:00');

/*
| Hapus foto bukti absen sesuai retensi face recognition (default 60 hari).
|
*/
Schedule::command('face:housekeeping')->dailyAt('03:30');
