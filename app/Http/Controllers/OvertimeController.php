<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Overtime;
use Carbon\Carbon;
use App\Services\ApprovalFlowService;
use App\Services\OvertimePunchService;

use App\Support\SubmissionStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OvertimeController extends Controller
{
    /**
     * Durasi minimum pengajuan lembur (menit).
     * Jam lembur dihitung per 60 menit penuh, jadi di bawah ini
     * pengajuan akan menghasilkan surat tanpa jam lembur.
     */
    private const MIN_OVERTIME_MINUTES = 60;

    /**
     * Aturan main absen lembur realtime: jendela waktu boleh absen, validasi
     * GPS + selfie, anti absen ganda, dan volume jam dari jam nyata.
     */
    public function __construct(
        protected OvertimePunchService $punches
    ) {
    }

    /** Role yang boleh mengoreksi absen lembur saat karyawan lupa absen. */
    private const CORRECTOR_ROLES = ['pj', 'hrd', 'director', 'admin'];


    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        /*
        |--------------------------------------------------------------------------
        | PENGAJUAN SAYA
        |--------------------------------------------------------------------------
        | Ditampilkan langsung di halaman pengajuan agar pengaju bisa memantau
        | status dan merevisi pengajuannya sendiri tanpa membuka menu Riwayat.
        */
        $overtimes = Overtime::where('user_id', auth()->id())
            ->latest()
            ->limit(5)
            ->get();

        // Nama approver diambil sekali untuk semua pengajuan (hindari N+1).
        SubmissionStatus::primeApproverNames($overtimes);

        /*
        |--------------------------------------------------------------------------
        | KARTU ABSEN LEMBUR REALTIME
        |--------------------------------------------------------------------------
        | Status sesi absen yang sedang berjalan / jendela yang sedang dibuka,
        | dipakai komponen x-overtime-punch-card di halaman ini.
        */
        $punchState = $this->punches->state(auth()->user());

        /*
        |--------------------------------------------------------------------------
        | MODE "SAMPAI SELESAI" UNTUK FORM
        |--------------------------------------------------------------------------
        | Hanya ditawarkan pada tanggal yang tidak punya jadwal kerja reguler
        | (hari libur/off) karena pada hari itu absen realtime adalah satu-satunya
        | bukti kehadiran. Bila tersedia, form cukup mengisi Jam Mulai dan
        | pengajuan yang dikirim sekaligus menjadi absen mulai lembur.
        */
        $openEnded = $this->punches->openEndedAvailability(auth()->user());

        return view('lembur', compact('overtimes', 'punchState', 'openEnded'));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE (PROSES SIMPAN & GENERATE PDF)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | DUA MODE PENGISIAN FORM
        |--------------------------------------------------------------------------
        | 1. Mode rencana: Jam Mulai + Jam Berakhir diisi (alur lama; dipakai juga
        |    untuk lembur hari libur yang diajukan sebelum harinya tiba).
        | 2. Mode "sampai selesai" (Jam Berakhir dikosongkan): khusus tanggal tanpa
        |    jadwal kerja reguler. Pengajuan yang dikirim sekaligus mencatat ABSEN
        |    MULAI realtime (GPS + selfie wajib) dan jam selesai diambil dari absen
        |    pulang, jadi volume jam lembur sepenuhnya jam nyata.
        */
        $openEnded = blank($request->end_time);

        $request->validate($this->storeRules($openEnded));

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | BUKTI, JAM, DAN PENGAJUAN DIPERIKSA DALAM SATU ALUR
        |--------------------------------------------------------------------------
        | Kebijakan: pengajuan hari libur tanpa GPS + selfie yang sah DITOLAK
        | seluruhnya, tidak ada surat lembur tanpa absen. Pemeriksaan bukti
        | dilakukan sebelum transaksi penulisan dibuka supaya tidak ada baris
        | pengajuan yang tertinggal saat bukti tidak memenuhi syarat.
        */
        $evidence = $this->punchEvidenceData($request);

        /*
        |--------------------------------------------------------------------------
        | HITUNG JAM LEMBUR (PEMBULATAN KE BAWAH PER 60 MENIT)
        |--------------------------------------------------------------------------
        | Total selisih menit dihitung dulu, lalu setiap 60 menit penuh dihitung
        | 1 jam dan sisa menit di bawah 60 tidak dihitung:
        | 60 menit -> 1 jam, 110 menit -> 1 jam, 120 menit -> 2 jam.
        | Durasi di bawah 60 menit ditolak agar surat tidak bernilai 0 jam.
        | Pada mode "sampai selesai" jam rencana kosong: volume baru terisi oleh
        | absen pulang (0 jam sementara waktu, lihat OvertimePunchService::finish).
        */
        $totalHours = 0;

        if (!$openEnded) {
            $start = Carbon::parse($request->start_time);
            $end = Carbon::parse($request->end_time);

            // Validasi jikalau lembur manual melewati tengah malam
            if ($end <= $start) {
                $end->addDay();
            }

            $totalMinutes = (int) $start->diffInMinutes($end);

            if ($totalMinutes < self::MIN_OVERTIME_MINUTES) {
                return back()
                    ->withErrors([
                        'end_time' => 'Durasi lembur minimal ' . self::MIN_OVERTIME_MINUTES . ' menit',
                    ])
                    ->withInput();
            }

            $totalHours = intdiv($totalMinutes, 60);
        }

        /*
        |--------------------------------------------------------------------------
        | APPROVAL FLOW
        |--------------------------------------------------------------------------
        */
        $userRole = $user->role;
        $flow = ApprovalFlowService::handle($userRole);

        /*
        |--------------------------------------------------------------------------
        | SIMPAN DATA AWAL + ABSEN MULAI (SATU KESATUAN KERJA)
        |--------------------------------------------------------------------------
        | Bila aturan absen menolak (tanggal, jendela waktu, atau bukti), surat
        | lemburnya ikut dibatalkan: tidak pernah ada SPL tanpa absen mulai.
        */
        try {
            if ($openEnded) {
                $this->punches->assertEvidence($evidence);
            }

            $overtime = DB::transaction(function () use (
                $request,
                $user,
                $flow,
                $totalHours,
                $openEnded,
                $evidence
            ) {
                $overtime = Overtime::create([
                    'user_id' => $user->id,
                    'department' => $user->role, // Menyimpan role pengaju sebagai department
                    'day_type' => $request->day_type,
                    'overtime_date' => $request->overtime_date,
                    'start_time' => $request->start_time,
                    'end_time' => $openEnded ? null : $request->end_time,
                    'total_hours' => $totalHours, // Jam penuh; sisa menit di bawah 60 tidak dihitung
                    'planned_hours' => $openEnded ? null : $totalHours,

                    'employee_signature' => $request->employee_signature,
                    'reason' => $request->reason,
                    // $flow membawa status global + seluruh kolom status stage approval
                ] + $flow);

                if ($openEnded) {
                    $this->punches->startOnSubmission($user, $overtime, $evidence);
                }

                return $overtime;
            });
        } catch (HttpException $exception) {
            return back()
                ->withErrors(['start_time' => $exception->getMessage()])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | PROSES GENERATE PDF (SINKRONISASI SURAT PERINTAH LEMBUR)
        |--------------------------------------------------------------------------
        */
        // Load template view pdf dengan mempassing data relasi user lengkap
        $pdf = Pdf::loadView('pdf.overtime-letter', [
            'overtime' => $overtime->load('user')
        ]);

        // Menentukan nama & lokasi file pdf jikalau ingin diunduh sewaktu-waktu
        $fileName = 'overtime-pdf/' . $overtime->id . '.pdf';

        // Simpan file biner PDF ke dalam public storage folder
        Storage::disk('public')->put($fileName, $pdf->output());

        // Update data jikalau Anda memiliki field `pdf_file` di tabel overtimes
        if (\Schema::hasColumn('overtimes', 'pdf_file')) {
            $overtime->update([
                'pdf_file' => $fileName
            ]);
        }

        return redirect('/dashboard')
            ->with(
                'success',
                $openEnded
                    ? 'Pengajuan lembur hari libur terkirim dan absen mulai tercatat pukul '
                        . $overtime->fresh()->actual_start_at->format('H:i')
                        . '. Jangan lupa menekan "Selesai Lembur" saat pulang.'
                    : 'Pengajuan lembur berhasil dikirim dan berkas PDF telah dibuat.'
            );
    }
    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD PDF FORM LEMBUR
    |--------------------------------------------------------------------------
    */
    public function downloadPdf($id)
    {
        $overtime = Overtime::findOrFail($id);

        if ($overtime->user_id !== auth()->id() && auth()->user()->role !== 'hrd' && auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $filePath = 'storage/' . $overtime->pdf_file;

        if ($overtime->pdf_file && file_exists(public_path($filePath))) {
            return response()->download(public_path($filePath), 'Surat_Lembur_' . $overtime->id . '.pdf');
        }

        $pdf = Pdf::loadView('pdf.overtime-letter', [
            'overtime' => $overtime->load('user')
        ]);

        return $pdf->stream('Surat_Lembur_' . $overtime->id . '.pdf');
    }

    /*
    |--------------------------------------------------------------------------
    | USER UBAH LEMBUR (REVISI SAAT PENDING / SETELAH DITOLAK)
    |--------------------------------------------------------------------------
    | Hanya pemilik pengajuan dan hanya selama status `pending` atau `rejected`.
    | Revisi dari pengajuan yang ditolak otomatis dikirim ulang ke tahap awal
    | approval; catatan penolakan lama tetap tersimpan untuk menu Riwayat.
    */
    public function update(Request $request, $id)
    {
        $overtime = Overtime::findOrFail($id);

        if ($overtime->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (!SubmissionStatus::isEditable($overtime)) {
            return back()->with(
                'error',
                'Pengajuan lembur yang sudah diverifikasi atau disetujui tidak dapat diubah lagi.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MODE "SAMPAI SELESAI" SAAT REVISI
        |--------------------------------------------------------------------------
        | Pengajuan tanpa Jam Berakhir (lembur hari libur) boleh disimpan ulang
        | tanpa Jam Berakhir; pengajuan biasa tetap mewajibkannya.
        */
        $openEnded = $this->punches->isOpenEnded($overtime) && blank($request->end_time);

        $request->validate([
            'overtime_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => $openEnded ? 'nullable' : 'required',
            'reason' => 'required|string',
            'day_type' => 'required|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | HITUNG ULANG VOLUME JAM (sama seperti store)
        |--------------------------------------------------------------------------
        | Setiap 60 menit penuh dihitung 1 jam; durasi di bawah 60 menit ditolak.
        | Pada mode "sampai selesai" tidak ada jam rencana: volume dibiarkan 0
        | sampai absen pulang tercatat.
        |--------------------------------------------------------------------------
        */
        $totalHours = 0;

        if (!$openEnded) {
            $start = Carbon::parse($request->start_time);
            $end = Carbon::parse($request->end_time);

            if ($end <= $start) {
                $end->addDay();
            }

            $totalMinutes = (int) $start->diffInMinutes($end);

            if ($totalMinutes < self::MIN_OVERTIME_MINUTES) {
                return back()
                    ->withErrors([
                        'end_time' => 'Durasi lembur minimal ' . self::MIN_OVERTIME_MINUTES . ' menit',
                    ])
                    ->withInput();
            }

            $totalHours = intdiv($totalMinutes, 60);
        }

        /*
        |--------------------------------------------------------------------------
        | JAM TERKUNCI BILA ABSEN REALTIME SUDAH BERJALAN
        |--------------------------------------------------------------------------
        | Setelah karyawan menekan "Mulai Lembur", rencana jam tidak boleh diubah
        | lagi karena jam nyata sudah menjadi dasar perhitungan volume.
        */
        if ($overtime->actual_start_at) {
            return back()->withErrors([
                'start_time' => 'Jam lembur terkunci karena absen realtime sudah berjalan pada '
                    . $overtime->actual_start_at->format('d-m-Y H:i') . '.',
            ])->withInput();
        }

        $wasRejected = $overtime->status === 'rejected';

        $overtime->update([
            'day_type' => $request->day_type,
            'overtime_date' => $request->overtime_date,
            'start_time' => $request->start_time,
            'end_time' => $openEnded ? null : $request->end_time,
            'total_hours' => $totalHours,
            'planned_hours' => $openEnded ? null : $totalHours,
            'reason' => $request->reason,
        ]);

        /*
        |--------------------------------------------------------------------------
        | KIRIM ULANG SETELAH PENOLAKAN
        |--------------------------------------------------------------------------
        */
        if ($wasRejected) {
            $overtime->update(ApprovalFlowService::handle(auth()->user()->role));
        }

        $overtime = $overtime->fresh();
        $overtime->load('user');

        /*
        |--------------------------------------------------------------------------
        | PERBARUI BERKAS PDF SURAT PERINTAH LEMBUR
        |--------------------------------------------------------------------------
        */
        $pdf = Pdf::loadView('pdf.overtime-letter', [
            'overtime' => $overtime
        ]);

        $fileName = 'overtime-pdf/' . $overtime->id . '.pdf';

        Storage::disk('public')->put($fileName, $pdf->output());

        if (\Schema::hasColumn('overtimes', 'pdf_file')) {
            $overtime->update([
                'pdf_file' => $fileName
            ]);
        }

        return back()->with(
            'success',
            $wasRejected
                ? 'Perubahan lembur tersimpan dan pengajuan dikirim ulang untuk persetujuan.'
                : 'Perubahan pengajuan lembur berhasil disimpan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ABSEN LEMBUR REALTIME (AJAX)
    |--------------------------------------------------------------------------
    | Bukti kehadiran = waktu SERVER + GPS + selfie. Seluruh aturan ada di
    | App\Services\OvertimePunchService agar bisa dipakai juga oleh jalur
    | absen wajah / perangkat tanpa menyalin aturannya.
    */

    /** Status sesi absen (dipakai timer kartu & penyegaran berkala). */
    public function activePunch()
    {
        return response()->json(
            $this->punches->state(auth()->user())
        );
    }

    /** Absen mulai lembur. */
    public function startPunch(Request $request, $id)
    {
        $user = auth()->user();

        $overtime = $this->punches->findOwned($user, (int) $id);

        $punch = $this->punches->start(
            $user,
            $overtime,
            $this->validatePunchEvidence($request) + [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Absen mulai lembur tercatat pukul '
                . $punch->punched_at->format('H:i:s') . '.',
            'state' => $this->punches->state($user),
        ]);
    }

    /**
     * Absen selesai lembur. Volume jam surat langsung dihitung dari jam nyata
     * (dibulatkan ke bawah per 60 menit) dan berkas surat diperbarui.
     */
    public function finishPunch(Request $request, $id)
    {
        $user = auth()->user();

        $overtime = $this->punches->findOwned($user, (int) $id);

        $this->punches->finish(
            $user,
            $overtime,
            $this->validatePunchEvidence($request) + [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        $overtime = $overtime->fresh();

        $this->syncPdf($overtime);

        return response()->json([
            'success' => true,
            'message' => 'Absen selesai lembur tercatat pukul '
                . $overtime->actual_end_at->format('H:i:s')
                . '. Volume lembur ' . $overtime->total_hours . ' jam ('
                . $overtime->actual_duration_label . ').',
            'total_hours' => (int) $overtime->total_hours,
            'state' => $this->punches->state($user),
        ]);
    }

    /**
     * Koreksi jam nyata oleh PJ/HRD (mis. karyawan lupa absen).
     * Log absen lama tetap utuh; hanya ringkasan & bukti yang diperbarui.
     */
    public function correctPunch(Request $request, $id)
    {
        $user = auth()->user();

        abort_unless(
            in_array($user->role, self::CORRECTOR_ROLES),
            403,
            'Hanya PJ/HRD yang dapat mengoreksi absen lembur.'
        );

        $overtime = Overtime::findOrFail($id);

        $data = $request->validate([
            'actual_start_at' => 'required|date',
            'actual_end_at' => 'required|date|after:actual_start_at',
            'note' => 'required|string|max:500',
        ]);

        $overtime = $this->punches->correct($overtime, $user, $data);

        $this->syncPdf($overtime);

        return back()->with(
            'success',
            'Koreksi tersimpan. Volume lembur menjadi ' . $overtime->total_hours
                . ' jam sesuai jam nyata ' . $overtime->actual_range_label . '.'
        );
    }

    /**
     * Aturan validasi form pengajuan. Pada mode "sampai selesai" bukti absen
     * (GPS + selfie) ikut menjadi syarat wajib karena absen mulai tercatat
     * bersamaan dengan pengiriman pengajuan.
     */
    private function storeRules(bool $openEnded): array
    {
        $rules = [
            'overtime_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => $openEnded ? 'nullable' : 'required',
            'reason' => 'required|string',
            'day_type' => 'required|string',
            'employee_signature' => 'required|string',
        ];

        return $openEnded ? $rules + $this->punchEvidenceRules() : $rules;
    }

    /** Aturan validasi bukti yang wajib menyertai setiap absen lembur. */
    private function punchEvidenceRules(): array
    {
        return [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'image' => (OvertimePunchService::SELFIE_REQUIRED
                ? 'required|string'
                : 'nullable|string'),
        ];
    }

    /** Isi request yang dipakai sebagai bukti absen + metadata log. */
    private function punchEvidenceData(Request $request): array
    {
        return $request->only(['latitude', 'longitude', 'accuracy', 'image']) + [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * Bukti yang wajib menyertai setiap absen lembur.
     */
    private function validatePunchEvidence(Request $request): array
    {
        return $request->validate($this->punchEvidenceRules());
    }

    /**
     * Perbarui berkas PDF surat perintah lembur agar volume pada surat selalu
     * sama dengan jam nyata hasil absen.
     */
    private function syncPdf(Overtime $overtime): void
    {
        $pdf = Pdf::loadView('pdf.overtime-letter', [
            'overtime' => $overtime->load('user'),
        ]);

        $fileName = 'overtime-pdf/' . $overtime->id . '.pdf';

        Storage::disk('public')->put($fileName, $pdf->output());

        if (\Schema::hasColumn('overtimes', 'pdf_file')) {
            $overtime->update([
                'pdf_file' => $fileName,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        $overtimes = Overtime::where('user_id', auth()->id())
            ->latest()
            ->get();

        // Nama approver diambil sekali untuk semua pengajuan (hindari N+1).
        SubmissionStatus::primeApproverNames($overtimes);

        return view('lembur-history', compact('overtimes'));
    }
}
