<x-app-layout>
    @php
        $recapCount = count($recaps);
        $totalHadir = collect($recaps)->sum('hadir');
        $totalTelat = collect($recaps)->sum('telat');
        $totalIzin = collect($recaps)->sum('izin');
        $totalSakit = collect($recaps)->sum('sakit');
        $totalAlpha = collect($recaps)->sum('alpha');
        $totalHours = collect($recaps)->sum('total_jam');
        $totalOvertime = collect($recaps)->pluck('overtimes')->filter()->implode(', ');
        $periodLabel = $startDate->format('d M Y') . ' – ' . $endDate->format('d M Y');
    @endphp

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Material+Symbols+Outlined:FILL,wght@0..1,300..700" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9ff; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-size: 1.25rem; line-height: 1; font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24; }
        html, body { overflow-x: hidden; }
        .recap-desktop-only { display: none !important; }
        .recap-mobile-only { display: block !important; }
        @media (min-width: 768px) {
            .recap-desktop-only { display: block !important; }
            .recap-desktop-workbench { display: grid !important; }
            .recap-mobile-only { display: none !important; }
        }
        .recap-primary { background: #0037b0 !important; color: #fff !important; }
        .recap-primary:hover { background: #1d4ed8 !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { scrollbar-width: none; -ms-overflow-style: none; }
    </style>

    <main class="min-h-screen bg-[#f8f9ff] px-4 pb-28 pt-5 sm:px-6 lg:px-8 lg:pt-8">
        <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-5">
            {{-- Page identity and actual period controls --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <div class="mb-1 flex items-center gap-2 text-[11px] font-bold uppercase tracking-[.14em] text-[#0037b0]"><span class="material-symbols-outlined text-base">clinical_notes</span>HRD · Presensi</div>
                        <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900">Pusat Rekap Absensi HRD</h1>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Monitoring rekapitulasi kehadiran, jam kerja, dan performa kedisiplinan pegawai.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <form method="GET" action="{{ route('hrd.rekap') }}" autocomplete="off" class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2">
                            <label class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500"><span class="material-symbols-outlined text-base text-[#0037b0]">date_range</span>Periode</label>
                            <input type="date" id="recapStartDate" name="start_date" value="{{ $startDate->format('Y-m-d') }}" title="Tanggal mulai" autocomplete="off" class="h-8 rounded-lg border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 focus:border-[#0037b0] focus:ring-blue-100">
                            <span class="text-xs font-bold text-slate-400">s/d</span>
                            <input type="date" id="recapEndDate" name="end_date" value="{{ $endDate->format('Y-m-d') }}" title="Tanggal selesai" autocomplete="off" class="h-8 rounded-lg border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 focus:border-[#0037b0] focus:ring-blue-100">
                            <input type="hidden" name="month" value="{{ $month }}">
                            <button type="submit" class="inline-flex h-8 items-center gap-1 rounded-lg bg-[#0037b0] px-3 text-xs font-bold text-white transition hover:bg-blue-800" title="Terapkan rentang tanggal">
                                <span class="material-symbols-outlined text-base">check</span>Terapkan
                            </button>
                            <a href="{{ route('hrd.rekap') }}" class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50" title="Kembali ke periode bulan ini">Reset</a>
                            <span id="periodPreview" class="basis-full text-[10px] font-semibold text-slate-500" aria-live="polite">Periode dipilih: {{ $startDate->format('d M Y') }} – {{ $endDate->format('d M Y') }}</span>
                        </form>
                        <form action="{{ route('hrd.export.excel') }}" method="GET" class="flex items-center gap-2">
                            <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                            <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                            <select name="role" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 focus:border-[#0037b0] focus:ring-blue-100"><option value="all">Semua Role</option>@foreach($roles as $role)<option value="{{ $role }}">{{ $roleLabels[$role] ?? strtoupper(str_replace('_', ' ', $role)) }}</option>@endforeach</select>
                            <button type="button" onclick="openShiftCalendar()" class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50"><span class="material-symbols-outlined text-base text-[#0037b0]">calendar_month</span><span class="hidden sm:inline">Kalender</span></button><button type="submit" class="recap-primary inline-flex h-10 items-center gap-1.5 rounded-xl px-3.5 text-xs font-bold shadow-sm"><span class="material-symbols-outlined text-base">download</span><span class="hidden sm:inline">Export Excel</span><span class="sm:hidden">Export</span></button>
                        </form>
                    </div>
                </div>
            </section>

            {{-- Actual data-backed summary cards --}}
            <section aria-label="Ringkasan rekap presensi" class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4"><div class="flex items-center justify-between"><span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Pegawai</span><span class="material-symbols-outlined text-[#0037b0]">group</span></div><p class="font-display mt-2 text-2xl font-bold text-slate-900">{{ $recapCount }}</p><p class="mt-1 text-[11px] text-slate-500">Dalam rekap</p></div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4"><div class="flex items-center justify-between"><span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Hadir</span><span class="material-symbols-outlined text-emerald-600">check_circle</span></div><p class="font-display mt-2 text-2xl font-bold text-emerald-700">{{ $totalHadir }}</p><p class="mt-1 text-[11px] text-slate-500">{{ $totalHadir }} Presensi tercatat</p></div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4"><div class="flex items-center justify-between"><span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Terlambat</span><span class="material-symbols-outlined text-amber-600">schedule</span></div><p class="font-display mt-2 text-2xl font-bold text-amber-700">{{ $totalTelat }}</p><p class="mt-1 text-[11px] text-slate-500">Status terlambat</p></div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4"><div class="flex items-center justify-between"><span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Alpha</span><span class="material-symbols-outlined text-rose-600">person_off</span></div><p class="font-display mt-2 text-2xl font-bold text-rose-700">{{ $totalAlpha }}</p><p class="mt-1 text-[11px] text-slate-500">Tanpa presensi/izin/cuti</p></div>
                <div class="col-span-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:col-span-1 sm:p-4"><div class="flex items-center justify-between"><span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Jam Kerja</span><span class="material-symbols-outlined text-[#0037b0]">timelapse</span></div><p class="font-display mt-2 text-2xl font-bold text-[#0037b0]">{{ number_format($totalHours, 1) }}</p><p class="mt-1 text-[11px] text-slate-500">Total jam presensi</p></div>
            </section>

            {{-- Actual report navigation; no fake tabs are created. --}}
            <section class="no-scrollbar flex items-center gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-slate-100/70 p-1.5">
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-xs font-bold text-[#0037b0] shadow-sm"><span class="material-symbols-outlined text-base">table_chart</span>Rekap Presensi</span>
                <a href="{{ route('hrd.reports.attendance.daily', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white"><span class="material-symbols-outlined text-base">today</span>Hadir Hari Ini</a>
                <a href="{{ route('hrd.reports.absent.daily', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white"><span class="material-symbols-outlined text-base">person_off</span>Tidak Hadir</a>
                <a href="{{ route('hrd.reports.leave', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white"><span class="material-symbols-outlined text-base">event_available</span>Cuti</a>
                <a href="{{ route('hrd.reports.permission', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white"><span class="material-symbols-outlined text-base">assignment</span>Izin</a>
                 <a href="{{ route('hrd.reports.overtime', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white"><span class="material-symbols-outlined text-base">more_time</span>Lembur</a>
                 <a href="{{ route('hrd.rekap.telat', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white"><span class="material-symbols-outlined text-base">schedule</span>Rekap Telat</a>
             </section>

            {{-- Desktop workbench: search, role filter, and matrix --}}
            <section class="recap-desktop-only recap-desktop-workbench grid-cols-1 gap-5 xl:grid-cols-12 xl:items-start">
                <div class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-8">
                    <div class="border-b border-slate-200 p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="relative w-full lg:max-w-md"><span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">search</span><input id="searchInputDesktop" type="search" placeholder="Cari nama pegawai atau role..." autocomplete="off" oninput="syncRecapSearch(this.value)" class="h-10 w-full rounded-xl border border-slate-200 bg-[#f8f9ff] pl-10 pr-10 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#0037b0] focus:ring-blue-100"><button type="button" id="clearSearchDesktop" onclick="clearRecapSearch()" class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-lg p-1 text-slate-400 hover:bg-slate-100"><span class="material-symbols-outlined text-base">close</span></button></div>
                            <div class="flex items-center gap-2"><label class="sr-only" for="roleFilterDesktop">Filter role</label><select id="roleFilterDesktop" onchange="applyRecapFilters()" class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 focus:border-[#0037b0] focus:ring-blue-100"><option value="all">Semua Role</option>@foreach($roles as $role)<option value="{{ strtolower($role) }}">{{ $roleLabels[$role] ?? strtoupper(str_replace('_', ' ', $role)) }}</option>@endforeach</select></div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] border-collapse text-left text-xs">
                            <thead class="border-b border-slate-200 bg-slate-50 font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3.5">Karyawan &amp; Role</th><th class="px-3 py-3.5 text-center">Total Shift</th><th class="px-3 py-3.5 text-center">Hadir</th><th class="px-3 py-3.5 text-center">Telat</th><th class="px-3 py-3.5 text-center">Izin/Sakit</th><th class="px-3 py-3.5 text-center">Jam Kerja</th><th class="px-3 py-3.5 text-center">Lembur</th><th class="px-3 py-3.5 text-center">Status</th><th class="px-4 py-3.5 text-right">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($recaps as $index => $recap)
                                    @php $employee = $recap['employee']; $roleKey = strtolower($employee->role); $roleKey = str_replace('pj_', '', $roleKey); $roleKey = $roleKey === 'nurse_ok' ? 'nurse' : $roleKey; @endphp
                                    <tr class="recap-result transition hover:bg-slate-50/70" data-search="{{ strtolower($employee->name . ' ' . $employee->role . ' ' . $employee->role_label) }}" data-role="{{ $roleKey }}">
                                        <td class="px-4 py-3.5"><div class="flex items-center gap-2.5"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 font-bold text-[#0037b0]">{{ strtoupper(substr($employee->name, 0, 1)) }}</span><div class="min-w-0"><p class="truncate font-display font-bold text-slate-900">{{ $employee->name }}</p><p class="truncate text-[11px] font-semibold text-[#0037b0]">{{ $employee->role_label }}</p></div></div></td>
                                        <td class="px-3 py-3.5 text-center font-semibold text-slate-700">{{ $recap['total_shift'] }}</td><td class="px-3 py-3.5 text-center font-semibold text-emerald-700">{{ $recap['hadir'] }}</td><td class="px-3 py-3.5 text-center font-semibold text-amber-700">{{ $recap['telat'] }}</td><td class="px-3 py-3.5 text-center text-slate-700">{{ $recap['izin'] + $recap['sakit'] }}</td><td class="px-3 py-3.5 text-center font-semibold text-[#0037b0]">{{ number_format($recap['total_jam'], 1) }}j</td><td class="px-3 py-3.5 text-center text-slate-700">{{ $recap['overtimes'] ?: '0' }}</td>
                                        <td class="px-3 py-3.5 text-center"><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold {{ $recap['performance'] === 'Baik' ? 'bg-emerald-50 text-emerald-700' : ($recap['performance'] === 'Evaluasi' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">{{ $recap['performance'] }}</span></td>
                                        <td class="px-4 py-3.5 text-right"><button type="button" onclick="showShiftModal({{ $employee->id }}, @js($employee->name))" title="Lihat detail shift" class="rounded-lg p-2 text-slate-500 hover:bg-blue-50 hover:text-[#0037b0]"><span class="material-symbols-outlined text-lg">visibility</span></button></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="px-6 py-14 text-center text-sm text-slate-500">Belum ada data rekap pada periode ini.</td></tr>
                                @endforelse
                                <tr id="desktopSearchEmpty" class="hidden"><td colspan="9" class="px-6 py-14 text-center text-sm text-slate-500">Tidak ada pegawai yang cocok dengan filter.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <aside class="flex flex-col gap-5 xl:col-span-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center gap-3 border-b border-slate-200 pb-3"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-[#0037b0]"><span class="material-symbols-outlined">policy</span></span><div><h2 class="font-display text-sm font-bold text-slate-900">Tata Kelola Kuota Cuti</h2><p class="text-[11px] text-slate-500">Kuota aktual per pegawai</p></div></div><p class="mt-4 text-sm leading-6 text-slate-600">Saldo kuota yang ditampilkan mengikuti nilai <code class="rounded bg-slate-100 px-1 text-xs">leave_quota</code> aktual dan dapat diperbarui melalui detail pegawai.</p><div class="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-600"><span class="material-symbols-outlined mr-1 align-middle text-base text-[#0037b0]">info</span>Policy cuti dan carry-over tidak ditampilkan karena tidak dikirim sebagai data policy pada halaman ini.</div></div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between border-b border-slate-200 pb-3"><h2 class="font-display text-sm font-bold text-slate-900">Cek Kuota Pegawai</h2><span class="material-symbols-outlined text-[#0037b0]">search</span></div><input id="quotaSearchDesktop" type="search" oninput="syncRecapSearch(this.value)" placeholder="Cari nama atau role..." class="mt-4 h-10 w-full rounded-xl border border-slate-200 bg-[#f8f9ff] px-3 text-xs focus:border-[#0037b0] focus:ring-blue-100"><p class="mt-3 text-[11px] text-slate-500">Saldo kuota berikut berasal dari <code class="rounded bg-slate-100 px-1">leave_quota</code> aktual.</p><div class="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">@forelse($recaps as $recap) @php $quotaEmployee = $recap['employee']; @endphp <div class="quota-result rounded-xl border border-slate-100 bg-slate-50 p-2.5" data-search="{{ strtolower($quotaEmployee->name . ' ' . $quotaEmployee->role . ' ' . $quotaEmployee->role_label) }}"><div class="flex items-center justify-between gap-2"><div class="min-w-0"><p class="truncate text-xs font-bold text-slate-800">{{ $quotaEmployee->name }}</p><p class="truncate text-[10px] text-slate-500">{{ $quotaEmployee->role_label }}</p></div><span class="shrink-0 text-sm font-bold text-[#0037b0]">{{ $quotaEmployee->leave_quota ?? 0 }}</span></div><form action="{{ route('hrd.update.leave.quota', $quotaEmployee->id) }}" method="POST" class="mt-2 flex gap-2">@csrf<input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}"><input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}"><input type="number" name="leave_quota" min="0" value="{{ $quotaEmployee->leave_quota ?? 0 }}" class="h-8 min-w-0 flex-1 rounded-lg border-slate-200 bg-white px-2 text-xs"><button type="submit" class="recap-primary h-8 rounded-lg px-2.5 text-[11px] font-bold">Simpan</button></form></div>@empty <p class="py-4 text-center text-xs text-slate-500">Belum ada saldo kuota.</p>@endforelse</div></div>
                </aside>
            </section>

            {{-- Mobile-specific workbench and employee cards --}}
            <section class="recap-mobile-only space-y-4">
                <div class="sticky top-0 z-20 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur"><div class="grid grid-cols-2 gap-2"><div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-2"><span class="material-symbols-outlined text-base text-slate-500">calendar_month</span><div class="min-w-0"><span class="block text-[10px] uppercase tracking-wide text-slate-500">Periode aktif</span><span class="block truncate text-xs font-bold text-slate-800">{{ $periodLabel }}</span></div></div><div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-2"><span class="material-symbols-outlined text-base text-[#0037b0]">group</span><div class="min-w-0"><span class="block text-[10px] uppercase tracking-wide text-slate-500">Pegawai</span><span class="block truncate text-xs font-bold text-[#0037b0]">{{ $recapCount }} dalam rekap</span></div></div></div><div class="mt-2 flex gap-2"><button type="button" onclick="document.querySelector('input[name=start_date]').focus()" class="inline-flex min-h-9 flex-1 items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700"><span class="material-symbols-outlined text-base">date_range</span>Ubah Periode</button><button type="button" onclick="openShiftCalendar()" class="inline-flex min-h-9 flex-1 items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700"><span class="material-symbols-outlined text-base">calendar_month</span>Kalender</button><a href="{{ route('hrd.export.excel', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'role' => 'all']) }}" class="recap-primary inline-flex min-h-9 flex-1 items-center justify-center gap-1 rounded-xl text-xs font-bold"><span class="material-symbols-outlined text-base">download</span>Export</a></div></div>
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100/70 p-1.5"><div class="relative min-w-0 flex-1"><span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-base text-slate-400">search</span><input id="searchInputMobile" type="search" oninput="syncRecapSearch(this.value)" placeholder="Cari nama atau role..." class="h-10 w-full rounded-lg border-0 bg-white pl-9 pr-8 text-xs focus:ring-2 focus:ring-blue-100"><button type="button" id="clearSearchMobile" onclick="clearRecapSearch()" class="absolute right-1.5 top-1/2 hidden -translate-y-1/2 rounded p-1 text-slate-400"><span class="material-symbols-outlined text-base">close</span></button></div><select id="roleFilterMobile" onchange="applyRecapFilters()" class="h-10 max-w-[112px] rounded-lg border-0 bg-white px-2 text-[11px] font-semibold text-slate-700"><option value="all">Semua Role</option>@foreach($roles as $role)<option value="{{ strtolower($role) }}">{{ $roleLabels[$role] ?? strtoupper(str_replace('_', ' ', $role)) }}</option>@endforeach</select></div>
                <div class="no-scrollbar flex gap-1.5 overflow-x-auto"><button type="button" class="status-filter shrink-0 rounded-full bg-[#0037b0] px-3 py-1.5 text-[11px] font-bold text-white" data-status="all" onclick="setStatusFilter('all', this)">Semua ({{ $recapCount }})</button><button type="button" class="status-filter shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600" data-status="present" onclick="setStatusFilter('present', this)">Ada Hadir</button><button type="button" class="status-filter shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600" data-status="late" onclick="setStatusFilter('late', this)">Ada Telat</button><button type="button" class="status-filter shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600" data-status="absence" onclick="setStatusFilter('absence', this)">Alpha</button></div>
                <div class="flex items-center justify-between"><h2 class="font-display text-sm font-bold text-slate-900">Data Presensi Pegawai</h2><span class="text-[11px] text-slate-500">{{ $periodLabel }}</span></div>
                <div class="space-y-3">
                    @forelse($recaps as $index => $recap)
                        @php $employee = $recap['employee']; $roleKey = strtolower($employee->role); $roleKey = str_replace('pj_', '', $roleKey); $roleKey = $roleKey === 'nurse_ok' ? 'nurse' : $roleKey; @endphp
                        <article class="recap-result rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm" data-search="{{ strtolower($employee->name . ' ' . $employee->role . ' ' . $employee->role_label) }}" data-role="{{ $roleKey }}" data-status-present="{{ $recap['hadir'] > 0 ? '1' : '0' }}" data-status-late="{{ $recap['telat'] > 0 ? '1' : '0' }}" data-status-absence="{{ $recap['alpha'] > 0 ? '1' : '0' }}">
                            <div class="flex items-start justify-between gap-3"><div class="flex min-w-0 items-center gap-2.5"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 font-bold text-[#0037b0]">{{ strtoupper(substr($employee->name, 0, 1)) }}</span><div class="min-w-0"><h3 class="truncate font-display text-sm font-bold text-slate-900">{{ $employee->name }}</h3><p class="truncate text-xs text-slate-500">{{ $employee->role_label }}</p></div></div><span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $recap['performance'] === 'Baik' ? 'bg-emerald-50 text-emerald-700' : ($recap['performance'] === 'Evaluasi' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">{{ $recap['performance'] }}</span></div>
                            <div class="mt-3 grid grid-cols-4 gap-1.5 rounded-lg bg-slate-50 px-2 py-2 text-center"><div><span class="block text-[10px] text-slate-500">Shift</span><strong class="text-xs text-slate-900">{{ $recap['total_shift'] }}</strong></div><div><span class="block text-[10px] text-slate-500">Hadir</span><strong class="text-xs text-emerald-700">{{ $recap['hadir'] }}</strong></div><div><span class="block text-[10px] text-slate-500">Telat</span><strong class="text-xs text-amber-700">{{ $recap['telat'] }}</strong></div><div><span class="block text-[10px] text-slate-500">Izin/Sakit</span><strong class="text-xs text-slate-900">{{ $recap['izin'] + $recap['sakit'] }}</strong></div></div>
                            <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-200 pt-3"><span class="truncate text-[11px] text-slate-500">Jam {{ number_format($recap['total_jam'], 1) }} · Lembur {{ $recap['overtimes'] ?: '0' }}</span><button type="button" onclick="showShiftModal({{ $employee->id }}, @js($employee->name))" class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1.5 text-[11px] font-bold text-[#0037b0]"><span class="material-symbols-outlined text-sm">visibility</span>Detail Shift</button></div>
                            <details class="mt-3 border-t border-slate-100 pt-2"><summary class="cursor-pointer text-[11px] font-semibold text-[#0037b0]">Kelola kuota cuti ({{ $employee->leave_quota ?? 0 }})</summary><form action="{{ route('hrd.update.leave.quota', $employee->id) }}" method="POST" class="mt-2 flex gap-2">@csrf<input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}"><input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}"><input type="number" name="leave_quota" min="0" value="{{ $employee->leave_quota ?? 0 }}" class="h-9 min-w-0 flex-1 rounded-lg border-slate-200 px-2 text-xs"><button type="submit" class="recap-primary h-9 rounded-lg px-3 text-xs font-bold">Simpan</button></form></details>
                        </article>
                    @empty
                        <div class="rounded-xl border border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500">Belum ada data rekap pada periode ini.</div>
                    @endforelse
                    <div id="mobileSearchEmpty" class="hidden rounded-xl border border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500">Tidak ada pegawai yang cocok dengan filter.</div>
                </div>
                <section class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm"><div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-[#0037b0]"><span class="material-symbols-outlined text-base">policy</span></span><div><h3 class="font-display text-sm font-bold text-slate-900">Tata Kelola Kuota Cuti</h3><p class="text-[11px] text-slate-500">Nilai kuota aktual per pegawai</p></div></div><div class="mt-3 relative"><span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-base text-slate-400">search</span><input id="quotaSearchMobile" type="search" oninput="syncRecapSearch(this.value)" placeholder="Cek nama atau role..." class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 text-xs focus:border-[#0037b0] focus:ring-blue-100"></div><p class="mt-2 text-[11px] leading-5 text-slate-500">Pengaturan kuota dilakukan pada detail masing-masing pegawai.</p></section>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-slate-100/70 p-4 sm:p-5"><div class="flex items-start gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-[#0037b0]"><span class="material-symbols-outlined">verified_user</span></span><div><h2 class="font-display text-sm font-bold text-slate-900">Catatan Tata Kelola Rekap</h2><p class="mt-1 text-sm leading-6 text-slate-600">Rekap mengikuti data attendance, shift, izin approved, dan cuti approved pada rentang tanggal yang dipilih. Nilai kuota ditampilkan dari data pegawai aktual.</p></div></div></section>
        </div>
    </main>

    {{-- Existing shift calendar modal and AJAX detail flow --}}
    <div id="shiftModal" class="fixed inset-0 z-[60] hidden items-center justify-center overflow-y-auto bg-slate-950/50 p-3 backdrop-blur-sm md:p-5">
        <div class="flex h-[92dvh] max-h-[92dvh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-4 py-4 sm:px-5"><div><h2 class="font-display text-lg font-bold text-slate-900">Kalender Shift Bulan</h2><p class="text-xs text-slate-500">Jadwal seluruh pegawai</p></div><div class="flex items-center gap-2"><select id="calendarRoleFilter" class="hidden rounded-xl border-slate-200 px-3 py-2 text-xs shadow-sm sm:block"><option value="all">Semua Role</option>@foreach($roles as $role)<option value="{{ $role }}">{{ $roleLabels[$role] ?? ucfirst($role) }}</option>@endforeach</select><button id="exportCalendarBtn" type="button" class="hidden rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700 sm:inline-flex"><span class="material-symbols-outlined mr-1 align-middle text-sm">download</span>Export</button><button id="closeShiftModal" type="button" class="rounded-xl bg-slate-100 p-2 font-bold text-slate-600 hover:bg-slate-200"><span class="material-symbols-outlined">close</span></button></div></div>
            <div class="min-h-0 flex-1 overflow-auto p-2"><div id="calendarStatus" class="hidden rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800" role="status" aria-live="polite"></div><div id="calendar"></div></div>
        </div>
    </div>

    @vite('resources/js/vendor-flatpickr.js')
    <script>
        const fullCalendarEntry = @js(Vite::asset('resources/js/vendor-fullcalendar.js'));
        const calendarEventsEndpoint = @js(route('hrd.calendar.events'));
        let activeRecapRole = 'all';
        let activeRecapStatus = 'all';
        let calendarLoadPromise = null;
        let calendarInitPromise = null;
        const calendarEventsCache = new Map();
        const calendarEventsRequests = new Map();

        /*
        | Pemilih tanggal periode memakai format tampilan yang eksplisit
        | ("10 Nov 2026"). Nilai yang dikirim ke server tetap Y-m-d, sehingga
        | tidak bergantung pada format lokal browser (MM/DD/YYYY vs DD/MM/YYYY).
        */
        function updatePeriodPreview() {
            const start = document.getElementById('recapStartDate')?.value;
            const end = document.getElementById('recapEndDate')?.value;
            const preview = document.getElementById('periodPreview');
            if (!preview) return;

            const namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const format = (value) => {
                if (!value) return '—';
                const parts = value.split('-');
                if (parts.length !== 3) return value;
                const [tahun, bulan, tanggal] = parts;
                return `${parseInt(tanggal, 10)} ${namaBulan[parseInt(bulan, 10) - 1] ?? bulan} ${tahun}`;
            };

            preview.textContent = 'Periode dipilih: ' + format(start) + ' – ' + format(end);
        }

        function initPeriodDatePickers() {
            const startInput = document.getElementById('recapStartDate');
            const endInput = document.getElementById('recapEndDate');
            if (!startInput || !endInput) return;

            // Fallback preview saat Flatpickr belum/gagal dimuat.
            startInput.addEventListener('change', updatePeriodPreview);
            endInput.addEventListener('change', updatePeriodPreview);
            updatePeriodPreview();

            const flatpickr = window.flatpickr;
            if (!flatpickr) return;

            const config = {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd M Y',
                allowInput: false,
                disableMobile: true,
                altInputClass: 'h-8 w-[122px] rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-700 focus:border-[#0037b0] focus:ring-blue-100',
                onChange: updatePeriodPreview,
            };

            flatpickr(startInput, config);
            flatpickr(endInput, config);
        }

        function syncRecapSearch(value) {
            document.querySelectorAll('#searchInputDesktop, #searchInputMobile, #quotaSearchDesktop, #quotaSearchMobile').forEach((input) => { if (input.value !== (value || '')) input.value = value || ''; });
            applyRecapFilters();
        }

        function clearRecapSearch() { syncRecapSearch(''); }

        function applyRecapFilters() {
            const keyword = (document.getElementById('searchInputDesktop')?.value || document.getElementById('searchInputMobile')?.value || '').toLowerCase().trim();
            const role = document.getElementById('roleFilterDesktop')?.value || document.getElementById('roleFilterMobile')?.value || 'all';
            const results = document.querySelectorAll('.recap-result');
            let visible = 0;
            results.forEach((result) => {
                const matchesText = !keyword || (result.dataset.search || '').includes(keyword);
                const matchesRole = role === 'all' || result.dataset.role === role;
                const matchesStatus = activeRecapStatus === 'all' || result.dataset['status' + activeRecapStatus.charAt(0).toUpperCase() + activeRecapStatus.slice(1)] === '1';
                const show = matchesText && matchesRole && matchesStatus;
                result.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            document.querySelectorAll('#roleFilterDesktop, #roleFilterMobile').forEach((select) => { if (select.value !== role) select.value = role; });
            document.querySelectorAll('.quota-result').forEach((result) => result.classList.toggle('hidden', !!keyword && !(result.dataset.search || '').includes(keyword)));
            document.querySelectorAll('[id^="clearSearch"]').forEach((button) => button.classList.toggle('hidden', !keyword));
            document.getElementById('desktopSearchEmpty')?.classList.toggle('hidden', visible > 0 || results.length === 0);
            document.getElementById('mobileSearchEmpty')?.classList.toggle('hidden', visible > 0 || results.length === 0);
        }

        function setStatusFilter(status, button) {
            activeRecapStatus = status;
            document.querySelectorAll('.status-filter').forEach((item) => { item.classList.remove('bg-[#0037b0]', 'text-white'); item.classList.add('bg-white', 'text-slate-600', 'border', 'border-slate-200'); });
            button.classList.remove('bg-white', 'text-slate-600', 'border', 'border-slate-200');
            button.classList.add('bg-[#0037b0]', 'text-white');
            applyRecapFilters();
        }

        function filterRole(role, button) { activeRecapRole = role; activeRecapStatus = 'all'; const select = document.getElementById('roleFilterMobile') || document.getElementById('roleFilterDesktop'); if (select) select.value = role; applyRecapFilters(); }

        function openShiftCalendar() {
            const modal = document.getElementById('shiftModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            initializeShiftCalendar().catch(() => {});
        }

        function calendarRequestParams() {
            return {
                start_date: document.querySelector('input[name="start_date"]')?.value || '{{ $startDate->format('Y-m-d') }}',
                end_date: document.querySelector('input[name="end_date"]')?.value || '{{ $endDate->format('Y-m-d') }}',
                role: document.getElementById('calendarRoleFilter')?.value || 'all',
            };
        }

        function calendarRequestKey(params) {
            return [params.start_date, params.end_date, params.role].join('|');
        }

        async function fetchCalendarEvents() {
            const params = calendarRequestParams();
            const key = calendarRequestKey(params);
            if (calendarEventsCache.has(key)) return calendarEventsCache.get(key);
            if (calendarEventsRequests.has(key)) return calendarEventsRequests.get(key);

            const query = new URLSearchParams(params);
            const request = fetch(`${calendarEventsEndpoint}?${query.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            }).then(async (response) => {
                let payload;
                try {
                    payload = await response.json();
                } catch (error) {
                    throw new Error('Respons kalender tidak valid.');
                }
                if (!response.ok || payload?.success !== true || !Array.isArray(payload.events)) {
                    throw new Error(payload?.message || 'Data kalender gagal dimuat.');
                }
                calendarEventsCache.set(key, payload.events);
                return payload.events;
            }).finally(() => {
                calendarEventsRequests.delete(key);
            });

            calendarEventsRequests.set(key, request);
            return request;
        }

        async function refreshCalendarEvents() {
            const events = await fetchCalendarEvents();
            if (window.calendar) {
                window.calendar.removeAllEvents();
                window.calendar.addEventSource(events);
            }
            return events;
        }

        function setCalendarStatus(message, type = 'loading') {
            const status = document.getElementById('calendarStatus');
            if (!status) return;
            status.textContent = message;
            status.className = type === 'error'
                ? 'rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800'
                : 'rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800';
            status.classList.remove('hidden');
            if (type === 'error' && !document.getElementById('retryCalendarBtn')) {
                status.insertAdjacentHTML('beforeend', ' <button id="retryCalendarBtn" type="button" class="mt-2 rounded-lg bg-rose-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-800" onclick="retryShiftCalendar()">Coba lagi</button>');
            }
            if (type !== 'error') document.getElementById('retryCalendarBtn')?.remove();
        }

        function hideCalendarStatus() {
            document.getElementById('calendarStatus')?.classList.add('hidden');
        }

        function retryShiftCalendar() {
            const status = document.getElementById('calendarStatus');
            if (status) status.textContent = 'Mencoba memuat kalender lagi...';
            calendarInitPromise = null;
            calendarLoadPromise = null;
            // Bila kalender sudah tampil (mis. gagal saat ganti filter role),
            // cukup muat ulang event tanpa membuat instance FullCalendar baru.
            const attempt = window.calendar ? refreshCalendarEvents() : initializeShiftCalendar();
            attempt.then(hideCalendarStatus).catch(() => {});
        }

        async function loadFullCalendar() {
            if (window.FullCalendar?.Calendar) return window.FullCalendar.Calendar;
            if (!calendarLoadPromise) {
                calendarLoadPromise = import(fullCalendarEntry).then((module) => {
                    const Calendar = module.default?.Calendar || module.Calendar || window.FullCalendar?.Calendar;
                    if (!Calendar) throw new Error('FullCalendar tidak tersedia dari entry Vite.');
                    window.FullCalendar = { Calendar };
                    return Calendar;
                }).catch((error) => {
                    calendarLoadPromise = null;
                    throw error;
                });
            }
            return calendarLoadPromise;
        }

        async function initializeShiftCalendar() {
            if (window.calendar) {
                window.calendar.updateSize();
                return window.calendar;
            }

            if (calendarInitPromise) return calendarInitPromise;

            setCalendarStatus('Memuat kalender shift...');
            calendarInitPromise = Promise.all([loadFullCalendar(), fetchCalendarEvents()]).then(([Calendar, events]) => {
                const calendarElement = document.getElementById('calendar');
                if (!calendarElement) throw new Error('Container kalender tidak ditemukan.');

                if (!window.calendar) {
                    window.calendar = new Calendar(calendarElement, { initialView: 'timeGridWeek', locale: 'id', allDaySlot: false, height: '100%', slotMinTime: '00:00:00', slotMaxTime: '24:00:00', expandRows: true, headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' }, events, eventClick: (info) => showShiftModal(info.event.extendedProps.user_id, info.event.title) });
                    window.calendar.render();
                }
                hideCalendarStatus();
                window.setTimeout(() => window.calendar?.updateSize(), 50);
                return window.calendar;
            }).catch((error) => {
                calendarInitPromise = null;
                setCalendarStatus('Kalender gagal dimuat. Periksa koneksi lalu coba lagi.', 'error');
                console.error('Gagal memuat FullCalendar:', error);
                throw error;
            });

            return calendarInitPromise;
        }

        function showShiftModal(userId, employeeName) {
            const selectedMonth = document.querySelector('input[name="month"]')?.value || '{{ $month }}';
            fetch(`/hrd/employee-shifts/${userId}?month=${selectedMonth}`).then((response) => response.json()).then((result) => {
                let rows = '';
                (result.data || []).forEach((item) => { const late = item.check_in && item.check_in !== '-' && item.start_time && item.check_in > item.start_time; rows += `<tr><td>${item.shift_date}</td><td>${item.shift_name ?? '-'}</td><td>${item.start_time}</td><td>${item.end_time}</td><td class="${late ? 'late' : ''}">${item.check_in ?? '-'}</td><td>${item.check_out ?? '-'}</td></tr>`; });
                if (!rows) rows = '<tr><td colspan="6" class="empty">Tidak ada data shift</td></tr>';
                if (window.Swal) { Swal.fire({ title: employeeName, html: `<div class="shift-detail-table"><table><thead><tr><th>Tanggal</th><th>Shift</th><th>Jadwal Masuk</th><th>Jadwal Keluar</th><th>Check In</th><th>Check Out</th></tr></thead><tbody>${rows}</tbody></table></div>`, width: 1100, showCancelButton: true, confirmButtonText: 'Export Excel', cancelButtonText: 'Tutup' }).then((answer) => { if (answer.isConfirmed) window.open(`/hrd/calendar/export-user/${userId}?month=${selectedMonth}`, '_blank'); }); }
            }).catch(() => { if (window.Swal) Swal.fire('Gagal', 'Tidak dapat memuat jadwal shift', 'error'); });
        }

        document.addEventListener('DOMContentLoaded', function () {
            initPeriodDatePickers();
            const calendarModal = document.getElementById('shiftModal');
            const closeCalendar = document.getElementById('closeShiftModal');
            closeCalendar?.addEventListener('click', () => { calendarModal.classList.add('hidden'); calendarModal.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); });
            calendarModal?.addEventListener('click', (event) => { if (event.target === calendarModal) closeCalendar.click(); });
            document.getElementById('calendarRoleFilter')?.addEventListener('change', function () { if (!window.calendar) return; setCalendarStatus('Memuat filter kalender...'); refreshCalendarEvents().then(hideCalendarStatus).catch((error) => { setCalendarStatus(error.message || 'Filter kalender gagal dimuat.', 'error'); }); });
            document.getElementById('exportCalendarBtn')?.addEventListener('click', function () { const role = document.getElementById('calendarRoleFilter')?.value || 'all'; const month = document.querySelector('input[name="month"]')?.value || '{{ $month }}'; window.open(role === 'all' ? `/hrd/calendar/export-all?month=${month}` : `/hrd/calendar/export?month=${month}&role=${role}`, '_blank'); });
        });
    </script>
    <style>
        .shift-detail-table { max-height: 540px; overflow: auto; text-align: left; }
        .shift-detail-table table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .shift-detail-table th, .shift-detail-table td { border: 1px solid #e2e8f0; padding: 9px; }
        .shift-detail-table th { position: sticky; top: 0; background: #f8fafc; }
        .shift-detail-table .late { color: #dc2626; font-weight: 700; }
        .shift-detail-table .empty { padding: 20px; text-align: center; color: #64748b; }
    </style>
</x-app-layout>
