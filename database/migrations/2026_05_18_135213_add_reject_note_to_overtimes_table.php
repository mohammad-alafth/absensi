<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {

            $table->text('pj_note')
                ->nullable()
                ->after('pj_status');

            $table->text('hrd_note')
                ->nullable()
                ->after('hrd_status');
        });
    }

    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {

            $table->dropColumn([
                'pj_note',
                'hrd_note'
            ]);
        });
    }
};
