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
        Schema::create('shift_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('shift_date');
            $table->foreignId('current_shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            // requested_shift_id boleh NULL = karyawan mengajukan LIBUR / tidak ada shift
            // (jadwal kosong) pada tanggal tersebut, bukan pindah ke shift lain.
            $table->foreignId('requested_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('pj_note')->nullable();
            $table->unsignedBigInteger('pj_approved_by')->nullable();
            $table->timestamp('pj_approved_at')->nullable();
            $table->timestamps();

            $table->foreign('pj_approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_change_requests');
    }
};
