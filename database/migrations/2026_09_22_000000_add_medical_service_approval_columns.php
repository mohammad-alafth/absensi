<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom approval Medical Service.
     *
     * Alur approval berjenjang: PIPP -> waiting_medical_service ->
     * (disetujui Medical Service) -> waiting_hrd -> approved.
     * Kolom medical_service_* dipakai untuk menyimpan jejak approval
     * oleh role medical_service (status, TTD, approver, waktu).
     */
    public function up(): void
    {
        foreach (['leaves', 'permissions', 'overtimes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {

                if (!Schema::hasColumn($tableName, 'medical_service_status')) {
                    $table->string('medical_service_status')
                        ->default('pending')
                        ->after('status');
                }

                if (!Schema::hasColumn($tableName, 'medical_service_signature')) {
                    $table->longText('medical_service_signature')
                        ->nullable()
                        ->after('medical_service_status');
                }

                if (!Schema::hasColumn($tableName, 'medical_service_approved_by')) {
                    $table->unsignedBigInteger('medical_service_approved_by')
                        ->nullable()
                        ->after('medical_service_signature');
                }

                if (!Schema::hasColumn($tableName, 'medical_service_approved_at')) {
                    $table->timestamp('medical_service_approved_at')
                        ->nullable()
                        ->after('medical_service_approved_by');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['leaves', 'permissions', 'overtimes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {

                if (Schema::hasColumn($tableName, 'medical_service_status')) {
                    $table->dropColumn([
                        'medical_service_status',
                        'medical_service_signature',
                        'medical_service_approved_by',
                        'medical_service_approved_at',
                    ]);
                }
            });
        }
    }
};
