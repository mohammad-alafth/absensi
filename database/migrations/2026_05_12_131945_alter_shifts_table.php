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
        Schema::table('shifts', function (Blueprint $table) {

            $table->integer('work_hours')
                ->default(8);

            $table->integer('grace_minutes')
                ->default(15);

            $table->enum('category', [
                'pagi',
                'siang',
                'middle',
                'malam'
            ])->nullable();

            $table->boolean('is_overnight')
                ->default(false);
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
