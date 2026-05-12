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
        Schema::table('attendances', function (Blueprint $table) {

            $table->foreignId('shift_id')
                ->nullable();

            $table->integer('late_minutes')
                ->default(0);

            $table->integer('overtime_minutes')
                ->default(0);

            $table->timestamp('scheduled_checkin')
                ->nullable();

            $table->timestamp('scheduled_checkout')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
