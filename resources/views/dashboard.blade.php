<x-app-layout>
    @php
        $alreadyCheckin = $todayAttendance && $todayAttendance->jam_masuk;
        $alreadyCheckout = $todayAttendance && $todayAttendance->jam_keluar;
        $isPjUnit = str_starts_with((string) $user->role, 'pj_');
        $approverStage = app(\App\Services\ApprovalFlowService::class)->stageForApproverRole($user->role);
        $isApprover = $approverStage !== null;
        $portalApprovalUrl = $isApprover
            ? route('hrd.dashboard')
            : ($isPjUnit ? route('pj.dashboard') : null);
        $portalApprovalQueue = $isApprover
            ? match ($approverStage) {
                'medical_service' => 'Yanmed',
                'manager_umum', 'manager_finance' => 'Manager',
                default => \App\Services\ApprovalFlowService::labelForStage($approverStage),
            }
            : ($isPjUnit ? 'PJ Unit' : null);
        $roleMenu = app(\App\Services\RoleMenuService::class);
        // PJ approval access is governed by the dedicated PJ route/controller
        // scope. Keep its portal visible even when a particular queue
        // permission is absent from the menu projection.
        $canApprovalPortal = $isPjUnit || $roleMenu->allowsAny($user, [
            'leave.view',
            'permission.view',
            'overtime.view',
            'shift_request.view',
        ]);
        $portalMenus = $roleMenu->leaves(\App\Services\RoleMenuService::DASHBOARD_PORTALS, $user);
        $canCheckout = false;
        $checkoutAlertText = 'Checkout hanya bisa dilakukan mendekati jam pulang shift.';

        if ($scheduleData && !empty($scheduleData['shift_end'])) {
            $checkoutTime = $scheduleData['shift_end']->copy()->subMinutes(5);
            $earlyOpenAt = app(\App\Services\AttendancePunchService::class)->earlyCheckoutOpenAt(
                auth()->user(),
                $scheduleData['shift_date'] ?? today()->format('Y-m-d')
            );

            if ($earlyOpenAt && $earlyOpenAt->lt($checkoutTime)) {
                $checkoutTime = $earlyOpenAt;
                $checkoutAlertText = 'Absen pulang dibuka mulai pukul '
                    . $checkoutTime->format('H:i') . ' WIB sesuai izin pulang cepat.';
            }

            $canCheckout = now()->gte($checkoutTime);
        }
    @endphp

    <div class="font-app-body -mt-6 -mb-24 min-h-screen bg-[#f8f9ff] pb-[calc(5.25rem+env(safe-area-inset-bottom))] text-[#0b1c30] md:mb-0 md:pb-0">
        <main class="mx-auto w-full max-w-5xl space-y-5 px-3 py-4 sm:px-6 lg:px-8">
            <section class="relative overflow-hidden rounded-2xl border border-[#c4c5d7]/60 bg-white p-5 shadow-sm sm:p-6">
                <div class="pointer-events-none absolute -right-20 -top-24 h-56 w-56 rounded-full bg-[#dce9ff] opacity-70 blur-3xl"></div>
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 text-left">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-[#565e74]">Beranda presensi</p>
                        <h1 class="font-app-heading mt-1 truncate text-2xl font-bold tracking-tight text-[#0b1c30] sm:text-3xl">Halo, {{ $user->name }}</h1>
                        <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-[#565e74]">
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#eff4ff] px-2.5 py-1 text-xs font-semibold text-[#0037b0]"><span class="material-symbols-outlined text-[15px]">badge</span>{{ strtoupper((string) $user->role) }}</span>
                            <span>RS MATA PBEC</span>
                        </p>
                    </div>
                    <div class="relative shrink-0 rounded-2xl bg-[#0037b0] px-4 py-3 text-left text-white sm:min-w-44 sm:text-right">
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-blue-100">Jam saat ini</p>
                        <p id="clock" class="font-app-heading app-tabular mt-1 text-3xl font-bold tracking-tight"></p>
                        <p class="mt-1 text-xs text-blue-100">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>

                <div class="relative mx-auto mt-5 max-w-2xl rounded-xl border border-[#c4c5d7]/60 bg-[#eff4ff] p-3 text-left">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined mt-0.5 text-[22px] text-[#2151da]">event_available</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-[#565e74]">Jadwal Shift Hari Ini</p>
                            <p class="mt-1 break-words text-sm font-bold leading-5 text-[#0b1c30]">{{ $schedule }}</p>
                            @if($scheduleData && !empty($scheduleData['start_time']))
                                <p class="mt-1 text-xs text-[#565e74]">
                                    {{ $scheduleData['shift_name'] ?? 'Shift' }} ·
                                    {{ \Carbon\Carbon::parse($scheduleData['start_time'])->format('H:i') }}–{{ \Carbon\Carbon::parse($scheduleData['end_time'])->format('H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <x-overtime-punch-banner :state="$overtimePunchState ?? null" />

            @if(session('success'))
                <div id="success-alert" class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    <span class="material-symbols-outlined text-[18px]">check_circle</span>{{ session('success') }}
                </div>
            @endif

            <section class="rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-app-heading text-lg font-semibold leading-6 text-[#0b1c30]">Presensi Hari Ini</h2>
                        <p class="mt-0.5 text-xs text-[#565e74]">Gunakan alur biometrik dan lokasi resmi aplikasi.</p>
                    </div>
                    <span class="material-symbols-outlined text-[#2151da]">location_on</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    @if($alreadyCheckin && !$alreadyCheckout)
                        <button type="button" onclick="alreadyCheckinAlert()" class="flex min-h-14 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-xs font-bold text-emerald-700">
                            <span class="material-symbols-outlined text-[20px]">task_alt</span> Sudah Absen Masuk
                        </button>
                    @else
                        <a href="{{ route('face.scan') }}" class="flex min-h-14 items-center justify-center gap-2 rounded-xl bg-[#1d4ed8] px-3 text-xs font-bold text-white shadow-[0_8px_20px_-4px_rgba(29,78,216,0.35)] transition hover:bg-[#0037b0]">
                            <span class="material-symbols-outlined text-[20px]">login</span> Absen Masuk
                        </a>
                    @endif

                    @if(!$alreadyCheckin)
                        <button type="button" onclick="notCheckinAlert()" class="flex min-h-14 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-3 text-xs font-bold text-slate-500">
                            <span class="material-symbols-outlined text-[20px]">block</span> Belum Absen Masuk
                        </button>
                    @elseif($alreadyCheckout)
                        <button type="button" class="flex min-h-14 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 text-xs font-bold text-emerald-700">
                            <span class="material-symbols-outlined text-[20px]">task_alt</span> Sudah Absen Pulang
                        </button>
                    @elseif(!$canCheckout)
                        <button type="button" onclick="checkoutNotAllowedAlert()" class="flex min-h-14 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-3 text-xs font-bold text-slate-500">
                            <span class="material-symbols-outlined text-[20px]">schedule</span> Belum Jam Pulang
                        </button>
                    @else
                        <a href="{{ route('face.scan') }}" class="flex min-h-14 items-center justify-center gap-2 rounded-xl border border-[#c4c5d7] bg-white px-3 text-xs font-bold text-[#0037b0] transition hover:bg-[#eff4ff]">
                            <span class="material-symbols-outlined text-[20px]">logout</span> Absen Pulang
                        </a>
                    @endif
                </div>
            </section>

            @php
                $serviceCounts = [
                    'cuti' => $pendingLeaveCount,
                    'izin' => $pendingPermissionCount,
                    'lembur' => $pendingOvertimeCount,
                    'shift-change.create' => $pendingShiftChangeCount ?? 0,
                ];
                $services = collect($roleMenu->leaves(\App\Services\RoleMenuService::DASHBOARD_SERVICES, $user))
                    ->map(function (array $service) use ($serviceCounts): array {
                        $service['count'] = (int) ($serviceCounts[$service['route']] ?? 0);

                        return $service;
                    })
                    ->all();
            @endphp

            @if($services !== [])
            <section class="space-y-3">
                <div class="flex items-end justify-between px-1">
                    <div>
                        <h2 class="font-app-heading text-lg font-semibold leading-6 text-[#0b1c30]">Layanan Kehadiran Mandiri</h2>
                        <p class="text-xs text-[#565e74]">Pengajuan tetap diproses melalui handler resmi.</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 items-stretch gap-3 sm:grid-cols-4">
                    @foreach($services as $service)
                        <a href="{{ route($service['route']) }}" class="relative flex min-h-[108px] flex-col justify-between rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2151da]">
                            @if($service['count'] > 0)<span class="absolute right-2.5 top-2.5 min-w-5 rounded-full bg-[#ba1a1a] px-1.5 py-0.5 text-center text-[10px] font-bold text-white">{{ $service['count'] }}</span>@endif
                            <span class="material-symbols-outlined text-[25px] text-[#2151da]">{{ $service['icon'] }}</span>
                            <p class="mt-3 text-xs font-bold text-[#0b1c30]">{{ $service['label'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
            @endif

            @if($canApprovalPortal && $portalApprovalQueue !== null && $portalApprovalUrl !== null)
                <x-portal-approval-card :queue="$portalApprovalQueue" :href="$portalApprovalUrl" />
            @endif

            @if($portalMenus !== [])
                <section class="space-y-3">
                    <div class="px-1">
                        <h2 class="font-app-heading text-lg font-semibold leading-6 text-[#0b1c30]">Portal Sesuai Akses</h2>
                        <p class="text-sm text-[#565e74]">Buka ruang kerja sesuai role dan otorisasi yang terdaftar.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($portalMenus as $portal)
                            <a href="{{ route($portal['route']) }}" @class([
                                'flex min-h-[104px] items-center gap-4 rounded-2xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#2151da]',
                                'border-blue-200 bg-blue-50/70' => $portal['route'] === 'admin.control-center',
                                'border-[#c4c5d7]/60 bg-white' => $portal['route'] !== 'admin.control-center',
                            ])>
                                <span @class([
                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-[#0037b0]',
                                    'bg-white' => $portal['route'] === 'admin.control-center',
                                    'bg-[#eff4ff]' => $portal['route'] !== 'admin.control-center',
                                ])><span class="material-symbols-outlined text-[24px]" aria-hidden="true">{{ $portal['icon'] }}</span></span>
                                <span class="min-w-0"><span class="font-app-heading block text-base font-semibold text-[#0b1c30]">{{ $portal['label'] }}</span><span class="mt-1 block text-sm leading-5 text-[#565e74]">{{ $portal['description'] ?? '' }}</span></span>
                                <span class="material-symbols-outlined ml-auto shrink-0 text-[#0037b0]" aria-hidden="true">arrow_forward</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($latestPermission)
                <section class="rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-app-heading text-lg font-semibold leading-6 text-[#0b1c30]">Pelacakan Berkas Terkini</h2>
                            <p class="mt-1 text-xs text-[#565e74]">{{ $latestPermission->jenis }} · {{ $latestPermission->tanggal }}</p>
                        </div>
                        @if($latestPermission->status === 'pending')
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700">Pending</span>
                        @elseif($latestPermission->status === 'approved')
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700">Approved</span>
                        @elseif($latestPermission->status === 'rejected')
                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700">Rejected</span>
                        @endif
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-[#565e74]">Aktivitas terbaru</p>
                        <h2 class="font-app-heading text-lg font-semibold leading-6 text-[#0b1c30]">Riwayat presensi</h2>
                    </div>
                    <a href="{{ route('history') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#0037b0]">Lihat semua <span class="material-symbols-outlined text-[16px]">arrow_forward</span></a>
                </div>
                <div class="mt-4 divide-y divide-[#c4c5d7]/50 rounded-xl border border-[#c4c5d7]/50">
                    @forelse($histories as $history)
                        <div class="flex items-center justify-between gap-3 px-3 py-3 text-xs sm:px-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#eff4ff] text-[#0037b0]"><span class="material-symbols-outlined text-[18px]">event_available</span></span>
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-[#0b1c30]">{{ $history->tanggal ?? '-' }}</p>
                                    <p class="mt-0.5 text-[#565e74]">{{ $history->jam_masuk ?? '--:--' }} masuk · {{ $history->jam_keluar ?? '--:--' }} pulang</p>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">Tercatat</span>
                        </div>
                    @empty
                        <div class="p-7 text-center text-xs text-[#565e74]"><span class="material-symbols-outlined text-[28px] text-[#747686]">event_busy</span><p class="mt-2">Belum ada riwayat presensi.</p></div>
                    @endforelse
                </div>
            </section>
        </main>
    </div>

    <script>
        function checkoutNotAllowedAlert() { Swal.fire({ icon: 'warning', title: 'Belum Jam Pulang', text: @json($checkoutAlertText), confirmButtonColor: '#1D4ED8' }); }
        function notCheckinAlert() { Swal.fire({ icon: 'info', title: 'Belum Absen Masuk', text: 'Silakan lakukan absen masuk terlebih dahulu.', confirmButtonColor: '#1D4ED8' }); }
        function alreadyCheckinAlert() { Swal.fire({ icon: 'info', title: 'Sudah Absen Masuk', text: 'Anda sudah melakukan absen masuk hari ini, tunggu jam pulang untuk checkout.', confirmButtonColor: '#1D4ED8' }); }
        function updateClock() {
            const clock = document.getElementById('clock');
            if (clock) clock.textContent = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'}) + ' WIB';
        }
        setInterval(updateClock, 1000); updateClock();
        setTimeout(() => { const alert = document.getElementById('success-alert'); if (alert) alert.remove(); }, 3000);
    </script>
</x-app-layout>
