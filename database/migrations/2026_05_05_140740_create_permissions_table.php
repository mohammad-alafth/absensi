<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('tanggal');

            $table->enum('jenis', [
                'izin pribadi',
                'sakit',
                'keperluan keluarga',
                'terlambat masuk',
                'pulang lebih awal',
                'dinas luar'
            ]);

            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();

            $table->text('alasan');

            $table->string('lampiran')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
