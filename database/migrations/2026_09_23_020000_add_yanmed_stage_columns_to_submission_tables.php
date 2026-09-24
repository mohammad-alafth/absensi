<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom stage 'medical_service' (penunjang medis) belum tersedia di MySQL karena
 * file migrasi restructure_approval_stage_columns dijalankan dalam versi
 * lama (sebelum blok yanmed_* ditambahkan). Migrasi ini melengkapi kolom
 * yanmed_* pada leaves, permissions & overtimes secara idempoten.
 */
return new class extends Migration
{
    private const TABLES = ['leaves', 'permissions', 'overtimes'];

    private const SUFFIXES = ['_status', '_signature', '_note', '_approved_by', '_approved_at'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            foreach (self::SUFFIXES as $suffix) {
                $column = 'medical_service' . $suffix;

                if (Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($column, $suffix) {
                    match ($suffix) {
                        '_status'       => $table->string($column)->default('pending'),
                        '_signature'    => $table->longText($column)->nullable(),
                        '_note'         => $table->text($column)->nullable(),
                        '_approved_by'  => $table->unsignedBigInteger($column)->nullable(),
                        '_approved_at'  => $table->timestamp($column)->nullable(),
                    };
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            $columns = [];

            foreach (self::SUFFIXES as $suffix) {
                if (Schema::hasColumn($tableName, 'medical_service' . $suffix)) {
                    $columns[] = 'medical_service' . $suffix;
                }
            }

            if ($columns !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
