@extends('layouts.submission')

@section('content')
    <style>
        .rekap-page { font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    </style>

    @php
        $workLabels = [
            'office_5' => 'Office 5 Hari (Senin - Jumat)',
            'office_6' => 'Office 6 Hari (Senin - Sabtu)',
            'shift'    => 'Shift (Jadwal Dinamis)',
        ];
        $todayStr = $today->format('Y-m-d');

        $selectedDateStr = $selectedDay ? $selectedDay->format('Y-m-d') : null;
        $selectedRow = $selectedDateStr
            ? collect($calendarRows)->firstWhere('date_str', $selectedDateStr)
            : null;

        $statusTone = [
            'hadir'       => [
                'cell'  => 'border-emerald-200 bg-emerald-50/70',
                'badge' => 'bg-emerald-100 text-emerald-700',
            ],
            'terlambat'   => [
                'cell'  => 'border-amber-200 bg-amber-50/70',
                'badge' => 'bg-amber-100 text-amber-700',
            ],
            'alpa'        => [
                'cell'  => 'border-red-200 bg-red-50/70',
                'badge' => 'bg-red-100 text-red-700',
            ],
            'cuti'        => [
                'cell'  => 'border-violet-200 bg-violet-50/70',
                'badge' => 'bg-violet-100 text-violet-700',
            ],
            'izin'        => [
                'cell'  => 'border-sky-200 bg-sky-50/70',
                'badge' => 'bg-sky-100 text-sky-700',
            ],
            'belum_absen' => [
                'cell'  => 'border-orange-200 bg-orange-50/70',
                'badge' => 'bg-orange-100 text-orange-700',
            ],
            'terjadwal'   => [
                'cell'  => 'border-indigo-200 bg-indigo-50/70',
                'badge' => 'bg-indigo-100 text-indigo-700',
            ],
            'off'         => [
                'cell'  => 'border-slate-200 bg-slate-50/80',
                'badge' => 'bg-slate-100 text-slate-500',
            ],
            'off_future'  => [
                'cell'  => 'border-slate-200 bg-slate-50/80',
                'badge' => 'bg-slate-100 text-slate-500',
            ],
        ];

        $statusCellLabels = [
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'alpa'        => 'Alpa',
            'cuti'        => 'Cuti',
            'izin'        => 'Izin',
            'belum_absen' => 'Belum',
            'terjadwal'   => 'Jadwal',
            'off'         => 'Off',
            'off_future'  => 'Off',
        ];

        $detailNote = $selectedRow['note'] ?? null;
        if (!$detailNote && $selectedRow && in_array($selectedRow['status'], ['off', 'off_future'], true)) {
            $detailNote = 'Tidak ada jadwal (Libur/Off)';
        }
    @endphp

    <div class="rekap-page min-h-screen bg-[#f8f9ff] px-3 py-3 pb-24 sm:px-5">
        <div class="w-full max-w-5xl mx-auto space-y-5">

            {{-- HEADER --}}
            <header class="bg-white rounded-2xl shadow-sm border border-[#c4c5d7]/50 p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-xl bg-[#eff4ff] border border-[#2151da]/20 text-[#0037b0] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[24px]">clinical_notes</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase tracking-[0.08em] font-bold text-[#565e74]">Riwayat &amp; Aktivitas</p>
                            <h1 class="text-lg sm:text-xl font-bold tracking-tight text-[#0b1c30] truncate">Rekap Absensi Saya</h1>
                            <p class="text-[11px] text-[#565e74]">Ringkasan presensi berdasarkan periode yang dipilih.</p>
                        </div>
                    </div>
                    <a href="{{ route('history') }}" class="inline-flex items-center gap-1 text-[#1E40AF] font-semibold text-xs hover:underline shrink-0">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span> Riwayat
                    </a>
                </div>
            </header>

            {{-- PROFIL USER --}}
            <section class="bg-white rounded-2xl shadow-sm border border-[#c4c5d7]/50 p-4 sm:p-5">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#eff4ff] border border-[#c4c5d7] text-[#0037b0] flex items-center justify-center shadow-sm">
                        <span class="material-symbols-outlined text-[30px]">person</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-extrabold text-[#0b1c30] truncate">{{ $user->name }}</h2>
                            <span class="bg-[#eff4ff] text-[#0037b0] border border-[#c4c5d7] text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">{{ $user->role_label }}</span>
                        </div>
                        <p class="text-xs text-[#565e74] font-medium mt-1">
                            ID User {{ $user->id }} <span class="text-[#c4c5d7]">•</span> {{ $workLabels[$user->work_type] ?? $user->work_type ?? '-' }}
                        </p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-[10px] uppercase tracking-widest text-[#565e74] font-bold">Periode Rekap</p>
                        <p class="text-xl font-extrabold text-[#0b1c30]">{{ $periodLabel }}</p>
                    </div>
                </div>

                <div class="mt-5 pt-5 border-t border-[#c4c5d7]/40 flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-[#565e74] font-bold mb-2 flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-[#2151da]">calendar_month</span>Pilih Periode / Bulan</p>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('history.rekap', ['bulan' => $prevMonth]) }}" title="Bulan sebelumnya"
                                class="w-9 h-9 rounded-xl bg-white border border-[#c4c5d7] text-[#565e74] hover:bg-[#1E40AF] hover:text-white transition flex items-center justify-center font-bold text-lg">‹</a>
                            <form method="GET" action="{{ route('history.rekap') }}" class="m-0">
                                <input type="month" name="bulan" value="{{ $month }}" onchange="this.form.submit()"
                                       class="rounded-xl border-[#c4c5d7] text-sm font-bold text-[#0b1c30] px-3 py-2 bg-white focus:ring-[#1E40AF] focus:border-[#1E40AF] cursor-pointer">
                            </form>
                            <a href="{{ route('history.rekap', ['bulan' => $nextMonth]) }}" title="Bulan berikutnya"
                                class="w-9 h-9 rounded-xl bg-white border border-[#c4c5d7] text-[#565e74] hover:bg-[#1E40AF] hover:text-white transition flex items-center justify-center font-bold text-lg">›</a>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-[#565e74] font-bold mb-2 flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-[#2151da]">filter_alt</span>Filter Tanggal (Hari Ini / Sebelumnya)</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('history.rekap', ['bulan' => $month, 'mode' => 'bulanan']) }}"
                               class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ !$selectedDay ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'bg-white text-[#565e74] border-[#c4c5d7] hover:bg-[#eff4ff]' }}">
                                Rekap Bulan Ini
                            </a>
                            <a href="{{ route('history.rekap', ['bulan' => $today->format('Y-m'), 'mode' => 'tanggal', 'tanggal' => $todayStr]) }}"
                               class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ ($selectedDay && $selectedDay->isSameDay($today)) ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'bg-white text-[#565e74] border-[#c4c5d7] hover:bg-[#eff4ff]' }}">
                                Hari Ini
                            </a>
                            <form method="GET" action="{{ route('history.rekap') }}" class="m-0 flex items-center gap-2 bg-white border border-[#c4c5d7] rounded-xl px-2 py-1">
                                <span class="text-[10px] text-[#565e74] font-bold uppercase whitespace-nowrap">Atau tgl</span>
                                <input type="date" name="tanggal" max="{{ $todayStr }}" value="{{ $selectedDay ? $selectedDay->format('Y-m-d') : '' }}"
                                       class="border-0 text-xs font-semibold text-[#0b1c30] focus:ring-0 p-0">
                                <input type="hidden" name="mode" value="tanggal">
                                <button type="submit" class="bg-[#1D4ED8] hover:bg-[#0037b0] text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition">Tampilkan</button>
                            </form>
                        </div>
                    </div>
                </div>

                @if($selectedDay)
                <div class="mt-4 flex flex-wrap items-center gap-2 bg-blue-50 border border-blue-100 text-[#1E40AF] rounded-2xl px-4 py-3 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[17px]">event</span>Menampilkan rekap tanggal <span class="font-extrabold">{{ $selectedDay->format('d-m-Y') }}</span>
                        ({{ $rows[0]['day_name'] ?? '' }})</span>
                    <a href="{{ route('history.rekap', ['bulan' => $month]) }}" class="ml-auto bg-white border border-[#c4c5d7] hover:bg-[#1E40AF] hover:text-white px-3 py-1.5 rounded-lg transition">
                        Lihat seluruh bulan {{ $periodLabel }}
                    </a>
                </div>
                @endif
            </div>

            {{-- RINGKASAN --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white border border-[#c4c5d7]/60 rounded-2xl p-4 shadow-sm text-center">
                    <p class="text-[10px] uppercase tracking-widest text-emerald-500 font-bold">Hadir</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $counts['hadir'] }}</p>
                </div>
                <div class="bg-white border border-[#c4c5d7]/60 rounded-2xl p-4 shadow-sm text-center">
                    <p class="text-[10px] uppercase tracking-widest text-amber-500 font-bold">Terlambat</p>
                    <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $counts['terlambat'] }}</p>
                </div>
                <div class="bg-white border border-[#c4c5d7]/60 rounded-2xl p-4 shadow-sm text-center">
                    <p class="text-[10px] uppercase tracking-widest text-red-400 font-bold">Alpa</p>
                    <p class="text-2xl font-extrabold text-red-500 mt-1">{{ $counts['alpa'] }}</p>
                </div>
                <div class="bg-white border border-[#c4c5d7]/60 rounded-2xl p-4 shadow-sm text-center">
                    <p class="text-[10px] uppercase tracking-widest text-violet-400 font-bold">Cuti</p>
                    <p class="text-2xl font-extrabold text-violet-600 mt-1">{{ $counts['cuti'] }}</p>
                </div>
                <div class="bg-white border border-[#c4c5d7]/60 rounded-2xl p-4 shadow-sm text-center">
                    <p class="text-[10px] uppercase tracking-widest text-sky-500 font-bold">Izin</p>
                    <p class="text-2xl font-extrabold text-sky-600 mt-1">{{ $counts['izin'] }}</p>
                </div>
                <div class="bg-white border border-[#c4c5d7]/60 rounded-2xl p-4 shadow-sm text-center">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Libur / Off</p>
                    <p class="text-2xl font-extrabold text-slate-500 mt-1">{{ $counts['off'] }}</p>
                </div>
            </div>

            @if($presentPercent !== null)
            <div class="bg-white rounded-2xl shadow-sm border border-[#c4c5d7]/50 p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-[#565e74] flex items-center gap-1.5"><span class="material-symbols-outlined text-[17px] text-[#2151da]">trending_up</span>Tingkat kehadiran (hari kerja tanpa cuti/izin)</p>
                    <p class="text-sm font-extrabold text-[#1E40AF]">{{ $presentPercent }}%</p>
                </div>
                <div class="h-2 w-full bg-[#e5eeff] rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-[#1D4ED8] transition-all"
                         style="width: {{ $presentPercent }}%"></div>
                </div>
            </div>
            @endif

            {{-- KALENDER REKAP HARIAN --}}
            <section class="bg-white rounded-2xl shadow-sm border border-[#c4c5d7]/50 overflow-hidden">
                <div class="flex flex-col gap-3 px-4 sm:px-5 pt-5 pb-3 border-b border-[#c4c5d7]/40 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-extrabold text-gray-800">Rekap Harian {{ $periodLabel }}</h3>
                            @if($selectedDay)
                                <span class="bg-[#eff4ff] text-[#0037b0] border border-[#c4c5d7]/60 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase">Tanggal terpilih</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-[#565e74] mt-0.5">
                            Data absensi (check-in/out), jadwal, cuti &amp; izin disetujui. Ringkasan dihitung s.d. hari ini.
                        </p>
                    </div>
                    <div class="flex items-center justify-between gap-1.5 sm:justify-end">
                        <a href="{{ route('history.rekap', ['bulan' => $prevMonth]) }}" title="Bulan sebelumnya" aria-label="Bulan sebelumnya"
                           class="w-8 h-8 rounded-lg bg-white border border-[#c4c5d7] text-[#565e74] hover:bg-[#1E40AF] hover:text-white transition flex items-center justify-center font-bold text-lg">‹</a>
                        <span class="px-1 text-xs font-extrabold text-[#0b1c30] whitespace-nowrap">{{ $periodLabel }}</span>
                        <a href="{{ route('history.rekap', ['bulan' => $nextMonth]) }}" title="Bulan berikutnya" aria-label="Bulan berikutnya"
                           class="w-8 h-8 rounded-lg bg-white border border-[#c4c5d7] text-[#565e74] hover:bg-[#1E40AF] hover:text-white transition flex items-center justify-center font-bold text-lg">›</a>
                    </div>
                </div>

                <div class="px-3 pt-3 sm:px-5 sm:pt-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#565e74]">
                            {{ $periodStart->format('j') }}–{{ $periodEnd->format('j') }} {{ $periodLabel }}
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5 text-[9px] font-bold">
                            <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Hadir</span>
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Terlambat</span>
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Alpa</span>
                            <span class="bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">Cuti</span>
                            <span class="bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full">Izin</span>
                            <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">Libur/Off</span>
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">Terjadwal</span>
                            <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">Belum Absen</span>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-7 gap-1 text-center text-[9px] font-bold uppercase tracking-wide text-[#565e74] sm:gap-1.5 sm:text-[10px]">
                        <span>Sen</span>
                        <span>Sel</span>
                        <span>Rab</span>
                        <span>Kam</span>
                        <span>Jum</span>
                        <span class="text-rose-500">Sab</span>
                        <span class="text-rose-500">Min</span>
                    </div>

                    <div class="mt-2 grid grid-cols-7 gap-1 pb-4 sm:gap-1.5 sm:pb-5">
                        @for($i = 0; $i < $leadingBlankCount; $i++)
                            <div aria-hidden="true" class="min-h-[64px] rounded-lg border border-dashed border-[#c4c5d7]/60 bg-slate-50/60 sm:min-h-[84px]"></div>
                        @endfor

                        @foreach($calendarRows as $row)
                            @php
                                $tone = $statusTone[$row['status']] ?? $statusTone['off'];
                                $isSelected = $selectedDateStr === $row['date_str'];
                                $cellAria = $row['day_name'] . ', ' . $row['date_label'] . ' ' . $periodStart->format('Y')
                                    . ' — ' . $row['status_label']
                                    . ($row['masuk'] ? ', masuk ' . $row['masuk'] : '')
                                    . ($row['keluar'] ? ', pulang ' . $row['keluar'] : '')
                                    . ($row['checkout_pending'] ? ', belum pulang' : '')
                                    . ($row['note'] ? ', ' . $row['note'] : '');
                            @endphp
                            <a href="{{ route('history.rekap', ['bulan' => $month, 'mode' => 'tanggal', 'tanggal' => $row['date_str']]) }}#detail-rekap"
                               data-date="{{ $row['date_str'] }}"
                               data-status="{{ $row['status'] }}"
                               data-today="{{ $row['is_today'] ? '1' : '0' }}"
                               data-future="{{ $row['is_future'] ? '1' : '0' }}"
                               aria-label="{{ $cellAria }}"
                               title="{{ $cellAria }}"
                               class="group flex min-h-[64px] min-w-0 flex-col justify-between rounded-lg border p-1 text-left transition hover:-translate-y-0.5 hover:border-[#1D4ED8] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1D4ED8] sm:min-h-[84px] sm:p-1.5 {{ $tone['cell'] }} {{ $row['is_future'] ? 'opacity-80' : '' }} {{ $isSelected ? 'ring-2 ring-[#1D4ED8]/30 border-[#1D4ED8]' : '' }} {{ $row['is_today'] && !$isSelected ? 'ring-1 ring-[#1D4ED8]/30' : '' }}">
                                <div class="flex items-start justify-between gap-0.5">
                                    <span class="text-[11px] font-extrabold leading-none text-[#0b1c30] sm:text-xs">{{ (int) substr($row['date_str'], 8, 2) }}</span>
                                    @if($row['is_today'])
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#1D4ED8]" title="Hari ini"></span>
                                    @endif
                                </div>

                                <div class="mt-0.5 min-w-0">
                                    <span class="block rounded px-0.5 py-0.5 text-center text-[7px] font-bold leading-tight sm:text-[8px] {{ $tone['badge'] }}">{{ $statusCellLabels[$row['status']] ?? $row['status_label'] }}</span>

                                    @if($row['masuk'] || $row['keluar'])
                                        <span class="mt-0.5 block truncate text-center text-[7px] font-semibold leading-tight text-[#565e74] tabular-nums sm:text-[8px]">
                                            {{ $row['masuk'] ?? '—' }}@if($row['masuk'] && $row['keluar'])–@endif{{ $row['keluar'] ?? '' }}
                                        </span>
                                    @endif

                                    @if($row['checkout_pending'])
                                        <span class="mt-0.5 block text-center text-[6px] font-bold leading-tight text-orange-600">Belum pulang</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach

                        @for($i = 0; $i < $trailingBlankCount; $i++)
                            <div aria-hidden="true" class="min-h-[64px] rounded-lg border border-dashed border-[#c4c5d7]/60 bg-slate-50/60 sm:min-h-[84px]"></div>
                        @endfor
                    </div>
                </div>
            </section>

            {{-- DETAIL TANGGAL --}}
            <section id="detail-rekap" class="scroll-mt-24 bg-white rounded-2xl shadow-sm border border-[#c4c5d7]/50 overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-2 px-4 sm:px-5 pt-4 pb-3 border-b border-[#c4c5d7]/40">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-[#565e74]">Detail Tanggal</p>
                        <h3 class="mt-0.5 text-base font-extrabold text-[#0b1c30]">
                            @if($selectedRow)
                                {{ $selectedRow['day_name'] }}, {{ $selectedRow['date_label'] }} {{ $periodStart->format('Y') }}
                            @else
                                Pilih tanggal pada kalender
                            @endif
                        </h3>
                    </div>
                    @if($selectedDay)
                        <a href="{{ route('history.rekap', ['bulan' => $month]) }}" class="inline-flex items-center gap-1 text-[#1E40AF] font-semibold text-xs hover:underline">
                            <span class="material-symbols-outlined text-[15px]">calendar_month</span> Seluruh bulan
                        </a>
                    @endif
                </div>

                @if($selectedRow)
                    <div class="p-4 sm:p-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-[#c4c5d7]/60 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Karyawan</p>
                            <p class="mt-1 text-sm font-bold text-[#0b1c30] break-words">{{ $user->name }}</p>
                            <p class="mt-0.5 text-[11px] font-medium text-[#565e74]">{{ $user->role_label }}</p>
                        </div>

                        <div class="rounded-xl border border-[#c4c5d7]/60 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Status Kehadiran</p>
                            <span class="mt-1 inline-block rounded-full px-2.5 py-1 text-[10px] font-bold {{ $statusTone[$selectedRow['status']]['badge'] ?? $statusTone['off']['badge'] }}">{{ $selectedRow['status_label'] }}</span>
                            @if($selectedRow['is_today'])
                                <span class="mt-1 ml-1 inline-block rounded-full bg-[#1E40AF] px-2 py-1 text-[9px] font-bold uppercase text-white">Hari Ini</span>
                            @endif
                        </div>

                        <div class="rounded-xl border border-[#c4c5d7]/60 p-3 sm:col-span-2">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Jadwal Kerja</p>
                            @if($selectedRow['schedule'])
                                <p class="mt-1 text-sm font-bold text-[#0b1c30]">{{ $selectedRow['schedule']['name'] }}</p>
                                <p class="mt-0.5 text-xs font-medium text-[#565e74]">
                                    {{ substr((string) $selectedRow['schedule']['start'], 0, 5) }} - {{ substr((string) $selectedRow['schedule']['end'], 0, 5) }} WIB
                                    @if(!empty($selectedRow['schedule']['overnight']))
                                        <span class="material-symbols-outlined text-[14px] align-middle" title="Shift lintas hari">nights_stay</span>
                                    @endif
                                </p>
                            @else
                                <p class="mt-1 text-sm font-bold text-[#565e74]">Tidak ada jadwal kerja reguler</p>
                            @endif
                        </div>

                        <div class="rounded-xl border border-[#c4c5d7]/60 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Jam Masuk</p>
                            <p class="mt-1 text-sm font-extrabold text-[#0b1c30]">{{ $selectedRow['masuk'] ?? '—' }}</p>
                        </div>

                        <div class="rounded-xl border border-[#c4c5d7]/60 p-3">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Jam Pulang</p>
                            @if($selectedRow['keluar'])
                                <p class="mt-1 text-sm font-extrabold text-[#0b1c30]">{{ $selectedRow['keluar'] }}</p>
                            @elseif($selectedRow['checkout_pending'])
                                <p class="mt-1 text-sm font-extrabold text-orange-600">Belum pulang</p>
                            @else
                                <p class="mt-1 text-sm font-extrabold text-[#0b1c30]">—</p>
                            @endif
                        </div>

                        <div class="rounded-xl border border-[#c4c5d7]/60 p-3 sm:col-span-2">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Keterangan</p>
                            <p class="mt-1 text-xs font-medium leading-relaxed text-[#565e74]">{{ $detailNote ?? '—' }}</p>
                        </div>
                    </div>
                @else
                    <div class="px-4 py-8 text-center sm:px-5">
                        <span class="material-symbols-outlined text-3xl text-[#c4c5d7] block mb-2">event</span>
                        <p class="text-xs font-medium text-[#565e74]">Klik salah satu tanggal pada kalender untuk melihat rincian absensi, jadwal, cuti, atau izin pada tanggal tersebut.</p>
                    </div>
                @endif
            </section>

        </div>
    </div>
@endsection
