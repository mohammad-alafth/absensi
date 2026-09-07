<x-app-layout>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9ff; }
    </style>

    @php
        $workLabels = [
            'office_5' => 'Office 5 Hari (Senin - Jumat)',
            'office_6' => 'Office 6 Hari (Senin - Sabtu)',
            'shift'    => 'Shift (Jadwal Dinamis)',
        ];
        $todayStr = $today->format('Y-m-d');
    @endphp

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-28">
        <div class="w-full max-w-5xl mx-auto space-y-5">

            {{-- HEADER --}}
            <div class="flex flex-wrap justify-between items-center gap-3">
                <a href="{{ route('history') }}" class="text-[#1E40AF] font-semibold text-sm flex items-center gap-1 hover:underline">
                    ← Kembali ke Riwayat
                </a>
                <div class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-5 py-2 rounded-2xl shadow-lg text-sm font-semibold tracking-wide whitespace-nowrap">
                    📊 Rekap Absensi Saya
                </div>
            </div>

            {{-- PROFIL USER --}}
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 sm:p-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#1E40AF] to-blue-400 text-white flex items-center justify-center text-xl font-extrabold shadow-md">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-extrabold text-gray-800 truncate">{{ $user->name }}</h2>
                            <span class="bg-[#1E40AF]/10 text-[#1E40AF] border border-blue-100 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">{{ $user->role_label }}</span>
                        </div>
                        <p class="text-xs text-gray-400 font-medium mt-1">
                            ID User {{ $user->id }} • {{ $workLabels[$user->work_type] ?? $user->work_type ?? '-' }}
                        </p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Periode Rekap</p>
                        <p class="text-xl font-extrabold text-gray-800">{{ $periodLabel }}</p>
                    </div>
                </div>

                <div class="mt-5 pt-5 border-t border-gray-100 flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Pilih Periode / Bulan</p>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('history.rekap', ['bulan' => $prevMonth]) }}" title="Bulan sebelumnya"
                               class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:bg-[#1E40AF] hover:text-white transition flex items-center justify-center font-bold text-lg">‹</a>
                            <form method="GET" action="{{ route('history.rekap') }}" class="m-0">
                                <input type="month" name="bulan" value="{{ $month }}" onchange="this.form.submit()"
                                       class="rounded-xl border-gray-200 text-sm font-bold text-gray-700 px-3 py-2 bg-white focus:ring-[#1E40AF] focus:border-[#1E40AF] cursor-pointer">
                            </form>
                            <a href="{{ route('history.rekap', ['bulan' => $nextMonth]) }}" title="Bulan berikutnya"
                               class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:bg-[#1E40AF] hover:text-white transition flex items-center justify-center font-bold text-lg">›</a>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Filter Tanggal (Hari Ini / Sebelumnya)</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('history.rekap', ['bulan' => $month, 'mode' => 'bulanan']) }}"
                               class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ !$selectedDay ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'bg-white text-gray-600 border-gray-200 hover:bg-blue-50' }}">
                                Rekap Bulan Ini
                            </a>
                            <a href="{{ route('history.rekap', ['bulan' => $today->format('Y-m'), 'mode' => 'tanggal', 'tanggal' => $todayStr]) }}"
                               class="px-3 py-2 rounded-xl text-xs font-bold transition border {{ ($selectedDay && $selectedDay->isSameDay($today)) ? 'bg-[#1E40AF] text-white border-[#1E40AF]' : 'bg-white text-gray-600 border-gray-200 hover:bg-blue-50' }}">
                                Hari Ini
                            </a>
                            <form method="GET" action="{{ route('history.rekap') }}" class="m-0 flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-2 py-1">
                                <span class="text-[10px] text-gray-400 font-bold uppercase whitespace-nowrap">Atau tgl</span>
                                <input type="date" name="tanggal" max="{{ $todayStr }}" value="{{ $selectedDay ? $selectedDay->format('Y-m-d') : '' }}"
                                       class="border-0 text-xs font-semibold text-gray-700 focus:ring-0 p-0">
                                <input type="hidden" name="mode" value="tanggal">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition">Tampilkan</button>
                            </form>
                        </div>
                    </div>
                </div>

                @if($selectedDay)
                <div class="mt-4 flex flex-wrap items-center gap-2 bg-blue-50 border border-blue-100 text-[#1E40AF] rounded-2xl px-4 py-3 text-xs font-semibold">
                    <span>Menampilkan rekap tanggal <span class="font-extrabold">{{ $selectedDay->format('d-m-Y') }}</span>
                        ({{ $rows[0]['day_name'] ?? '' }})</span>
                    <a href="{{ route('history.rekap', ['bulan' => $month]) }}" class="ml-auto bg-white border border-blue-200 hover:bg-[#1E40AF] hover:text-white px-3 py-1.5 rounded-lg transition">
                        Lihat seluruh bulan {{ $periodLabel }}
                    </a>
                </div>
                @endif
            </div>

            {{-- RINGKASAN --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-center">
                    <p class="text-[10px] uppercase tracking-widest text-emerald-500 font-bold">Hadir</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $counts['hadir'] }}</p>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-center">
                    <p class="text-[10px] uppercase tracking-widest text-amber-500 font-bold">Terlambat</p>
                    <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $counts['terlambat'] }}</p>
                </div>
                <div class="bg-red-50 border border-red-100 rounded-2xl p-4 text-center">
                    <p class="text-[10px] uppercase tracking-widest text-red-400 font-bold">Alpa</p>
                    <p class="text-2xl font-extrabold text-red-500 mt-1">{{ $counts['alpa'] }}</p>
                </div>
                <div class="bg-violet-50 border border-violet-100 rounded-2xl p-4 text-center">
                    <p class="text-[10px] uppercase tracking-widest text-violet-400 font-bold">Cuti</p>
                    <p class="text-2xl font-extrabold text-violet-600 mt-1">{{ $counts['cuti'] }}</p>
                </div>
                <div class="bg-sky-50 border border-sky-100 rounded-2xl p-4 text-center">
                    <p class="text-[10px] uppercase tracking-widest text-sky-500 font-bold">Izin</p>
                    <p class="text-2xl font-extrabold text-sky-600 mt-1">{{ $counts['izin'] }}</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-center">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Libur / Off</p>
                    <p class="text-2xl font-extrabold text-slate-500 mt-1">{{ $counts['off'] }}</p>
                </div>
            </div>

            @if($presentPercent !== null)
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-gray-600">📈 Tingkat kehadiran (hari kerja tanpa cuti/izin)</p>
                    <p class="text-sm font-extrabold text-[#1E40AF]">{{ $presentPercent }}%</p>
                </div>
                <div class="h-3 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-[#1E40AF] to-emerald-400 transition-all"
                         style="width: {{ $presentPercent }}%"></div>
                </div>
            </div>
            @endif

            {{-- TABEL REKAP --}}
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 px-5 pt-5 pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-800">Rekap Harian {{ $periodLabel }}</h3>
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            Data absensi (check-in/out), jadwal, cuti &amp; izin disetujui. Ringkasan dihitung s.d. hari ini.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold">
                        <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Hadir</span>
                        <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Terlambat</span>
                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Alpa</span>
                        <span class="bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full">Cuti</span>
                        <span class="bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full">Izin</span>
                        <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">Libur/Off</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[760px]">
                        <thead>
                            <tr class="bg-[#f8f9ff] text-left text-[10px] uppercase tracking-widest text-gray-400">
                                <th class="px-5 py-3 font-extrabold">Tanggal</th>
                                <th class="px-3 py-3 font-extrabold">Jadwal Shift</th>
                                <th class="px-3 py-3 font-extrabold text-center">Jam Masuk</th>
                                <th class="px-3 py-3 font-extrabold text-center">Jam Pulang</th>
                                <th class="px-3 py-3 font-extrabold text-center">Status</th>
                                <th class="px-5 py-3 font-extrabold">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                            @php
                                $sched = $row['schedule'];
                                $start = $sched && $sched['start'] ? substr((string) $sched['start'], 0, 5) : null;
                                $end = $sched && $sched['end'] ? substr((string) $sched['end'], 0, 5) : null;
                                $badge = match ($row['status']) {
                                    'hadir'       => 'bg-emerald-100 text-emerald-700',
                                    'terlambat'   => 'bg-amber-100 text-amber-700',
                                    'alpa'        => 'bg-red-100 text-red-700',
                                    'cuti'        => 'bg-violet-100 text-violet-700',
                                    'izin'        => 'bg-sky-100 text-sky-700',
                                    'belum_absen' => 'bg-orange-100 text-orange-700',
                                    'terjadwal'   => 'bg-indigo-100 text-indigo-700',
                                    default       => 'bg-slate-100 text-slate-500',
                                };
                            @endphp
                            <tr class="border-t border-gray-50 hover:bg-[#f8f9ff]/70 transition {{ $row['is_future'] ? 'opacity-60' : '' }} {{ $row['is_today'] ? 'bg-blue-50/50' : '' }}">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="font-extrabold text-gray-800 text-sm">{{ $row['date_label'] }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium">{{ $row['day_name'] }}</p>
                                        </div>
                                        @if($row['is_today'])
                                        <span class="bg-[#1E40AF] text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full uppercase">Hari Ini</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    @if($sched)
                                        <p class="font-bold text-gray-700 text-xs">{{ $sched['name'] }}</p>
                                        @if($start && $end)
                                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $start }} - {{ $end }} WIB
                                            @if(!empty($sched['overnight'])) <span title="Shift lintas hari">🌙</span>@endif
                                        </p>
                                        @endif
                                    @else
                                        <p class="text-xs text-slate-300 font-semibold">Tidak ada jadwal</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center font-extrabold text-gray-700">{{ $row['masuk'] ?? '—' }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if($row['keluar'])
                                        <span class="font-extrabold text-gray-700">{{ $row['keluar'] }}</span>
                                    @elseif($row['checkout_pending'])
                                        <span class="text-[10px] text-orange-500 font-bold bg-orange-50 border border-orange-100 px-2 py-0.5 rounded-full whitespace-nowrap">Belum pulang</span>
                                    @else
                                        <span class="text-slate-300 font-semibold">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold whitespace-nowrap {{ $badge }}">{{ $row['status_label'] }}</span>
                                </td>
                                <td class="px-5 py-3 text-xs text-gray-500 font-medium max-w-[220px]">{{ $row['note'] ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">
                                    Belum ada data absensi pada periode ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
