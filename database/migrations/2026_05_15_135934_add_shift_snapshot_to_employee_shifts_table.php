<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_shifts', function (Blueprint $table) {

            $table->time('start_time')->nullable();

            $table->time('end_time')->nullable();

            $table->boolean('is_overnight')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('employee_shifts', function (Blueprint $table) {

            $table->dropColumn([
                'start_time',
                'end_time',
                'is_overnight'
            ]);
        });
    }
};
