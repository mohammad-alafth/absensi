<?php

namespace App\Services;

class ApprovalFlowService
{
    public static function handle($userRole)
    {
        $base = [
            'status' => 'pending',
            'pj_status' => 'pending',
            'hrd_status' => 'pending',
        ];
        /**
         * SKIP PJ ROLE
         */
        $skipPjRoles = [
            'hrd',
            'head_pegawai',
            'director',
            'it',
            'marketing',
            'konten_creator',
        ];

        if (in_array($userRole, $skipPjRoles)) {
            return [
                'status' => 'waiting_head',
                'pj_status' => 'approved',
                'hrd_status' => 'pending',
            ];
        }

        /**
         * HEAD PEGAWAI
         */
        if ($userRole === 'head_pegawai') {
            return [
                'status' => 'waiting_director',
                'pj_status' => 'approved',
                'hrd_status' => 'pending',
            ];
        }

        /**
         * PIPP
         */
        if ($userRole === 'pipp') {
            return [
                'status' => 'waiting_medical_service',
                'pj_status' => 'approved',
                'hrd_status' => 'pending',
            ];
        }

        /**
         * SEMUA PJ ROLE (termasuk pj_casemix)
         */
        if (str_starts_with($userRole, 'pj_')) {
            return [
                'status' => 'waiting_hrd',
                'pj_status' => 'approved',
                'hrd_status' => 'pending',
            ];
        }

        return $base;
    }
}
