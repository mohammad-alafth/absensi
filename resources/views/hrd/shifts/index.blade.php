<x-app-layout>
    @php
        $roleLabels = is_array($roleLabels ?? null) ? $roleLabels : [];
        $roleName = static function ($role) use ($roleLabels) {
            return $roleLabels[$role] ?? ucwords(str_replace('_', ' ', str_replace('pj_', '', $role)));
        };
        $shiftCount = $shifts->count();
        $overnightCount = $shifts->filter(fn ($shift) => (bool) $shift->is_overnight)->count();
        $categories = $shifts->pluck('category')->filter()->unique()->values();
        $categoryName = static fn ($category) => ucwords(str_replace('_', ' ', $category));
    @endphp

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Material+Symbols+Outlined:FILL,wght@0..1,300..700" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9ff; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-size: 1.25rem; line-height: 1; font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24; }
        .shift-desktop-only { display: none !important; }
        .shift-mobile-only { display: block !important; }
        @media (min-width: 768px) {
            .shift-desktop-only { display: block !important; }
            .shift-mobile-only { display: none !important; }
        }
        .shift-modal-panel {
            width: min(calc(100vw - 24px), 42rem) !important;
            height: min(90dvh, 720px) !important;
            max-height: 90dvh !important;
        }
        .shift-primary-button {
            background-color: #1e40af !important;
            border: 1px solid #1e40af !important;
            color: #ffffff !important;
            opacity: 1 !important;
        }
        .shift-primary-button:hover { background-color: #17358f !important; }
        .shift-primary-button:focus-visible { outline: 2px solid #93c5fd; outline-offset: 2px; }
        .shift-primary-button:disabled {
            background-color: #bfdbfe !important;
            border-color: #93c5fd !important;
            color: #1e3a8a !important;
            opacity: 1 !important;
        }
        .shift-secondary-button {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #334155 !important;
            opacity: 1 !important;
        }
    </style>

    <main class="min-h-screen bg-[#f8f9ff] px-4 pb-28 pt-6 sm:px-6 lg:px-8 lg:pt-8">
        <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-6">
            {{-- Stitch page title and contextual status; global navigation remains in x-app-layout. --}}
            <section class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><span class="md:hidden">Master Shift &amp; Scoping</span><span class="hidden md:inline">Master Data Shift &amp; Role Assignment</span></h1>
                    <p class="mt-1.5 max-w-3xl text-sm leading-6 text-slate-500">Katalog shift resmi faskes dan matriks pembatasan wewenang plotting PJ Unit RS Mata PEK Eye Center.</p>
                </div>
                <button type="button" onclick="toggleShiftModal('modal-tambah', true)" class="shift-primary-button inline-flex min-h-10 w-fit items-center gap-1.5 rounded-xl px-3.5 text-xs font-bold shadow-md shadow-blue-200 md:hidden"><span class="material-symbols-outlined text-base">add</span>Shift Baru</button>
                <div class="hidden w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 shadow-sm md:inline-flex">
                    <span class="material-symbols-outlined text-[18px] text-[#1e40af]">verified</span>
                    <div><p class="text-xs font-bold text-[#1e40af]">Data master tersedia</p><p class="text-[10px] text-slate-500">{{ $shiftCount }} shift dari sistem</p></div>
                </div>
            </section>

            {{-- Data-backed summary only; no mock Stitch metrics are copied. --}}
            <section aria-label="Ringkasan master shift" class="grid grid-cols-3 gap-2 sm:gap-3">
                <div class="flex min-w-0 flex-col justify-between rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:p-4">
                    <div><p class="truncate text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs sm:normal-case sm:tracking-normal">Total Shift</p><p class="font-display mt-1 text-xl font-bold text-[#1e40af] sm:text-2xl">{{ $shiftCount }} <span class="hidden text-sm font-normal text-slate-500 sm:inline">Shift</span></p><p class="hidden text-[11px] text-slate-500 sm:block">Data aktual tersimpan</p></div>
                    <span class="mt-2 flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-[#1e40af] sm:mt-0 sm:h-11 sm:w-11 sm:rounded-xl"><span class="material-symbols-outlined text-base sm:text-xl">schedule</span></span>
                </div>
                <div class="flex min-w-0 flex-col justify-between rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:p-4">
                    <div><p class="truncate text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs sm:normal-case sm:tracking-normal">Role Scope</p><p class="font-display mt-1 text-xl font-bold text-slate-900 sm:text-2xl">{{ count($roles) }} <span class="hidden text-sm font-normal text-slate-500 sm:inline">Role</span></p><p class="hidden text-[11px] text-slate-500 sm:block">Pilihan role scope</p></div>
                    <span class="mt-2 flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 sm:mt-0 sm:h-11 sm:w-11 sm:rounded-xl"><span class="material-symbols-outlined text-base sm:text-xl">group</span></span>
                </div>
                <div class="flex min-w-0 flex-col justify-between rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:p-4">
                    <div><p class="truncate text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs sm:normal-case sm:tracking-normal">Cross-day</p><p class="font-display mt-1 text-xl font-bold text-slate-900 sm:text-2xl">{{ $overnightCount }} <span class="hidden text-sm font-normal text-slate-500 sm:inline">Shift</span></p><p class="hidden text-[11px] text-slate-500 sm:block">Lewat tengah malam</p></div>
                    <span class="mt-2 flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 sm:mt-0 sm:h-11 sm:w-11 sm:rounded-xl"><span class="material-symbols-outlined text-base sm:text-xl">bedtime</span></span>
                </div>
            </section>

            {{-- Stitch mobile has a notice before the list; this copy reflects the actual role contract. --}}
            <section class="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-100/70 p-3 text-sm text-slate-600 md:hidden">
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-[#1e40af]">admin_panel_settings</span>
                <p><strong class="font-semibold text-[#1e40af]">Role scope master shift:</strong> setiap shift menyimpan satu role yang diizinkan melalui <code class="rounded bg-white px-1 text-xs">allowed_roles</code>.</p>
            </section>

            {{-- Stitch toolbar: search is client-side over the rendered, authoritative shift data. --}}
            <section class="shift-desktop-only rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative w-full lg:max-w-2xl">
                        <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">search</span>
                        <input id="shift-search-desktop" type="search" autocomplete="off" oninput="filterShifts(this.value)" placeholder="Cari kode shift, nama tugas, atau role spesifik..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1e40af] focus:ring-2 focus:ring-blue-100">
                        <button id="shift-search-clear-desktop" type="button" aria-label="Hapus pencarian" onclick="clearShiftSearch()" class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><span class="material-symbols-outlined text-base">close</span></button>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="relative"><span class="sr-only">Filter role target</span><select id="shift-role-filter" onchange="filterShifts()" class="h-11 rounded-xl border border-slate-200 bg-white px-3.5 pr-8 text-sm font-semibold text-slate-700 focus:border-[#1e40af] focus:ring-blue-100"><option value="">Semua Role Target</option>@foreach($roles as $role)<option value="{{ strtolower($role) }}">{{ $roleName($role) }}</option>@endforeach</select></label>
                        <button type="button" onclick="toggleShiftModal('modal-tambah', true)" class="shift-primary-button inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl px-5 text-sm font-bold shadow-md shadow-blue-200 transition active:scale-[.98]"><span class="material-symbols-outlined text-lg">add</span>Tambah Master Shift Baru</button>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-200 pt-3">
                    <span class="mr-1 flex items-center gap-1 text-xs font-semibold text-slate-500"><span class="material-symbols-outlined text-base">filter_alt</span>Kategori:</span>
                    <button type="button" class="category-filter rounded-full bg-[#1e40af] px-3.5 py-1.5 text-xs font-semibold text-white" data-category-filter="" onclick="setCategoryFilter('')">Semua ({{ $shiftCount }})</button>
                    @foreach($categories as $category)
                        <button type="button" class="category-filter rounded-full border border-slate-200 bg-slate-50 px-3.5 py-1.5 text-xs font-semibold text-slate-600" data-category-filter="{{ strtolower($category) }}" onclick="setCategoryFilter('{{ strtolower($category) }}')">{{ $categoryName($category) }}</button>
                    @endforeach
                </div>
            </section>

            {{-- Desktop: Stitch matrix composition, limited to fields supported by the application. --}}
            <section class="shift-desktop-only overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div><h2 class="font-display text-base font-bold text-slate-900">Matriks Konfigurasi Shift RS Mata PEK</h2><p class="mt-1 text-xs text-slate-500">Acuan jam kerja presensi dan pembatasan role plotting jadwal unit.</p></div>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-[#1e40af]"><span class="h-1.5 w-1.5 rounded-full bg-[#1e40af]"></span>{{ $shiftCount }} Shift Ditampilkan</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] border-collapse text-left">
                        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold text-slate-600">
                            <tr><th class="px-4 py-3.5">ID Shift</th><th class="px-4 py-3.5">Nama Shift &amp; Kategori</th><th class="px-4 py-3.5">Jam Kerja</th><th class="px-4 py-3.5">Durasi</th><th class="px-4 py-3.5">Cross-midnight</th><th class="min-w-[220px] px-4 py-3.5">Role Scoping</th><th class="px-4 py-3.5 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @forelse($shifts as $shift)
                                @php
                                    $currentRole = is_array($shift->allowed_roles) && !empty($shift->allowed_roles) ? $shift->allowed_roles[0] : null;
                                    $startMinutes = \Carbon\Carbon::parse($shift->start_time)->hour * 60 + \Carbon\Carbon::parse($shift->start_time)->minute;
                                    $endMinutes = \Carbon\Carbon::parse($shift->end_time)->hour * 60 + \Carbon\Carbon::parse($shift->end_time)->minute;
                                    $durationMinutes = $endMinutes - $startMinutes + ($shift->is_overnight ? 1440 : 0);
                                @endphp
                                <tr class="shift-result transition-colors hover:bg-slate-50/70" data-shift-search="{{ strtolower($shift->name . ' ' . $shift->id . ' ' . ($currentRole ? $roleName($currentRole) : '')) }}" data-shift-role="{{ strtolower($currentRole ?? '') }}" data-shift-category="{{ strtolower($shift->category ?? '') }}">
                                    <td class="px-4 py-4 align-top"><span class="inline-flex rounded-md border border-blue-100 bg-blue-50 px-2.5 py-1 font-display text-xs font-bold text-[#1e40af]">#{{ $shift->id }}</span></td>
                                    <td class="px-4 py-4 align-top"><p class="font-display font-semibold text-slate-900">{{ $shift->name }}</p><p class="mt-1 text-[11px] text-slate-500">{{ $shift->category ? $categoryName($shift->category) : 'Master shift resmi' }}</p></td>
                                    <td class="px-4 py-4 align-top"><p class="font-display font-bold tabular-nums text-slate-900">{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}</p><p class="mt-1 text-[11px] text-slate-500">{{ $shift->is_overnight ? 'Hari berikutnya' : 'Hari yang sama' }}</p></td>
                                    <td class="px-4 py-4 align-top"><p class="font-medium text-slate-800">{{ intdiv(max($durationMinutes, 0), 60) }} jam{{ $durationMinutes % 60 ? ' ' . ($durationMinutes % 60) . ' menit' : '' }}</p><p class="mt-1 text-[11px] text-slate-500">Durasi terhitung</p></td>
                                    <td class="px-4 py-4 align-top"><span class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $shift->is_overnight ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">{{ $shift->is_overnight ? 'Ya' : 'Tidak' }}</span></td>
                                    <td class="px-4 py-4 align-top"><span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-[#1e40af]"><span class="material-symbols-outlined text-[14px]">lock</span>{{ $currentRole ? $roleName($currentRole) : 'Belum dipilih' }}</span><p class="mt-1 text-[11px] italic text-slate-400">Eksklusif untuk role terpilih</p></td>
                                    <td class="px-4 py-4 text-right align-top"><button type="button" title="Edit Shift" aria-label="Edit {{ $shift->name }}" onclick="toggleShiftModal('modal-edit-{{ $shift->id }}', true)" class="rounded-lg p-2 text-slate-500 transition hover:bg-blue-50 hover:text-[#1e40af]"><span class="material-symbols-outlined">edit</span></button></td>
                                </tr>
                            @empty
                                <tr class="shift-no-data"><td colspan="7" class="px-6 py-14 text-center text-sm text-slate-500">Belum ada master shift.</td></tr>
                            @endforelse
                            <tr id="shift-empty-desktop" class="hidden"><td colspan="7" class="px-6 py-14 text-center text-sm text-slate-500">Tidak ada shift yang cocok dengan pencarian.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Mobile: Stitch card composition with metrics and role guard. --}}
            <section class="shift-mobile-only space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-display text-sm font-bold text-slate-900">Daftar Shift</h2>
                </div>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-lg text-slate-400">search</span>
                    <input id="shift-search-mobile" type="search" autocomplete="off" oninput="filterShifts(this.value)" placeholder="Cari kode shift, unit, atau nama role..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#1e40af] focus:ring-2 focus:ring-blue-100">
                    <button id="shift-search-clear-mobile" type="button" aria-label="Hapus pencarian" onclick="clearShiftSearch()" class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><span class="material-symbols-outlined text-base">close</span></button>
                </div>
                <div class="no-scrollbar flex items-center gap-2 overflow-x-auto py-0.5">
                    <button type="button" class="category-filter shrink-0 rounded-full bg-[#1e40af] px-3 py-1.5 text-xs font-semibold text-white" data-category-filter="" onclick="setCategoryFilter('')">Semua ({{ $shiftCount }})</button>
                    @foreach($categories as $category)
                        <button type="button" class="category-filter shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600" data-category-filter="{{ strtolower($category) }}" onclick="setCategoryFilter('{{ strtolower($category) }}')">{{ $categoryName($category) }}</button>
                    @endforeach
                </div>
                @forelse($shifts as $shift)
                    @php
                        $currentRole = is_array($shift->allowed_roles) && !empty($shift->allowed_roles) ? $shift->allowed_roles[0] : null;
                        $startMinutes = \Carbon\Carbon::parse($shift->start_time)->hour * 60 + \Carbon\Carbon::parse($shift->start_time)->minute;
                        $endMinutes = \Carbon\Carbon::parse($shift->end_time)->hour * 60 + \Carbon\Carbon::parse($shift->end_time)->minute;
                        $durationMinutes = $endMinutes - $startMinutes + ($shift->is_overnight ? 1440 : 0);
                    @endphp
                    <article class="shift-result space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" data-shift-search="{{ strtolower($shift->name . ' ' . $shift->id . ' ' . ($currentRole ? $roleName($currentRole) : '')) }}" data-shift-role="{{ strtolower($currentRole ?? '') }}" data-shift-category="{{ strtolower($shift->category ?? '') }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-2.5"><span class="inline-flex shrink-0 rounded-lg bg-blue-50 px-2.5 py-1 font-display text-xs font-bold tracking-wide text-[#1e40af]">#{{ $shift->id }}</span><div class="min-w-0"><h3 class="font-display truncate text-sm font-semibold text-slate-900">{{ $shift->name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $shift->category ? $categoryName($shift->category) : 'Master shift resmi RS Mata PEK' }}</p></div></div>
                            <button type="button" title="Edit Shift" aria-label="Edit {{ $shift->name }}" onclick="toggleShiftModal('modal-edit-{{ $shift->id }}', true)" class="shrink-0 rounded-lg p-1 text-slate-500 hover:bg-slate-100 hover:text-[#1e40af]"><span class="material-symbols-outlined text-lg">edit</span></button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                            <div class="flex min-w-0 items-center gap-2"><span class="material-symbols-outlined text-base text-[#1e40af]">timelapse</span><div class="min-w-0"><span class="block text-[10px] font-bold tracking-wide text-slate-500">JAM KERJA</span><span class="block truncate text-xs font-semibold text-slate-900">{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}</span></div></div>
                            <div class="flex min-w-0 items-center gap-2"><span class="material-symbols-outlined text-base text-slate-500">{{ $shift->is_overnight ? 'bedtime' : 'timer' }}</span><div class="min-w-0"><span class="block text-[10px] font-bold tracking-wide text-slate-500">{{ $shift->is_overnight ? 'CROSS-MIDNIGHT' : 'DURASI' }}</span><span class="block truncate text-xs font-semibold text-slate-900">{{ intdiv(max($durationMinutes, 0), 60) }} jam{{ $durationMinutes % 60 ? ' ' . ($durationMinutes % 60) . ' menit' : '' }}</span></div></div>
                        </div>
                        <div class="pt-1"><div class="mb-1.5 flex items-center justify-between gap-2"><span class="flex items-center gap-1 text-[10px] font-bold tracking-wide text-slate-500"><span class="material-symbols-outlined text-xs text-[#1e40af]">lock</span> OTORISASI ROLE RESMI</span><span class="truncate text-[10px] font-semibold text-[#1e40af]">{{ $currentRole ? $roleName($currentRole) : 'Belum dipilih' }}</span></div><div class="flex flex-wrap gap-1.5"><span class="inline-flex items-center gap-1 rounded-md border border-blue-100 bg-blue-50 px-2 py-1 text-[11px] font-semibold text-[#1e40af]"><span class="material-symbols-outlined text-xs">badge</span>{{ $currentRole ? $roleName($currentRole) : 'Role belum dipilih' }}</span></div></div>
                    </article>
                @empty
                    <div class="rounded-xl border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">Belum ada master shift.</div>
                @endforelse
                <div id="shift-empty-mobile" class="hidden rounded-xl border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">Tidak ada shift yang cocok dengan pencarian.</div>
            </section>

            <section class="flex flex-col items-start gap-4 rounded-2xl border border-slate-200 bg-slate-100/70 p-5 md:flex-row">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#1e40af]"><span class="material-symbols-outlined">gavel</span></span>
                <div><h3 class="font-display text-sm font-bold text-slate-900">Ketentuan Role Scoping Master Shift</h3><p class="mt-1 text-sm leading-6 text-slate-600">Role yang dipilih pada konfigurasi shift disimpan sebagai cakupan pengguna. Saat PJ melakukan plotting, data shift yang tersedia mengikuti role yang tersimpan pada master.</p></div>
            </section>
        </div>
    </main>

    {{-- Stitch modal visual, with only the application's supported CRUD fields. --}}
    <div id="modal-tambah" class="fixed inset-0 z-[60] hidden items-center justify-center overflow-x-hidden overflow-y-auto bg-slate-950/50 p-3 pb-[calc(.75rem+env(safe-area-inset-bottom))] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modal-tambah-title">
        <form action="{{ route('hrd.shifts.store') }}" method="POST" class="shift-modal-panel flex min-w-0 max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            @csrf
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6"><div class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1e40af] text-white"><span class="material-symbols-outlined">more_time</span></span><div class="min-w-0"><h2 id="modal-tambah-title" class="font-display truncate text-base font-bold text-slate-900">Tambah Master Shift &amp; Scope Role</h2><p class="truncate text-xs text-slate-500">Definisikan jam operasional dan hak akses role.</p></div></div><button type="button" aria-label="Tutup" onclick="toggleShiftModal('modal-tambah', false)" class="shift-secondary-button shrink-0 rounded-lg p-2"><span class="material-symbols-outlined">close</span></button></div>
            <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain p-5 sm:p-6"><div><h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-[#1e40af]"><span class="material-symbols-outlined text-lg">badge</span>1. Identitas Shift</h3><label class="block text-xs font-semibold text-slate-700">Nama Shift *<input type="text" name="name" placeholder="Contoh: Shift Pagi" class="mt-1.5 h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-400 focus:ring-blue-100" required></label></div><hr class="border-slate-200"><div><h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-[#1e40af]"><span class="material-symbols-outlined text-lg">schedule</span>2. Pengaturan Jam Kerja</h3><div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><label class="block text-xs font-semibold text-slate-700">Jam Masuk<input type="time" name="start_time" class="mt-1.5 h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-400 focus:ring-blue-100" required></label><label class="block text-xs font-semibold text-slate-700">Jam Pulang<input type="time" name="end_time" class="mt-1.5 h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-400 focus:ring-blue-100" required></label></div><label class="mt-4 flex items-start gap-2.5 text-xs font-semibold text-slate-700"><input type="checkbox" name="is_overnight" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1e40af] focus:ring-blue-200"> Lompat Hari / Cross-midnight (Shift Malam)</label></div><hr class="border-slate-200"><div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4"><h3 class="flex items-center gap-2 text-sm font-bold text-[#1e40af]"><span class="material-symbols-outlined">admin_panel_settings</span>3. Aturan Role Scoping</h3><p class="mt-2 mb-4 text-xs leading-5 text-slate-600">Shift hanya disimpan untuk satu role scope sesuai kontrak aplikasi.</p><label class="block text-xs font-semibold text-slate-700">Role pengguna<select name="role" class="mt-1.5 h-11 w-full rounded-xl border-slate-200 bg-white text-sm focus:border-blue-400 focus:ring-blue-100" required><option value="">Pilih role pengguna</option>@foreach($roles as $role)<option value="{{ $role }}">{{ $roleName($role) }}</option>@endforeach</select></label></div></div>
            <div class="flex shrink-0 flex-col-reverse justify-between gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 pb-[calc(.75rem+env(safe-area-inset-bottom))] sm:flex-row sm:items-center sm:px-6"><button type="button" onclick="toggleShiftModal('modal-tambah', false)" class="shift-secondary-button rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-slate-100">Batal</button><button type="submit" class="shift-primary-button inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-bold shadow-md"><span class="material-symbols-outlined mr-1 align-middle text-base">save</span>Simpan Master Shift</button></div>
        </form>
    </div>

    @foreach($shifts as $shift)
        @php $currentRole = is_array($shift->allowed_roles) && !empty($shift->allowed_roles) ? $shift->allowed_roles[0] : null; @endphp
        <div id="modal-edit-{{ $shift->id }}" class="fixed inset-0 z-[60] hidden items-center justify-center overflow-x-hidden overflow-y-auto bg-slate-950/50 p-3 pb-[calc(.75rem+env(safe-area-inset-bottom))] backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modal-edit-title-{{ $shift->id }}">
            <form action="{{ route('hrd.shifts.update', $shift->id) }}" method="POST" class="shift-modal-panel flex min-w-0 max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                @csrf @method('PUT')
                <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6"><div class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1e40af] text-white"><span class="material-symbols-outlined">edit_calendar</span></span><div class="min-w-0"><h2 id="modal-edit-title-{{ $shift->id }}" class="font-display truncate text-base font-bold text-slate-900">Edit Master Shift &amp; Scope Role</h2><p class="truncate text-xs text-slate-500">{{ $shift->name }}</p></div></div><button type="button" aria-label="Tutup" onclick="toggleShiftModal('modal-edit-{{ $shift->id }}', false)" class="shift-secondary-button shrink-0 rounded-lg p-2"><span class="material-symbols-outlined">close</span></button></div>
                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain p-5 sm:p-6"><div><h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-[#1e40af]"><span class="material-symbols-outlined text-lg">schedule</span>Pengaturan Jam Kerja</h3><div class="grid grid-cols-1 gap-4 sm:grid-cols-2"><label class="block text-xs font-semibold text-slate-700">Jam Masuk<input type="text" name="start_time" value="{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}" class="timepicker mt-1.5 h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-400 focus:ring-blue-100" required></label><label class="block text-xs font-semibold text-slate-700">Jam Pulang<input type="text" name="end_time" value="{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}" class="timepicker mt-1.5 h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-400 focus:ring-blue-100" required></label></div><label class="mt-4 flex items-start gap-2.5 text-xs font-semibold text-slate-700"><input type="checkbox" name="is_overnight" {{ $shift->is_overnight ? 'checked' : '' }} class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#1e40af] focus:ring-blue-200"> Lompat Hari / Cross-midnight</label></div><div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4"><h3 class="flex items-center gap-2 text-sm font-bold text-[#1e40af]"><span class="material-symbols-outlined">admin_panel_settings</span>Aturan Role Scoping</h3><p class="mt-2 mb-4 text-xs leading-5 text-slate-600">Role terpilih akan disimpan ke <code class="rounded bg-white px-1">allowed_roles</code>.</p><label class="block text-xs font-semibold text-slate-700">Role pengguna<select name="role" class="mt-1.5 h-11 w-full rounded-xl border-slate-200 bg-white text-sm focus:border-blue-400 focus:ring-blue-100" required>@foreach($roles as $role)<option value="{{ $role }}" {{ $currentRole === $role ? 'selected' : '' }}>{{ $roleName($role) }}</option>@endforeach</select></label></div></div>
                <div class="flex shrink-0 flex-col-reverse justify-between gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 pb-[calc(.75rem+env(safe-area-inset-bottom))] sm:flex-row sm:items-center sm:px-6"><button type="button" onclick="toggleShiftModal('modal-edit-{{ $shift->id }}', false)" class="shift-secondary-button rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-slate-100">Batal</button><button type="submit" class="shift-primary-button inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-bold shadow-md"><span class="material-symbols-outlined mr-1 align-middle text-base">save</span>Simpan Perubahan</button></div>
            </form>
        </div>
    @endforeach

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        let activeCategory = '';

        function filterShifts(value) {
            const desktopInput = document.getElementById('shift-search-desktop');
            const mobileInput = document.getElementById('shift-search-mobile');
            const searchValue = value === undefined ? (desktopInput?.value || mobileInput?.value || '') : value;
            const query = searchValue.trim().toLowerCase();
            const role = (document.getElementById('shift-role-filter')?.value || '').toLowerCase();
            const results = document.querySelectorAll('.shift-result');
            let visible = 0;

            document.querySelectorAll('input[id^="shift-search-"]').forEach((input) => {
                if (input.value !== searchValue) input.value = searchValue;
            });
            document.querySelectorAll('button[id^="shift-search-clear-"]').forEach((button) => {
                button.classList.toggle('hidden', query.length === 0);
            });
            results.forEach((result) => {
                const matchesText = query === '' || (result.dataset.shiftSearch || '').toLowerCase().includes(query);
                const matchesRole = role === '' || (result.dataset.shiftRole || '').toLowerCase() === role;
                const matchesCategory = activeCategory === '' || (result.dataset.shiftCategory || '').toLowerCase() === activeCategory;
                const matches = matchesText && matchesRole && matchesCategory;
                result.classList.toggle('hidden', !matches);
                if (matches) visible += 1;
            });

            const hasRenderedShifts = results.length > 0;
            const hasFilters = query !== '' || role !== '' || activeCategory !== '';
            document.getElementById('shift-empty-desktop')?.classList.toggle('hidden', !hasRenderedShifts || visible > 0 || !hasFilters);
            document.getElementById('shift-empty-mobile')?.classList.toggle('hidden', !hasRenderedShifts || visible > 0 || !hasFilters);
        }

        function setCategoryFilter(category) {
            activeCategory = (category || '').toLowerCase();
            document.querySelectorAll('.category-filter').forEach((button) => {
                const selected = (button.dataset.categoryFilter || '') === activeCategory;
                button.classList.toggle('bg-[#1e40af]', selected);
                button.classList.toggle('text-white', selected);
                button.classList.toggle('bg-white', !selected);
                button.classList.toggle('bg-slate-50', !selected);
                button.classList.toggle('border', !selected);
                button.classList.toggle('border-slate-200', !selected);
                button.classList.toggle('text-slate-600', !selected);
            });
            filterShifts();
        }

        function clearShiftSearch() {
            filterShifts('');
        }

        function toggleShiftModal(id, show) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.toggle('hidden', !show);
            modal.classList.toggle('flex', show);
            document.body.classList.toggle('overflow-hidden', show);
        }
        document.querySelectorAll('.timepicker').forEach((input) => flatpickr(input, { enableTime: true, noCalendar: true, dateFormat: 'H:i', time_24hr: true, minuteIncrement: 15 }));
        document.querySelectorAll('[role="dialog"]').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) toggleShiftModal(modal.id, false); }));
    </script>
</x-app-layout>
