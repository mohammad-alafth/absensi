<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionChangeLog;
use App\Models\PermissionLegacyException;
use App\Models\PermissionModule;
use App\Models\PermissionRegistry;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\RolePermissionChangeService;
use App\Services\PermissionDecision;
use App\Services\PermissionResolver;
use App\Services\PermissionVersionConflictException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class PermissionMatrixController extends Controller
{
    public function __construct(
        private readonly PermissionResolver $permissions,
        private readonly \App\Services\ProtectedRoutePolicy $protectedRoutePolicy,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless(config('admin_control_center.enabled', false), 404);

        $admin = auth()->user();
        abort_unless($admin !== null && $admin->role === 'admin', 403);

        $parentKey = (string) config(
            'admin_control_center.permission_key',
            'admin.control-center.view'
        );
        $query = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $roles = collect();
        $roleStats = collect();
        $summary = ['roles' => 0, 'modules' => 0, 'permissions' => 0, 'configured' => 0, 'unmapped' => 0];
        $readError = false;

        try {
            $decision = $this->permissions->resolve((string) $admin->role, $parentKey);
            abort_unless($decision->decision === PermissionDecision::GRANTED, 403);

            $roles = User::query()
                ->select('role')
                ->selectRaw('COUNT(*) as users_count')
                ->whereNotNull('role')
                ->groupBy('role')
                ->orderBy('role')
                ->get();

            $mappingStats = RolePermission::query()
                ->join('permission_registry', 'permission_registry.id', '=', 'role_permissions.permission_id')
                ->join('permission_modules', 'permission_modules.id', '=', 'permission_registry.module_id')
                ->where('permission_registry.is_active', true)
                ->where('permission_modules.is_active', true)
                ->select('role_permissions.role_key')
                ->selectRaw('COUNT(*) as mapping_count')
                ->selectRaw('SUM(CASE WHEN role_permissions.granted = 1 THEN 1 ELSE 0 END) as granted_count')
                ->groupBy('role_permissions.role_key')
                ->get()
                ->keyBy('role_key');

            $viewStats = RolePermission::query()
                ->join('permission_registry', 'permission_registry.id', '=', 'role_permissions.permission_id')
                ->join('permission_modules', 'permission_modules.id', '=', 'permission_registry.module_id')
                ->where('permission_registry.is_active', true)
                ->where('permission_modules.is_active', true)
                ->where('permission_registry.category', 'view')
                ->where('role_permissions.granted', true)
                ->select('role_permissions.role_key')
                ->selectRaw('COUNT(DISTINCT permission_modules.id) as view_module_count')
                ->groupBy('role_permissions.role_key')
                ->get()
                ->keyBy('role_key');

            $roleStats = $roles->mapWithKeys(function ($role) use ($mappingStats, $viewStats): array {
                $mapping = $mappingStats->get($role->role);
                $view = $viewStats->get($role->role);
                $isProtected = $role->role === 'admin';
                $isConfigured = $isProtected || $mapping !== null;

                return [$role->role => [
                    'active_permission_count' => (int) ($mapping?->granted_count ?? 0),
                    'module_count' => (int) ($view?->view_module_count ?? 0),
                    'status' => $isProtected ? 'protected' : ($isConfigured ? 'configured' : 'unmapped'),
                ]];
            });

            $summary = [
                'roles' => $roles->count(),
                'modules' => PermissionModule::query()->where('is_active', true)->count(),
                'permissions' => PermissionRegistry::query()
                    ->where('is_active', true)
                    ->whereHas('module', fn ($module) => $module->where('is_active', true))
                    ->count(),
                'configured' => $roleStats->filter(fn (array $stat) => $stat['status'] !== 'unmapped')->count(),
                'unmapped' => $roleStats->filter(fn (array $stat) => $stat['status'] === 'unmapped')->count(),
            ];

            $roles = $roles
                ->filter(function ($role) use ($query, $statusFilter, $roleStats): bool {
                    $matchesQuery = $query === '' || str_contains(mb_strtolower((string) $role->role), mb_strtolower($query));
                    $matchesStatus = $statusFilter === '' || ($roleStats->get($role->role)['status'] ?? null) === $statusFilter;

                    return $matchesQuery && $matchesStatus;
                })
                ->values();
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $readError = true;
            Log::error('Permission matrix data read failed.', [
                'exception' => $exception::class,
                'route' => 'admin.access-management.index',
            ]);
        }

        return view('admin.access-management.index', [
            'roles' => $roles,
            'query' => $query,
            'statusFilter' => $statusFilter,
            'roleStats' => $roleStats,
            'summary' => $summary,
            'readError' => $readError,
        ]);
    }

    public function editor(string $role)
    {
        abort_unless(config('admin_control_center.enabled', false), 404);

        $admin = auth()->user();
        abort_unless($admin !== null && $admin->role === 'admin', 403);

        $parentKey = (string) config(
            'admin_control_center.permission_key',
            'admin.control-center.view'
        );
        $decision = $this->permissions->resolve((string) $admin->role, $parentKey);
        abort_unless($decision->decision === PermissionDecision::GRANTED, 403);

        $target = User::query()->where('role', $role)->first();
        abort_unless($target !== null, 404);

        $modules = PermissionModule::query()
            ->where('is_active', true)
            ->with(['permissions' => function ($permissions) use ($role): void {
                $permissions
                    ->where('is_active', true)
                    ->with(['rolePermissions' => fn ($mappings) => $mappings->where('role_key', $role)])
                    ->orderBy('key');
            }])
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get();

        $legacyCounts = PermissionLegacyException::query()
            ->where('is_active', true)
            ->select('owner_module')
            ->selectRaw('COUNT(*) as exception_count')
            ->groupBy('owner_module')
            ->pluck('exception_count', 'owner_module');

        // Target admin may be edited, but system-protected capabilities remain
        // locked individually by the registry policy.
        $isProtectedRole = false;
        $editorModules = $modules
            ->map(function (PermissionModule $module) use ($isProtectedRole, $legacyCounts): array {
                $permissions = $module->permissions->map(function (PermissionRegistry $permission) use ($module, $isProtectedRole, $legacyCounts): array {
                    $mapping = $permission->rolePermissions->first();
                    $actionKey = $this->permissionActionKey($permission);

                    $classification = $this->permissionClassification($permission->key, (string) $permission->category);

                    return [
                        'id' => $permission->id,
                        'key' => $permission->key,
                        'label' => $permission->label,
                        'description' => $permission->description,
                        'category' => (string) $permission->category,
                        'action_key' => $actionKey,
                        'action_label' => $this->actionLabel($permission),
                        'baseline' => (bool) $mapping?->granted,
                        'mapped' => $mapping !== null,
                        'active' => (bool) $permission->is_active && (bool) $module->is_active,
                        'classification' => $classification,
                        'system_protected' => $classification === 'system-protected',
                        'restricted' => $classification === 'restricted',
                        'protected_reason' => $this->classificationReason($classification),
                        'legacy_count' => (int) ($legacyCounts[$module->key] ?? 0),
                    ];
                })->values();

                return [
                    'key' => $module->key,
                    'label' => $module->label,
                    'description' => $module->description,
                    'permissions' => $permissions,
                    'legacy_count' => (int) ($legacyCounts[$module->key] ?? 0),
                ];
            })
            ->filter(fn (array $module): bool => $module['permissions']->isNotEmpty())
            ->values();

        $actions = $editorModules
            ->flatMap(fn (array $module) => $module['permissions']->pluck('action_key'))
            ->unique()
            ->sortBy(function (string $action): int {
                $position = array_search($action, ['view', 'create', 'update', 'delete', 'export', 'approve', 'reject', 'pdf'], true);

                return $position === false ? 99 : $position;
            })
            ->values();

        $activePermissionCount = $editorModules->sum(fn (array $module): int => $module['permissions']->count());
        $grantedCount = $editorModules->sum(fn (array $module): int => $module['permissions']->where('baseline', true)->count());
        $viewModuleCount = $editorModules->filter(fn (array $module): bool => $module['permissions']->contains(fn (array $permission): bool => $permission['category'] === 'view' && $permission['baseline']))->count();

        return view('admin.access-management.editor', [
            'role' => $role,
            'targetUserCount' => User::query()->where('role', $role)->count(),
            'isProtectedRole' => $isProtectedRole,
            'modules' => $editorModules,
            'actions' => $actions,
            'activePermissionCount' => $activePermissionCount,
            'grantedCount' => $grantedCount,
            'viewModuleCount' => $viewModuleCount,
            'permissionVersion' => app(\App\Services\PermissionVersionService::class)->current(),
        ]);
    }

    /** Persist the editor's sparse desired state through the atomic batch engine. */
    public function saveBatch(Request $request, string $role, RolePermissionChangeService $changes): JsonResponse
    {
        $admin = $request->user();
        if (!config('admin_control_center.enabled', false)) {
            return response()->json(['message' => 'Admin Control Center tidak aktif.'], 404);
        }

        $parentKey = (string) config('admin_control_center.permission_key', 'admin.control-center.view');
        $decision = $admin === null
            ? null
            : $this->permissions->resolve((string) $admin->role, $parentKey);
        if ($admin === null || $admin->role !== 'admin' || $decision?->decision !== PermissionDecision::GRANTED) {
            return response()->json(['message' => 'Anda tidak berwenang mengubah permission.'], 403);
        }

        $validated = $request->validate([
            'target_role' => ['required', 'string', 'max:255'],
            'desired_permissions' => ['nullable', 'array'],
            'expected_version' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $desiredPermissions = $validated['desired_permissions'] ?? [];
        if ($role !== (string) $validated['target_role']) {
            return response()->json([
                'message' => 'Target role tidak cocok dengan editor yang dibuka.',
                'errors' => ['target_role' => ['Target role harus sama dengan role pada URL editor.']],
            ], 422);
        }
        foreach ($desiredPermissions as $permissionKey => $desired) {
            if (!is_string($permissionKey) || $permissionKey === '' || !is_bool($desired)) {
                return response()->json([
                    'message' => 'Payload permission harus berupa map key registry ke nilai boolean.',
                    'errors' => ['desired_permissions' => ['Setiap permission key harus memiliki nilai boolean.']],
                ], 422);
            }
        }

        try {
            $result = $changes->changeMany(
                $admin,
                (string) $validated['target_role'],
                $desiredPermissions,
                (int) $validated['expected_version'],
                (string) $validated['reason'],
                (string) \Illuminate\Support\Str::uuid(),
            );
        } catch (PermissionVersionConflictException $exception) {
            return response()->json([
                'message' => 'Data permission telah berubah. Muat ulang data sebelum menyimpan lagi.',
                'conflict' => true,
                'expected_version' => $exception->expectedVersion,
                'actual_version' => $exception->actualVersion,
            ], 409);
        } catch (AuthorizationException) {
            return response()->json(['message' => 'Perubahan permission tidak diizinkan.'], 403);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return response()->json([
                'message' => 'Data permission tidak valid.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Perubahan tidak dapat disimpan dan telah dibatalkan.'], 500);
        }

        return response()->json([
            'message' => $result->changedCount() === 0
                ? 'Tidak ada perubahan permission untuk disimpan.'
                : sprintf('%d perubahan permission berhasil disimpan.', $result->changedCount()),
            'role' => $result->roleKey,
            'version' => $result->version,
            'previous_version' => $result->previousVersion,
            'changed_count' => $result->changedCount(),
            'changes' => $result->changes,
        ]);
    }

    public function update(Request $request, RolePermissionChangeService $changes): RedirectResponse
    {
        $admin = auth()->user();
        $parentKey = (string) config(
            'admin_control_center.permission_key',
            'admin.control-center.view'
        );
        $decision = $this->permissions->resolve((string) $admin?->role, $parentKey);
        abort_unless($admin !== null
            && $admin->role === 'admin'
            && $decision->decision === PermissionDecision::GRANTED, 403);

        $validated = $request->validate([
            'role' => ['required', 'string'],
            'module' => ['required', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $targetRole = (string) $validated['role'];
        if ($targetRole === 'admin') {
            throw new AuthorizationException('The protected admin role cannot be changed through the permission matrix.');
        }

        $module = PermissionModule::query()
            ->where('key', $validated['module'])
            ->where('is_active', true)
            ->first();
        if ($module === null) {
            return back()->withInput()->withErrors(['module' => 'Pilih modul aktif yang terdaftar.']);
        }

        $permissions = $module->permissions()
            ->where('is_active', true)
            ->orderBy('key')
            ->get();
        $selectedKeys = array_values(array_unique($validated['permissions'] ?? []));
        $allowedKeys = $permissions->pluck('key')->all();
        if (array_diff($selectedKeys, $allowedKeys) !== []) {
            return back()->withInput()->withErrors(['permissions' => 'Permission harus berasal dari modul aktif yang dipilih.']);
        }

        if (!User::query()->where('role', $targetRole)->exists()) {
            return back()->withInput()->withErrors(['role' => 'Role target tidak terdaftar pada pengguna.']);
        }

        $mappings = RolePermission::query()
            ->where('role_key', $targetRole)
            ->whereIn('permission_id', $permissions->pluck('id'))
            ->get()
            ->keyBy('permission_id');
        $changesToApply = $permissions->filter(function (PermissionRegistry $permission) use ($mappings, $selectedKeys): bool {
            $current = $mappings->get($permission->id)?->granted;
            $desired = in_array($permission->key, $selectedKeys, true);

            return $current === null || (bool) $current !== $desired;
        });

        if ($changesToApply->isEmpty()) {
            return back()->with('status', 'Tidak ada perubahan akses untuk disimpan.');
        }

        $correlationId = (string) Str::uuid();
        try {
            DB::transaction(function () use ($changesToApply, $selectedKeys, $targetRole, $admin, $validated, $changes, $correlationId): void {
                foreach ($changesToApply as $permission) {
                    $changes->change(
                        $admin,
                        $targetRole,
                        $permission->key,
                        in_array($permission->key, $selectedKeys, true),
                        (string) $validated['reason'],
                        $correlationId,
                    );
                }
            }, 3);
        } catch (AuthorizationException|\Illuminate\Validation\ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['permissions' => 'Perubahan tidak dapat disimpan dan telah dibatalkan.']);
        }

        return back()->with('status', sprintf('%d perubahan akses berhasil disimpan.', $changesToApply->count()));
    }

    private function actionLabel(PermissionRegistry $permission): string
    {
        return match (true) {
            str_ends_with($permission->key, '.create') => 'Create',
            str_ends_with($permission->key, '.update') => 'Update',
            str_ends_with($permission->key, '.delete') => 'Delete',
            str_ends_with($permission->key, '.export') => 'Export',
            str_ends_with($permission->key, '.approve') => 'Approve',
            str_ends_with($permission->key, '.reject') => 'Reject',
            str_ends_with($permission->key, '.pdf') => 'PDF',
            $permission->category === 'view' => 'View',
            default => ucfirst((string) $permission->category),
        };
    }

    private function permissionClassification(string $permissionKey, string $category): string
    {
        if ($this->protectedRoutePolicy->isSystemProtectedCapability($permissionKey)) {
            return 'system-protected';
        }

        if (str_starts_with($permissionKey, 'approval_config.')
            && $permissionKey !== 'approval_config.view') {
            return 'restricted';
        }

        $action = strtolower((string) str($permissionKey)->afterLast('.'));

        return in_array($action, ['approve', 'reject', 'export', 'pdf'], true)
            || $category === 'special'
            ? 'restricted'
            : 'admin-manageable';
    }

    private function classificationReason(string $classification): string
    {
        return match ($classification) {
            'system-protected' => 'Invariant keamanan sistem. Tidak dapat diubah melalui matrix role.',
            'restricted' => 'Capability sensitif. Perubahan hanya untuk Admin dan selalu dicatat di audit log.',
            default => 'Capability operasional yang dapat dikelola Admin sesuai kewenangan.',
        };
    }

    private function permissionActionKey(PermissionRegistry $permission): string
    {
        $suffix = strtolower((string) str($permission->key)->afterLast('.'));

        return in_array($suffix, ['view', 'create', 'update', 'delete', 'export', 'approve', 'reject', 'pdf'], true)
            ? $suffix
            : strtolower((string) $permission->category);
    }
}
