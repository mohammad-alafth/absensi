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
