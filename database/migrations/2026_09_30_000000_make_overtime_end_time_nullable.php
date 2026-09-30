<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| JAM SELESAI LEMBUR JADI OPSIONAL (MODE "SAMPAI SELESAI")
|--------------------------------------------------------------------------
| Lembur pada hari libur tidak lagi perlu memperkirakan jam selesai: karyawan
| hanya mengisi JAM MULAI, pengajuan sekaligus mencatat absen mulai realtime,
| dan jam selesai ditentukan oleh absen pulang.
|
| end_time = NULL berarti "sampai selesai" (volume jam mengikuti jam nyata dari
| absen). Pengajuan lama tetap punya jam selesai seperti sebelumnya.
*/

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('overtimes', 'end_time')) {
            return;
        }

        Schema::table('overtimes', function (Blueprint $table) {
            $table->time('end_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('overtimes', 'end_time')) {
            return;
        }

        // Baris "sampai selesai" tidak bisa dikembalikan ke jam tetap; diisi
        // 00:00 agar kolomnya bisa dibuat wajib (NOT NULL) lagi seperti dulu.
        DB::table('overtimes')->whereNull('end_time')->update(['end_time' => '00:00:00']);

        Schema::table('overtimes', function (Blueprint $table) {
            $table->time('end_time')->nullable(false)->change();
        });
    }
};
