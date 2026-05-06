<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {

            $table->string('recipient')->nullable()->after('user_id');
            $table->date('return_date')->nullable()->after('end_date');

            $table->string('delegate_name')->nullable()->after('reason');
            $table->string('delegate_nik')->nullable()->after('delegate_name');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {

            $table->dropColumn([
                'recipient',
                'return_date',
                'delegate_name',
                'delegate_nik'
            ]);
        });
    }
};
