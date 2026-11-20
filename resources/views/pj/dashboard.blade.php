<x-app-layout>
    @php
        $roleMenu = app(\App\Services\RoleMenuService::class);
        // Keep the approval queue projection for the cards above. The
        // duplicate operational-menu section below has been removed.
        $operationMenus = collect($roleMenu->leaves(\App\Services\RoleMenuService::PJ_OPERATIONS, auth()->user()))->keyBy('key');
        $queueCounts = [
            'pj_cuti' => $pendingLeave,
            'pj_izin' => $pendingPermission,
            'pj_lembur' => $pendingOvertime,
            'pj_shift_change' => $pendingShiftChange ?? 0,
        ];
    @endphp

    <div class="font-app-body -mt-6 min-h-screen bg-[#f8f9ff] pb-20 text-[#0b1c30] md:pb-0">
        <main class="mx-auto w-full max-w-6xl space-y-5 px-3 py-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-[#c4c5d7]/60 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-[#565e74]">Portal PJ Unit</p>
                        <h1 class="font-app-heading mt-1 truncate text-2xl font-bold leading-8 text-[#0b1c30]">Dashboard Penanggung Jawab</h1>
                        <p class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-[#565e74]"><span class="material-symbols-outlined text-[16px]">domain</span><span>RS MATA PBEC</span><span aria-hidden="true">·</span><span>{{ strtoupper($divisionRole) }}</span></p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#eff4ff] px-2.5 py-1 text-[10px] font-bold text-[#0037b0]"><span class="material-symbols-outlined text-[14px]">shield_person</span> PJ Unit</span>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3 border-t border-[#c4c5d7]/60 pt-4 sm:grid-cols-4">
                    @foreach([
                        ['label' => 'Menunggu', 'value' => $pendingLeave + $pendingPermission + $pendingOvertime + $pendingShiftChange, 'tone' => 'text-amber-700'],
                        ['label' => 'Disetujui', 'value' => $approvedLeave, 'tone' => 'text-emerald-700'],
                        ['label' => 'Ditolak', 'value' => $rejectedLeave, 'tone' => 'text-rose-700'],
                        ['label' => 'Cakupan', 'value' => 'Unit', 'tone' => 'text-[#0037b0]'],
                    ] as $summary)
                        <div class="rounded-xl bg-[#f8f9ff] px-3 py-2.5">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-[#565e74]">{{ $summary['label'] }}</p>
                            <p class="font-app-heading app-tabular mt-1 text-xl font-bold {{ $summary['tone'] }}">{{ is_numeric($summary['value']) ? number_format($summary['value']) : $summary['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            @if($operationMenus->has('pj_cuti') || $operationMenus->has('pj_izin') || $operationMenus->has('pj_lembur') || $operationMenus->has('pj_shift_change'))
            <section class="grid grid-cols-2 items-stretch gap-3 sm:grid-cols-4">
                @foreach(['pj_cuti', 'pj_izin', 'pj_lembur', 'pj_shift_change'] as $queueKey)
                    @if($operationMenus->has($queueKey))
                        @php $menu = $operationMenus->get($queueKey); @endphp
                        <a href="{{ route($menu['route']) }}" class="flex min-h-[132px] flex-col justify-between rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2151da]">
                            <span class="material-symbols-outlined text-[23px] text-[#2151da]">{{ $menu['icon'] }}</span>
                            <p class="mt-3 text-[11px] font-semibold text-[#565e74]">{{ $menu['label'] }}</p>
                            <p class="font-app-heading app-tabular mt-1 text-3xl font-bold text-[#0b1c30]">{{ (int) ($queueCounts[$queueKey] ?? 0) }}</p>
                        </a>
                    @endif
                @endforeach
            </section>
            @endif

            <section class="rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-app-heading text-lg font-semibold leading-6">Antrean Approval Unit</h2>
                        <p class="mt-1 text-xs text-[#565e74]">Data dibatasi oleh cakupan divisi dari controller PJ.</p>
                    </div>
                    <span class="material-symbols-outlined text-[#2151da]">inbox</span>
                </div>

                <div class="mt-4 divide-y divide-[#c4c5d7]/50 rounded-xl border border-[#c4c5d7]/50">
                    @forelse($recentSubmissions as $item)
                        <div class="flex items-center justify-between gap-3 p-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#eff4ff] text-sm font-bold text-[#0037b0]">{{ strtoupper(substr($item->user->name ?? 'N', 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold">{{ $item->user->name ?? 'N/A' }}</p>
                                    <p class="mt-1 truncate text-[11px] text-[#565e74]">{{ $item->leave_type ?? 'Pengajuan' }} · {{ $item->created_at?->format('d M Y, H:i') ?? '-' }}</p>
                                </div>
                            </div>
                            <span class="max-w-[42%] shrink-0 rounded-full bg-amber-50 px-2 py-1 text-right text-[10px] font-bold leading-4 text-amber-700">{{ str_replace('_', ' ', $item->status ?? 'pending') }}</span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-[#565e74]"><span class="material-symbols-outlined text-[28px] text-[#747686]">inbox</span><p class="mt-2">Belum ada pengajuan pada cakupan unit Anda.</p></div>
                    @endforelse
                </div>
            </section>

        </main>
    </div>
</x-app-layout>
