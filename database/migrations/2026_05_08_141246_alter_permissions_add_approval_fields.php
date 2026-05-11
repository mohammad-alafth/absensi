<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {


            // HRD
            $table->string('hrd_status')
                ->default('pending');

            $table->unsignedBigInteger('hrd_approved_by')
                ->nullable();

            $table->timestamp('hrd_approved_at')
                ->nullable();

            $table->text('hrd_note')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {

            $table->dropColumn([

                'pj_status',
                'pj_approved_by',
                'pj_approved_at',
                'pj_note',

                'hrd_status',
                'hrd_approved_by',
                'hrd_approved_at',
                'hrd_note',

            ]);
        });
    }
};
