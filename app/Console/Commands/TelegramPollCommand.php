<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use App\Support\FaceSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Jalankan sekali untuk memproses pesan yang sedang antre lalu berhenti}';

    protected $description = 'Jalankan bot Telegram via polling (cocok untuk pengujian di localhost tanpa domain publik)';

    public function handle(TelegramBotService $botService): int
    {
        $token = FaceSettings::str('telegram.bot_token');
        $chatId = FaceSettings::str('telegram.chat_id');

        if ($token === '') {
            $this->error('Token bot Telegram belum disetel di tabel attendance_settings / menu admin.');
            return self::FAILURE;
        }

        $this->info("Bot Telegram siap berjalan via polling.");
        $this->info("Admin Chat ID terdaftar: " . ($chatId ?: '(belum disetel)'));

        $offset = 0;
        $runOnce = $this->option('once');

        do {
            try {
                $response = Http::timeout(25)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => $runOnce ? 0 : 20,
                ]);

                if (!$response->successful()) {
                    $this->warn('Gagal menghubungi Telegram API: HTTP ' . $response->status());
                    if ($runOnce) break;
                    sleep(2);
                    continue;
                }

                $data = $response->json();
                $updates = $data['result'] ?? [];

                if (!empty($updates)) {
                    foreach ($updates as $update) {
                        $updateId = $update['update_id'];
                        $offset = $updateId + 1;

                        $sender = $update['message']['from']['first_name'] ?? 'User';
                        $text = $update['message']['text'] ?? '[bukan teks]';

                        $this->line("<fg=cyan>[Telegram]</> Pesan dari <fg=yellow>{$sender}</>: {$text}");

                        $result = $botService->processUpdate($update);

                        if (($result['action'] ?? '') === 'published') {
                            $this->info("  -> Pengumuman berhasil ditayangkan: \"{$result['message']}\"");
                        } elseif (($result['action'] ?? '') === 'dismissed') {
                            $this->info("  -> Pengumuman dinonaktifkan.");
                        } elseif (isset($result['error'])) {
                            $this->warn("  -> " . $result['error']);
                        }
                    }
                } elseif ($runOnce) {
                    $this->info("Tidak ada pesan antre di Telegram.");
                }

            } catch (Throwable $e) {
                $this->error('Error saat polling: ' . $e->getMessage());
                if ($runOnce) break;
                sleep(3);
            }

        } while (!$runOnce);

        return self::SUCCESS;
    }
}
