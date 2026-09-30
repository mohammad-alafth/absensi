<?php

namespace App\Console\Commands;

use App\Models\Overtime;
use App\Services\OvertimePunchService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/*
|--------------------------------------------------------------------------
| DAFTAR LEMBUR YANG BELUM DIABSEN SELESAI
|--------------------------------------------------------------------------
| Pembantu operasional PJ/HRD: mencari pengajuan lembur yang jam rencananya
| sudah lewat tetapi tidak memiliki absen selesai, sehingga bisa dikoreksi
| manual sebelum rekap gaji dibuat. Tidak mengubah data apa pun.
|
| Lembur "sampai selesai" (end_time NULL) ikut diperiksa: batas rencananya
| dihitung OvertimePunchService::plannedEnd() = absen mulai + 600 menit, jadi
| karyawan yang lupa menekan "Selesai Lembur" tetap muncul di daftar ini.
*/

class RemindOvertimePunch extends Command
{
    protected $signature = 'lembur:ingatkan-absen';

    protected $description = 'Daftar pengajuan lembur realtime yang melewati jam rencana tanpa absen selesai';

    public function handle(OvertimePunchService $punches): int
    {
        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | CANDIDAT 3 HARI TERAKHIR (lembur malam selesai lewat tengah malam)
        |--------------------------------------------------------------------------
        | Diberi kelonggaran 30 menit agar tidak menandai sesi yang masih berjalan.
        */
        $candidates = Overtime::whereNotIn('status', ['rejected'])
            ->whereNull('actual_end_at')
            ->whereBetween('overtime_date', [
                $now->copy()->subDays(3)->toDateString(),
                $now->toDateString(),
            ])
            ->with('user')
            ->get()
            ->filter(function (Overtime $overtime) use ($punches, $now) {
                /*
                |------------------------------------------------------------------
                | Hanya lembur yang memang memakai absen realtime. Jam lembur yang
                | bersinggungan dengan atau berada setelah jam kerja reguler tidak
                | akan pernah punya absen realtime (kehadirannya lewat absen
                | harian), jadi tidak perlu diingatkan di sini.
                |------------------------------------------------------------------
                */
                return $now->gt($punches->plannedEnd($overtime)->addMinutes(30))
                    && $punches->eligibility($overtime, $overtime->user)['allowed'];
            });

        if ($candidates->isEmpty()) {
            $this->info('Aman: tidak ada lembur realtime tanpa absen selesai dalam 3 hari terakhir.');

            return self::SUCCESS;
        }

        $this->warn($candidates->count() . ' pengajuan lembur realtime melewati jam rencana tanpa absen selesai:');

        foreach ($candidates as $overtime) {
            $this->line(sprintf(
                ' - #%d %s | %s | %s | volume: %s | bukti: %s',
                $overtime->id,
                $overtime->user->name ?? '-',
                $overtime->overtime_date,
                $overtime->planned_range_label,
                $overtime->hours_label,
                $overtime->proof_label
            ));
        }

        logger()->warning('Lembur tanpa absen selesai', [
            'jumlah' => $candidates->count(),
            'id' => $candidates->pluck('id')->all(),
        ]);

        return self::SUCCESS;
    }
}
