<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * requested_shift_id diizinkan NULL = pengajuan libur / tidak ada shift (jadwal kosong).
     */
    public function up(): void
    {
        Schema::table('shift_change_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_shift_id']);
            $table->foreignId('requested_shift_id')->nullable()->change();
            $table->foreign('requested_shift_id')->references('id')->on('shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_change_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_shift_id']);
            $table->foreignId('requested_shift_id')->nullable(false)->change();
            $table->foreign('requested_shift_id')->references('id')->on('shifts')->onDelete('cascade');
        });
    }
};
