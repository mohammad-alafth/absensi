<?php

use App\Services\ApprovalStageSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The original stage migration skipped columns when approval_stages
        // was empty. Runtime fallback still supports kabag_nurse, so repair
        // the schema independently and idempotently.
        ApprovalStageSchema::ensureStageColumns('kabag_nurse');
    }

    public function down(): void
    {
        // Intentionally non-destructive; approval history columns are retained.
    }
};
