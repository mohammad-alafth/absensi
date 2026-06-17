<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tables = ['permissions', 'leaves', 'overtimes'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Status
                $table->string('head_status')->default('pending')->nullable();
                $table->string('director_status')->default('pending')->nullable();
                
                // Note
                $table->text('head_note')->nullable();
                $table->text('director_note')->nullable();
                
                // Signature
                $table->text('head_signature')->nullable();
                $table->text('director_signature')->nullable();
                
                // Approved By
                $table->unsignedBigInteger('head_approved_by')->nullable();
                $table->unsignedBigInteger('director_approved_by')->nullable();
                
                // Timestamp
                $table->timestamp('head_approved_at')->nullable();
                $table->timestamp('director_approved_at')->nullable();
            });
        }
    }

    public function down()
    {
        $tables = ['permissions', 'leaves', 'overtimes'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn([
                    'head_status', 'director_status',
                    'head_note', 'director_note',
                    'head_signature', 'director_signature',
                    'head_approved_by', 'director_approved_by',
                    'head_approved_at', 'director_approved_at'
                ]);
            });
        }
    }
};