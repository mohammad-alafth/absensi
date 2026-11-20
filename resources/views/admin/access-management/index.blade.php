<x-app-layout>
    <div class="min-h-screen bg-slate-100 px-4 py-6 pb-28 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Admin Control Center / Hak Akses</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Manajemen Hak Akses</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Kelola hak akses menu dan fitur aplikasi berdasarkan role pengguna.</p>
                </div>
                <a href="{{ route('admin.control-center') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-blue-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">&larr; Kembali ke Control Center</a>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" aria-label="Ringkasan hak akses">
                @foreach([
                    ['label' => 'Total role', 'value' => $summary['roles'], 'icon' => 'groups', 'icon_class' => 'bg-blue-50 text-blue-700'],
                    ['label' => 'Modul aktif', 'value' => $summary['modules'], 'icon' => 'view_module', 'icon_class' => 'bg-violet-50 text-violet-700'],
                    ['label' => 'Permission aktif', 'value' => $summary['permissions'], 'icon' => 'verified_user', 'icon_class' => 'bg-emerald-50 text-emerald-700'],
                    ['label' => 'Sudah dikonfigurasi', 'value' => $summary['configured'], 'icon' => 'task_alt', 'icon_class' => 'bg-amber-50 text-amber-700'],
                    ['label' => 'Belum dipetakan', 'value' => $summary['unmapped'], 'icon' => 'pending', 'icon_class' => 'bg-slate-100 text-slate-700'],
                ] as $card)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $card['icon_class'] }}"><span class="material-symbols-outlined" aria-hidden="true">{{ $card['icon'] }}</span></span>
                            <p class="text-2xl font-black text-slate-900">{{ number_format($card['value']) }}</p>
                        </div>
                        <p class="mt-4 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    </article>
                @endforeach
            </section>

            <form method="GET" action="{{ route('admin.access-management.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                    <label class="block min-w-0 flex-1">
                        <span class="text-xs font-bold text-slate-600">Cari role</span>
                        <input name="q" value="{{ $query }}" class="mt-1 min-h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: nurse, hrd, pj_..." autocomplete="off">
                    </label>
                    <label class="block lg:w-64">
                        <span class="text-xs font-bold text-slate-600">Status konfigurasi</span>
                        <select name="status" class="mt-1 min-h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua status</option>
                            <option value="protected" @selected($statusFilter === 'protected')>Protected</option>
                            <option value="configured" @selected($statusFilter === 'configured')>Sudah Dikonfigurasi</option>
                            <option value="unmapped" @selected($statusFilter === 'unmapped')>Belum Dikonfigurasi</option>
                        </select>
                    </label>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-700 px-5 py-2 text-sm font-bold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Terapkan</button>
                        <a href="{{ route('admin.access-management.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Reset</a>
                    </div>
                </div>
            </form>

            @if($readError)
                <section role="alert" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 shadow-sm">
                    <h2 class="text-lg font-black text-rose-900">Data role belum dapat dimuat</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-rose-800">Terjadi kendala saat membaca konfigurasi hak akses. Tidak ada data pengganti yang ditampilkan.</p>
                    <a href="{{ request()->fullUrl() }}" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-700 px-5 py-3 text-sm font-bold text-white hover:bg-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">Coba lagi</a>
                </section>
            @elseif($roles->isEmpty())
                <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center shadow-sm">
                    <span class="material-symbols-outlined text-5xl text-slate-300" aria-hidden="true">manage_accounts</span>
                    <h2 class="mt-3 text-lg font-black text-slate-800">Role tidak ditemukan</h2>
                    <p class="mt-2 text-sm text-slate-500">Coba ubah kata pencarian atau reset filter.</p>
                </section>
            @else
                <section aria-labelledby="role-list-heading">
                    <div class="mb-4 flex items-end justify-between gap-3 px-1">
                        <div>
                            <h2 id="role-list-heading" class="text-xl font-black text-slate-900">Daftar Role</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ number_format($roles->count()) }} role aktual tersedia dari data pengguna aplikasi.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($roles as $role)
                            @php
                                $stat = $roleStats->get($role->role, ['active_permission_count' => 0, 'module_count' => 0, 'status' => 'unmapped']);
                                $isAdmin = $stat['status'] === 'protected';
                            @endphp
                            <article class="flex min-h-[190px] flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $isAdmin ? 'bg-blue-700 text-white' : 'bg-slate-100 text-blue-700' }}"><span class="material-symbols-outlined text-[20px]" aria-hidden="true">{{ $isAdmin ? 'admin_panel_settings' : 'person' }}</span></span>
                                    @if($isAdmin)
                                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-blue-800">Protected</span>
                                    @elseif($stat['status'] === 'configured')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-800">Sudah Dikonfigurasi</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-amber-800">Belum Dikonfigurasi</span>
                                    @endif
                                </div>

                                <div class="mt-4">
                                    <h3 class="break-words text-base font-bold text-slate-900">{{ $role->role }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ number_format($role->users_count) }} pengguna terdaftar</p>
                                </div>

                                <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-200/80 pt-3">
                                    <div><dt class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Permission aktif</dt><dd class="mt-1 text-lg font-black text-slate-800">{{ number_format($stat['active_permission_count']) }}</dd></div>
                                    <div><dt class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Modul punya akses</dt><dd class="mt-1 text-lg font-black text-slate-800">{{ number_format($stat['module_count']) }}</dd></div>
                                </dl>

                                <div class="mt-auto pt-5">
                                    <a href="{{ route('admin.access-management.editor', ['role' => $role->role]) }}" class="inline-flex min-h-10 w-full items-center justify-center rounded-lg {{ $isAdmin ? 'border border-blue-200 bg-blue-50 text-blue-800 hover:bg-blue-100' : 'bg-blue-700 text-white hover:bg-blue-800' }} px-4 py-2 text-center text-sm font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Kelola akses <span class="material-symbols-outlined ml-1 text-[16px]" aria-hidden="true">arrow_forward</span></a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
