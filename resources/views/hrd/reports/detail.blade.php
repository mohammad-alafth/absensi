{{--
| DETAIL REKAP (CUTI / IZIN / LEMBUR)
|---------------------------------------------------------------------------
| HTML siap tempel untuk modal pada hrd.reports.*. Diambil lewat AJAX oleh
| tombol Status di tabel rekap (lihat HRDController::reportDetail).
| Isi: identitas + detail pengajuan, riwayat persetujuan, surat (PDF), dan
| bukti (tanda tangan pengaju / foto absen lembur) bila ada.
--}}

@php
    $isLeave      = $type === 'leave';
    $isPermission = $type === 'permission';
    $isOvertime   = $type === 'overtime';

    // Baris label -> nilai untuk grid detail (index ke-3 = lebar penuh).
    $rows = [];

    if ($isLeave) {
        $range = \Carbon\Carbon::parse($item->start_date)->format('d/m/Y')
            . ($item->end_date
                ? ' s/d ' . \Carbon\Carbon::parse($item->end_date)->format('d/m/Y')
                : '');
        $days = \Carbon\Carbon::parse($item->start_date)->diffInDays(
            \Carbon\Carbon::parse($item->end_date ?: $item->start_date)
        ) + 1;

        $rows = [
            ['Tanggal', $range],
            ['Durasi', $days . ' Hari'],
            ['Jenis Cuti', ucfirst(str_replace('_', ' ', (string) $item->leave_type))],
            ['Alasan', $item->reason ?: '-', true],
        ];
    } elseif ($isPermission) {
        $range = \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y');
        if ($item->tanggal_selesai) {
            $end = \Carbon\Carbon::parse($item->tanggal_selesai);
            if (!$end->isSameDay(\Carbon\Carbon::parse($item->tanggal))) {
                $range .= ' s/d ' . $end->format('d/m/Y');
            }
        }

        $rows = [
            ['Tanggal', $range],
            ['Jenis Izin', ucfirst(str_replace('_', ' ', (string) $item->jenis))],
            ['Jam', substr((string) ($item->jam_mulai ?: '-'), 0, 5)
                . ' - ' . substr((string) ($item->jam_selesai ?: '-'), 0, 5)],
            ['Alasan', $item->alasan ?: '-', true],
        ];
    } else {
        $rows = [
            ['Tanggal', \Carbon\Carbon::parse($item->overtime_date)->format('d/m/Y')],
            ['Jam Rencana', $item->planned_range_label],
            ['Volume', $item->hours_label],
            ['Jam Nyata', $item->actual_range_label ?? 'belum ada absen'],
            ['Durasi Nyata', $item->actual_duration_label ?? '-'],
            ['Klasifikasi Hari', $item->day_type === 'hari_libur'
                ? 'Hari Libur / Off'
                : 'Hari Kerja Aktif'],
            ['Kehadiran', $item->proof_label],
            ['Alasan', $item->reason ?: '-', true],
        ];
    }

    $rows[] = ['Diajukan Pada', $item->created_at
        ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i')
        : '-'];
@endphp

