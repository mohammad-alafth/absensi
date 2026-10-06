<?php

namespace App\Services\Face;

use App\Services\FaceVerificationService;
use App\Support\FaceSettings;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Kendali proses mikroservice face recognition (InsightFace) dari UI admin.
 *
 * Microservice ini menjalankan uvicorn (port 8100) sebagai proses terpisah.
 * Sebelumnya service hanya bisa dinyalakan manual lewat PowerShell, sehingga
 * admin tidak punya cara menyalakannya dari aplikasi - dan kalau mati,
 * verifikasi wajah diam-diam fail open (siapa pun bisa absen tanpa dicek).
 *
 * Service ini menambah kendali Start / Stop / Restart plus status untuk UI.
 *
 * Catatan keamanan:
 *  - Hanya boleh dipanggil dari route ber-middleware `role:admin`.
 *  - Tidak ada input pengguna yang masuk ke perintah shell. Path berasal dari
 *    base_path(), sedangkan port diambil dari face.service_url lalu dipaksa
 *    menjadi integer sebelum dipakai pada netstat / taskkill.
 */
class FaceServiceProcess
{
    /** Port bawaan bila URL tidak menyebut port. */
    private const DEFAULT_PORT = 8100;

    public function __construct(
        private readonly FaceVerificationService $service = new FaceVerificationService(),
    ) {
    }

    /**
     * Status layanan untuk ditampilkan di UI.
     *
     * @return array{
     *     state: 'running'|'starting'|'stopped'|'not_installed',
     *     label: string, bool: bool, message: string,
     *     port: int, script: string
     * }
     */
    public function status(): array
    {
        if (!$this->isInstalled()) {
            return $this->result('not_installed', 'Belum Terpasang', false,
                $this->isWindows()
                    ? 'Virtual environment mikroservice belum ada. Jalankan sekali: cd face-service; '
                      . 'python -m venv .venv; .\.venv\Scripts\python.exe -m pip install -r requirements.txt'
                    : 'Virtual environment mikroservice belum ada. Jalankan sekali lewat SSH/terminal Plesk: '
                      . 'cd face-service && sh setup.sh');
        }

        $health = $this->service->health();

        if ($health !== null) {
            return $this->result('running', 'Aktif', true,
                'Layanan wajah merespons: ' . ($health['message'] ?? 'siap'));
        }

        // Port sudah didengarkan proses, tapi /health belum siap: model
        // InsightFace (~275 MB) masih dimuat.
        if ($this->pidsOnPort() !== []) {
            return $this->result('starting', 'Sedang Dimuat', false,
                'Proses jalan tetapi model belum siap. Tunggu beberapa detik lalu muat ulang halaman.');
        }

        return $this->result('stopped', 'Mati', false,
            'Mikroservice tidak berjalan. Absen saat ini TIDAK memverifikasi wajah - '
            . 'siapa pun bisa absen tanpa diperiksa. Sebaiknya nyalakan sekarang.');
    }

