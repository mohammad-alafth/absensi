<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {

            // cek dulu sebelum add column
            if (!Schema::hasColumn('leaves', 'pj_status')) {
                $table->string('pj_status')->default('pending');
            }

            if (!Schema::hasColumn('leaves', 'pj_note')) {
                $table->text('pj_note')->nullable();
            }

            if (!Schema::hasColumn('leaves', 'pj_approved_by')) {
                $table->unsignedBigInteger('pj_approved_by')->nullable();
            }

            if (!Schema::hasColumn('leaves', 'pj_approved_at')) {
                $table->timestamp('pj_approved_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {

            $table->dropColumn([
                'pj_status',
                'pj_note',
                'pj_approved_by',
                'pj_approved_at'
            ]);
        });
    }
};
