<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persiapan untuk absensi berbasis face recognition (real face).
 *
 * 1. users      : vektor wajah (terenkripsi di level aplikasi lewat accessor),
 *                  status registrasi, jumlah sampel, ambang khusus per orang.
 * 2. attendances: bukti hasil verifikasi pada setiap punch (foto, skor, hasil,
 *                  metode, waktu verifikasi, serta jejak review HRD).
 * 3. attendance_settings: semua parameter wajah & absensi (threshold, policy
 *                  per role, retensi, endpoint service) supaya dapat diubah
 *                  HR/admin tanpa mengubah source code.
 *
 * Catatan: kolom `users.face_descriptor` yang lama (berisi path foto selfie)
 * dibiarkan apa adanya dan tidak dipakai sebagai vektor wajah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->longText('face_embedding')->nullable()->after('face_descriptor');
            $table->string('face_status', 20)->default('none')->after('face_embedding');
            $table->timestamp('face_enrolled_at')->nullable()->after('face_status');
            $table->unsignedTinyInteger('face_samples')->default(0)->after('face_enrolled_at');
            $table->decimal('face_threshold', 4, 3)->nullable()->after('face_samples');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('face_image')->nullable()->after('status');
            $table->decimal('face_score', 4, 3)->nullable()->after('face_image');
            $table->boolean('face_match')->nullable()->after('face_score');
            $table->string('face_method', 24)->nullable()->after('face_match');
            $table->timestamp('face_verified_at')->nullable()->after('face_method');
            $table->unsignedBigInteger('face_reviewed_by')->nullable()->after('face_verified_at');
            $table->text('face_review_note')->nullable()->after('face_reviewed_by');

            $table->index(['face_match'], 'attendances_face_match_index');
            $table->index(['face_verified_at'], 'attendances_face_verified_at_index');
        });

        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->string('skey', 60)->primary();
            $table->text('svalue')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_face_match_index');
            $table->dropIndex('attendances_face_verified_at_index');

            $table->dropColumn([
                'face_image',
                'face_score',
                'face_match',
                'face_method',
                'face_verified_at',
                'face_reviewed_by',
                'face_review_note',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'face_embedding',
                'face_status',
                'face_enrolled_at',
                'face_samples',
                'face_threshold',
            ]);
        });
    }
};