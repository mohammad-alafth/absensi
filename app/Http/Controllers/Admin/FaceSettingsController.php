<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Face\FaceServiceProcess;
use App\Services\FaceAuditLogger;
use App\Services\FaceVerificationService;
use App\Services\TelegramNotifier;
use App\Support\FacePolicy;
use App\Support\FaceSettings;
use Illuminate\Http\Request;

/**
 * Pengaturan face recognition (B5: ambang & policy tersimpan di database).
 *
 * Admin role dapat mengubah seluruh parameter tanpa menyentuh kode:
 * master switch, dua ambang similarity, jumlah sampel registrasi, maksimum
 * percobaan scan, retensi foto, role yang bebas, titik & radius kantor, serta
 * endpoint + token microservice.
 */
class FaceSettingsController extends Controller
{
    /**
     * Kunci checkbox.
     *
     * Checkbox HTML TIDAK terkirim sama sekali saat tidak dicentang, sehingga
     * "tidak terkirim" tidak boleh dianggap "tidak diubah". Kunci di bawah ini
     * selalu ditulis ulang (1/0) setiap kali form disimpan, sehingga admin
     * dapat MENONGGKAN verifikasi wajah dari UI.
     *
     * @var array<int, string>
     */
    private const TOGGLES = ['face.enabled', 'face.spoof_enabled'];

    public function index()
    {
        return view('admin.face-settings.index', [
            'settings' => FaceSettings::all(),
            'defaults' => FaceSettings::DEFAULTS,
            'exemptRoles' => FaceSettings::exemptRoles(),
            'assignableRoles' => \App\Services\ApprovalFlowService::assignableRoles(),
            'policyEnabled' => FacePolicy::enabled(),
            'serviceProcess' => app(FaceServiceProcess::class),
        ]);
    }

    /**
     * Aturan validasi per kunci pengaturan.
     *
     * Kunci memakai nama datar bertitik (mis. `face.match_threshold`) dan dibaca
     * langsung dari input, bukan lewat `validated()`, karena `validated()`
     * menganggap titik sebagai jalur bersarang.
     *
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'face.enabled' => 'nullable|boolean',
            'face.match_threshold' => 'nullable|numeric|min:0|max:1',
            'face.gray_threshold' => 'nullable|numeric|min:0|max:1',
            'face.max_attempts' => 'nullable|integer|min:1|max:10',
            'face.attempt_timeout_ms' => 'nullable|integer|min:500|max:30000',
            'face.min_samples' => 'nullable|integer|min:1|max:20',
            'face.min_quality' => 'nullable|numeric|min:0|max:1',
            'face.retention_days' => 'nullable|integer|min:1|max:3650',
            'face.audit_retention_days' => 'nullable|integer|min:1|max:3650',
            'face.spoof_enabled' => 'nullable|boolean',
            'face.reject_action' => 'nullable|in:keep,void',
            'face.consent_notice_version' => 'nullable|string|max:50',
            'telegram.bot_token' => 'nullable|string|max:255',
            'telegram.chat_id' => 'nullable|string|max:100',
            'telegram.alert_mismatch_threshold' => 'nullable|integer|min:1|max:1000',
            'face.service_url' => 'nullable|string|max:255',
            'face.service_token' => 'nullable|string|max:255',
            'face.exempt_roles' => 'nullable|string|max:500',
            'office.radius_meters' => 'nullable|numeric|min:0|max:100000',
            'office.latitude' => 'nullable|numeric|min:-90|max:90',
            'office.longitude' => 'nullable|numeric|min:-180|max:180',
        ];
    }

    /**
     * Kumpulkan nilai pengaturan dari request.
     *
     * PENTING: PHP mengubah TITIK pada nama field POST menjadi underscore
     * (`face.enabled` -> `$_POST['face_enabled']`) saat data dikirim sebagai
     * application/x-www-form-urlencoded. Karena itu field form memakai notasi
     * array (`settings[face.enabled]`), yang membuat PHP menyusunnya sebagai
     * array dan TITIKNYA tetap utuh. Test client Laravel tidak melewati
     * parsing SAPI sehingga tidak menangkap masalah ini.
     *
     * Kunci datar bersamadot tetap diterima untuk backwards compatibility
     * (dipakai pemanggilan internal & test).
     *
     * @return array<string, mixed>
     */
    private function collect(Request $request): array
    {
        $input = $request->input('settings', []);

        if (! is_array($input)) {
            $input = [];
        }

        foreach ($request->all() as $key => $value) {
            if (str_contains($key, '.') && ! array_key_exists($key, $input)) {
                $input[$key] = $value;
            }
        }

        return $input;
    }

