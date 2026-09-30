<?php

namespace App\Services;

use App\Models\EmployeeShift;
use App\Models\Overtime;
use App\Models\OvertimePunch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| ABSEN LEMBUR REALTIME
|--------------------------------------------------------------------------
| Menyatukan aturan main "absen lembur" di satu tempat: jendela waktu boleh
| absen, validasi GPS + selfie, kunci anti absen ganda, dan perhitungan
| volume jam lembur dari jam nyata.
|
| Kebijakan yang berlaku (hasil kesepakatan):
| - Jam NYATA dari absen menjadi dasar volume jam lembur yang disahkan.
| - Jam pada form SPL hanya menjadi rencana (planned_hours).
| - Waktu absen selalu memakai waktu SERVER, bukan waktu perangkat.
|
| KAPAN ABSEN REALTIME DIPAKAI (gerbang kelayakan = method eligibility()):
| - Hari/tanggal yang TIDAK ada jadwal kerja reguler bagi user tsb
|   (office_5 pada Sabtu-Minggu, office_6 pada Minggu, atau user shift yang
|   tidak punya baris employee_shifts pada tanggal itu) ->Boleh, karena jalur
|   absen harian diblokir dengan pesan "Hari ini anda libur".
| - User shift yang lembur SEBELUM jam masuk shift-nya (mis. masuk siang
|   14:00 atau malam 22:00, lembur pagi 07:00-11:00) -> Boleh, karena absen
|   harian belum mencatat kehadirannya pada jam itu.
| - Jam lembur yang tumpang tindih dengan jam kerja reguler, atau yang berada
|   SETELAH shift selesai -> TIDAK boleh: kehadirannya sudah dibuktikan oleh
|   check-in/check-out absen harian, jadi volume tetap mengikuti SPL dan
|   jalur koreksi PJ/HRD bila perlu.
*/

class OvertimePunchService
{
    /*
    |--------------------------------------------------------------------------
    | ATURAN (UBAH DI SINI, BUKAN DI CONTROLLER)
    |--------------------------------------------------------------------------
    */

    /** Absen "mulai" boleh sedekat ini sebelum jam mulai rencana (menit). */
    public const PRE_WINDOW_MINUTES = 60;

    /** Absen masih diterima sedekat ini setelah jam selesai rencana (menit). */
    public const POST_WINDOW_MINUTES = 180;

    /**
     * Berapa hari ke depan kartu absen mencari pengajuan yang belum dibuka
     * jendela absennya, supaya karyawan melihat KAPAN absennya dibuka alih-alih
     * mengira fiturnya tidak muncul.
     */
    public const LOOKAHEAD_DAYS = 14;

    /** Maksimal jarak ke kantor (meter). Samakan dengan FaceController. */
    public const RADIUS_METERS = 200;

    /** Akurasi GPS maksimal yang masih diterima (meter). */
    public const MAX_ACCURACY_METERS = 200;

    /** Durasi nyata di bawah ini dianggap bukan lembur (volume 0 jam). */
    public const MIN_OVERTIME_MINUTES = 60;

    /** Pembulatan volume: setiap 60 menit penuh = 1 jam (sisa dibuang). */
    public const ROUNDING_MINUTES = 60;

    /**
     * Lembur "sampai selesai" (jam selesai dikosongkan pada form hari libur):
     * absen selesai paling lama diterima sekian menit sejak absen mulai.
     * Lewat itu sesi dianggap terlupa dan ditangani lewat koreksi PJ/HRD,
     * karena sesi tidak boleh dibiarkan berjalan tanpa batas.
     */
    public const OPEN_ENDED_MAX_MINUTES = 600;

    /** Foto selfie wajib untuk absen (bukti kehadiran seperti absen wajah). */
    public const SELFIE_REQUIRED = true;

    /**
     * Lembur yang seluruh jamnya berada SEBELUM jam masuk jadwal reguler boleh
     * diabsen realtime (mis. masuk siang/malam, lembur pagi).
     */
    public const ALLOW_BEFORE_SCHEDULE = true;

    /**
     * Lembur sesudah jadwal reguler selesai TIDAK diabsen realtime: kehadirannya
     * sudah tercatat pada check-out absen harian.
     */
    public const ALLOW_AFTER_SCHEDULE = false;

    /**
     * Tipe kerja yang boleh memakai celah "sebelum jam masuk".
     *
     * Hanya 'shift': bagi office_5/office_6, jam sebelum masuk masih tercakup
     * jendela check-in awal absen harian (2 jam sebelum masuk), sehingga absen
     * realtime khusus dipakai pada hari liburnya saja (Sabtu-Minggu / Minggu).
     */
    public const BEFORE_SCHEDULE_WORK_TYPES = ['shift'];

    /** Folder penyimpanan foto bukti pada disk public. */
    public const PHOTO_FOLDER = 'overtime-punches';

    /**
     * Koordinat kantor (RS Mata Pekanbaru Eye Center) — nilai sama seperti
     * yang dipakai FaceController pada absen wajah.
     */
    public const OFFICE_LAT = 0.4761258;

    public const OFFICE_LNG = 101.4190600;

    /*
    |--------------------------------------------------------------------------
    | BATAS WAKTU RENCANA
    |--------------------------------------------------------------------------
    */

