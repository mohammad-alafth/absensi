<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('version')->unique();
            $table->longText('snapshot');
            $table->string('snapshot_hash', 64)->unique();
            $table->unsignedBigInteger('changed_by')->nullable()->index();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('approval_workflow_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_version_id')->nullable()->constrained('approval_workflow_versions')->nullOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('action', 60);
            $table->string('entity_type', 60);
            $table->string('entity_id', 100)->nullable();
            $table->longText('before_json')->nullable();
            $table->longText('after_json')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });

        foreach (['leaves', 'permissions', 'overtimes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('approval_workflow_version_id')->nullable()->index();
                $table->longText('approval_workflow_snapshot')->nullable();
                $table->string('approval_workflow_snapshot_hash', 64)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['leaves', 'permissions', 'overtimes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex([$tableName . '_approval_workflow_version_id_index']);
                $table->dropIndex([$tableName . '_approval_workflow_snapshot_hash_index']);
                $table->dropColumn(['approval_workflow_version_id', 'approval_workflow_snapshot', 'approval_workflow_snapshot_hash']);
            });
        }

        Schema::dropIfExists('approval_workflow_audits');
        Schema::dropIfExists('approval_workflow_versions');
    }
};
