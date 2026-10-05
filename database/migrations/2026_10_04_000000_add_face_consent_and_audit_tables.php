<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consent & audit untuk biometrik wajah.
 *
 * 1. face_consents : bukti persetujuan (bukan sekadar kolom boolean). Menyimpan
 *    versi-members informed notice, hash dokumen, waktu, cara persetujuan
 *    (ketik nama / tanda tangan digital / unggah bukti), serta bukti pendukung
 *    (IP, user agent, tanda tangan berupa gambar).
 *
 * 2. face_audit_logs : jejak akses & pemrosesan data biometrik
 *    (registrasi, verifikasi, spoof check, review HRD, perubahan pengaturan).
 *    Hanya dapat dilihat role `admin` dan dihapus otomatis sesuai masa simpan.
 *
 * CATATAN KEPATUHAN: tabel ini hanya MEMBUKTIKAN bahwa informasi telah diberikan
 * dan persetujuan dicatat. Kepatuhan terhadap UU PDP tetap bergantung pada
 * keseluruhan proses dan DASAR PEMROSESAN yang dipilih organisasi (persetujuan
 * hanyalah salah satu dasar yang diakui UU PDP). Keputusan dasar pemrosesan
 * tetap milik pihak legal/compliance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notice_version', 40)->index();
            $table->string('notice_hash', 64)->comment('SHA-256 teks informasi yang ditampilkan');
            $table->string('method', 30)->comment('typed_name | drawn_signature | uploaded_document');
            $table->text('evidence')->nullable()->comment('Tanda tangan (base64) atau path berkas bukti');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at'], 'face_consents_user_active_index');
        });

        Schema::create('face_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor_role', 40)->nullable()->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('action', 40)->index();
            $table->string('result', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_audit_logs');
        Schema::dropIfExists('face_consents');
    }
};