    /** Batas mulai rencana lembur sebagai timestamp penuh. */
    public function plannedStart(Overtime $overtime): Carbon
    {
        return Carbon::parse($overtime->overtime_date . ' ' . $overtime->start_time);
    }

    /**
     * Lembur "sampai selesai": jam selesai sengaja dikosongkan pada form
     * (khas lembur hari libur) sehingga jam nyata dari absen pulang yang
     * menjadi patokan. Lihat App\Http\Controllers\OvertimeController::store().
     */
    public function isOpenEnded(Overtime $overtime): bool
    {
        return blank($overtime->end_time);
    }

    /**
     * Batas selesai rencana lembur. Bila jam selesai <= jam mulai berarti
     * lembur melewati tengah malam (sama seperti perhitungan di form SPL).
     * Pada lembur "sampai selesai" dipakai batas absen sejak absen mulai.
     */
    public function plannedEnd(Overtime $overtime): Carbon
    {
        $start = $this->plannedStart($overtime);

        if ($this->isOpenEnded($overtime)) {
            return $this->openEndedClosesAt($overtime);
        }

        $end = Carbon::parse($overtime->overtime_date . ' ' . $overtime->end_time);

        if ($end->lte($start)) {
            $end->addDay();
        }

        return $end;
    }

    /**
     * Batas akhir lembur "sampai selesai": dihitung dari absen mulai bila sudah
     * ada, jika belum dari jam mulai rencana. Ini sekaligus menjadi penutup
     * jendela absen selesai bagi sesi yang lupa diabsen pulang.
     */
    public function openEndedClosesAt(Overtime $overtime): Carbon
    {
        $from = $overtime->actual_start_at
            ? Carbon::parse($overtime->actual_start_at)
            : $this->plannedStart($overtime);

        return $from->copy()->addMinutes(self::OPEN_ENDED_MAX_MINUTES);
    }

    /** Rentang rencana untuk tampilan: "08:00 - 12:00" atau "08:00 - sampai selesai". */
    public function plannedLabel(Overtime $overtime): string
    {
        $start = $this->plannedStart($overtime)->format('H:i');

        if ($this->isOpenEnded($overtime)) {
            return $start . ' - sampai selesai';
        }

        return $start . ' - ' . $this->plannedEnd($overtime)->format('H:i');
    }

    /** Absen mulai dibuka sejak menit ini. */
    public function windowOpensAt(Overtime $overtime): Carbon
    {
        return $this->plannedStart($overtime)->copy()
            ->subMinutes(self::PRE_WINDOW_MINUTES);
    }

    /** Absen ditutup pada menit ini (berlaku untuk mulai maupun selesai). */
    public function windowClosesAt(Overtime $overtime): Carbon
    {
        if ($this->isOpenEnded($overtime)) {
            return $this->openEndedClosesAt($overtime);
        }

        return $this->plannedEnd($overtime)->copy()
            ->addMinutes(self::POST_WINDOW_MINUTES);
    }

    /*
    |--------------------------------------------------------------------------
    | PENCARIAN PENGAJUAN YANG BISA DIABSEN
    |--------------------------------------------------------------------------
    */

    /**
     * Semua pengajuan milik user yang sedang berada dalam jendela absen,
     * tanpa melihat kelayakan (dipakai untuk menjelaskan status pada kartu).
     *
     * Tanggal dicari sampai H-1 supaya lembur yang dimulai malam sebelumnya
     * (mis. 22:00 - 01:30) masih bisa diabsen selesai pada hari berikutnya.
     */
    public function windowSubmissions(User $user, ?Carbon $now = null)
    {
        $now = $now ? $now->copy() : Carbon::now();

        return Overtime::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected'])
            ->whereNull('actual_end_at')
            ->whereBetween('overtime_date', [
                $now->copy()->subDay()->toDateString(),
                $now->toDateString(),
            ])
            ->get()
            ->filter(function (Overtime $overtime) use ($now) {
                return $now->gte($this->windowOpensAt($overtime))
                    && $now->lte($this->windowClosesAt($overtime));
            })
            ->values();
    }

    /**
     * Pengajuan yang boleh diabsen realtime: sesi yang sedang berjalan, atau
     * pengajuan yang jamnya berada di luar jam kerja reguler user.
     */
    public function candidates(User $user, ?Carbon $now = null)
    {
        return $this->windowSubmissions($user, $now)
            ->filter(function (Overtime $overtime) use ($user) {
                // Sesi yang sudah berjalan tetap ditampilkan supaya absen
                // selesai masih bisa dilakukan walau jadwalnya berubah.
                if ($overtime->actual_start_at !== null) {
                    return true;
                }

                // Selain itu: hanya lembur di luar jam kerja reguler.
                return $this->eligibility($overtime, $user)['allowed'];
            })
            ->values();
    }

