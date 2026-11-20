<?php

namespace App\Services;

use App\Models\SystemAnnouncement;
use App\Support\FaceSettings;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    public function __construct(
        protected TelegramNotifier $notifier
    ) {}

    /**
     * Proses satu update dari Telegram (bisa dari Webhook atau Polling).
     *
     * @param array $update Struktur data pesan dari Telegram
     * @return array Status pemrosesan
     */
    public function processUpdate(array $update): array
    {
        if (!isset($update['message']['text']) || !isset($update['message']['chat']['id'])) {
            return ['ok' => true, 'action' => 'ignored_non_text'];
        }

        $senderChatId = (string) $update['message']['chat']['id'];
        $rawText = trim($update['message']['text']);
        $adminChatId = FaceSettings::str('telegram.chat_id');

        // Verifikasi pengirim adalah admin yang terdaftar
        if ($adminChatId === '' || $senderChatId !== $adminChatId) {
            Log::warning('Telegram bot: akses ditolak untuk chat_id tidak dikenal', [
                'sender_chat_id' => $senderChatId,
                'configured_admin_chat_id' => $adminChatId,
                'text' => $rawText,
            ]);

            $this->notifier->send(
                "⛔ Akses ditolak. Chat ID Anda ({$senderChatId}) belum terdaftar sebagai admin di sistem absensi.\nHubungi administrator atau setel Chat ID di menu Admin > Face Settings.",
                $senderChatId,
                ''
            );

            return ['ok' => false, 'error' => 'unauthorized'];
        }

        // Tangani perintah bantuan /start atau /help
        if (in_array(strtolower($rawText), ['/start', '/help', 'help'])) {
            $help = "🤖 *Bot Absensi RS Mata PBEC*\n\n"
                  . "Perintah Pengumuman Dashboard:\n"
                  . "• `/alert <pesan>`\n"
                  . "  Contoh: `/alert notif all system update`\n"
                  . "  Tayangkan banner pengumuman di dashboard seluruh karyawan.\n\n"
                  . "• `/alert dismiss` atau `/alert clear`\n"
                  . "  Hapus/tutup banner pengumuman aktif dari dashboard.\n\n"
                  . "• `/alert status`\n"
                  . "  Cek status pengumuman yang sedang tayang saat ini.";

            $this->notifier->send($help, $senderChatId, '');
            return ['ok' => true, 'action' => 'help_sent'];
        }

        // Cek apakah pesan diawali alert (dengan atau tanpa slash)
        $pattern = '/^(\/)?alert\b/i';
        if (!preg_match($pattern, $rawText)) {
            return ['ok' => true, 'action' => 'ignored_not_alert'];
        }

        // Ambil isi setelah kata 'alert'
        $body = trim(preg_replace($pattern, '', $rawText));

        // Subperintah: dismiss / clear / hapus
        if (in_array(strtolower($body), ['dismiss', 'clear', 'hapus', 'close', 'off'])) {
            $count = SystemAnnouncement::dismissAll();
            $msg = $count > 0
                ? "✅ *Pengumuman Ditutup*\nBanner pengumuman di dashboard karyawan berhasil dinonaktifkan."
                : "ℹ️ Tidak ada pengumuman aktif di dashboard.";

            $this->notifier->send($msg, $senderChatId, '');
            return ['ok' => true, 'action' => 'dismissed'];
        }

        // Subperintah: status
        if (in_array(strtolower($body), ['status', 'cek', 'check'])) {
            $current = SystemAnnouncement::current();
            if (!$current) {
                $this->notifier->send("ℹ️ Saat ini *tidak ada* pengumuman aktif di dashboard.", $senderChatId, '');
            } else {
                $statusMsg = "📢 *Pengumuman Sedang Tayang di Dashboard*\n\n"
                           . "Pesan: *{$current->message}*\n"
                           . "Dibuat: {$current->created_at->translatedFormat('d F Y H:i')}\n\n"
                           . "_Ketik `/alert dismiss` untuk menutup._";
                $this->notifier->send($statusMsg, $senderChatId, '');
            }
            return ['ok' => true, 'action' => 'status_sent'];
        }

        // Pembersihan format pesan:
        $cleanMessage = $body;

        // Hilangkan petik di awal/akhir bila ada
        $cleanMessage = trim($cleanMessage, " \t\n\r\0\x0B\"'");

        // Jika diawali "notif all ", bersihkan prefiks tersebut agar pesan intinya rapi
        if (preg_match('/^notif\s+all\s*:?\s*(.*)$/is', $cleanMessage, $matches)) {
            $cleanMessage = trim($matches[1], " \t\n\r\0\x0B\"'");
            if ($cleanMessage === '') {
                $cleanMessage = 'Pemberitahuan: Sistem sedang mengalami pembaruan.';
            }
        }

        if ($cleanMessage === '') {
            $this->notifier->send(
                "⚠️ *Format pesan kosong.*\nContoh penggunaan:\n`/alert notif all system update`\natau\n`/alert Pemeliharaan server pukul 14:00`",
                $senderChatId,
                ''
            );
            return ['ok' => false, 'error' => 'empty_message'];
        }

        // Nonaktifkan pengumuman sebelumnya
        SystemAnnouncement::dismissAll();

        // Simpan pengumuman baru
        $announcement = SystemAnnouncement::create([
            'title' => 'Pemberitahuan Sistem',
            'message' => $cleanMessage,
            'level' => 'warning',
            'created_by_chat_id' => $senderChatId,
            'is_active' => true,
            'expires_at' => now()->addHours(24),
        ]);

        $reply = "📢 *PENGUMUMAN DITAYANGKAN DI DASHBOARD!*\n\n"
               . "Isi Pesan:\n*\"{$cleanMessage}\"*\n\n"
               . "Target: Seluruh Karyawan (Dashboard)\n"
               . "Waktu: {$announcement->created_at->format('d/m/Y H:i')}\n"
               . "Masa Tayang: 24 Jam (Otomatis Expired)\n\n"
               . "💡 _Ketik `/alert dismiss` jika ingin menghapus pengumuman ini segera._";

        $this->notifier->send($reply, $senderChatId, '');

        return [
            'ok' => true,
            'action' => 'published',
            'announcement_id' => $announcement->id,
            'message' => $cleanMessage,
        ];
    }
}
