<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {

            /*
            |----------------------------------------------------------------------
            | PJ (kolom pj_* wajib ada untuk alur approval PJ -> HRD).
            |----------------------------------------------------------------------
            */
            if (!Schema::hasColumn('permissions', 'pj_status')) {
                $table->string('pj_status')
                    ->default('pending')
                    ->after('status');
            }

            if (!Schema::hasColumn('permissions', 'pj_approved_by')) {
                $table->unsignedBigInteger('pj_approved_by')
                    ->nullable()
                    ->after('status');
            }

            if (!Schema::hasColumn('permissions', 'pj_approved_at')) {
                $table->timestamp('pj_approved_at')
                    ->nullable()
                    ->after('status');
            }

            if (!Schema::hasColumn('permissions', 'pj_note')) {
                $table->text('pj_note')
                    ->nullable()
                    ->after('status');
            }

            // HRD
            if (!Schema::hasColumn('permissions', 'hrd_status')) {
                $table->string('hrd_status')
                    ->default('pending')
                    ->after('status');
            }

            if (!Schema::hasColumn('permissions', 'hrd_approved_by')) {
                $table->unsignedBigInteger('hrd_approved_by')
                    ->nullable()
                    ->after('status');
            }

            if (!Schema::hasColumn('permissions', 'hrd_approved_at')) {
                $table->timestamp('hrd_approved_at')
                    ->nullable()
                    ->after('status');
            }

            if (!Schema::hasColumn('permissions', 'hrd_note')) {
                $table->text('hrd_note')
                    ->nullable()
                    ->after('status');
            }
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
