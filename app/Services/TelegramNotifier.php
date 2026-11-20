<?php

namespace App\Services;

use App\Support\FaceSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notifikasi Telegram untuk upkeep face recognition & broadcast sistem.
 *
 * Dipakai untuk: layanan hilang, mismatch melonjak, antrean review menumpuk,
 * kegagalan registrasi wajah, dan respons webhook bot. Token bot & chat id disimpan di
 * `attendance_settings` (bukan kode), sehingga dapat diubah admin.
 */
class TelegramNotifier
{
    /** Kirim pesan; null bila konfigurasi kosong atau layanan gagal. */
    public function send(string $message, ?string $toChatId = null, ?string $customPrefix = null): ?bool
    {
        $token = FaceSettings::str('telegram.bot_token');
        $chatId = $toChatId ?: FaceSettings::str('telegram.chat_id');

        if ($token === '' || $chatId === '') {
            return null;
        }

        $prefix = $customPrefix !== null ? $customPrefix : '[Absensi Wajah] ';
        $fullText = $prefix ? ($prefix . $message) : $message;

        try {
            $response = Http::timeout(5)->asJson()->post(
                'https://api.telegram.org/bot' . $token . '/sendMessage',
                [
                    'chat_id' => $chatId,
                    'text' => $fullText,
                    'disable_web_page_preview' => true,
                ]
            );

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('TelegramNotifier gagal mengirim', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /** Kirim hanya bila nilai $value melewati ambang (dipakai health check). */
    public function sendWhenAbove(string $message, int $value, int $threshold): ?bool
    {
        if ($value < $threshold) {
            return null;
        }

        return $this->send($message . ' (nilai: ' . $value . ', ambang: ' . $threshold . ')');
    }

    /** Atur Webhook URL ke Telegram. */
    public function setWebhook(string $url): array
    {
        $token = FaceSettings::str('telegram.bot_token');

        if ($token === '') {
            return ['ok' => false, 'message' => 'Token bot Telegram belum diisi.'];
        }

        try {
            $response = Http::timeout(10)->asJson()->post(
                'https://api.telegram.org/bot' . $token . '/setWebhook',
                ['url' => $url]
            );

            $data = $response->json();
            return [
                'ok' => (bool) ($data['ok'] ?? false),
                'message' => $data['description'] ?? ($response->successful() ? 'Webhook berhasil didaftarkan.' : 'Gagal mendaftarkan webhook.'),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Koneksi ke Telegram gagal: ' . $e->getMessage()];
        }
    }

    /** Ambil status Webhook Telegram saat ini. */
    public function getWebhookInfo(): array
    {
        $token = FaceSettings::str('telegram.bot_token');

        if ($token === '') {
            return ['ok' => false, 'message' => 'Token bot Telegram belum diisi.'];
        }

        try {
            $response = Http::timeout(10)->get('https://api.telegram.org/bot' . $token . '/getWebhookInfo');
            return $response->json() ?? ['ok' => false, 'message' => 'Respon tidak valid'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