    /**
     * Nyalakan mikroservice.
     *
     * @return array{ok: bool, message: string}
     */
    public function start(): array
    {
        if (!$this->isInstalled()) {
            return ['ok' => false, 'message' => 'Mikroservice belum terpasang (butuh .venv). '
                . 'Lihat catatan instalasi pada status layanan.'];
        }

        if ($this->service->health() !== null) {
            return ['ok' => true, 'message' => 'Mikroservice sudah aktif, tidak perlu dinyalakan lagi.'];
        }

        if ($this->pidsOnPort() !== []) {
            return ['ok' => false, 'message' => 'Port ' . $this->port()
                . ' sedang dipakai proses lain. Coba Restart, atau nyalakan ulang komputer.'];
        }

        try {
            $process = new Process($this->startCommand(), base_path('face-service'), $this->env());

            // Wajib: tanpa create_new_console, Process::__destruct() memanggil
            // stop(0) sehingga proses baru dimatikan begitu request HTTP selesai.
            // Microservice harus BERTAHAN setelah admin menekan tombol.
            //
            // Output TIDAK diset di sini: startup.ps1 sudah mengarahkan log
            // uvicorn ke service.log lewat Start-Process -RedirectStandardOutput.
            $process->setOptions(['create_new_console' => $this->isWindows()]);
            $process->setTimeout(null);

            $process->start();

            Log::info('FaceService: start dijalankan dari UI admin.', [
                'pid' => $process->getPid(),
                'port' => $this->port(),
            ]);
        } catch (Throwable $e) {
            Log::error('FaceService: gagal menjalankan start.', ['error' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Gagal menjalankan mikroservice: ' . $e->getMessage()];
        }

        return [
            'ok' => true,
            'message' => 'Mikroservice sedang dinyalakan. Model InsightFace butuh 30-60 detik '
                . 'pada start-up pertama - muat ulang halaman ini untuk melihat status terbaru.',
        ];
    }
/**
     * Matikan mikroservice, lalu VERIFIKASI benar-benar sudah mati.
     *
     * Kill PID saja belum tentu cukup: proses bisa butuh waktu melepas port,
     * dan `taskkill` bisa gagal diam-diam. Tanpa verifikasi, UI pernah
     * menampilkan "AKTIF" padahal prosesnya sudah tidak merespons - atau
     * sebaliknya. Jadi setelah kill, poll /health sampai diam.
     *
     * @return array{ok: bool, message: string}
     */
    public function stop(): array
    {
        $pids = $this->pidsOnPort();

        if ($pids !== []) {
            foreach ($pids as $pid) {
                $command = $this->isWindows()
                    ? ['taskkill', '/PID', (string) $pid, '/F', '/T']
                    : ['kill', '-TERM', (string) $pid];

                try {
                    (new Process($command))->run();
                } catch (Throwable $e) {
                    Log::warning('FaceService: gagal menghentikan proses.', [
                        'pid' => $pid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Tunggu proses melepas port.
        for ($i = 0; $i < 10 && $this->pidsOnPort() !== []; $i++) {
            usleep(300_000);
        }

        // Verifikasi: service harus diam. Kalau masih merespons, paksa lagi.
        if ($this->service->health() !== null) {
            Log::warning('FaceService: proses masih merespons setelah taskkill, mencoba lagi.');

            foreach ($this->pidsOnPort() as $pid) {
                $command = $this->isWindows()
                    ? ['taskkill', '/PID', (string) $pid, '/F', '/T']
                    : ['kill', '-KILL', (string) $pid];

                try {
                    (new Process($command))->run();
                } catch (Throwable $e) {
                    // diabaikan, sudah dicatat di atas
                }
            }

            for ($i = 0; $i < 15; $i++) {
                usleep(400_000);
                if ($this->service->health() === null) {
                    break;
                }
            }
        }

        $alive = $this->service->health() !== null;

        Log::info('FaceService: stop dijalankan dari UI admin.', [
            'pids' => $pids,
            'pids_after' => $this->pidsOnPort(),
            'still_responding' => $alive,
        ]);

        if ($alive) {
            return [
                'ok' => false,
                'message' => 'Mikroservice TIDAK berhasil dimatikan - servicenya masih merespons '
                    . 'di port ' . $this->port() . '. Tutup jendela/konsol Python yang menyala '
                    . 'secara manual, lalu coba lagi. Verifikasi wajah MASIH AKTIF.',
            ];
        }

        if ($pids === []) {
            return ['ok' => true, 'message' => 'Mikroservice memang sudah tidak berjalan.'];
        }

        return [
            'ok' => true,
            'message' => 'Mikroservice dimatikan dan terverifikasi tidak merespons lagi. '
                . 'Verifikasi wajah SEKARANG TIDAK AKTIF - siapa pun bisa absen tanpa '
                . 'pemeriksaan wajah sampai service dinyalakan lagi.',
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function restart(): array
    {
        $this->stop();

        return $this->start();
    }

    /** @return array<int, int> daftar PID yang listen di port layanan */
    public function pidsOnPort(): array
    {
        $port = $this->port();

        try {
            $pids = [];

            if ($this->isWindows()) {
                $process = new Process(['netstat', '-ano', '-p', 'TCP']);
                $process->run();

                foreach (preg_split('/\R/', $process->getOutput()) ?: [] as $line) {
                    if (!preg_match('/^\s*TCP\s+\S+:(\d+)\s+\S+\s+LISTENING\s+(\d+)\s*$/i', $line, $m)) {
                        continue;
                    }
                    if ((int) $m[1] === $port) {
                        $pids[] = (int) $m[2];
                    }
                }
            } else {
                $process = new Process(['lsof', '-t', '-i:' . $port . '-sTCP:LISTEN']);
                $process->run();

                foreach (preg_split('/\R/', trim($process->getOutput())) ?: [] as $line) {
                    if (ctype_digit(trim($line))) {
                        $pids[] = (int) trim($line);
                    }
                }

                // lsof sering tidak terpasang di Plesk/distro minimal. Tanpa ini
                // tombol Matikan tidak menemukan PID sehingga service tidak bisa
                // dimatikan dari UI. ss (util-linux) hampir selalu tersedia.
                if ($pids === []) {
                    // Tanpa -H (kadang tidak didukung iproute2 lama); baris
                    // header tetap aman karena tidak mengandung "LISTEN" + pid=.
                    $ss = new Process(['ss', '-ltnp']);
                    $ss->run();

                    foreach (preg_split('/\R/', trim($ss->getOutput())) ?: [] as $line) {
                        if (!preg_match(
                            '/\bLISTEN\b.*:' . preg_quote((string) $port, '/') . '\s.*\bpid=(\d+)/i',
                            $line,
                            $m
                        )) {
                            continue;
                        }
                        $pids[] = (int) $m[1];
                    }
                }
            }

            return array_values(array_unique($pids));
        } catch (Throwable $e) {
            Log::warning('FaceService: gagal membaca daftar PID.', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /** Port layanan, diambil dari face.service_url. Selalu integer. */
    public function port(): int
    {
        $url = trim(FaceSettings::str('face.service_url'));
        $port = $url === '' ? false : parse_url($url, PHP_URL_PORT);

        return is_int($port) && $port > 0 ? $port : self::DEFAULT_PORT;
    }

    /** Path skrip start yang dipakai UI (menyesuaikan OS server). */
    public function script(): string
    {
        return base_path('face-service' . DIRECTORY_SEPARATOR
            . ($this->isWindows() ? 'startup.ps1' : 'start.sh'));
    }

    private function isInstalled(): bool
    {
        return is_file($this->interpreter());
    }

    private function interpreter(): string
    {
        $base = base_path('face-service' . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR);

        return $this->isWindows()
            ? $base . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe'
            : $base . 'bin' . DIRECTORY_SEPARATOR . 'python';
    }

    /** Perintah start. Tidak pernah memuat input pengguna. @return array<int, string> */
    private function startCommand(): array
    {
        $script = $this->script();

        if ($this->isWindows()) {
            return [
                'powershell.exe',
                '-NoProfile',
                '-WindowStyle', 'Hidden',
                '-ExecutionPolicy', 'Bypass',
                '-File', $script,
            ];
        }

        // Linux/Plesk: lewat start.sh, BUKAN uvicorn langsung.
        //
        // Alasannya dua:
        //  1. start.sh memakai `setsid -f` sehingga uvicorn berjalan di session
        //     sendiri. Tanpa itu Process::__destruct() (yang memanggil stop(0))
        //     mengirim SIGTERM/SIGKILL ke proses child begitu request HTTP
        //     selesai - tombol "Nyalakan" akan mematikan service-nya sendiri.
        //  2. start.sh mengarahkan stdout/stderr ke service.log, jadi uvicorn
        //     tidak mewarisi pipe Symfony yang membuat request menunggu.
        return [
            'bash', '-c',
            'FACE_SERVICE_TOKEN=' . escapeshellarg(FaceSettings::str('face.service_token'))
            . ' exec sh ' . escapeshellarg(base_path('face-service' . DIRECTORY_SEPARATOR . 'start.sh')),
        ];
    }

    /**
     * Environment untuk proses.
     *
     * Token diambil dari `face.service_token` yang sama persis dengan yang
     * dipakai Laravel saat memanggil /match, jadi tidak mungkin tidak cocok.
     *
     * @return array<string, string>
     */
    private function env(): array
    {
        return ['FACE_SERVICE_TOKEN' => FaceSettings::str('face.service_token')];
    }

    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\' || PHP_OS_FAMILY === 'Windows';
    }

    /**
     * @return array{state: string, label: string, bool: bool, message: string, port: int, script: string}
     */
    private function result(string $state, string $label, bool $bool, string $message): array
    {
        return [
            'state' => $state,
            'label' => $label,
            'bool' => $bool,
            'message' => $message,
            'port' => $this->port(),
            'script' => $this->script(),
        ];
    }
}