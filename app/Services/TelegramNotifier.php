<?php

namespace App\Services;

use App\Support\FaceSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notifikasi Telegram untuk upkeep face recognition.
 *
 * Dipakai untuk: layanan hilang, mismatch melonjak, antrean review menumpuk,
 * dan kegagalan registrasi wajah. Token bot & chat id disimpan di
 * `attendance_settings` (bukan kode), sehingga dapat diubah admin.
 */
class TelegramNotifier
{
    /** Kirim pesan; null bila konfigurasi kosong atau layanan gagal. */
    public function send(string $message): ?bool
    {
        $token = FaceSettings::str('telegram.bot_token');
        $chatId = FaceSettings::str('telegram.chat_id');

        if ($token === '' || $chatId === '') {
            return null;
        }

        try {
            $response = Http::timeout(5)->asJson()->post(
                'https://api.telegram.org/bot' . $token . '/sendMessage',
                [
                    'chat_id' => $chatId,
                    'text' => '[Absensi Wajah] ' . $message,
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
}