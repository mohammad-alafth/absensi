<x-app-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-6 pb-28 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <header class="rounded-3xl bg-slate-950 px-6 py-7 text-white shadow-xl sm:px-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-300">Admin Control Center</p>
                        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Pusat kendali sistem</h1>
                        <p class="mt-2 max-w-2xl text-sm text-slate-300">Ringkasan aktual konfigurasi dan aktivitas administrasi. Nilai sensitif tidak ditampilkan.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm">
                        <p class="font-bold">{{ $user->name }}</p>
                        <p class="text-slate-300">Role: {{ $user->role }}</p>
                    </div>
                </div>
            </header>

            <section aria-label="Ringkasan sistem" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach([
                    ['label' => 'Total pengguna', 'value' => $summary['users'], 'tone' => 'blue'],
                    ['label' => 'Menunggu persetujuan akun', 'value' => $summary['pendingUsers'], 'tone' => 'amber'],
                    ['label' => 'Presensi hari ini', 'value' => $summary['attendanceToday'], 'tone' => 'emerald'],
                    ['label' => 'Pengajuan pending', 'value' => $summary['pendingSubmissions'], 'tone' => 'violet'],
                ] as $card)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-black text-slate-900">{{ number_format($card['value']) }}</p>
                    </article>
                @endforeach
            </section>

            <div class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Modul administrasi</h2>
                            <p class="mt-1 text-sm text-slate-500">Akses tetap mengikuti route dan authorization masing-masing modul.</p>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($menus as $menu)
                            @if($menu['available'])
                                <a href="{{ $menu['href'] }}" @class([
                                    'group rounded-2xl border p-4 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                    'border-blue-300 bg-blue-50/60 ring-1 ring-blue-100' => $menu['route'] === 'admin.control-center',
                                    'border-slate-200 bg-white hover:border-blue-300 hover:bg-blue-50' => $menu['route'] !== 'admin.control-center',
                                ])>
                            @else
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                            @endif
                                <div class="flex items-start justify-between gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                                        <span class="material-symbols-outlined text-[24px]" aria-hidden="true">{{ $menu['icon'] }}</span>
                                    </span>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 {{ $menu['statusClass'] }}">{{ $menu['status'] }}</span>
                                </div>
                                <p class="mt-4 font-bold text-slate-900">{{ $menu['label'] }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $menu['description'] }}</p>
                                @if($menu['available'])
                                    <span class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-blue-700 group-hover:text-blue-800">
                                        {{ $menu['route'] === 'admin.control-center' ? 'Sedang dibuka' : 'Buka modul' }}
                                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">arrow_forward</span>
                                    </span>
                                @else
                                    <span class="mt-4 inline-flex text-xs font-semibold text-slate-500">Belum tersedia</span>
                                @endif
                            @if($menu['available'])
                                </a>
                            @else
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-black text-slate-900">Status keamanan</h2>
                    <div class="mt-5 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-4"><span class="text-slate-500">Verifikasi wajah</span><strong class="text-slate-900">{{ $security['faceEnabled'] ? 'Aktif' : 'Nonaktif' }}</strong></div>
                        <div class="flex items-center justify-between gap-4"><span class="text-slate-500">Anti-spoof</span><strong class="text-slate-900">{{ $security['facePolicyEnabled'] ? 'Aktif' : 'Nonaktif' }}</strong></div>
                        <div class="flex items-center justify-between gap-4"><span class="text-slate-500">Ambang match</span><strong class="text-slate-900">{{ number_format($security['matchThreshold'], 2) }}</strong></div>
                        <div class="flex items-center justify-between gap-4"><span class="text-slate-500">Ambang abu-abu</span><strong class="text-slate-900">{{ number_format($security['grayThreshold'], 2) }}</strong></div>
                    </div>
                    <p class="mt-5 rounded-xl bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">Token, URL layanan, koordinat, dan secret tidak ditampilkan.</p>
                </section>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-black text-slate-900">Aktivitas terbaru</h2>
                @forelse($activities as $activity)
                    <div class="flex flex-col gap-1 border-b border-slate-100 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        <div><p class="text-sm font-bold text-slate-800">{{ $activity['type'] }} · {{ $activity['action'] }}</p><p class="text-xs text-slate-500">Target/status: {{ $activity['result'] ?: '—' }}</p></div>
                        <time class="text-xs text-slate-400">{{ optional($activity['at'])->format('d M Y H:i') }}</time>
                    </div>
                @empty
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada aktivitas audit yang tersedia.</div>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
