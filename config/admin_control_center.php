<?php

return [
    // The admin dashboard is opt-in for local/testing environments.
    'enabled' => (bool) env('ADMIN_CONTROL_CENTER_ENABLED', false),
    'permission_key' => 'admin.control-center.view',

    // Invariants that must never be changed through ordinary role mapping.
    // Route metadata still protects access; it is not itself a reason to
    // disable every operational checkbox in the access matrix.
    'system_protected_permissions' => [
        'admin.control-center.view',
        'approval_config.create',
        'approval_config.update',
        'approval_config.delete',
        'approval_config.activate',
        'face_settings.view',
        'face_settings.update',
        'face_settings.reset',
        'face_service.test',
        'face_service.control',
        'face_audit.view',
    ],
];
