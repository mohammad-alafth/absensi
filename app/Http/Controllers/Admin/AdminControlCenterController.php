<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\FaceAuditLog;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Permission;
use App\Models\PermissionChangeLog;
use App\Models\User;
use App\Services\PermissionDecision;
use App\Services\PermissionResolver;
use App\Services\RoleMenuService;
use App\Support\FaceSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminControlCenterController extends Controller
{
    public function __construct(
        private readonly PermissionResolver $permissions,
        private readonly RoleMenuService $roleMenu,
    ) {
    }

    public function index()
    {
        abort_unless(config('admin_control_center.enabled', false), 404);

        $user = auth()->user();
        abort_unless($user !== null && $user->role === 'admin', 403);

        $permissionKey = (string) config(
            'admin_control_center.permission_key',
            'admin.control-center.view'
        );
        $decision = $this->permissions->resolve((string) $user->role, $permissionKey);
        abort_unless($decision->decision === PermissionDecision::GRANTED, 403);

        return view('admin.control-center.index', [
            'user' => $user,
            'permissionKey' => $permissionKey,
            'summary' => [
                'users' => User::query()->count(),
                'pendingUsers' => User::query()->where('is_approved', false)->count(),
                'attendanceToday' => Attendance::query()->whereDate('tanggal', today())->count(),
                'pendingSubmissions' => $this->pendingSubmissions(),
            ],
            'security' => [
                'faceEnabled' => FaceSettings::bool('face.enabled'),
                'facePolicyEnabled' => FaceSettings::bool('face.spoof_enabled'),
                'matchThreshold' => FaceSettings::float('face.match_threshold'),
                'grayThreshold' => FaceSettings::float('face.gray_threshold'),
            ],
            'activities' => $this->recentActivities(),
            'menus' => $this->adminMenus($user),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function adminMenus(User $user): array
    {
        $definitions = [
            [
                'label' => 'Dashboard Overview',
                'description' => 'Ringkasan kondisi, keamanan, dan aktivitas Admin Control Center.',
                'icon' => 'dashboard',
                'route' => 'admin.control-center',
            ],
            [
                'label' => 'Manajemen Hak Akses',
                'description' => 'Tinjau registry, mapping, status, riwayat, dan perubahan permission per role.',
                'icon' => 'admin_panel_settings',
                'route' => 'admin.access-management.index',
            ],
            [
                'label' => 'Face Recognition Settings',
                'description' => 'Kelola konfigurasi Face Recognition yang tersedia untuk Admin.',
                'icon' => 'face',
                'route' => 'admin.face-settings.index',
            ],
            [
                'label' => 'Approval Workflow',
                'description' => 'Kelola tahap dan pemetaan alur approval yang tersedia.',
                'icon' => 'account_tree',
                'route' => 'admin.approval-flows',
            ],
            [
                'label' => 'Face Access Logs',
                'description' => 'Tinjau audit aktivitas akses dan verifikasi Face Recognition.',
                'icon' => 'fact_check',
                'route' => 'admin.face-settings.audit',
            ],
        ];

        $visibleRoutes = array_column(
            $this->roleMenu->menu(RoleMenuService::ADMIN_CONTROL_CENTER, $user),
            'route'
        );

        $definitions = array_values(array_filter(
            $definitions,
            fn (array $menu): bool => in_array($menu['route'], $visibleRoutes, true)
        ));

        return array_map(function (array $menu): array {
            $available = Route::has($menu['route']);

            return $menu + [
                'available' => $available,
                'status' => $available ? 'Aktif' : 'Belum Diimplementasikan',
                'statusClass' => $available
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                    : 'bg-slate-100 text-slate-600 ring-slate-200',
                'href' => $available ? route($menu['route']) : null,
            ];
        }, $definitions);
    }

    private function pendingSubmissions(): int
    {
        return Leave::query()->where('status', 'pending')->count()
            + Permission::query()->where('status', 'pending')->count()
            + Overtime::query()->where('status', 'pending')->count();
    }

    /** @return Collection<int, array{type:string, action:string, result:string, at:mixed}> */
    private function recentActivities(): Collection
    {
        $permissionActivities = PermissionChangeLog::query()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (PermissionChangeLog $log): array => [
                'type' => 'Hak akses',
                'action' => (string) ($log->reason ?: 'Perubahan pemetaan'),
                'result' => (string) $log->target_role_key,
                'at' => $log->created_at,
            ]);

        $faceActivities = FaceAuditLog::query()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (FaceAuditLog $log): array => [
                'type' => 'Face audit',
                'action' => (string) $log->action,
                'result' => (string) $log->result,
                'at' => $log->created_at,
            ]);

        return $permissionActivities
            ->concat($faceActivities)
            ->sortByDesc('at')
            ->take(8)
            ->values();
    }
}
