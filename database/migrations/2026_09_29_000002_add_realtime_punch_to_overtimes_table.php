<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| KOLOM ABSEN LEMBUR REALTIME PADA TABEL LEMBUR
|--------------------------------------------------------------------------
| Kebijakan: JAM NYATA dari absen realtime menjadi dasar volume jam lembur
| yang disahkan, sedangkan jam yang ditulis di form SPL menjadi rencana.
|
| - planned_hours : snapshot jam rencana dari form (tidak pernah berubah)
| - total_hours   : tetap "volume sah" yang dipakai surat/rekap, tetapi
|                   isinya diperbarui otomatis dari absen bila bukti ada
| - actual_*      : waktu nyata hasil absen (timestamp, aman lintas
|                   tengah malam karena overtimes.start_time bertipe time)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {

            $table->unsignedTinyInteger('planned_hours')
                ->nullable()
                ->after('total_hours');

            $table->timestamp('actual_start_at')
                ->nullable()
                ->after('planned_hours');

            $table->timestamp('actual_end_at')
                ->nullable()
                ->after('actual_start_at');

            $table->unsignedSmallInteger('actual_minutes')
                ->nullable()
                ->after('actual_end_at');

            $table->unsignedTinyInteger('actual_hours')
                ->nullable()
                ->after('actual_minutes');

            $table->enum('proof_type', [
                'manual',
                'realtime',
                'dari_absen',
                'koreksi',
            ])->default('manual')->after('actual_hours');

            $table->string('proof_note')->nullable()->after('proof_type');

            $table->foreignId('proof_corrected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('proof_note');

            $table->boolean('needs_review')->default(false)->after('proof_corrected_by');
        });

        /*
        |--------------------------------------------------------------------------
        | BACKFILL: DATA LAMA BERLAKU SEBAGAI RENCANA SEKALIGUS VOLUME
        |--------------------------------------------------------------------------
        */
        DB::table('overtimes')
            ->whereNull('planned_hours')
            ->update(['planned_hours' => DB::raw('total_hours')]);
    }

    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {

            $table->dropForeign(['proof_corrected_by']);

            $table->dropColumn([
                'planned_hours',
                'actual_start_at',
                'actual_end_at',
                'actual_minutes',
                'actual_hours',
                'proof_type',
                'proof_note',
                'proof_corrected_by',
                'needs_review',
            ]);
        });
    }
};
