<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('leaves', function ($table) {

        $table->longText('pj_signature')->nullable();

        $table->longText('hrd_signature')->nullable();

    });
}

public function down()
{
    Schema::table('leaves', function ($table) {

        $table->dropColumn([
            'pj_signature',
            'hrd_signature'
        ]);

    });
}
};