    /**
     * Pengajuan yang belum dibuka jendela absennya (tanggalnya akan datang),
     * tapi sudah pasti boleh diabsen realtime. Dipakai untuk memberi tahu
     * karyawan KAPAN absen dibuka, bukan hanya diam saat kartu tidak muncul.
     *
     * Yang dicari: tanggal hari ini sampai LOOKAHEAD_DAYS hari ke depan, belum
     * ditolak, belum diabsen mulai/selesai, jendela absennya masih di masa
     * depan, dan lolos gerbang kelayakan.
     */
    public function upcomingSubmissions(User $user, ?Carbon $now = null)
    {
        $now = $now ? $now->copy() : Carbon::now();

        return Overtime::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected'])
            ->whereNull('actual_start_at')
            ->whereNull('actual_end_at')
            ->whereBetween('overtime_date', [
                $now->copy()->toDateString(),
                $now->copy()->addDays(self::LOOKAHEAD_DAYS)->toDateString(),
            ])
            ->get()
            ->filter(function (Overtime $overtime) use ($user, $now) {
                return $now->lt($this->windowOpensAt($overtime))
                    && $this->eligibility($overtime, $user)['allowed'];
            })
            ->sortBy(fn (Overtime $overtime) => $this->windowOpensAt($overtime)->timestamp)
            ->values();
    }

    /**
     * Ringkasan pengajuan yang akan datang untuk kartu penjelasan.
     *
     * @return array<string, mixed>|null
     */
    public function upcoming(User $user, ?Carbon $now = null): ?array
    {
        $now = $now ? $now->copy() : Carbon::now();

        $overtime = $this->upcomingSubmissions($user, $now)->first();

        if (!$overtime) {
            return null;
        }

        $opens = $this->windowOpensAt($overtime);
        $closes = $this->windowClosesAt($overtime);

        return [
            'overtime_id' => $overtime->id,
            'overtime_date' => (string) $overtime->overtime_date,
            'day_label' => $this->dayLabel($opens),
            'planned' => $this->plannedLabel($overtime),
            'opens_at' => $opens->format('Y-m-d H:i:s'),
            'opens_label' => $this->dayLabel($opens) . ' pukul ' . $opens->format('H:i'),
            'closes_at' => $closes->format('Y-m-d H:i:s'),
            'closes_label' => $this->dayLabel($closes) . ' pukul ' . $closes->format('H:i'),
            'opens_in' => $this->humanLead($now, $opens),
        ];
    }

    /** Nama hari + tanggal, mis. "Sabtu, 03-10-2026" (tanpa bergantung locale). */
    public function dayLabel(Carbon $date): string
    {
        $days = [
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
        ];

        return $days[$date->dayOfWeek] . ', ' . $date->format('d-m-Y');
    }

    /** Jarak manusia ke jadwal berikutnya, mis. "4 hari" atau "3 jam 20 menit". */
    public function humanLead(Carbon $from, Carbon $to): string
    {
        $minutes = max(0, (int) $from->diffInMinutes($to, false));

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        if ($days > 0) {
            return $days . ' hari' . ($hours > 0 ? ' ' . $hours . ' jam' : '') . ' lagi';
        }

        if ($hours > 0) {
            return $hours . ' jam' . ($mins > 0 ? ' ' . $mins . ' menit' : '') . ' lagi';
        }

        return $mins . ' menit lagi';
    }

    /**
     * Pengajuan yang sedang aktif. Yang sudah diabsen mulai tapi belum
     * diabsen selesai diprioritaskan, supaya satu orang tidak membuka dua
     * sesi lembur bersamaan.
     */
    public function activeSubmission(User $user, ?Carbon $now = null): ?Overtime
    {
        $candidates = $this->candidates($user, $now);

        $running = $candidates->first(
            fn (Overtime $overtime) => $overtime->actual_start_at !== null
        );

        if ($running) {
            return $running;
        }

        return $candidates->sortBy(
            fn (Overtime $overtime) => $this->windowOpensAt($overtime)->timestamp
        )->first();
    }

    /** Ambil pengajuan milik user ini (tidak bisa mengakses punyanya orang lain). */
    public function findOwned(User $user, int $overtimeId): Overtime
    {
        return Overtime::where('id', $overtimeId)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | KELAYAKAN ABSEN REALTIME (ELIGIBILITY)
    |--------------------------------------------------------------------------
    | Absen realtime hanya untuk lembur yang kehadirannya TIDAK bisa dibuktikan
    | absen harian: tanggal tanpa jadwal kerja (libur/off) atau lembur sebelum
    | jam masuk shift (masuk siang/malam). Sisanya tetap alur SPL + absen harian.
    */

    /**
     * Jendela jam kerja reguler user pada suatu tanggal.
     *
     * @return array<int, array{start: Carbon, end: Carbon, label: string}>
     */
    public function scheduleWindows(User $user, Carbon $date): array
    {
        $day = $date->copy()->startOfDay();

        // office_5 / office_6: hari kerja ditentukan dari hari di kalender dan
        // ScheduleService sudah mengembalikan null pada hari liburnya
        // (office_5: Sabtu & Minggu, office_6: Minggu).
        if ($user->work_type !== 'shift') {
            $schedule = ScheduleService::getTodaySchedule($user, $day);

            if (!$schedule || empty($schedule['shift_start']) || empty($schedule['shift_end'])) {
                return [];
            }

            $start = Carbon::parse($schedule['shift_start']);
            $end = Carbon::parse($schedule['shift_end']);

            return [[
                'start' => $start,
                'end' => $end,
                'label' => ($schedule['shift_name'] ?? 'Kerja') . ' '
                    . $start->format('H:i') . ' - ' . $end->format('H:i'),
            ]];
        }

        $windows = [];

        // Shift pada tanggal lembur itu.
        foreach ($this->shiftRows($user, $day) as $row) {
            $window = $this->shiftWindow($row);

            if ($window) {
                $windows[] = $window;
            }
        }

        // Shift malam dari kemarin yang masih berjalan pagi ini (22:00 - 06:00).
        foreach ($this->shiftRows($user, $day->copy()->subDay()) as $row) {
            $window = $this->shiftWindow($row);

            if ($window && $window['end']->gt($day)) {
                $windows[] = $window;
            }
        }

        return $windows;
    }

    protected function shiftRows(User $user, Carbon $date)
    {
        return EmployeeShift::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $date->toDateString())
            ->get();
    }

