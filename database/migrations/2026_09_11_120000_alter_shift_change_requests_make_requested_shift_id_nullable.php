<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memastikan kolom requested_shift_id pada shift_change_requests boleh NULL.
 *
 * NULL dipakai untuk pengajuan "Hari Libur / Tidak Ada Shift (Jadwal Kosong)":
 * karyawan mengajukan agar jadwal shift pada tanggal tersebut dihapus.
 *
 * Migration ini idempotent: bila kolom sudah nullable (mis. DB yang pernah
 * menjalankan alter versi lama), migration akan dilewati tanpa error.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->isRequestedShiftNullable()) {
            return;
        }

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

    /**
     * Cek apakah kolom requested_shift_id sudah nullable.
     */
    private function isRequestedShiftNullable(): bool
    {
        $column = collect(Schema::getColumns('shift_change_requests'))
            ->firstWhere('name', 'requested_shift_id');

        return $column !== null && (bool) $column['nullable'];
    }
};
