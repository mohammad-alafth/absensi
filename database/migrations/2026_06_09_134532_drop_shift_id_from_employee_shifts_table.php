<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employee_shifts', function (Blueprint $table) {
            // Jika ada foreign key, hapus dulu constraint-nya
            $table->dropForeign(['shift_id']);
            
            // Hapus kolom
            $table->dropColumn('shift_id');
        });
    }

    public function down()
    {
        Schema::table('employee_shifts', function (Blueprint $table) {
            // Jika suatu saat ingin dikembalikan (rollback)
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->foreign('shift_id')->references('id')->on('shifts');
        });
    }
};