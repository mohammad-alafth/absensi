<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan enum baru dulu
        DB::statement("
            ALTER TABLE users
            MODIFY work_type
            ENUM(
                'pelayanan',
                'office',
                'shift',
                'office_5',
                'office_6'
            )
            DEFAULT 'office_5'
        ");

        // 2. Convert data lama
        DB::table('users')
            ->whereNotIn('work_type', [
                'shift',
                'office_5',
                'office_6'
            ])
            ->update([
                'work_type' => 'office_5'
            ]);

        // 3. Hapus enum lama
        DB::statement("
            ALTER TABLE users
            MODIFY work_type
            ENUM(
                'shift',
                'office_5',
                'office_6'
            )
            DEFAULT 'office_5'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY work_type
            ENUM('pelayanan', 'office')
            DEFAULT 'office'
        ");
    }
};
    