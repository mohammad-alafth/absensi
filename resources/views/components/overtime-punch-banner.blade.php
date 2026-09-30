@props(['state' => null])

{{--
|--------------------------------------------------------------------------
| PENANDA SESI LEMBUR BERJALAN (DASHBOARD)
|--------------------------------------------------------------------------
| Pengingat ringan agar absen "selesai" tidak terlupa; aksi absen tetap
| dilakukan pada halaman /lembur (butuh GPS + selfie).
--}}

@if(!empty($state['has_submission']) && !empty($state['running']))
<a href="{{ url('/lembur') }}"
   class="block mt-5 bg-emerald-50 border border-emerald-200 rounded-3xl p-4 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-bold text-emerald-800">
                ⏱️ Sesi lembur sedang berjalan
            </p>
            <p class="text-[11px] text-emerald-700 mt-0.5 truncate">
                Sejak {{ \Carbon\Carbon::parse($state['actual_start_at'])->format('H:i') }}
                &middot; {{ intdiv((int) $state['elapsed_seconds'], 3600) }} jam
                {{ intdiv((int) $state['elapsed_seconds'] % 3600, 60) }} menit
                &middot; jangan lupa absen selesai
            </p>
        </div>
        <span class="shrink-0 text-[10px] font-bold text-white bg-emerald-600 px-3 py-2 rounded-full">
            Buka
        </span>
    </div>
</a>
@endif
