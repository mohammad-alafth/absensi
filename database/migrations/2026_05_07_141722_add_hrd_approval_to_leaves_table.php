<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {

            $table->unsignedBigInteger('hrd_approved_by')
                ->nullable()
                ->after('approved_at');

            $table->timestamp('hrd_approved_at')
                ->nullable()
                ->after('hrd_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {

            $table->dropColumn([
                'hrd_approved_by',
                'hrd_approved_at'
            ]);
        });
    }
};
