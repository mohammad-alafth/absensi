<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaceAuditLog;
use App\Support\FaceSettings;
use Illuminate\Http\Request;

/**
 * Jejak audit data biometrik (keputusan B3).
 *
 * Hanya dapat diakses role `admin` (middleware `role:admin`).
 * Baris audit dihapus otomatis sesuai `face.audit_retention_days` (bawaan 60 hari
 * = 2 bulan) oleh command `face:purge-audit`.
 *
 * CATATAN: log ini membantu membuktikan PROSES, bukan otomatis membuktikan
 * kepatuhan. Kepatuhan tetap bergantung pada keseluruhan proses dan dasar
 * pemrosesan yang dipilih organisasi.
 */
class FaceAuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = FaceAuditLog::with(['actor:id,name,role', 'subject:id,name'])
            ->when($request->query('action'), fn ($query, $action) => $query->where('action', $action))
            ->when($request->query('user_id'), fn ($query, $id) => $query->where('user_id', $id))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('admin.face-settings.audit', [
            'logs' => $logs,
            'retentionDays' => FaceSettings::int('face.audit_retention_days'),
            'actions' => [
                'face.enroll',
                'face.verify',
                'face.spoof_check',
                'face.review.approve',
                'face.review.reject',
                'face.consent',
                'face.consent.revoke',
                'face.settings.update',
            ],
        ]);
    }
}