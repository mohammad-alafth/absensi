<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\FaceAuditLog;
use App\Services\FaceVerificationService;
use App\Services\TelegramNotifier;
use App\Support\FacePolicy;
use App\Support\FaceSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Operasi harian untuk face recognition:
 *  1. Hapus foto bukti absen yang melewati masa retensi (bawaan 60 hari).
 *  2. Hapus jejak audit yang melewati `face.audit_retention_days` (bawaan 60 hari).
 *  3. Cek kondisi layanan + antrean review, lalu kirim alert Telegram bila perlu.
 */
class FaceHousekeeping extends Command
{
    protected $signature = 'face:housekeeping';

    protected $description = 'Retensi foto/audit face recognition, cek layanan, dan alert Telegram';

    public function handle(): int
    {
        $this->purgePhotos();
        $this->purgeAuditLogs();
        $this->checkService();
        $this->checkReviewQueue();

        return self::SUCCESS;
    }

    private function purgePhotos(): void
    {
        $days = max(1, FaceSettings::int('face.retention_days'));

        $attendances = Attendance::whereNotNull('face_image')
            ->where('face_verified_at', '<=', now()->subDays($days))
            ->limit(1000)
            ->get();

        $deleted = 0;

        foreach ($attendances as $attendance) {
            try {
                if (filled($attendance->face_image)) {
                    Storage::disk('local')->delete($attendance->face_image);
                }

                $attendance->update(['face_image' => null]);
                $deleted++;
            } catch (\Throwable $e) {
                $this->warn('Gagal memproses attendance #' . $attendance->id . ': ' . $e->getMessage());
            }
        }

        $this->info("Foto dihapus: {$deleted} berkas (retensi {$days} hari).");
    }

    private function purgeAuditLogs(): void
    {
        $days = max(1, FaceSettings::int('face.audit_retention_days'));

        $deleted = FaceAuditLog::where('created_at', '<=', now()->subDays($days))->delete();

        $this->info("Log audit dihapus: {$deleted} baris (retensi {$days} hari).");
    }

    private function checkService(): void
    {
        if (!FacePolicy::enabled()) {
            return;
        }

        $health = app(FaceVerificationService::class)->health();

        if ($health === null) {
            app(TelegramNotifier::class)->send('Microservice face recognition tidak dapat dihubungi.');
            $this->warn('Layanan wajah tidak dapat dihubungi.');

            return;
        }

        $this->info('Layanan wajah merespons normal: ' . $health['message']);
    }

    private function checkReviewQueue(): void
    {
        $pending = Attendance::whereNull('face_reviewed_by')
            ->where(function ($query) {
                $query->where('face_match', false)->orWhereNull('face_match');
            })
            ->whereNotNull('face_verified_at')
            ->count();

        app(TelegramNotifier::class)->sendWhenAbove(
            'Antrean verifikasi wajah HRD menumpuk.',
            $pending,
            max(1, FaceSettings::int('telegram.alert_mismatch_threshold'))
        );
    }
}