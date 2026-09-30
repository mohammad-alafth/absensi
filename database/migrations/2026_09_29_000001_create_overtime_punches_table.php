<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ABSEN LEMBUR REALTIME (BUKTI KEHADIRAN)
|--------------------------------------------------------------------------
| Tabel log append-only berisi setiap kali karyawan menekan "Mulai Lembur"
| atau "Selesai Lembur". Waktu yang disimpan SELALU waktu server (punched_at)
| supaya tidak bisa dimanipulasi dari perangkat klien, dilengkapi koordinat
| GPS dan foto selfie sebagai bukti.
|
| Baris bersifat permanen (tidak pernah di-update) agar bisa diaudit; jam
| turunan untuk surat/rekap disimpan di kolom overtimes.actual_*.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_punches', function (Blueprint $table) {

            $table->id();

            $table->foreignId('overtime_id')
                ->constrained('overtimes')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            /*
            |----------------------------------------------------------------------
            | JENIS & WAKTU ABSEN
            |----------------------------------------------------------------------
            | start   = absen mulai lembur
            | end     = absen selesai lembur
            | koreksi = input manual PJ/HRD saat karyawan lupa absen
            */
            $table->enum('type', [
                'start',
                'end',
                'koreksi',
            ]);

            $table->timestamp('punched_at');

            /*
            |----------------------------------------------------------------------
            | BUKTI LOKASI & FOTO
            |----------------------------------------------------------------------
            */
            $table->decimal('latitude', 10, 7)->nullable();

            $table->decimal('longitude', 10, 7)->nullable();

            $table->decimal('accuracy', 8, 2)->nullable();

            $table->decimal('distance_m', 8, 2)->nullable();

            $table->string('photo')->nullable();

            /*
            |----------------------------------------------------------------------
            | SUMBER ABSEN
            |----------------------------------------------------------------------
            */
            $table->enum('source', [
                'web',
                'face_device',
                'koreksi_pj',
            ])->default('web');

            $table->text('note')->nullable();

            $table->string('ip_address', 45)->nullable();

            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'punched_at']);

            $table->index(['overtime_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_punches');
    }
};
