<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan indeks untuk mempercepat query rekap HRD dan halaman lain
     * yang memfilter berdasarkan rentang tanggal.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('tanggal');
            $table->index(['user_id', 'tanggal']);
        });

        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->index('shift_date');
            $table->index(['user_id', 'shift_date']);
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
            $table->dropIndex(['user_id', 'tanggal']);
        });

        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->dropIndex(['shift_date']);
            $table->dropIndex(['user_id', 'shift_date']);
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex(['start_date', 'end_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['tanggal']);
        });
    }
};
