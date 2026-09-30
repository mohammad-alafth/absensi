@props(['item'])

@php
/*
|--------------------------------------------------------------------------
| BUKTI KEHADIRAN LEMBUR
|--------------------------------------------------------------------------
| Menampilkan jam nyata hasil absen (bukan jam rencana), foto bukti, serta
| form koreksi untuk PJ/HRD ketika karyawan lupa absen.
*/
$isCorrector = auth()->check()
    && in_array(auth()->user()->role, ['pj', 'hrd', 'director', 'admin']);

$punches = $item->relationLoaded('punches') ? $item->punches : $item->punches;

$plannedStart = \Carbon\Carbon::parse($item->overtime_date . ' ' . ($item->start_time ?: '00:00'));

/*
| Jam rencana selesai boleh kosong (lembur hari libur mode "sampai selesai").
| Untuk form koreksi, usulan awal dipakai 1 jam setelah jam mulai.
*/
$plannedEnd = $item->end_time
    ? \Carbon\Carbon::parse($item->overtime_date . ' ' . $item->end_time)
    : $plannedStart->copy()->addHour();

if ($plannedEnd->lte($plannedStart)) {
    $plannedEnd->addDay();
}
@endphp

<div {{ $attributes->merge(['class' => 'mt-4 border rounded-2xl p-4 ' . ($item->needs_review ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200 bg-slate-50/60')]) }}>

    <div class="flex items-center justify-between gap-2 flex-wrap">
        <p class="text-sm font-bold text-slate-700">Kehadiran Nyata (Absen Lembur)</p>

        <span class="text-[10px] font-bold border px-2 py-1 rounded-full {{ $item->proof_tone }}">
            {{ $item->proof_label }}
        </span>
    </div>

    @if($item->actual_start_at)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-xs">
        <div class="bg-white border rounded-xl px-3 py-2">
            <p class="text-gray-400">Mulai Nyata</p>
            <p class="font-bold text-gray-800 mt-0.5">{{ $item->actual_start_at->format('d M Y H:i') }}</p>
        </div>
        <div class="bg-white border rounded-xl px-3 py-2">
            <p class="text-gray-400">Selesai Nyata</p>
            <p class="font-bold text-gray-800 mt-0.5">
                {{ $item->actual_end_at ? $item->actual_end_at->format('d M Y H:i') : 'masih berjalan' }}
            </p>
        </div>
        <div class="bg-white border rounded-xl px-3 py-2">
            <p class="text-gray-400">Durasi Nyata</p>
            <p class="font-bold text-gray-800 mt-0.5">{{ $item->actual_duration_label ?? '-' }}</p>
        </div>
        <div class="bg-white border rounded-xl px-3 py-2">
            <p class="text-gray-400">Volume Disahkan</p>
            <p class="font-bold text-emerald-700 mt-0.5">
                {{ $item->total_hours }} jam
                <span class="text-[10px] font-medium text-gray-400">
                    (rencana {{ $item->planned_hours !== null ? $item->planned_hours . ' jam' : 'sampai selesai' }})
                </span>
            </p>
        </div>
    </div>

    @if($punches->where('photo', '!=', null)->isNotEmpty())
    <div class="flex items-center gap-2 mt-3 flex-wrap">
        <p class="text-[11px] text-gray-500 w-full">Foto bukti absen:</p>
        @foreach($punches->where('photo', '!=', null) as $punch)
        <a href="{{ $punch->photo_url }}" target="_blank" class="text-center">
            <img src="{{ $punch->photo_url }}" alt="Foto {{ $punch->type_label }}"
                 class="w-16 h-16 object-cover rounded-xl border border-slate-200">
            <span class="block text-[9px] text-gray-400 mt-0.5">
                {{ $punch->type_label }} {{ $punch->punched_at->format('H:i') }}
            </span>
        </a>
        @endforeach
    </div>
    @endif

    @if($item->proof_note)
    <p class="text-[11px] text-amber-700 mt-3 bg-white border border-amber-200 rounded-xl px-3 py-2">
        Catatan koreksi ({{ $item->proofCorrector->name ?? 'PJ/HRD' }}): {{ $item->proof_note }}
    </p>
    @endif
    @else
    <p class="text-[11px] text-gray-500 mt-2">
        @if($item->planned_hours !== null)
        Belum ada absen realtime untuk pengajuan ini, sehingga volume masih mengikuti jam rencana
        ({{ $item->planned_hours }} jam). Gunakan form koreksi di bawah bila karyawan terbukti
        lembur tetapi lupa absen.
        @else
        Lembur hari libur ini berjalan "sampai selesai": volume jam baru terisi setelah absen
        "Selesai Lembur" tercatat. Gunakan form koreksi di bawah bila karyawan lupa absen.
        @endif
    </p>
    @endif

    @if($item->needs_review)
    <p class="text-[11px] font-semibold text-amber-700 mt-3">
        ⚠️ Perlu ditinjau: durasi nyata di bawah 1 jam atau melebihi jam yang direncanakan.
    </p>
    @endif

    @if($isCorrector)
    <form method="POST" action="{{ route('lembur.punch.correct', $item->id) }}"
          class="mt-4 pt-4 border-t border-dashed grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
        @csrf

        <div>
            <label class="block text-[11px] text-gray-500 mb-1">Jam Nyata Mulai</label>
            <input type="datetime-local" name="actual_start_at" required
                   value="{{ ($item->actual_start_at ?? $plannedStart)->format('Y-m-d\TH:i') }}"
                   class="w-full border rounded-xl px-2 py-2 text-xs bg-white">
        </div>

        <div>
            <label class="block text-[11px] text-gray-500 mb-1">Jam Nyata Selesai</label>
            <input type="datetime-local" name="actual_end_at" required
                   value="{{ ($item->actual_end_at ?? $plannedEnd)->format('Y-m-d\TH:i') }}"
                   class="w-full border rounded-xl px-2 py-2 text-xs bg-white">
        </div>

        <div>
            <label class="block text-[11px] text-gray-500 mb-1">Catatan Koreksi</label>
            <input type="text" name="note" required maxlength="500" placeholder="mis. karyawan lupa absen"
                   value="{{ $item->proof_note }}"
                   class="w-full border rounded-xl px-2 py-2 text-xs bg-white">
        </div>

        <button type="submit"
                class="py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold">
            Simpan Koreksi
        </button>
    </form>
    @endif
</div>
