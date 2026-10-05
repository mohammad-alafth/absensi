<?php

namespace App\Http\Controllers\HRD;

use App\Http\Controllers\Controller;
use App\Services\FaceAuditLogger;
use App\Support\FaceSettings;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Antrean verifikasi wajah oleh HRD.
 *
 * Baris absen masuk daftar ini bila wajahnya tidak dipastikan:
 *  - `face_match` = false  (zona abu-abu atau tidak dikenali), atau
 *  - `face_match` NULL     (layanan wajah mati / belum terdaftar).
 *
 * Skor dan foto disimpan sebagai bukti keputusan. Foto sendiri dihapus
 * otomatis setelah masa retensi (lihat command `face:purge-photos`).
 */
class FaceReviewController extends Controller
{
    /** Antrean review (maksimal 100 baris terbaru). */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = Attendance::with('user')
            ->whereNotNull('face_verified_at')
            ->whereNull('face_reviewed_by');

        if ($status === 'pending') {
            $query->where(function ($q) {
                $q->where('face_match', false)->orWhereNull('face_match');
            });
        }

        $attendances = $query->orderByDesc('face_verified_at')->limit(100)->get();

        return view('hrd.face-review.index', [
            'attendances' => $attendances,
            'status' => $status,
        ]);
    }

    /** Setujui: wajah dianggap sesuai. */
    /** Setujui: wajah dianggap sesuai. */
    public function approve(Request $request, Attendance $attendance)
    {
        $attendance->update([
            'face_match' => true,
            'face_reviewed_by' => auth()->id(),
            'face_review_note' => $this->note($request),
        ]);

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::REVIEW_APPROVE,
            $attendance->user_id,
            'approved',
            ['attendance_id' => $attendance->id, 'score' => $attendance->face_score],
            $request
        );

        return back()->with('success', 'Absensi disetujui oleh HRD.');
    }

    /**
     * Tolak: tandai tidak sesuai lalu terapkan aturan FaceSettings
'     * `face.reject_action`:
     *  - keep : kehadiran tetap dihitung seperti semula (bawaan),
     *  - void : baris absen dibatalkan (jam masuk & keluar dikosongkan),
     *    sehingga dihitung tidak hadir oleh rekap.

     */
    public function reject(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'note' => 'required|string|max:255',
        ]);

        $attendance->update([
            'face_match' => false,
            'face_reviewed_by' => auth()->id(),
            'face_review_note' => $data['note'],
        ]);

        $voided = false;

        if (FaceSettings::str('face.reject_action') === 'void') {
            $attendance->update([
                'status' => 'alpa',
                'jam_masuk' => null,
                'jam_keluar' => null,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
            ]);

            $voided = true;
        }

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::REVIEW_REJECT,
            $attendance->user_id,
            'rejected',
            ['attendance_id' => $attendance->id, 'voided' => $voided, 'note' => $data['note']],
            $request
        );

        return back()->with(
            'success',
            $voided
                ? 'Absensi ditolak dan dihitung tidak hadir.'
                : 'Absensi ditolak oleh HRD.'
        );
    }

    private function note(Request $request): ?string
    {
        $note = trim((string) $request->input('note', ''));

        return $note === '' ? null : $note;
    }
}
