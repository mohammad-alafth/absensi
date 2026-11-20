<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Re-run the idempotent P2.1 completion after registry seeding for
        // databases that had already recorded the earlier migration.
        $completion = require database_path('migrations/2026_11_05_000001_complete_p2_1_role_permission_matrix.php');
        $completion->up();
    }

    public function down(): void
    {
        // Intentionally irreversible; mappings and audit history are retained.
    }
};