    /** Jendela satu baris employee_shift; null bila jamnya tidak lengkap. */
    protected function shiftWindow(EmployeeShift $row): ?array
    {
        if (blank($row->start_time) || blank($row->end_time) || blank($row->shift_date)) {
            return null;
        }

        $start = Carbon::parse($row->shift_date . ' ' . $row->start_time);
        $end = Carbon::parse($row->shift_date . ' ' . $row->end_time);

        // Shift melewati tengah malam: jam selesai jatuh di hari berikutnya.
        // Ditentukan dari jamnya (sama seperti ScheduleService) karena flag
        // is_overnight tidak selalu tersimpan pada data lama.
        if ($end->lte($start)) {
            $end->addDay();
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => (optional($row->shift)->name ?? 'Shift') . ' '
                . $start->format('d-m H:i') . ' - ' . $end->format('d-m H:i'),
        ];
    }

    /**
     * Apakah pengajuan lembur ini boleh diabsen realtime?
     *
     * @return array{allowed: bool, code: string, reason: string, schedule: string|null}
     */
    public function eligibility(Overtime $overtime, ?User $user = null): array
    {
        $user = $user ?? $overtime->user;

        // Jadwal kerja tidak dikenal -> jangan menahan absen karyawan.
        if (!$user) {
            return [
                'allowed' => true,
                'code' => 'tanpa_jadwal',
                'reason' => 'Jadwal kerja tidak dikenal, absen lembur dibuka.',
                'schedule' => null,
            ];
        }

        $plannedStart = $this->plannedStart($overtime);
        $plannedEnd = $this->plannedEnd($overtime);

        $windows = $this->scheduleWindows($user, $plannedStart);

        if ($windows === []) {
            return [
                'allowed' => true,
                'code' => 'tanpa_jadwal',
                'reason' => 'Tidak ada jadwal kerja reguler pada '
                    . $plannedStart->format('d-m-Y')
                    . ' (hari libur / off), jadi absen realtime menjadi bukti kehadiran.',
                'schedule' => null,
            ];
        }

        usort($windows, fn (array $a, array $b) => $a['start'] <=> $b['start']);

        // (1) Jam lembur bersinggungan dengan jam kerja reguler.
        foreach ($windows as $window) {
            if ($plannedStart->lt($window['end']) && $plannedEnd->gt($window['start'])) {
                return [
                    'allowed' => false,
                    'code' => 'dalam_jadwal',
                    'reason' => 'Jam lembur ini bersinggungan dengan jam kerja reguler Anda ('
                        . $window['label'] . '). Bukti kehadiran sudah tercatat pada absen '
                        . 'harian, jadi absen lembur realtime tidak digunakan.',
                    'schedule' => $window['label'],
                ];
            }
        }

        // (2) Lembur seluruhnya berada sebelum jam masuk shift berikutnya.
        foreach ($windows as $window) {
            if ($window['end']->gt($plannedEnd) && $plannedEnd->lte($window['start'])) {
                $boleh = self::ALLOW_BEFORE_SCHEDULE
                    && in_array((string) $user->work_type, self::BEFORE_SCHEDULE_WORK_TYPES, true);

                if ($boleh) {
                    return [
                        'allowed' => true,
                        'code' => 'sebelum_jadwal',
                        'reason' => 'Lembur dikerjakan sebelum jam masuk reguler Anda ('
                            . $window['label'] . '), jadi absen realtime menjadi bukti kehadiran.',
                        'schedule' => $window['label'],
                    ];
                }

                return [
                    'allowed' => false,
                    'code' => 'sebelum_jadwal_tidak_dijinkan',
                    'reason' => 'Jam kerja reguler Anda pada tanggal ini adalah '
                        . $window['label'] . '. Untuk karyawan office, absen lembur realtime '
                        . 'hanya dipakai pada hari libur (Sabtu/Minggu) atau tanggal tanpa jadwal.',
                    'schedule' => $window['label'],
                ];
            }
        }

        // (3) Lembur sesudah jam kerja reguler selesai.
        $last = end($windows);

        if (self::ALLOW_AFTER_SCHEDULE) {
            return [
                'allowed' => true,
                'code' => 'setelah_jadwal',
                'reason' => 'Lembur dikerjakan setelah jam kerja reguler ('
                    . $last['label'] . ') selesai.',
                'schedule' => $last['label'],
            ];
        }

        return [
            'allowed' => false,
            'code' => 'setelah_jadwal',
            'reason' => 'Lembur ini berada setelah jam kerja reguler Anda (' . $last['label']
                . ') selesai. Bukti kehadiran mengikuti check-out absen harian, jadi absen '
                . 'lembur realtime tidak digunakan. Bila jam sebenarnya berbeda, minta '
                . 'koreksi PJ/HRD.',
            'schedule' => $last['label'],
        ];
    }

