<?php

namespace App\Http\Middleware;

use App\Services\ApprovalFlowService;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $userRole = auth()->user()->role;

        foreach ($roles as $role) {

            // APROVER DINAMIS: daftar role approver diambil dari katalog tahap
            // (konfigurasi admin bila ada, konstanta bawaan bila tidak).
            // Dengan begitu role tahap baru bisa langsung buka pusat approval
            // tanpa menambah daftar role di routes/web.php.
            if ($role === 'approver') {
                if (ApprovalFlowService::stageForApproverRole($userRole) !== null) {
                    return $next($request);
                }
            }

            // PJ GROUP
            if ($role === 'pj') {
                if (str_starts_with($userRole, 'pj_')) {
                    return $next($request);
                }
            }

            // HRD
            if ($role === 'hrd') {
                if ($userRole === 'hrd') {
                    return $next($request);
                }
            }

            // exact match fallback
            if ($userRole === $role) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak');
    }
}
