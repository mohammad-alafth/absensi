<?php

namespace Database\Seeders;

use App\Models\PermissionLegacyException;
use App\Models\PermissionModule;
use App\Models\PermissionRegistry;
use Illuminate\Database\Seeder;

/**
 * Registry definition only.
 *
 * Seeder ini sengaja TIDAK membuat role_permissions. Role baru tetap
 * Belum Dipetakan dan tidak menerima grant otomatis.
 */
class PermissionRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'sort_order' => 10],
            ['key' => 'attendance', 'label' => 'Presensi', 'sort_order' => 20],
            ['key' => 'face_registration', 'label' => 'Registrasi Wajah', 'sort_order' => 30],
            ['key' => 'leave', 'label' => 'Cuti', 'sort_order' => 40],
            ['key' => 'permission', 'label' => 'Izin', 'sort_order' => 50],
            ['key' => 'overtime', 'label' => 'Lembur', 'sort_order' => 60],
            ['key' => 'shift_request', 'label' => 'Tukar Shift', 'sort_order' => 70],
            ['key' => 'shift', 'label' => 'Shift', 'sort_order' => 80],
            ['key' => 'roster', 'label' => 'Roster', 'sort_order' => 90],
            ['key' => 'user_admin', 'label' => 'Administrasi Pengguna', 'sort_order' => 100],
            ['key' => 'reports', 'label' => 'Laporan dan Kalender', 'sort_order' => 110],
            ['key' => 'face_review', 'label' => 'Review Wajah', 'sort_order' => 120],
            ['key' => 'approval_config', 'label' => 'Konfigurasi Approval', 'sort_order' => 130],
            ['key' => 'face_settings', 'label' => 'Pengaturan Wajah', 'sort_order' => 140],
        ];

        $moduleModels = [];
        foreach ($modules as $module) {
            $moduleModels[$module['key']] = PermissionModule::query()->firstOrCreate(
                ['key' => $module['key']],
                [
                    'label' => $module['label'],
                    'sort_order' => $module['sort_order'],
                ]
            );
        }

        $permissions = [
            'dashboard' => [
                ['key' => 'dashboard.view', 'label' => 'Lihat Dashboard', 'category' => 'view'],
            ],
            'attendance' => [
                ['key' => 'attendance.view', 'label' => 'Lihat Presensi', 'category' => 'view'],
                ['key' => 'attendance.create', 'label' => 'Mencatat Presensi', 'category' => 'action'],
            ],
            'face_registration' => [
                ['key' => 'face.registration.view', 'label' => 'Lihat Registrasi Wajah', 'category' => 'view'],
                ['key' => 'face.registration.create', 'label' => 'Registrasi Wajah', 'category' => 'action'],
                ['key' => 'face.registration.delete', 'label' => 'Hapus Registrasi Wajah', 'category' => 'action'],
                ['key' => 'face.consent.manage', 'label' => 'Kelola Consent Wajah', 'category' => 'special'],
            ],
            'leave' => [
                ['key' => 'leave.view', 'label' => 'Lihat Cuti', 'category' => 'view'],
                ['key' => 'leave.create', 'label' => 'Buat Cuti', 'category' => 'action'],
                ['key' => 'leave.update', 'label' => 'Ubah Cuti', 'category' => 'action'],
                ['key' => 'leave.approve', 'label' => 'Setujui Cuti', 'category' => 'action'],
                ['key' => 'leave.reject', 'label' => 'Tolak Cuti', 'category' => 'action'],
                ['key' => 'leave.pdf', 'label' => 'Unduh PDF Cuti', 'category' => 'pdf'],
                ['key' => 'leave.export', 'label' => 'Export Cuti', 'category' => 'export'],
            ],
            'permission' => [
                ['key' => 'permission.view', 'label' => 'Lihat Izin', 'category' => 'view'],
                ['key' => 'permission.create', 'label' => 'Buat Izin', 'category' => 'action'],
                ['key' => 'permission.update', 'label' => 'Ubah Izin', 'category' => 'action'],
                ['key' => 'permission.approve', 'label' => 'Setujui Izin', 'category' => 'action'],
                ['key' => 'permission.reject', 'label' => 'Tolak Izin', 'category' => 'action'],
                ['key' => 'permission.pdf', 'label' => 'Unduh PDF Izin', 'category' => 'pdf'],
                ['key' => 'permission.export', 'label' => 'Export Izin', 'category' => 'export'],
            ],
            'overtime' => [
                ['key' => 'overtime.view', 'label' => 'Lihat Lembur', 'category' => 'view'],
                ['key' => 'overtime.create', 'label' => 'Buat Lembur', 'category' => 'action'],
                ['key' => 'overtime.update', 'label' => 'Ubah Lembur', 'category' => 'action'],
                ['key' => 'overtime.approve', 'label' => 'Setujui Lembur', 'category' => 'action'],
                ['key' => 'overtime.reject', 'label' => 'Tolak Lembur', 'category' => 'action'],
                ['key' => 'overtime.punch', 'label' => 'Catat Bukti Absen Lembur', 'category' => 'special'],
                ['key' => 'overtime.pdf', 'label' => 'Unduh PDF Lembur', 'category' => 'pdf'],
                ['key' => 'overtime.export', 'label' => 'Export Lembur', 'category' => 'export'],
            ],
            'shift_request' => [
                ['key' => 'shift_request.view', 'label' => 'Lihat Tukar Shift', 'category' => 'view'],
                ['key' => 'shift_request.create', 'label' => 'Buat Tukar Shift', 'category' => 'action'],
                ['key' => 'shift_request.approve', 'label' => 'Setujui Tukar Shift', 'category' => 'action'],
                ['key' => 'shift_request.reject', 'label' => 'Tolak Tukar Shift', 'category' => 'action'],
            ],
            'shift' => [
                ['key' => 'shift.view', 'label' => 'Lihat Shift', 'category' => 'view'],
                ['key' => 'shift.assign', 'label' => 'Atur Shift', 'category' => 'action'],
                ['key' => 'shift.export', 'label' => 'Export Shift', 'category' => 'export'],
            ],
            'roster' => [
                ['key' => 'roster.view', 'label' => 'Lihat Roster', 'category' => 'view'],
                ['key' => 'roster.update', 'label' => 'Ubah Roster', 'category' => 'action'],
            ],
            'user_admin' => [
                ['key' => 'admin.control-center.view', 'label' => 'Lihat Admin Control Center', 'category' => 'view'],
                ['key' => 'user.access.manage', 'label' => 'Kelola Penerimaan dan Akses Akun', 'category' => 'action'],
                ['key' => 'user.approval', 'label' => 'Setujui Akun', 'category' => 'action'],
                ['key' => 'user.password.reset', 'label' => 'Reset Password Pengguna', 'category' => 'action'],
                ['key' => 'user.quota.update', 'label' => 'Ubah Kuota Cuti', 'category' => 'action'],
            ],
            'reports' => [
                ['key' => 'report.view', 'label' => 'Lihat Laporan', 'category' => 'view'],
                ['key' => 'report.export', 'label' => 'Export Laporan', 'category' => 'export'],
                ['key' => 'calendar.view', 'label' => 'Lihat Kalender', 'category' => 'view'],
                ['key' => 'calendar.export', 'label' => 'Export Kalender', 'category' => 'export'],
            ],
            'face_review' => [
                ['key' => 'face.review.view', 'label' => 'Lihat Review Wajah', 'category' => 'view'],
                ['key' => 'face.review.approve', 'label' => 'Setujui Review Wajah', 'category' => 'action'],
                ['key' => 'face.review.reject', 'label' => 'Tolak Review Wajah', 'category' => 'action'],
            ],
            'approval_config' => [
                ['key' => 'approval_config.view', 'label' => 'Lihat Konfigurasi Approval', 'category' => 'view'],
                ['key' => 'approval_config.create', 'label' => 'Buat Konfigurasi Approval', 'category' => 'action'],
                ['key' => 'approval_config.update', 'label' => 'Ubah Konfigurasi Approval', 'category' => 'action'],
                ['key' => 'approval_config.delete', 'label' => 'Hapus Konfigurasi Approval', 'category' => 'action'],
                ['key' => 'approval_config.activate', 'label' => 'Aktifkan Konfigurasi Approval', 'category' => 'action'],
            ],
            'face_settings' => [
                ['key' => 'face_settings.view', 'label' => 'Lihat Pengaturan Wajah', 'category' => 'view'],
                ['key' => 'face_settings.update', 'label' => 'Ubah Pengaturan Wajah', 'category' => 'action'],
                ['key' => 'face_settings.reset', 'label' => 'Reset Pengaturan Wajah', 'category' => 'action'],
                ['key' => 'face_service.test', 'label' => 'Test Face Service', 'category' => 'special'],
                ['key' => 'face_service.control', 'label' => 'Kontrol Face Service', 'category' => 'special'],
                ['key' => 'face_audit.view', 'label' => 'Lihat Audit Wajah', 'category' => 'view'],
            ],
        ];

        foreach ($permissions as $moduleKey => $items) {
            foreach ($items as $item) {
                PermissionRegistry::query()->firstOrCreate(
                    ['key' => $item['key']],
                    [
                        'module_id' => $moduleModels[$moduleKey]->id,
                        'label' => $item['label'],
                        'category' => $item['category'],
                    ]
                );
            }
        }

        $exceptions = [
            ['http_method' => 'POST', 'uri_pattern' => 'api/attendance', 'route_name' => null, 'controller_action' => 'AttendanceController@store', 'reason' => 'API absensi existing belum permission-aware.', 'existing_guard' => 'auth:sanctum + FacePolicy/service', 'risk' => 'Perubahan global dapat memutus kontrak API absensi.', 'migration_status' => 'active', 'owner_module' => 'attendance'],
            ['http_method' => 'POST', 'uri_pattern' => 'api/leave', 'route_name' => null, 'controller_action' => 'LeaveController@store', 'reason' => 'API pengajuan existing belum memakai capability terpisah.', 'existing_guard' => 'auth:sanctum', 'risk' => 'View/create belum dipisah.', 'migration_status' => 'active', 'owner_module' => 'leave'],
            ['http_method' => 'POST', 'uri_pattern' => 'api/device/face/register', 'route_name' => null, 'controller_action' => 'FaceController@register', 'reason' => 'Kontrak registrasi face device existing.', 'existing_guard' => 'auth + throttle', 'risk' => 'Biometric endpoint sensitif.', 'migration_status' => 'active', 'owner_module' => 'face'],
            ['http_method' => 'POST', 'uri_pattern' => 'api/device/face/scan', 'route_name' => null, 'controller_action' => 'FaceController@matchFace', 'reason' => 'Kontrak scan device existing.', 'existing_guard' => 'auth + throttle', 'risk' => 'Fail-open/degraded face behavior.', 'migration_status' => 'active', 'owner_module' => 'face'],
            ['http_method' => 'POST', 'uri_pattern' => 'api/device/bind', 'route_name' => null, 'controller_action' => 'DeviceController@bind', 'reason' => 'Device binding existing.', 'existing_guard' => 'auth + throttle', 'risk' => 'Ownership target harus diuji.', 'migration_status' => 'active', 'owner_module' => 'device'],
            ['http_method' => 'POST', 'uri_pattern' => 'shift/assign', 'route_name' => 'shift.assign', 'controller_action' => 'ShiftController@assign', 'reason' => 'Compatibility sampai PJ-only enforcement selesai.', 'existing_guard' => 'auth + verified', 'risk' => 'Role, unit, allowed_roles, dan IDOR belum lengkap.', 'migration_status' => 'blocked', 'owner_module' => 'shift'],
            ['http_method' => 'POST', 'uri_pattern' => 'shift/bulk-assign', 'route_name' => 'shift.bulk-assign', 'controller_action' => 'ShiftController@bulkAssign', 'reason' => 'Compatibility sampai PJ-only enforcement selesai.', 'existing_guard' => 'auth + verified', 'risk' => 'Bulk target scope belum lengkap.', 'migration_status' => 'blocked', 'owner_module' => 'shift'],
            ['http_method' => 'POST', 'uri_pattern' => 'shift/weekly-assign', 'route_name' => 'shift.weekly-assign', 'controller_action' => 'ShiftController@weeklyAssign', 'reason' => 'Compatibility sampai PJ-only enforcement selesai.', 'existing_guard' => 'auth + verified', 'risk' => 'Weekly target scope belum lengkap.', 'migration_status' => 'blocked', 'owner_module' => 'shift'],
        ];

        foreach ($exceptions as $exception) {
            PermissionLegacyException::query()->firstOrCreate(
                ['http_method' => $exception['http_method'], 'uri_pattern' => $exception['uri_pattern']],
                $exception
            );
        }

        // Tidak ada role_permissions yang dibuat di B2.
    }
}
