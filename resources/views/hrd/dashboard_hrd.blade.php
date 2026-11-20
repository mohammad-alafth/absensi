<x-app-layout>
    @php
        $roleMenu = app(\App\Services\RoleMenuService::class);
        $hrdUser = auth()->user();
        $governanceMenus = $roleMenu->leaves(\App\Services\RoleMenuService::HRD_GOVERNANCE, $hrdUser);
        $queueMenus = collect($roleMenu->leaves(\App\Services\RoleMenuService::HRD_QUEUES, $hrdUser))->keyBy('key');
        $queueCounts = [
            'hrd_cuti' => $pendingLeave,
            'hrd_izin' => $pendingPermission,
            'hrd_lembur' => $pendingOvertime,
        ];
    @endphp
    <div class="font-app-body -mt-6 min-h-screen bg-[#f8f9ff] pb-20 text-[#0b1c30] md:pb-0">
        <main class="mx-auto w-full max-w-6xl space-y-5 px-3 py-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-2xl border border-[#c4c5d7]/60 bg-white p-5 shadow-sm sm:p-6">
                <div class="pointer-events-none absolute -right-16 -top-20 h-44 w-44 rounded-full bg-[#dce9ff] opacity-70 blur-3xl"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-[#565e74]">Portal Approval Operasional</p>
                        <h1 class="font-app-heading mt-1 text-2xl font-bold leading-8 text-[#0b1c30] sm:text-3xl">Beranda {{ $stageLabel }}</h1>
                        <p class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-[#565e74]">
                            <span class="material-symbols-outlined text-[16px] text-[#2151da]">account_circle</span>
                            <span>{{ auth()->user()->name }}</span>
                            <span aria-hidden="true">·</span>
                            <span>RS MATA PBEC</span>
                        </p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#eff4ff] px-2.5 py-1 text-[10px] font-bold text-[#0037b0]">
                        <span class="material-symbols-outlined text-[14px]">verified_user</span>
                        {{ $stageLabel }}
                    </span>
                </div>
                <div class="relative mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-[#c4c5d7]/60 pt-3 text-xs text-[#565e74]">
                    <span class="inline-flex items-center gap-1.5 font-medium text-[#0037b0]"><span class="material-symbols-outlined text-[16px]">task_alt</span>Antrean sesuai kewenangan Anda</span>
                    <span>Perlu ditinjau sebelum keputusan berikutnya</span>
                </div>
            </section>

            @if($hrdUser->role === 'hrd' && $governanceMenus !== [])
            <section>
                <div class="mb-3 px-1">
                    <h2 class="font-app-heading text-lg font-semibold leading-6">Tata Kelola Pendukung</h2>
                    <p class="text-sm text-[#565e74]">Akses laporan dan master shift sesuai izin yang tersedia.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach($governanceMenus as $menu)
                        @php
                            $isRekap = $menu['key'] === 'hrd_rekap';
                        @endphp
                        <a href="{{ route($menu['route']) }}" class="group flex min-h-[150px] flex-col rounded-2xl border border-[#c4c5d7]/60 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2151da]">
                            <span class="material-symbols-outlined text-[30px] text-[#2151da]">{{ $menu['icon'] }}</span>
                            <h3 class="font-app-heading mt-3 text-base font-semibold leading-5 group-hover:text-[#0037b0]">{{ $isRekap ? 'Rekap Absensi' : $menu['label'] }}</h3>
                            <p class="mt-1 text-xs leading-5 text-[#565e74]">{{ $isRekap ? 'Monitoring rekapitulasi kehadiran melalui portal laporan.' : 'Kelola data master shift melalui halaman resmi.' }}</p>
                            <span class="mt-auto inline-flex items-center gap-1 pt-3 text-xs font-bold text-[#0037b0]">{{ $isRekap ? 'Buka rekap' : 'Buka master shift' }} <span class="material-symbols-outlined text-[16px]">arrow_forward</span></span>
                        </a>
                    @endforeach
                </div>
            </section>
            @endif

            @php
                $approvedHistoryCount = (int) ($historyCounts['approved'] ?? 0);
                $rejectedHistoryCount = (int) ($historyCounts['rejected'] ?? 0);
                $historyTotal = $approvedHistoryCount + $rejectedHistoryCount;
                $historyQueryBase = array_filter([
                    'history_status' => $historyStatus !== 'all' ? $historyStatus : null,
                    'history_search' => $historySearch !== '' ? $historySearch : null,
                ]);
                $historyOpenUrl = route('hrd.dashboard', $historyQueryBase + ['history_open' => 1]);
                $historyCloseUrl = route('hrd.dashboard', $historyQueryBase);
                $historyFilterUrl = fn (string $status) => route('hrd.dashboard', array_filter([
                    'history_status' => $status === 'all' ? null : $status,
                    'history_search' => $historySearch !== '' ? $historySearch : null,
                    'history_open' => 1,
                ]));
            @endphp

            @if($queueMenus->isNotEmpty())
            <section class="grid grid-cols-2 items-stretch gap-3 sm:grid-cols-4">
                @foreach($queueMenus as $queueKey => $menu)
                    @if($queueKey === 'hrd_history')
                        @if($stage)
                            <a href="{{ $historyOpenUrl }}" data-history-total="{{ $historyTotal }}" x-on:click.prevent="window.history.replaceState({}, '', $el.href); $dispatch('open-modal', 'history-approval')" aria-haspopup="dialog" aria-controls="history-approval" class="flex min-h-[132px] flex-col justify-between rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2151da]">
                                <span class="material-symbols-outlined text-[23px] text-[#2151da]">{{ $menu['icon'] }}</span>
                                <p class="mt-3 text-[11px] font-semibold text-[#565e74]">{{ $menu['label'] }}</p>
                                <p class="font-app-heading app-tabular mt-1 text-3xl font-bold text-[#0b1c30]">{{ $historyTotal }}</p>
                            </a>
                        @else
                            <a href="{{ route('hrd.dashboard') }}" class="flex min-h-[132px] flex-col justify-between rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2151da]">
                                <span class="material-symbols-outlined text-[23px] text-[#2151da]">inbox</span>
                                <p class="mt-3 text-[11px] font-semibold text-[#565e74]">Total Antrean</p>
                                <p class="font-app-heading app-tabular mt-1 text-3xl font-bold text-[#0b1c30]">{{ $pendingLeave + $pendingPermission + $pendingOvertime }}</p>
                            </a>
                        @endif
                    @else
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
                        <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-[#565e74]">Tugas Saat Ini</p>
                        <h2 class="font-app-heading text-lg font-semibold leading-6">Antrean {{ $stageLabel }}</h2>
                        <p class="mt-1 text-xs text-[#565e74]">Pengajuan berikut telah difilter oleh tahap approval yang sedang Anda tangani.</p>
                    </div>
                    <span class="material-symbols-outlined text-[25px] text-[#2151da]">inbox</span>
                </div>

                <div class="mt-4 divide-y divide-[#c4c5d7]/50 rounded-xl border border-[#c4c5d7]/50">
                    @forelse($recentSubmissions as $item)
                        <div class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#eff4ff] text-sm font-bold text-[#0037b0]">{{ strtoupper(substr($item->user->name ?? 'N', 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold">{{ $item->user->name ?? 'N/A' }}</p>
                                    <p class="mt-1 truncate text-[11px] text-[#565e74]">{{ $item->type ?? 'Pengajuan' }} · {{ $item->created_at?->format('d M Y, H:i') ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-center text-[10px] font-bold leading-4 text-amber-700">{{ \App\Services\ApprovalFlowService::statusLabel($item->status ?? null) }}</span>
                                @if(($item->type ?? '') === 'Cuti')
                                    <a href="{{ route('hrd.cuti') }}" class="inline-flex items-center gap-1 text-[10px] font-bold text-[#0037b0]">Buka <span class="material-symbols-outlined text-[14px]">arrow_forward</span></a>
                                @elseif(($item->type ?? '') === 'Izin')
                                    <a href="{{ route('hrd.izin') }}" class="inline-flex items-center gap-1 text-[10px] font-bold text-[#0037b0]">Buka <span class="material-symbols-outlined text-[14px]">arrow_forward</span></a>
                                @else
                                    <a href="{{ route('hrd.lembur') }}" class="inline-flex items-center gap-1 text-[10px] font-bold text-[#0037b0]">Buka <span class="material-symbols-outlined text-[14px]">arrow_forward</span></a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-[#565e74]"><span class="material-symbols-outlined text-[28px] text-[#747686]">inbox</span><p class="mt-2">Belum ada pengajuan pada tahap {{ $stageLabel }}.</p></div>
                    @endforelse
                </div>
            </section>

            @if($stage && $queueMenus->has('hrd_history'))
                <x-modal name="history-approval" :show="$historyOpen" maxWidth="2xl" focusable>
                    <div class="flex items-start justify-between gap-3 border-b border-[#c4c5d7]/60 px-4 py-4 sm:px-5">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-[#565e74]">Jejak Keputusan</p>
                            <h2 class="font-app-heading text-lg font-semibold leading-6">History Approval</h2>
                            <p class="mt-1 text-xs text-[#565e74]">Keputusan yang pernah Anda ambil pada tahap {{ $stageLabel }}.</p>
                        </div>
                        <a href="{{ $historyCloseUrl }}" x-on:click.prevent="$dispatch('close-modal', 'history-approval')" aria-label="Tutup history approval" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[#c4c5d7]/70 bg-white text-[#565e74] transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </a>
                    </div>

                    <div id="history-approval" data-history-state="{{ $historyOpen ? 'open' : 'closed' }}" data-history-close-url="{{ $historyCloseUrl }}" x-on:close-modal.window="if ($event.detail === 'history-approval') window.history.replaceState({}, '', $el.dataset.historyCloseUrl)" class="max-h-[72vh] overflow-y-auto px-4 py-4 sm:px-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ $historyFilterUrl('approved') }}" @if($historyStatus === 'approved') aria-current="page" @endif class="flex min-w-[104px] flex-col rounded-xl border px-3 py-2 transition {{ $historyStatus === 'approved' ? 'border-emerald-300 bg-emerald-50' : 'border-[#c4c5d7]/60 bg-white hover:border-emerald-300' }}">
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Disetujui</span>
                                <span class="font-app-heading app-tabular mt-0.5 text-xl font-bold text-emerald-800">{{ $approvedHistoryCount }}</span>
                            </a>
                            <a href="{{ $historyFilterUrl('rejected') }}" @if($historyStatus === 'rejected') aria-current="page" @endif class="flex min-w-[104px] flex-col rounded-xl border px-3 py-2 transition {{ $historyStatus === 'rejected' ? 'border-rose-300 bg-rose-50' : 'border-[#c4c5d7]/60 bg-white hover:border-rose-300' }}">
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-rose-700">Ditolak</span>
                                <span class="font-app-heading app-tabular mt-0.5 text-xl font-bold text-rose-800">{{ $rejectedHistoryCount }}</span>
                            </a>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="inline-flex w-fit items-center gap-1 rounded-xl border border-[#c4c5d7]/60 bg-[#f8f9ff] p-1">
                                @foreach([
                                    'all' => 'Semua',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                ] as $filterKey => $filterLabel)
                                    <a href="{{ $historyFilterUrl($filterKey) }}" @if($historyStatus === $filterKey) aria-current="page" @endif class="rounded-lg px-3 py-1.5 text-[11px] font-bold transition {{ $historyStatus === $filterKey ? 'bg-[#0037b0] text-white shadow-sm' : 'text-[#565e74] hover:text-[#0037b0]' }}">{{ $filterLabel }}</a>
                                @endforeach
                            </div>

                            <form method="GET" action="{{ route('hrd.dashboard') }}" class="flex w-full items-center gap-2 sm:w-auto">
                                <input type="hidden" name="history_open" value="1">
                                @if($historyStatus !== 'all')
                                    <input type="hidden" name="history_status" value="{{ $historyStatus }}">
                                @endif
                                <label for="historySearch" class="sr-only">Cari nama karyawan</label>
                                <div class="relative min-w-0 flex-1 sm:w-64">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#747686]">search</span>
                                    <input id="historySearch" type="search" name="history_search" value="{{ $historySearch }}" placeholder="Cari nama karyawan" class="h-10 w-full rounded-xl border border-[#c4c5d7]/70 bg-white pl-9 pr-3 text-xs text-[#0b1c30] placeholder:text-[#747686] focus:border-[#2151da] focus:outline-none focus:ring-2 focus:ring-[#2151da]/20">
                                </div>
                                <button type="submit" class="inline-flex h-10 items-center gap-1 rounded-xl bg-[#0037b0] px-3 text-xs font-bold text-white hover:bg-[#002c8c]">
                                    <span class="material-symbols-outlined text-[16px]">search</span>
                                    Cari
                                </button>
                                @if($historySearch !== '')
                                    <a href="{{ $historyFilterUrl($historyStatus) }}" class="inline-flex h-10 items-center rounded-xl border border-[#c4c5d7]/70 px-3 text-xs font-bold text-[#565e74] hover:text-[#0037b0]">Reset</a>
                                @endif
                            </form>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-xl border border-[#c4c5d7]/50">
                            <div class="hidden items-center gap-3 border-b border-[#c4c5d7]/50 bg-[#f8f9ff] px-3 py-2 text-[10px] font-bold uppercase tracking-wide text-[#565e74] sm:flex">
                                <span class="min-w-0 flex-1">Karyawan &amp; Pengajuan</span>
                                <span class="w-28 text-right">Tanggal Pengajuan</span>
                                <span class="w-36 text-right">Waktu Keputusan</span>
                                <span class="w-24 text-center">Status</span>
                            </div>

                            <div class="divide-y divide-[#c4c5d7]/50">
                                @forelse($approvalHistory as $history)
                                    @php
                                        $historyIcon = match ($history->type) {
                                            'Cuti' => 'event_busy',
                                            'Izin' => 'description',
                                            default => 'more_time',
                                        };
                                        $isApproved = $history->decision_status === 'approved';
                                        $historyCategory = $history->category
                                            ? ucfirst(str_replace('_', ' ', $history->category))
                                            : '-';
                                        $historyRequestDate = $history->request_date
                                            ? \Illuminate\Support\Carbon::parse($history->request_date)->format('d M Y')
                                            : '-';
                                        $historyPeriodEnd = $history->period_end
                                            ? \Illuminate\Support\Carbon::parse($history->period_end)->format('d M Y')
                                            : null;
                                        $historyTimeStart = $history->time_start
                                            ? \Illuminate\Support\Carbon::parse($history->time_start)->format('H:i')
                                            : null;
                                        $historyTimeEnd = $history->time_end
                                            ? \Illuminate\Support\Carbon::parse($history->time_end)->format('H:i')
                                            : null;
                                        $historySubmittedAt = $history->submitted_at
                                            ? \Illuminate\Support\Carbon::parse($history->submitted_at)->format('d M Y, H:i')
                                            : '-';
                                        $historyActionAt = $history->action_at
                                            ? \Illuminate\Support\Carbon::parse($history->action_at)->format('d M Y, H:i')
                                            : '-';
                                        $historyReason = trim((string) ($history->reason ?? ''));
                                        $historyAttachmentPath = filled($history->attachment ?? null) ? $history->attachment : null;
                                        $historyAttachmentUrl = $historyAttachmentPath ? asset('storage/' . $historyAttachmentPath) : null;
                                        $historyAttachmentIsImage = $historyAttachmentPath
                                            && in_array(strtolower(pathinfo($historyAttachmentPath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
                                        $historyPdfUrl = filled($history->pdf_file ?? null) ? asset('storage/' . $history->pdf_file) : null;
                                        $historySignature = filled($history->employee_signature ?? null) ? $history->employee_signature : null;
                                        $historyPunchLogs = ($historyPunches ?? collect())->get($history->id, collect());
                                        $historyProofLabel = match ($history->proof_type ?? null) {
                                            'realtime' => 'Absen realtime',
                                            'dari_absen' => 'Dari absen harian',
                                            'koreksi' => 'Koreksi PJ/HRD',
                                            default => 'Tanpa absen (manual)',
                                        };
                                        $historyActualStart = $history->actual_start_at
                                            ? \Illuminate\Support\Carbon::parse($history->actual_start_at)->format('d M Y, H:i')
                                            : null;
                                        $historyActualEnd = $history->actual_end_at
                                            ? \Illuminate\Support\Carbon::parse($history->actual_end_at)->format('d M Y, H:i')
                                            : null;
                                        $historyActualDuration = $history->actual_minutes !== null
                                            ? intdiv((int) $history->actual_minutes, 60) . ' jam ' . ((int) $history->actual_minutes % 60) . ' menit'
                                            : null;
                                        $hasHistoryEvidence = $historyPdfUrl
                                            || $historyAttachmentUrl
                                            || $historySignature
                                            || $historyPunchLogs->isNotEmpty()
                                            || filled($history->proof_note ?? null);
                                    @endphp
                                    <div x-data="{ detailOpen: false }">
                                        <div class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center">
                                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $isApproved ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                    <span class="material-symbols-outlined text-[20px]">{{ $historyIcon }}</span>
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="truncate text-xs font-bold">{{ $history->employee_name ?? 'N/A' }}</p>
                                                    <p class="mt-1 truncate text-[11px] text-[#565e74]">{{ $history->type ?? 'Pengajuan' }} · {{ $historyCategory }}</p>
                                                    <button type="button" x-on:click="detailOpen = !detailOpen" :aria-expanded="detailOpen.toString()" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-[#0037b0] hover:underline">
                                                        <span class="material-symbols-outlined text-[14px]" x-text="detailOpen ? 'expand_less' : 'expand_more'">expand_more</span>
                                                        <span x-text="detailOpen ? 'Tutup detail' : 'Detail surat'">Detail surat</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between gap-3 sm:contents">
                                                <div class="sm:w-28 sm:text-right">
                                                    <span class="block text-[10px] uppercase tracking-wide text-[#747686] sm:hidden">Tanggal pengajuan</span>
                                                    <span class="text-[11px] font-semibold text-[#0b1c30]">{{ $historyRequestDate }}</span>
                                                </div>
                                                <div class="sm:w-36 sm:text-right">
                                                    <span class="block text-[10px] uppercase tracking-wide text-[#747686] sm:hidden">Waktu keputusan</span>
                                                    <span class="text-[11px] text-[#565e74]">{{ $historyActionAt }}</span>
                                                </div>
                                                <div class="sm:w-24 sm:text-center">
                                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $isApproved ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                        <span class="material-symbols-outlined text-[13px]">{{ $isApproved ? 'check_circle' : 'cancel' }}</span>
                                                        {{ $isApproved ? 'Disetujui' : 'Ditolak' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div x-show="detailOpen" x-cloak class="border-t border-[#c4c5d7]/50 bg-[#f8f9ff] px-3 py-3 sm:px-4">
                                            <p class="text-[11px] font-bold text-[#0037b0]">Detail Surat {{ $history->type }} #{{ $history->id }}</p>
                                            <dl class="mt-2 grid grid-cols-1 gap-3 text-[11px] sm:grid-cols-2 lg:grid-cols-3">
                                                <div>
                                                    <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Karyawan</dt>
                                                    <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $history->employee_name ?? 'N/A' }}</dd>
                                                    <dd class="text-[#565e74]">{{ $history->employee_role ? ucwords(str_replace('_', ' ', $history->employee_role)) : '-' }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Kategori</dt>
                                                    <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $historyCategory }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Diajukan</dt>
                                                    <dd class="mt-0.5 text-[#565e74]">{{ $historySubmittedAt }}</dd>
                                                </div>

                                                @if($history->type === 'Cuti')
                                                    <div>
                                                        <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Periode Cuti</dt>
                                                        <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $historyRequestDate }}@if($historyPeriodEnd) – {{ $historyPeriodEnd }}@endif</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Total Hari</dt>
                                                        <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ (int) $history->duration }} hari</dd>
                                                    </div>
                                                @elseif($history->type === 'Izin')
                                                    <div>
                                                        <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Tanggal Izin</dt>
                                                        <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $historyRequestDate }}@if($historyPeriodEnd && $historyPeriodEnd !== $historyRequestDate) – {{ $historyPeriodEnd }}@endif</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Waktu</dt>
                                                        <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $historyTimeStart ?? '-' }} – {{ $historyTimeEnd ?? '-' }}</dd>
                                                    </div>
                                                @else
                                                    <div>
                                                        <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Tanggal Lembur</dt>
                                                        <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $historyRequestDate }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Waktu</dt>
                                                        <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $historyTimeStart ?? '-' }} – {{ $historyTimeEnd ?? '-' }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Total Jam</dt>
                                                        <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ (int) $history->duration }} jam</dd>
                                                    </div>
                                                    @if($historyActualStart)
                                                        <div>
                                                            <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Jam Nyata</dt>
                                                            <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $historyActualStart }}@if($historyActualEnd) – {{ $historyActualEnd }}@endif</dd>
                                                        </div>
                                                    @endif
                                                    @if($historyActualDuration)
                                                        <div>
                                                            <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Durasi Nyata</dt>
                                                            <dd class="mt-0.5 font-semibold text-[#0b1c30]">{{ $historyActualDuration }}</dd>
                                                        </div>
                                                    @endif
                                                @endif

                                                <div class="sm:col-span-2 lg:col-span-3">
                                                    <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Alasan / Keterangan</dt>
                                                    <dd class="mt-0.5 whitespace-pre-line leading-5 text-[#565e74]">{{ $historyReason !== '' ? $historyReason : '-' }}</dd>
                                                </div>
                                                <div class="sm:col-span-2 lg:col-span-3">
                                                    <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Catatan Keputusan</dt>
                                                    <dd class="mt-0.5 whitespace-pre-line leading-5 text-[#565e74]">{{ $history->note ?: '-' }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-[10px] uppercase tracking-wide text-[#747686]">Waktu Keputusan</dt>
                                                    <dd class="mt-0.5 text-[#565e74]">{{ $historyActionAt }}</dd>
                                                </div>
                                            </dl>

                                            <div class="mt-3 rounded-xl border border-sky-100 bg-white p-3">
                                                <p class="text-[10px] font-bold uppercase tracking-wide text-sky-800">Bukti &amp; Surat</p>

                                                @if($hasHistoryEvidence)
                                                    <div class="mt-2 space-y-3">
                                                        @if($historyPdfUrl || $history->type === 'Lembur')
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                @if($historyPdfUrl)
                                                                    <a href="{{ $historyPdfUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-emerald-700">
                                                                        <span class="material-symbols-outlined text-[15px]">picture_as_pdf</span>
                                                                        Buka Surat PDF
                                                                    </a>
                                                                @endif
                                                                @if($history->type === 'Lembur')
                                                                    <span class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[10px] font-bold text-sky-700">
                                                                        Kehadiran: {{ $historyProofLabel }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endif

                                                        @if($historyAttachmentUrl)
                                                            <div>
                                                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#747686]">Lampiran Bukti</p>
                                                                @if($historyAttachmentIsImage)
                                                                    <a href="{{ $historyAttachmentUrl }}" target="_blank" rel="noopener" class="mt-1 inline-block">
                                                                        <img src="{{ $historyAttachmentUrl }}" alt="Lampiran bukti {{ $history->type }}" class="max-h-40 max-w-full rounded-lg border border-sky-100 bg-white object-contain p-1">
                                                                    </a>
                                                                    <a href="{{ $historyAttachmentUrl }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 text-[10px] font-semibold text-sky-700 hover:underline">
                                                                        <span class="material-symbols-outlined text-[14px]">zoom_in</span>
                                                                        Buka ukuran penuh
                                                                    </a>
                                                                @else
                                                                    <a href="{{ $historyAttachmentUrl }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-sky-700">
                                                                        <span class="material-symbols-outlined text-[15px]">attach_file</span>
                                                                        Buka Lampiran
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        @endif

                                                        @if($historySignature)
                                                            <div>
                                                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#747686]">Tanda Tangan Pengaju</p>
                                                                <img src="{{ $historySignature }}" alt="Tanda tangan pengaju" class="mt-1 max-h-14 rounded-lg border border-sky-100 bg-white object-contain px-2 py-1">
                                                            </div>
                                                        @endif

                                                        @if($historyPunchLogs->isNotEmpty())
                                                            <div>
                                                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#747686]">Log Absen Lembur</p>
                                                                <div class="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                                    @foreach($historyPunchLogs as $punch)
                                                                        <div class="flex items-start gap-2 rounded-lg border border-sky-100 bg-[#f8fbff] px-2.5 py-2">
                                                                            @if($punch->photo_url)
                                                                                <a href="{{ $punch->photo_url }}" target="_blank" rel="noopener" class="shrink-0">
                                                                                    <img src="{{ $punch->photo_url }}" alt="Foto {{ $punch->type_label }}" class="h-12 w-12 rounded-lg border border-sky-100 object-cover">
                                                                                </a>
                                                                            @endif
                                                                            <div class="min-w-0 text-[10px] leading-4">
                                                                                <p class="font-bold text-[#0b1c30]">{{ $punch->type_label }}</p>
                                                                                <p class="text-[#565e74]">{{ $punch->punched_at?->format('d/m/Y H:i:s') }}</p>
                                                                                @if($punch->distance_m !== null)
                                                                                    <p class="text-[#747686]">Jarak {{ round($punch->distance_m) }} m dari kantor</p>
                                                                                @endif
                                                                                @if($punch->latitude !== null && $punch->longitude !== null)
                                                                                    <p class="text-[#747686]">GPS {{ number_format($punch->latitude, 6) }}, {{ number_format($punch->longitude, 6) }}</p>
                                                                                @endif
                                                                                @if($punch->note)
                                                                                    <p class="text-[#565e74]">{{ $punch->note }}</p>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if(filled($history->proof_note ?? null))
                                                            <p class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-[10px] text-amber-700">
                                                                Catatan bukti: {{ $history->proof_note }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                @else
                                                    <p class="mt-1 text-[11px] text-[#565e74]">Tidak ada bukti terlampir pada pengajuan ini.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-8 text-center text-xs text-[#565e74]">
                                        <span class="material-symbols-outlined text-[28px] text-[#747686]">history</span>
                                        <p class="mt-2">
                                            @if($historySearch !== '')
                                                Tidak ada history yang cocok dengan pencarian "{{ $historySearch }}".
                                            @elseif($historyStatus !== 'all')
                                                Belum ada keputusan {{ $historyStatus === 'approved' ? 'disetujui' : 'ditolak' }} pada tahap {{ $stageLabel }}.
                                            @else
                                                Belum ada keputusan yang Anda proses pada tahap {{ $stageLabel }}.
                                            @endif
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        @if($approvalHistory->total() > 0)
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-[11px] text-[#565e74]">
                                    Menampilkan {{ $approvalHistory->firstItem() }}–{{ $approvalHistory->lastItem() }} dari {{ $approvalHistory->total() }} keputusan
                                </p>
                                @if($approvalHistory->hasPages())
                                    {{ $approvalHistory->links() }}
                                @endif
                            </div>
                        @endif

                        <div class="mt-4 flex justify-end">
                            <a href="{{ $historyCloseUrl }}" x-on:click.prevent="$dispatch('close-modal', 'history-approval')" class="inline-flex h-10 items-center gap-1 rounded-xl border border-[#c4c5d7]/70 px-4 text-xs font-bold text-[#565e74] transition hover:border-[#2151da] hover:text-[#0037b0]">
                                <span class="material-symbols-outlined text-[16px]">close</span>
                                Tutup
                            </a>
                        </div>
                    </div>
                </x-modal>
            @endif

            <section class="rounded-2xl border border-[#c4c5d7]/60 bg-[#eff4ff] p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[24px] text-[#2151da]">account_tree</span>
                    <div>
                        <h2 class="font-app-heading text-sm font-semibold">Tahap approval aktif</h2>
                        <p class="mt-1 text-xs leading-5 text-[#565e74]">{{ $stageLabel }} hanya menampilkan pengajuan yang berada pada kewenangan tahap ini. Keputusan tetap diproses melalui halaman approval resmi.</p>
                    </div>
                </div>
            </section>

        </main>
    </div>
</x-app-layout>
