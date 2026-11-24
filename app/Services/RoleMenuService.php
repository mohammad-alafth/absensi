<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Server-side menu visibility.
 *
 * The catalog is static and only references exact permission keys from the
 * permission registry. Decisions come from PermissionResolver and the result
 * map is cached by role + the existing permission version.
 */
class RoleMenuService
{
    public const DESKTOP = 'desktop';
    public const MOBILE = 'mobile';
    public const BOTTOM = 'bottom';
    public const DASHBOARD_SERVICES = 'dashboard_services';
    public const DASHBOARD_PORTALS = 'dashboard_portals';
    public const HRD_GOVERNANCE = 'hrd_governance';
    public const HRD_QUEUES = 'hrd_queues';
    public const PJ_OPERATIONS = 'pj_operations';
    public const ADMIN_CONTROL_CENTER = 'admin_control_center';

    private const CACHE_PREFIX = 'role-menu-permissions';
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Menu nodes are keyed so that tree and flat catalogs can share one
     * definition. A node without a route is a group; a node with a route is a
     * leaf.
     *
     * @var array<string, array<string, mixed>>
     */
    private const NODES = [
        'dashboard' => [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'permission' => 'dashboard.view',
            'icon' => 'home',
        ],
        'attendance' => [
            'label' => 'Absen',
            'route' => 'face.scan',
            'permission' => 'attendance.view',
            'icon' => 'fingerprint',
        ],
        'history' => [
            'label' => 'Riwayat',
            'route' => 'history',
            'permissions' => [
                'attendance.view',
                'leave.view',
                'permission.view',
                'overtime.view',
                'shift_request.view',
            ],
            'icon' => 'history',
        ],
        'face_register' => [
            'label' => 'Register Wajah',
            'route' => 'face.register',
            'permission' => 'face.registration.view',
            'icon' => 'face_retouching_natural',
        ],
        'face_scan' => [
            'label' => 'Scan Wajah',
            'route' => 'face.scan',
            'permission' => 'attendance.view',
            'icon' => 'center_focus_strong',
        ],
        'leave_create' => [
            'label' => 'Pengajuan Cuti',
            'route' => 'cuti',
            'permission' => 'leave.view',
            'icon' => 'event_busy',
            'tone' => 'amber',
        ],
        'permission_create' => [
            'label' => 'Surat Izin',
            'route' => 'izin',
            'permission' => 'permission.view',
            'icon' => 'description',
            'tone' => 'violet',
        ],
        'overtime_create' => [
            'label' => 'Lembur (SPL)',
            'route' => 'lembur',
            'permission' => 'overtime.view',
            'icon' => 'more_time',
            'tone' => 'blue',
        ],
        'shift_change_create' => [
            'label' => 'Tukar Shift',
            'route' => 'shift-change.create',
            'permission' => 'shift_request.view',
            'icon' => 'swap_horiz',
            'work_types' => ['shift'],
        ],
        'admin_control' => [
            'label' => 'Admin Control Center',
            'route' => 'admin.control-center',
            'permission' => 'admin.control-center.view',
            'icon' => 'admin_panel_settings',
            'admin_only' => true,
            'feature_flag' => 'admin_control_center.enabled',
            'description' => 'Buka pusat kendali administrasi dan keamanan sistem.',
        ],
        'user_access_management' => [
            'label' => 'Manajemen Akun',
            'route' => 'hrd.users.approval',
            'permission' => 'user.access.manage',
            'icon' => 'manage_accounts',
            'description' => 'Terima akun baru, atur role, dan kelola password pengguna.',
        ],
        'access_management' => [
            'label' => 'Manajemen Hak Akses',
            'route' => 'admin.access-management.index',
            'permission' => 'admin.control-center.view',
            'icon' => 'admin_panel_settings',
            'admin_only' => true,
            'feature_flag' => 'admin_control_center.enabled',
        ],
        'approval_flows' => [
            'label' => 'Alur Approval',
            'route' => 'admin.approval-flows',
            'permission' => 'approval_config.view',
            'icon' => 'account_tree',
            'admin_only' => true,
        ],
        'face_settings' => [
            'label' => 'Pengaturan Wajah',
            'route' => 'admin.face-settings.index',
            'permission' => 'face_settings.view',
            'icon' => 'face',
            'admin_only' => true,
        ],
        'face_audit' => [
            'label' => 'Log Akses Wajah',
            'route' => 'admin.face-settings.audit',
            'permission' => 'face_audit.view',
            'icon' => 'fact_check',
            'admin_only' => true,
        ],
        'hrd_rekap' => [
            'label' => 'Rekap Absensi HRD',
            'route' => 'hrd.rekap',
            'permission' => 'report.view',
            'icon' => 'analytics',
            'roles' => ['hrd', 'director'],
            'description' => 'Monitoring laporan kehadiran yang tersedia.',
        ],
        'hrd_shifts' => [
            'label' => 'Master Shift',
            'route' => 'hrd.shifts.index',
            'permission' => 'shift.view',
            'icon' => 'calendar_month',
            'roles' => ['hrd', 'director'],
            'description' => 'Kelola master shift melalui modul HRD.',
        ],
        'hrd_cuti' => [
            'label' => 'Pending Cuti',
            'route' => 'hrd.cuti',
            'permission' => 'leave.view',
            'icon' => 'event_busy',
            'tone' => 'amber',
        ],
        'hrd_izin' => [
            'label' => 'Pending Izin',
            'route' => 'hrd.izin',
            'permission' => 'permission.view',
            'icon' => 'description',
            'tone' => 'violet',
        ],
        'hrd_lembur' => [
            'label' => 'Pending Lembur',
            'route' => 'hrd.lembur',
            'permission' => 'overtime.view',
            'icon' => 'more_time',
            'tone' => 'blue',
        ],
        'hrd_history' => [
            'label' => 'History Approval',
            'route' => 'hrd.dashboard',
            'permissions' => ['leave.view', 'permission.view', 'overtime.view'],
            'icon' => 'history',
        ],
        'pj_cuti' => [
            'label' => 'Approval Cuti',
            'route' => 'pj.cuti',
            'permission' => 'leave.view',
            'icon' => 'event_busy',
            'tone' => 'amber',
        ],
        'pj_izin' => [
            'label' => 'Approval Izin',
            'route' => 'pj.izin',
            'permission' => 'permission.view',
            'icon' => 'description',
            'tone' => 'violet',
        ],
        'pj_lembur' => [
            'label' => 'Approval Lembur',
            'route' => 'pj.lembur',
            'permission' => 'overtime.view',
            'icon' => 'more_time',
            'tone' => 'blue',
        ],
        'pj_shift_change' => [
            'label' => 'Approval Tukar Shift',
            'route' => 'pj.shift-change',
            'permission' => 'shift_request.view',
            'icon' => 'swap_horiz',
            'tone' => 'cyan',
        ],
        'pj_shift_management' => [
            'label' => 'Management Shift',
            'route' => 'shift.index',
            'permission' => 'shift.view',
            'icon' => 'calendar_month',
            'work_types' => ['shift'],
        ],
        'group_presence' => [
            'label' => 'Presensi & Riwayat',
            'children' => ['attendance', 'history'],
        ],
        'group_requests' => [
            'label' => 'Pengajuan',
            'children' => ['leave_create', 'permission_create', 'overtime_create', 'shift_change_create'],
        ],
        'group_face' => [
            'label' => 'Wajah',
            'children' => ['face_register', 'face_scan'],
        ],
        'group_admin' => [
            'label' => 'Administrasi',
            'children' => ['admin_control', 'access_management', 'approval_flows', 'face_settings', 'face_audit'],
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const CATALOGS = [
        self::DESKTOP => [
            'dashboard',
            'group_presence',
            'group_requests',
            'group_face',
            'group_admin',
        ],
        self::MOBILE => [
            'dashboard',
            'group_presence',
            'group_requests',
            'group_face',
            'group_admin',
        ],
        self::BOTTOM => [
            'dashboard',
            'attendance',
            'history',
        ],
        self::DASHBOARD_SERVICES => [
            'leave_create',
            'permission_create',
            'overtime_create',
            'shift_change_create',
        ],
        self::DASHBOARD_PORTALS => [
            'admin_control',
            'user_access_management',
            'hrd_rekap',
            'hrd_shifts',
            'pj_shift_management',
        ],
        self::HRD_GOVERNANCE => [
            'hrd_rekap',
            'hrd_shifts',
        ],
        self::HRD_QUEUES => [
            'hrd_cuti',
            'hrd_izin',
            'hrd_lembur',
            'hrd_history',
        ],
        self::PJ_OPERATIONS => [
            'pj_cuti',
            'pj_izin',
            'pj_lembur',
            'pj_shift_change',
        ],
        self::ADMIN_CONTROL_CENTER => [
            'admin_control',
            'access_management',
            'approval_flows',
            'face_settings',
            'face_audit',
        ],
    ];

    /**
     * Permission keys that can influence a menu. Keeping this list static means
     * the resolver is called once per key per role + version, not once per
     * rendered menu item on every request after the cache is warm.
     *
     * @var list<string>
     */
    private const PERMISSION_KEYS = [
        'dashboard.view',
        'attendance.view',
        'face.registration.view',
        'leave.view',
        'permission.view',
        'overtime.view',
        'shift_request.view',
        'shift.view',
        'report.view',
        'admin.control-center.view',
        'approval_config.view',
        'face_settings.view',
        'face_audit.view',
        'user.access.manage',
    ];

    public function __construct(
        private readonly PermissionResolver $permissions,
        private readonly PermissionVersionService $versions,
    ) {
    }

    /**
     * Return a filtered hierarchical menu. Groups with no visible child are
     * removed. Order follows the catalog order.
     *
     * @return list<array<string, mixed>>
     */
    public function menu(string $catalog, ?User $user = null): array
    {
        $role = $user?->role;
        $version = $this->currentVersion();

        if (!is_string($role) || $role === '' || $version === null) {
            return [];
        }

        $nodes = self::CATALOGS[$catalog] ?? [];
        $visiblePermissions = $this->visiblePermissions($role, $version);

        return $this->filterNodes($nodes, $role, $user, $visiblePermissions);
    }

    /**
     * Flatten a catalog to visible leaves. Useful for dashboard shortcut grids
     * and role-specific workstation menus.
     *
     * @return list<array<string, mixed>>
     */
    public function leaves(string $catalog, ?User $user = null): array
    {
        return $this->flatten($this->menu($catalog, $user));
    }

    /**
     * Exact any-of check for composite pages such as Riwayat and Portal
     * Approval. Every key is still an exact registry key.
     *
     * @param list<string> $permissionKeys
     */
    public function allowsAny(?User $user, array $permissionKeys): bool
    {
        $role = $user?->role;
        $version = $this->currentVersion();

        if (!is_string($role) || $role === '' || $version === null) {
            return false;
        }

        $visible = $this->visiblePermissions($role, $version);

        foreach ($permissionKeys as $permissionKey) {
            if (($visible[$permissionKey] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    public function allows(?User $user, string $permissionKey): bool
    {
        return $this->allowsAny($user, [$permissionKey]);
    }

    /**
     * @param list<string> $nodeKeys
     * @param array<string, bool> $visiblePermissions
     * @return list<array<string, mixed>>
     */
    private function filterNodes(
        array $nodeKeys,
        string $role,
        ?User $user,
        array $visiblePermissions,
    ): array {
        $result = [];

        foreach ($nodeKeys as $nodeKey) {
            $definition = self::NODES[$nodeKey] ?? null;
            if ($definition === null) {
                continue;
            }

            if (!$this->roleAndContextAllowed($definition, $role, $user)) {
                continue;
            }

            if (!$this->routeAllowed($definition)) {
                continue;
            }

            if (isset($definition['children'])) {
                $children = $this->filterNodes(
                    array_values($definition['children']),
                    $role,
                    $user,
                    $visiblePermissions,
                );

                if ($children === []) {
                    continue;
                }

                $result[] = array_replace($definition, [
                    'key' => $nodeKey,
                    'children' => $children,
                ]);

                continue;
            }

            if (!$this->leafAllowed($definition, $visiblePermissions)) {
                continue;
            }

            $result[] = array_replace($definition, ['key' => $nodeKey]);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, bool> $visiblePermissions
     */
    private function leafAllowed(array $definition, array $visiblePermissions): bool
    {
        $permissions = $definition['permissions'] ?? null;

        if (is_array($permissions) && $permissions !== []) {
            foreach ($permissions as $permissionKey) {
                if (($visiblePermissions[$permissionKey] ?? false) === true) {
                    return true;
                }
            }

            return false;
        }

        $permissionKey = $definition['permission'] ?? null;

        return is_string($permissionKey)
            && $permissionKey !== ''
            && ($visiblePermissions[$permissionKey] ?? false) === true;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function roleAndContextAllowed(array $definition, string $role, ?User $user): bool
    {
        if (($definition['admin_only'] ?? false) === true && $role !== 'admin') {
            return false;
        }

        $roles = $definition['roles'] ?? null;
        if (is_array($roles) && $roles !== [] && !in_array($role, $roles, true)) {
            return false;
        }

        $workTypes = $definition['work_types'] ?? null;
        if (is_array($workTypes) && $workTypes !== []
            && !in_array((string) ($user?->work_type ?? ''), $workTypes, true)) {
            return false;
        }

        $featureFlag = $definition['feature_flag'] ?? null;
        if (is_string($featureFlag) && $featureFlag !== ''
            && !(bool) config($featureFlag, false)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function routeAllowed(array $definition): bool
    {
        $route = $definition['route'] ?? null;

        return !is_string($route) || $route === '' || Route::has($route);
    }

    /**
     * @return array<string, bool>
     */
    private function visiblePermissions(string $role, int $version): array
    {
        $cacheKey = implode(':', [
            self::CACHE_PREFIX,
            $version,
            hash('sha256', $role),
        ]);

        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $this->normalizePermissionMap($cached);
            }
        } catch (Throwable) {
            // Cache failure is fail-closed for menu visibility; resolve now.
        }

        $visible = [];

        foreach (self::PERMISSION_KEYS as $permissionKey) {
            try {
                $visible[$permissionKey] = $this->permissions
                    ->resolve($role, $permissionKey)
                    ->granted();
            } catch (Throwable) {
                // A resolver failure must never reveal a protected menu.
                $visible[$permissionKey] = false;
            }
        }

        try {
            Cache::put($cacheKey, $visible, self::CACHE_TTL_SECONDS);
        } catch (Throwable) {
            // A cache write failure must not turn into an implicit grant.
        }

        return $visible;
    }

    /**
     * @param array<mixed> $value
     * @return array<string, bool>
     */
    private function normalizePermissionMap(array $value): array
    {
        $normalized = [];

        foreach (self::PERMISSION_KEYS as $permissionKey) {
            $normalized[$permissionKey] = ($value[$permissionKey] ?? false) === true;
        }

        return $normalized;
    }

    private function currentVersion(): ?int
    {
        try {
            return $this->versions->current();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param list<array<string, mixed>> $nodes
     * @return list<array<string, mixed>>
     */
    private function flatten(array $nodes): array
    {
        $leaves = [];

        foreach ($nodes as $node) {
            if (isset($node['children']) && is_array($node['children'])) {
                foreach ($this->flatten(array_values($node['children'])) as $leaf) {
                    $leaves[] = $leaf;
                }

                continue;
            }

            $leaves[] = $node;
        }

        return $leaves;
    }
}
