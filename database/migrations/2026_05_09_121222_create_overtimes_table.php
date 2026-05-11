<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtimes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            /*
            |--------------------------------------------------------------------------
            | DATA LEMBUR
            |--------------------------------------------------------------------------
            */
            $table->date('overtime_date');

            $table->time('start_time');

            $table->time('end_time');

            $table->integer('total_hours')
                ->default(0);

            $table->text('reason');

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */
            $table->enum('status', [
                'pending',
                'waiting_hrd',
                'approved',
                'rejected'
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | PJ
            |--------------------------------------------------------------------------
            */
            $table->enum('pj_status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->foreignId('pj_approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('pj_approved_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | HRD
            |--------------------------------------------------------------------------
            */
            $table->enum('hrd_status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->foreignId('hrd_approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('hrd_approved_at')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
