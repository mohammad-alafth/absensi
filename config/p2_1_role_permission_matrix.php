<?php

return [
    'employee' => [
        'dashboard.view',
        'attendance.view', 'attendance.create',
        'face.registration.view', 'face.registration.create', 'face.registration.delete', 'face.consent.manage',
        'leave.view', 'leave.create', 'leave.update', 'leave.pdf',
        'permission.view', 'permission.create', 'permission.update', 'permission.pdf',
        'overtime.view', 'overtime.create', 'overtime.update', 'overtime.punch', 'overtime.pdf',
        'shift_request.view', 'shift_request.create',
    ],
    'approval' => [
        'leave.view', 'leave.approve', 'leave.reject',
        'permission.view', 'permission.approve', 'permission.reject',
        'overtime.view', 'overtime.approve', 'overtime.reject',
    ],
    'self_service_roles' => [
        'administrasi', 'creator', 'cs', 'finance', 'ipsrs', 'it',
        'karyawan', 'kasir', 'marketing', 'medical_record', 'nurse',
        'nurse_ok', 'nutrition', 'pharmacist', 'pipp', 'ro', 'security',
        'staff', 'supervisor',
    ],
    'pj_roles' => [
        'pj_administrasi', 'pj_casemix', 'pj_cs', 'pj_finance',
        'pj_nurse', 'pj_pharmacist', 'pj_ro', 'pj_security',
        'pj_admission', 'pj_it', 'pj_ipsrs',
    ],
    'approver_roles' => [
        'medical_service', 'kabag_nurse', 'kabag_umum',
        'manager_umum', 'kabag_marketing', 'manager_finance',
    ],
];