    public function update(Request $request)
    {
        $rules = $this->rules();
        $input = $this->collect($request);
        $clean = [];
        $seen = [];

        foreach (array_keys($rules) as $key) {
            if (array_key_exists($key, $input)) {
                $seen[] = $key;
            }

            if (in_array($key, self::TOGGLES, true)) {
                // Selalu tulis 1/0: checkbox tidak terkirim saat tidak dicentang.
                //
                // CATATAN: nilai diambil dari $input dengan akses LITERAL. Jangan
                // memakai $request->boolean($key) / $request->input($key), karena
                // keduanya memakai data_get() yang membaca titik sebagai jalur
                // bersarang, sehingga kunci `face.enabled` selalu dianggap null.
                $clean[$key] = filter_var($input[$key] ?? null, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

                continue;
            }

            if (array_key_exists($key, $input) && $input[$key] !== '') {
                $clean[$key] = $input[$key];
            }
        }

        // Guard: form yang mengirim nol kunci yang dikenal berarti ada ketidakcocokan
        // nama field (mis. halaman lama masih tersimpan di cache browser). Lebih baik
        // memberi pesan jelas daripada diam-diam tidak menyimpan apa pun.
        if ($seen === []) {
            return back()->with(
                'error',
                'Tidak ada pengaturan yang dikenali pada kiriman form, jadi tidak ada yang disimpan. '
                . 'Muat ulang halaman pengaturan (Ctrl+F5) lalu simpan ulang.'
            );
        }

        // Validasi melempar error / redirect bila ada nilai tidak valid.
        validator($clean, $rules)->validate();

        // Nilai diambil apa adanya dari input (bukan dari validated(), karena
        // kunci bertitik dianggap jalur bersarang oleh helper tersebut).
        $data = $clean;

        $match = (float) ($data['face.match_threshold'] ?? FaceSettings::float('face.match_threshold'));
        $gray = (float) ($data['face.gray_threshold'] ?? FaceSettings::float('face.gray_threshold'));

        if ($gray >= $match) {
            return back()->withInput()->with(
                'error',
                'Ambang abu-abu harus lebih kecil dari ambang match (mis. abu 0.45, match 0.62).'
            );
        }

        foreach ($data as $key => $value) {
            FaceSettings::put($key, $value);
        }

        return back()->with('success', 'Pengaturan face recognition disimpan.');
    }

    public function reset()
    {
        FaceSettings::reset();

        return back()->with('success', 'Pengaturan dikembalikan ke nilai bawaan sistem.');
    }

    /** Uji koneksi ke microservice face recognition (GET /health). */
    public function testService(Request $request)
    {
        $health = app(FaceVerificationService::class)->health();
        $url = FaceSettings::str('face.service_url');

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::SETTINGS_UPDATE,
            null,
            $health === null ? 'service_failed' : 'service_ok',
            ['action' => 'test_service'],
            $request
        );

        if ($health === null) {
            return back()->with(
                'error',
                'Layanan wajah tidak dapat dihubungi di ' . ($url === '' ? '(URL microservice kosong)' : $url)
                . '. Periksa URL, token, dan apakah microservice berjalan.'
            );
        }

        return back()->with('success', 'Layanan wajah merespons: ' . $health['message']);
    }

    /**
 * Kendali proses microservice (Start / Stop / Restart).
 *
 * Semua aksi dicatat ke log audit karena mengubah apakah verifikasi wajah
 * benar-benar berjalan - ini impactful pada keamanan absensi.
 */
    public function serviceControl(Request $request, string $action)
    {
        if (!in_array($action, ['start', 'stop', 'restart'], true)) {
            abort(404);
        }

        $result = match ($action) {
            'start' => app(FaceServiceProcess::class)->start(),
            'stop' => app(FaceServiceProcess::class)->stop(),
            'restart' => app(FaceServiceProcess::class)->restart(),
        };

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::SETTINGS_UPDATE,
            auth()->id(),
            $result['ok'] ? 'service_' . $action : 'service_' . $action . '_failed',
            ['action' => $action, 'port' => app(FaceServiceProcess::class)->port()],
            $request
        );

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Uji kirim notifikasi Telegram. */
    public function testTelegram(Request $request)
    {
        $sent = app(TelegramNotifier::class)->send('Tes notifikasi Telegram dari menu pengaturan.');

        app(FaceAuditLogger::class)->log(
            FaceAuditLogger::SETTINGS_UPDATE,
            null,
            $sent === true ? 'telegram_sent' : 'telegram_failed',
            ['action' => 'telegram_test'],
            $request
        );

        if ($sent === null) {
            return back()->with('error', 'Token bot atau chat id Telegram belum diisi.');
        }

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? 'Notifikasi Telegram terkirim.' : 'Telegram menolak permintaan. Periksa token bot dan chat id.'
        );
    }
}