<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OvertimeController::store() menulis kolom `department` dan `day_type`,
     * tetapi kedua kolom tersebut belum pernah ada di tabel overtimes sehingga
     * pengajuan lembur selalu gagal ("Unknown column"). Migration ini
     * melengkapinya.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('overtimes', 'department')) {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->string('department')->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('overtimes', 'day_type')) {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->string('day_type')->nullable()->after('overtime_date');
            });
        }
    }

    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $columns = [];

            foreach (['department', 'day_type'] as $column) {
                if (Schema::hasColumn('overtimes', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
