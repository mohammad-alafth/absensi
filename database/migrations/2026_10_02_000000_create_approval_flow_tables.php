<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi alur approval (tree) dari database.
 *
 * Sebelumnya seluruh alur approval ditulis di dalam kode
 * (ApprovalFlowService::APPROVER_STAGES / GROUP_CHAINS / TOP_LEVEL_ROLES),
 * sehingga menambah atau mengubah tahap harus lewat developer.
 *
 * Tabel ini memindahkan konfigurasi ke database supaya role `admin` bisa
 * melakukan pengaturan lewat UI:
 *
 *  - approval_stages      : katalog tahap (key, label, role approver, aktif).
 *  - approval_flows       : daftar alur per jenis pengajuan (cuti/izin/lembur).
 *  - approval_flow_steps  : node tree di dalam flow (parent_id = node sebelumnya,
 *                           urut = pre-order, approval berjalan berurutan).
 *  - approval_role_flows  : pemetaan role pengaju -> flow + pengecualian PJ.
 *
 * Konvensi kolom tetap sama seperti sebelumnya (status/signature/note/
 * approved_by/approved_at per tahap), sehingga stage baru cukup dengan
 * menambah kolom `{key}_*` di tabel pengajuan (dilakukan otomatis oleh
 * App\Services\ApprovalStageSchema saat admin menyimpan stage baru).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_stages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('label', 120);
            $table->string('role', 60)->index();
            $table->boolean('is_pj')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('submission_type', 20)->index();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['submission_type', 'name']);
        });

        Schema::create('approval_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')
                ->constrained('approval_flows')
                ->cascadeOnDelete();
            $table->string('stage_key', 60)->index();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['approval_flow_id', 'sort_order']);
        });

        // Parent menunjuk node lain di flow yang sama (self reference).
        Schema::table('approval_flow_steps', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('approval_flow_steps')
                ->nullOnDelete();
        });

        Schema::create('approval_role_flows', function (Blueprint $table) {
            $table->id();
            $table->string('role', 60);
            $table->string('submission_type', 20)->default('*');
            $table->foreignId('approval_flow_id')
                ->nullable()
                ->constrained('approval_flows')
                ->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['role', 'submission_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_role_flows');
        Schema::dropIfExists('approval_flow_steps');
        Schema::dropIfExists('approval_flows');
        Schema::dropIfExists('approval_stages');
    }
};