<div class="space-y-4 text-xs text-gray-700">

    {{-- IDENTITAS + STATUS --}}
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-extrabold text-gray-900">{{ $title }}</p>
            <p class="text-[11px] text-gray-500 mt-0.5">
                {{ $item->user->name ?? 'N/A' }}
                @if($item->user->role)
                    · {{ strtoupper($item->user->role) }}
                @endif
            </p>
        </div>
        <span class="shrink-0 inline-block px-3 py-1 rounded-xl border font-bold text-[10px] uppercase tracking-wide {{ $statusTone }}">
            {{ $statusLabel }}
        </span>
    </div>

    {{-- DETAIL PENGAJUAN --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        @foreach($rows as $row)
        <div class="bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 {{ !empty($row[2]) ? 'col-span-2 md:col-span-3' : '' }}">
            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-bold">{{ $row[0] }}</p>
            <p class="font-semibold text-gray-800 mt-0.5 break-words">{{ $row[1] }}</p>
        </div>
        @endforeach
    </div>

    {{-- RIWAYAT PERSETUJUAN --}}
    @if(count($approvalTrail))
    <div class="border border-gray-100 rounded-xl overflow-hidden">
        <p class="px-3 py-2 bg-gray-50 text-[11px] font-bold text-gray-600 uppercase tracking-wide">
            Riwayat Persetujuan
        </p>
        <ul class="divide-y divide-gray-100">
            @foreach($approvalTrail as $step)
            <li class="px-3 py-2 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800">
                        {{ $step['label'] }}
                        <span class="ml-1 text-[10px] font-bold uppercase {{ $step['status'] === 'Disetujui' ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $step['status'] }}
                        </span>
                    </p>
                    @if($step['note'])
                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $step['note'] }}</p>
                    @endif
                </div>
                <div class="shrink-0 text-right text-[11px] text-gray-500">
                    @if($step['approver'])
                    <p class="font-semibold text-gray-700">{{ $step['approver'] }}</p>
                    @endif
                    @if($step['at'])
                    <p>{{ \Carbon\Carbon::parse($step['at'])->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- SURAT (PDF) --}}
    <div class="border border-emerald-100 bg-emerald-50/60 rounded-xl px-3 py-3">
        <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wide mb-2">Surat</p>
        @if($letterUrl)
        <a href="{{ $letterUrl }}" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm">
            <span>📥</span> Buka Surat (PDF)
        </a>
        @else
        <p class="text-[11px] text-gray-500">Surat PDF belum tersedia untuk pengajuan ini.</p>
        @endif
    </div>

    {{-- BUKTI (tanda tangan pengaju / foto absen lembur) --}}
    @php
        $hasSignature = filled($item->employee_signature);
        $punches = $isOvertime ? ($item->punches ?? collect()) : collect();
        $hasProof = $hasSignature || $punches->isNotEmpty();
    @endphp

    <div class="border border-sky-100 bg-sky-50/50 rounded-xl px-3 py-3">
        <p class="text-[11px] font-bold text-sky-800 uppercase tracking-wide mb-2">Bukti</p>

        @unless($hasProof)
        <p class="text-[11px] text-gray-500">Tidak ada bukti terlampir pada pengajuan ini.</p>
        @else
        <div class="space-y-3">
            @if($hasSignature)
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">Tanda Tangan Pengaju</p>
                <img src="{{ $item->employee_signature }}" alt="Tanda tangan pengaju"
                     class="mt-1 max-h-16 bg-white border border-sky-100 rounded-lg px-2 py-1 object-contain">
            </div>
            @endif

            @if($punches->isNotEmpty())
            <div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">Log Absen Lembur</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-1">
                    @foreach($punches as $punch)
                    <div class="bg-white border border-sky-100 rounded-lg px-3 py-2 flex items-start gap-2">
                        @if($punch->photo_url)
                        <img src="{{ $punch->photo_url }}" alt="Foto absen"
                             class="w-12 h-12 rounded-lg object-cover border border-sky-100 shrink-0">
                        @endif
                        <div class="min-w-0 text-[11px]">
                            <p class="font-bold text-gray-800">{{ $punch->type_label }}</p>
                            <p class="text-gray-500">
                                {{ $punch->punched_at ? \Carbon\Carbon::parse($punch->punched_at)->format('d/m/Y H:i:s') : '-' }}
                            </p>
                            @if($punch->distance_m !== null)
                            <p class="text-gray-400">Jarak {{ round($punch->distance_m) }} m dari kantor</p>
                            @endif
                            @if($punch->note)
                            <p class="text-gray-500 mt-0.5">{{ $punch->note }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($isOvertime && $item->proof_note)
            <p class="text-[11px] text-gray-600 bg-white border border-sky-100 rounded-lg px-3 py-2">
                Catatan bukti: {{ $item->proof_note }}
            </p>
            @endif
        </div>
        @endunless
    </div>
</div>
