<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // pj_ipsrs is an existing PJ role in ApprovalFlowService and its
        // controller scope; reuse the idempotent P2.1 completion only for
        // this evidenced role.
        $completion = require database_path('migrations/2026_11_05_000001_complete_p2_1_role_permission_matrix.php');
        $completion->up();
    }

    public function down(): void
    {
        // Intentionally irreversible; mapping and audit history are retained.
    }
};