    /** Tolak absen bila lembur ini seharusnya tidak memakai absen realtime. */
    public function guardPunchable(Overtime $overtime, ?User $user = null): void
    {
        $eligibility = $this->eligibility($overtime, $user);

        if (!$eligibility['allowed']) {
            abort(403, $eligibility['reason']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GERBANG (GUARD)
    |--------------------------------------------------------------------------
    | Setiap gagal absen diberi pesan yang bisa langsung dipahami karyawan.
    | Pada panggilan AJAX Laravel mengubahnya menjadi JSON
    | { "message": "..." } dengan status 4xx.
    */

    public function guardStartable(Overtime $overtime, ?Carbon $now = null, ?User $user = null): void
    {
        $now = $now ? $now->copy() : Carbon::now();

        if ($overtime->status === 'rejected') {
            abort(403, 'Pengajuan lembur ini sudah ditolak, absen tidak tersedia.');
        }

        if ($overtime->actual_start_at) {
            abort(422, 'Absen mulai lembur sudah tercatat pada '
                . $overtime->actual_start_at->format('d-m-Y H:i') . '.');
        }

        // Hanya lembur di luar jam kerja reguler yang boleh diabsen realtime.
        $this->guardPunchable($overtime, $user);

        if ($now->lt($this->windowOpensAt($overtime))) {
            abort(422, 'Absen lembur belum dibuka (baru dibuka '
                . self::PRE_WINDOW_MINUTES . ' menit sebelum jam lembur, yaitu '
                . $this->windowOpensAt($overtime)->format('d-m-Y H:i') . ').');
        }

        if ($now->gt($this->windowClosesAt($overtime))) {
            abort(422, 'Absen lembur sudah ditutup (tutup '
                . $this->windowClosesAt($overtime)->format('d-m-Y H:i')
                . '). Hubungi PJ untuk koreksi.');
        }
    }

    public function guardFinishable(Overtime $overtime, ?Carbon $now = null): void
    {
        $now = $now ? $now->copy() : Carbon::now();

        if (!$overtime->actual_start_at) {
            abort(422, 'Absen selesai tidak bisa diproses karena absen mulai belum dilakukan.');
        }

        if ($overtime->actual_end_at) {
            abort(422, 'Absen selesai lembur sudah tercatat pada '
                . $overtime->actual_end_at->format('d-m-Y H:i') . '.');
        }

        if ($now->lt($overtime->actual_start_at)) {
            abort(422, 'Absen selesai tidak boleh lebih awal dari absen mulai.');
        }

        if ($now->gt($this->windowClosesAt($overtime))) {
            abort(422, 'Absen lembur sudah ditutup (tutup '
                . $this->windowClosesAt($overtime)->format('d-m-Y H:i')
                . '). Hubungi PJ untuk koreksi.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ABSEN MULAI
    |--------------------------------------------------------------------------
    */
    public function start(User $user, Overtime $overtime, array $data): OvertimePunch
    {
        /*
        |--------------------------------------------------------------------------
        | WAKTU SERVER DIKUNCI DI AWAL
        |--------------------------------------------------------------------------
        | Waktu yang dikirim klien selalu diabaikan; yang dipakai hanya
        | koordinat, akurasi GPS, dan foto bukti.
        */
        $now = Carbon::now();

        $this->guardStartable($overtime, $now, $user);

        $evidence = $this->guardEvidence($data);

        return DB::transaction(function () use ($user, $overtime, $now, $evidence, $data) {

            $locked = Overtime::whereKey($overtime->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Dicek ulang di dalam transaksi (mencegah dua tab/klik ganda).
            $this->guardStartable($locked, $now, $user);

            $punch = $this->log($locked, $user, OvertimePunch::TYPE_START, $now, $evidence, $data);

            $locked->update([
                'actual_start_at' => $now,
                'proof_type' => 'realtime',
                'proof_note' => null,
                'proof_corrected_by' => null,
            ]);

            return $punch;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ABSEN SELESAI + HITUNG VOLUME JAM DARI JAM NYATA
    |--------------------------------------------------------------------------
    */
    public function finish(User $user, Overtime $overtime, array $data): OvertimePunch
    {
        $now = Carbon::now();

        $this->guardFinishable($overtime, $now);

        $evidence = $this->guardEvidence($data);

        return DB::transaction(function () use ($user, $overtime, $now, $evidence, $data) {

            $locked = Overtime::whereKey($overtime->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardFinishable($locked, $now);

            $punch = $this->log($locked, $user, OvertimePunch::TYPE_END, $now, $evidence, $data);

            $minutes = (int) $locked->actual_start_at->diffInMinutes($now);

            $locked->update(
                $this->volumeAttributes($locked, [
                    'actual_end_at' => $now,
                    'actual_minutes' => $minutes,
                    'proof_type' => 'realtime',
                    'proof_note' => null,
                    'proof_corrected_by' => null,
                ])
            );

            return $punch;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | KOREKSI OLEH PJ / HRD (SAAT KARYAWAN LUPA ABSEN)
    |--------------------------------------------------------------------------
    | Log absen lama tidak dihapus (audit tetap utuh); yang ditimpa hanya
    | ringkasan actual_* dan bukti ditandai "koreksi" agar terlihat pada
    | layar approval.
    */
    public function correct(Overtime $overtime, User $corrector, array $data): Overtime
    {
        $start = Carbon::parse($data['actual_start_at']);

        $end = Carbon::parse($data['actual_end_at']);

        if ($end->lte($start)) {
            $end->addDay();
        }

        $minutes = (int) $start->diffInMinutes($end);

        if ($minutes <= 0) {
            abort(422, 'Jam selesai koreksi harus setelah jam mulai.');
        }

        if (blank($data['note'] ?? null)) {
            abort(422, 'Catatan alasan koreksi wajib diisi.');
        }

        DB::transaction(function () use ($overtime, $corrector, $start, $end, $minutes, $data) {

            OvertimePunch::create([
                'overtime_id' => $overtime->id,
                'user_id' => $overtime->user_id,
                'type' => OvertimePunch::TYPE_CORRECTION,
                'punched_at' => $end,
                'source' => 'koreksi_pj',
                'note' => 'Koreksi: mulai ' . $start->format('d-m-Y H:i')
                    . ' s/d selesai ' . $end->format('d-m-Y H:i')
                    . ' — ' . $data['note'],
            ]);

            $overtime->update(
                $this->volumeAttributes($overtime, [
                    'actual_start_at' => $start,
                    'actual_end_at' => $end,
                    'actual_minutes' => $minutes,
                    'proof_type' => 'koreksi',
                    'proof_note' => $data['note'],
                    'proof_corrected_by' => $corrector->id,
                ])
            );
        });

        return $overtime->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | PERHITUNGAN VOLUME JAM
    |--------------------------------------------------------------------------
    | Jam nyata dibulatkan ke bawah per 60 menit (aturan lama dipertahankan):
    | 250 menit -> 4 jam. Durasi di bawah 60 menit menjadi 0 jam, dan bersama
    | kasus jam nyata melebihi rencana ditandai perlu ditinjau (needs_review).
    */
    public static function hours(int $minutes): int
    {
        return intdiv($minutes, self::ROUNDING_MINUTES);
    }

    public function volumeAttributes(Overtime $overtime, array $attributes): array
    {
        if (!array_key_exists('actual_minutes', $attributes)) {
            return $attributes;
        }

        $minutes = (int) $attributes['actual_minutes'];

        $hours = self::hours($minutes);

        /*
        | Rencana hanya ada bila pengajuan mengisi Jam Berakhir. Pada lembur
        | "sampai selesai" (end_time NULL, planned_hours NULL) tidak ada angka
        | yang bisa dilewati, jadi volume nyata tidak dibandingkan dengan rencana.
        */
        $plannedHours = $overtime->planned_hours === null
            ? null
            : (int) $overtime->planned_hours;

        $underMinimum = $minutes < self::MIN_OVERTIME_MINUTES;

        return $attributes + [
            'actual_hours' => $hours,

            /*
            | total_hours tetap menjadi "volume sah" yang dibaca surat dan
            | rekap, tetapi isinya kini turunan dari jam nyata.
            */
            'total_hours' => $underMinimum ? 0 : $hours,

            'needs_review' => $underMinimum
                || ($plannedHours !== null && $hours > $plannedHours),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI BUKTI (GPS + SELFIE)
    |--------------------------------------------------------------------------
    */
    protected function guardEvidence(array $data): array
    {
        $this->assertEvidence($data);

        $distance = $this->distanceTo(
            (float) $data['latitude'],
            (float) $data['longitude']
        );

        return [
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'accuracy' => isset($data['accuracy'])
                ? (float) $data['accuracy']
                : null,
            'distance_m' => round($distance, 2),
            'photo' => $this->storePhoto(
                $data['image'] ?? null,
                (int) ($data['user_id'] ?? 0)
            ),
        ];
    }

    /**
     * Periksa bukti absen tanpa menyimpan apa pun. Dipakai jalur pengajuan
     * (form SPL) yang harus menolak data sebelum satu barispun ditulis, dan
     * oleh absen biasa melalui guardEvidence().
     */
    public function assertEvidence(array $data): void
    {
        if (self::SELFIE_REQUIRED && blank($data['image'] ?? null)) {
            abort(422, 'Foto selfie wajib disertakan sebagai bukti absen lembur.');
        }

        if (blank($data['latitude'] ?? null) || blank($data['longitude'] ?? null)) {
            abort(422, 'Lokasi (GPS) wajib aktif saat absen lembur.');
        }

        if (!empty($data['accuracy'])
            && (float) $data['accuracy'] > self::MAX_ACCURACY_METERS) {
            abort(403, 'GPS tidak akurat (' . round((float) $data['accuracy'])
                . ' meter), aktifkan GPS lalu coba lagi.');
        }

        $distance = $this->distanceTo(
            (float) $data['latitude'],
            (float) $data['longitude']
        );

        if ($distance > self::RADIUS_METERS) {
            abort(403, 'Anda berada di luar radius kantor ('
                . round($distance, 2) . ' meter dari batas '
                . self::RADIUS_METERS . ' meter).');
        }
    }
    /*
    |--------------------------------------------------------------------------
    | LEMBUR HARI LIBUR: HANYA JAM MULAI + ABSEN SAAT KIRIM
    |--------------------------------------------------------------------------
    | Khas lembur hari libur: tidak ada yang perlu menebak jam selesai. Form
    | hanya mengisi Jam Mulai, dan tombol "Kirim Pengajuan" sekaligus mencatat
    | absen mulai realtime (GPS + selfie wajib). Jam selesai — sekaligus volume
    | jam lembur — diambil dari absen pulang (batas: OPEN_ENDED_MAX_MINUTES).
    */

    /**
     * Kondisi untuk form: bolehkah mode "sampai selesai" dipakai hari ini?
     * Syaratnya tanggal ini benar-benar tidak punya jadwal kerja reguler,
     * sehingga kehadiran memang hanya bisa dibuktikan lewat absen lembur.
     *
     * @return array{available: bool, code: string, reason: string, date: string}
     */
    public function openEndedAvailability(User $user, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : Carbon::now();

        $windows = $this->scheduleWindows($user, $now);

        if ($windows !== []) {
            usort($windows, fn (array $a, array $b) => $a['start'] <=> $b['start']);

            return [
                'available' => false,
                'code' => 'hari_kerja',
                'reason' => 'Hari ini masih ada jadwal kerja reguler ('
                    . $windows[0]['label'] . '), jadi pengajuan lembur tetap mengisi '
                    . 'Jam Mulai dan Jam Berakhir seperti biasa.',
                'date' => $now->toDateString(),
            ];
        }

        return [
            'available' => true,
            'code' => 'tanpa_jadwal',
            'reason' => 'Tidak ada jadwal kerja reguler pada '
                . $now->format('d-m-Y') . ' (hari libur / off), jadi cukup isi Jam '
                . 'Mulai. Jam selesai diambil dari absen pulang Anda.',
            'date' => $now->toDateString(),
        ];
    }

    /**
     * Tolak pengajuan "sampai selesai" yang tidak memenuhi aturannya; setiap
     * pesan menjelaskan apa yang harus dilakukan karyawan.
     *
     * @return array{opens_at: Carbon, closes_at: Carbon}
     */
    public function guardOpenEndedSubmission(Overtime $overtime, User $user, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : Carbon::now();

        $availability = $this->openEndedAvailability($user, $now);

        if (!$availability['available']) {
            abort(422, $availability['reason']);
        }

        if (Carbon::parse($overtime->overtime_date)->toDateString() !== $now->toDateString()) {
            abort(422, 'Mode "sampai selesai" hanya untuk lembur tanggal hari ini ('
                . $now->format('d-m-Y') . ') karena absen mulai ikut tercatat saat '
                . 'pengajuan dikirim. Untuk tanggal lain, isi Jam Berakhir terlebih dahulu.');
        }

        $opens = $this->windowOpensAt($overtime);
        $closes = $this->openEndedClosesAt($overtime);

        if ($now->lt($opens)) {
            abort(422, 'Absen mulai baru dibuka ' . self::PRE_WINDOW_MINUTES
                . ' menit sebelum jam lembur, yaitu ' . $opens->format('d-m-Y H:i')
                . '. Kirim pengajuan pada jam tersebut agar absennya langsung tercatat.');
        }

        if ($now->gt($closes)) {
            abort(422, 'Jam mulai ' . $this->plannedStart($overtime)->format('H:i')
                . ' sudah terlalu jauh (lewat ' . $closes->format('H:i')
                . '). Isi Jam Berakhir atau minta koreksi PJ/HRD.');
        }

        return ['opens_at' => $opens, 'closes_at' => $closes];
    }



    /**
     * Catat absen mulai sebagai bagian dari pengiriman pengajuan. Bedanya
     * dengan start(): tanggal, kelayakan, dan jendela absen sudah dipastikan
     * guardOpenEndedSubmission(), dan sesinya dimulai "sekarang juga".
     */
    public function startOnSubmission(User $user, Overtime $overtime, array $data): OvertimePunch
    {
        $now = Carbon::now();

        $this->guardOpenEndedSubmission($overtime, $user, $now);

        $evidence = $this->guardEvidence($data);

        return DB::transaction(function () use ($user, $overtime, $now, $evidence, $data) {

            $locked = Overtime::whereKey($overtime->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->actual_start_at) {
                abort(422, 'Absen mulai lembur sudah tercatat pada '
                    . $locked->actual_start_at->format('d-m-Y H:i') . '.');
            }

            $punch = $this->log(
                $locked,
                $user,
                OvertimePunch::TYPE_START,
                $now,
                $evidence,
                array_merge($data, [
                    'note' => 'Absen mulai tercatat bersamaan dengan pengiriman pengajuan lembur.',
                ])
            );

            $locked->update([
                'actual_start_at' => $now,
                'proof_type' => 'realtime',
                'proof_note' => null,
                'proof_corrected_by' => null,
            ]);

            return $punch;
        });
    }

    /**
     * Simpan foto base64 menjadi berkas jpg pada disk public.
     */
    protected function storePhoto(?string $image, int $userId): ?string
    {
        if (blank($image)) {
            return null;
        }

        $base64 = str_replace('data:image/jpeg;base64,', '', $image);

        $base64 = str_replace('data:image/png;base64,', '', $base64);

        $base64 = str_replace(' ', '+', $base64);

        $binary = base64_decode($base64, true);

        if ($binary === false || $binary === '') {
            abort(422, 'Foto bukti tidak valid, ambil ulang foto.');
        }

        $fileName = self::PHOTO_FOLDER . '/' . $userId . '_'
            . now()->format('YmdHis') . '_' . uniqid() . '.jpg';

        Storage::disk('public')->put($fileName, $binary);

        return $fileName;
    }

    /**
     * Jarak haversine dari kantor dalam meter (rumus sama seperti FaceController).
     */
    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 637200;

        $dLat = deg2rad($lat - self::OFFICE_LAT);

        $dLon = deg2rad($lng - self::OFFICE_LNG);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad(self::OFFICE_LAT))
            * cos(deg2rad($lat))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /*
    |--------------------------------------------------------------------------
    | TULIS LOG ABSEN
    |--------------------------------------------------------------------------
    */
    protected function log(
        Overtime $overtime,
        User $user,
        string $type,
        Carbon $at,
        array $evidence,
        array $data
    ): OvertimePunch {
        return OvertimePunch::create([
            'overtime_id' => $overtime->id,
            'user_id' => $user->id,
            'type' => $type,
            'punched_at' => $at,
            'latitude' => $evidence['latitude'] ?? null,
            'longitude' => $evidence['longitude'] ?? null,
            'accuracy' => $evidence['accuracy'] ?? null,
            'distance_m' => $evidence['distance_m'] ?? null,
            'photo' => $evidence['photo'] ?? null,
            'source' => $data['source'] ?? 'web',
            'note' => $data['note'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => isset($data['user_agent'])
                ? substr((string) $data['user_agent'], 0, 255)
                : null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS UNTUK KARTU ABSEN / API
    |--------------------------------------------------------------------------
    */
    public function state(User $user, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : Carbon::now();

        $overtime = $this->activeSubmission($user, $now);

        if (!$overtime) {
            /*
            |------------------------------------------------------------------
            | Tidak ada sesi yang bisa diabsen. Bila penyebabnya jadwal reguler
            | (jam lembur masih di dalam / setelah jam kerja), jelaskan
            | alasannya supaya karyawan tidak mengira fitur ini rusak.
            |------------------------------------------------------------------
            */
            $blocked = null;

            foreach ($this->windowSubmissions($user, $now) as $candidate) {
                $eligibility = $this->eligibility($candidate, $user);

                if (!$eligibility['allowed']) {
                    $blocked = array_merge($eligibility, [
                        'overtime_id' => $candidate->id,
                        'overtime_date' => (string) $candidate->overtime_date,
                        'planned' => $this->plannedLabel($candidate),
                    ]);

                    break;
                }
            }

            /*
            |------------------------------------------------------------------
            | Pengajuan yang akan datang: kartu absen sengaja baru muncul saat
            | jendela absennya tiba, jadi beri tahu kapan jamnya dibuka.
            |------------------------------------------------------------------
            */
            $upcoming = $this->upcoming($user, $now);

            $message = 'Tidak ada pengajuan lembur yang sedang dalam jendela absen.';

            if ($blocked) {
                $message = $blocked['reason'];
            } elseif ($upcoming) {
                $message = 'Absen lembur realtime untuk lembur '
                    . $upcoming['overtime_date'] . ' ' . $upcoming['planned']
                    . ' baru dibuka ' . $upcoming['opens_label']
                    . ' (' . $upcoming['opens_in'] . ').';
            }

            return [
                'has_submission' => false,
                'blocked' => $blocked,
                'upcoming' => $upcoming,
                'server_time' => $now->format('Y-m-d H:i:s'),
                'message' => $message,
            ];
        }

        $eligibility = $this->eligibility($overtime, $user);

        $running = $overtime->actual_start_at !== null
            && $overtime->actual_end_at === null;

        $elapsed = 0;

        if ($overtime->actual_start_at && $now->gt($overtime->actual_start_at)) {
            $until = $running ? $now : $overtime->actual_end_at;

            $elapsed = (int) $overtime->actual_start_at->diffInSeconds($until);
        }

        return [
            'has_submission' => true,
            'overtime_id' => $overtime->id,
            'overtime_date' => (string) $overtime->overtime_date,
            'planned' => $this->plannedLabel($overtime),
            'open_ended' => $this->isOpenEnded($overtime),
            'actual_start_at' => $overtime->actual_start_at?->format('Y-m-d H:i:s'),
            'actual_end_at' => $overtime->actual_end_at?->format('Y-m-d H:i:s'),
            'elapsed_seconds' => $elapsed,
            'running' => $running,
            'finished' => $overtime->actual_end_at !== null,
            'can_start' => $overtime->actual_start_at === null,
            'can_finish' => $overtime->actual_start_at !== null
                && $overtime->actual_end_at === null,
            'window_open_at' => $this->windowOpensAt($overtime)->format('Y-m-d H:i:s'),
            'window_close_at' => $this->windowClosesAt($overtime)->format('Y-m-d H:i:s'),
            'proof_type' => $overtime->proof_type,
            'eligible' => $eligibility['allowed'],
            'eligibility_code' => $eligibility['code'],
            'eligibility_reason' => $eligibility['reason'],
            'regular_schedule' => $eligibility['schedule'],
            'total_hours' => (int) $overtime->total_hours,
            'actual_minutes' => $overtime->actual_minutes !== null
                ? (int) $overtime->actual_minutes
                : null,
            'server_time' => $now->format('Y-m-d H:i:s'),
        ];
    }
}





