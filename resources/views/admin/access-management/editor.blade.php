<x-app-layout>
    @php
        $actionLabels = [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'update' => 'Ubah',
            'delete' => 'Hapus',
            'export' => 'Export',
            'approve' => 'Approve',
            'reject' => 'Reject',
            'pdf' => 'PDF',
            'action' => 'Aksi',
            'special' => 'Special',
        ];
    @endphp

    <div id="permission-editor" class="min-h-screen bg-slate-100 px-4 py-6 pb-28 sm:px-6 lg:px-8" data-protected="{{ $isProtectedRole ? '1' : '0' }}" data-role="{{ $role }}" data-version="{{ $permissionVersion }}" data-save-url="{{ route('admin.access-management.batch', ['role' => $role]) }}">
        <div class="mx-auto max-w-7xl space-y-6">
            <header class="rounded-3xl bg-slate-950 px-5 py-6 text-white shadow-xl sm:px-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <nav aria-label="Breadcrumb" class="text-xs font-semibold text-blue-200">
                            <a href="{{ route('admin.access-management.index') }}" class="hover:text-white hover:underline">Manajemen Hak Akses</a>
                            <span class="px-1.5">/</span><span>{{ $role }}</span>
                        </nav>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Pengaturan Akses Role</h1>
                            @if($role === 'admin')<span class="rounded-full bg-blue-400/20 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-blue-100 ring-1 ring-blue-300/40">Admin</span>@endif
                        </div>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Atur draft akses menu dan fitur untuk role <strong class="text-white">{{ $role }}</strong>. Perubahan belum disimpan ke database.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.access-management.index') }}" class="leave-link inline-flex min-h-11 items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-blue-300">&larr; Daftar Role</a>
                        <button type="button" id="reset-draft" class="min-h-11 rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-blue-300">Batalkan Perubahan</button>
                    </div>
                </div>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ringkasan role">
                @foreach([
                    ['label' => 'Permission aktif', 'value' => $activePermissionCount, 'icon' => 'verified_user', 'class' => 'bg-blue-50 text-blue-700'],
                    ['label' => 'Baseline diberikan', 'value' => $grantedCount, 'icon' => 'task_alt', 'class' => 'bg-emerald-50 text-emerald-700'],
                    ['label' => 'Modul dengan akses', 'value' => $viewModuleCount, 'icon' => 'view_module', 'class' => 'bg-violet-50 text-violet-700'],
                    ['label' => 'Pengguna role', 'value' => $targetUserCount, 'icon' => 'groups', 'class' => 'bg-amber-50 text-amber-700'],
                ] as $card)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-center justify-between gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $card['class'] }}"><span class="material-symbols-outlined" aria-hidden="true">{{ $card['icon'] }}</span></span><strong class="text-2xl font-black text-slate-900">{{ number_format($card['value']) }}</strong></div><p class="mt-4 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p></article>
                @endforeach
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_13rem_13rem_auto] lg:items-end">
                    <label class="block"><span class="text-xs font-bold text-slate-600">Cari menu / permission</span><input id="permission-search" type="search" class="mt-1 min-h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Cari nama modul, label, atau key..." autocomplete="off"></label>
                    <label class="block"><span class="text-xs font-bold text-slate-600">Filter kategori</span><select id="category-filter" class="mt-1 min-h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">Semua kategori</option>@foreach($modules->flatMap(fn ($module) => $module['permissions']->pluck('category'))->unique()->sort()->values() as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select></label>
                    <label class="block"><span class="text-xs font-bold text-slate-600">Filter status akses</span><select id="access-filter" class="mt-1 min-h-11 w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"><option value="">Semua status</option><option value="granted">Granted baseline</option><option value="denied">Explicit denied</option><option value="unmapped">Belum dipetakan</option></select></label>
                    <button type="button" id="reset-filters" class="min-h-11 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Reset filter</button>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-500"><span id="draft-state" class="rounded-full bg-amber-50 px-3 py-1.5 text-amber-800">Draft belum berubah</span><span id="filter-result">{{ $modules->count() }} modul ditampilkan</span></div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <section class="min-w-0 rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="permission-table-heading">
                    <div class="flex flex-col gap-2 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 id="permission-table-heading" class="text-lg font-black text-slate-900">Matrix Permission</h2><p class="mt-1 text-sm text-slate-500">Sumber menu dan permission: registry aktif aplikasi.</p></div><span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ $modules->count() }} modul</span></div>
                    <div class="overflow-x-auto rounded-b-2xl">
                        <table class="min-w-[820px] w-full text-left text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="w-[28%] px-5 py-3">Modul / Menu</th><th class="px-3 py-3 text-center">Semua</th>@foreach($actions as $action)<th class="px-3 py-3 text-center">{{ $actionLabels[$action] ?? ucfirst($action) }}</th>@endforeach</tr></thead>
                            <tbody id="permission-rows" class="divide-y divide-slate-100">
                                @foreach($modules as $module)
                                    <tr class="module-row align-top hover:bg-slate-50/70" data-module-row data-module-search="{{ strtolower($module['key'].' '.$module['label'].' '.$module['description']) }}" data-module-key="{{ $module['key'] }}">
                                        <td class="px-5 py-5"><div class="flex items-start gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700"><span class="material-symbols-outlined text-[20px]" aria-hidden="true">view_module</span></span><div><p class="font-black text-slate-900">{{ $module['label'] }}</p><code class="text-[11px] text-slate-500">{{ $module['key'] }}</code>
                                            @if($module['description'])
                                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $module['description'] }}</p>
                                            @endif
                                            @if($module['legacy_count'])
                                                <p class="mt-2 text-[11px] font-bold text-amber-700">{{ $module['legacy_count'] }} legacy exception terdaftar</p>
                                            @endif
                                        </div></div></td>
                                        <td class="px-3 py-5 text-center"><input type="checkbox" class="module-toggle h-5 w-5 rounded border-slate-300 text-blue-700 focus:ring-blue-500" data-module-toggle data-module="{{ $module['key'] }}" aria-label="Pilih semua permission {{ $module['label'] }}" @disabled($isProtectedRole)></td>
                                        @foreach($actions as $action)
                                            <td class="px-3 py-5 align-top"><div class="space-y-3">
                                                @foreach($module['permissions']->where('action_key', $action) as $permission)
                                                    @php $status = $permission['baseline'] ? 'granted' : ($permission['mapped'] ? 'denied' : 'unmapped'); @endphp
                                                     <label class="permission-item flex min-w-20 flex-col items-center gap-1 text-center {{ $permission['system_protected'] || $isProtectedRole ? 'opacity-60' : '' }}" data-permission-item data-search="{{ strtolower($permission['key'].' '.$permission['label'].' '.$permission['description']) }}" data-category="{{ $permission['category'] }}" data-status="{{ $status }}" title="{{ $permission['protected_reason'] }}"><input type="checkbox" class="permission-toggle h-5 w-5 rounded border-slate-300 text-blue-700 focus:ring-blue-500" data-permission-checkbox data-key="{{ $permission['key'] }}" data-module="{{ $module['key'] }}" data-action="{{ $permission['action_key'] }}" data-category="{{ $permission['category'] }}" data-baseline="{{ $permission['baseline'] ? '1' : '0' }}" data-mapped="{{ $permission['mapped'] ? '1' : '0' }}" @checked($permission['baseline']) @disabled($permission['system_protected'] || $isProtectedRole)><span class="max-w-24 break-words text-[10px] font-semibold leading-4 text-slate-600">{{ $permission['label'] }}</span><span class="text-[9px] font-black uppercase {{ $permission['system_protected'] ? 'text-slate-500' : ($permission['restricted'] ? 'text-amber-700' : 'text-emerald-700') }}">{{ $isProtectedRole ? 'Protected Admin' : ucfirst($permission['classification']) }}</span><span class="max-w-32 text-[9px] leading-3 text-slate-500">{{ $permission['protected_reason'] }}</span>@if($permission['legacy_count'])<span class="text-[9px] font-bold text-amber-700">Legacy</span>@endif</label>
                                                @endforeach
                                                @if($module['permissions']->where('action_key', $action)->isEmpty())<span class="block pt-1 text-center text-slate-300">—</span>@endif
                                            </div></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($role === 'admin')<div class="border-t border-slate-200 bg-slate-50 px-5 py-4 text-sm font-semibold text-slate-600"><strong>Catatan:</strong> permission keamanan inti tetap terkunci secara individual. Permission admin lainnya dapat dikelola dan dicatat dalam audit log.</div>@endif
                </section>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between gap-3"><h2 class="text-lg font-black text-slate-900">Ringkasan Draft</h2><span id="draft-count" class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700">0 dipilih</span></div><dl class="mt-5 space-y-3 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Dipertahankan</dt><dd id="kept-count" class="font-black text-slate-800">0</dd></div><div class="flex justify-between gap-3"><dt class="text-emerald-700">Akan ditambahkan</dt><dd id="added-count" class="font-black text-emerald-700">0</dd></div><div class="flex justify-between gap-3"><dt class="text-rose-700">Akan dicabut</dt><dd id="revoked-count" class="font-black text-rose-700">0</dd></div><div class="flex justify-between gap-3 border-t border-slate-100 pt-3"><dt class="font-bold text-slate-700">Total permission draft</dt><dd id="total-count" class="font-black text-slate-900">0</dd></div><div class="flex justify-between gap-3"><dt class="font-bold text-slate-700">Modul dengan view</dt><dd id="module-count" class="font-black text-slate-900">0</dd></div></dl></section>
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-black text-slate-900">Preview Menu</h2><p class="mt-1 text-xs leading-5 text-slate-500">Menu terlihat jika permission kategori view aktif.</p><div id="menu-preview" class="mt-4 space-y-2"></div><div id="action-warning" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-xs leading-5 text-amber-800"></div></section>
                    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm"><h2 class="text-sm font-black text-blue-950">Simpan Pengaturan</h2><p class="mt-2 text-xs leading-5 text-blue-900">Perubahan dikirim sebagai draft sparse dan divalidasi ulang oleh server.</p><label class="mt-4 block"><span class="text-xs font-bold text-blue-900">Alasan perubahan</span><textarea id="permission-reason" rows="3" maxlength="500" class="mt-1 w-full rounded-xl border-blue-200 bg-white text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Contoh: Penyesuaian akses role operasional"></textarea></label><button type="button" id="save-permissions" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-blue-700 px-4 py-2 text-sm font-black text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-60" disabled>Simpan Pengaturan</button><p id="save-status" class="mt-3 hidden rounded-xl px-3 py-2 text-xs font-semibold" role="status" aria-live="polite"></p><button type="button" id="reload-permissions" class="mt-3 hidden min-h-11 w-full items-center justify-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-black text-amber-900 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500">Muat ulang data permission</button></section>
                </aside>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('permission-editor');
            if (!root) return;
            const checkboxes = () => Array.from(root.querySelectorAll('[data-permission-checkbox]'));
            const moduleRows = () => Array.from(root.querySelectorAll('[data-module-row]'));
            const search = document.getElementById('permission-search');
            const category = document.getElementById('category-filter');
            const access = document.getElementById('access-filter');
            const dirty = () => checkboxes().some((box) => box.checked !== (box.dataset.baseline === '1'));
            const setText = (id, value) => { const node = document.getElementById(id); if (node) node.textContent = String(value); };
            let saving = false;

            function moduleBoxes(row) { return Array.from(row.querySelectorAll('[data-permission-checkbox]')); }
            function updateModuleToggle(row) {
                const toggle = row.querySelector('[data-module-toggle]');
                const boxes = moduleBoxes(row).filter((box) => !box.disabled);
                if (!toggle) return;
                const selected = boxes.filter((box) => box.checked).length;
                toggle.checked = boxes.length > 0 && selected === boxes.length;
                toggle.indeterminate = selected > 0 && selected < boxes.length;
                toggle.disabled = root.dataset.protected === '1' || boxes.length === 0;
            }
            function updateItemStatuses() {
                checkboxes().forEach((box) => {
                    const item = box.closest('[data-permission-item]');
                    if (!item) return;
                    item.dataset.status = box.checked ? 'granted' : (box.dataset.mapped === '1' ? 'denied' : 'unmapped');
                });
            }
            function updateSummary() {
                const boxes = checkboxes();
                const baselineGranted = boxes.filter((box) => box.dataset.baseline === '1');
                const currentGranted = boxes.filter((box) => box.checked);
                const added = currentGranted.filter((box) => box.dataset.baseline !== '1');
                const revoked = baselineGranted.filter((box) => !box.checked);
                setText('kept-count', currentGranted.length - added.length);
                setText('added-count', added.length);
                setText('revoked-count', revoked.length);
                setText('total-count', currentGranted.length);
                setText('draft-count', currentGranted.length + ' dipilih');
                setText('module-count', moduleRows().filter((row) => moduleBoxes(row).some((box) => box.checked && box.dataset.category === 'view')).length);
                const state = document.getElementById('draft-state');
                if (state) { state.textContent = dirty() ? 'Draft berubah — belum disimpan' : 'Draft belum berubah'; state.className = dirty() ? 'rounded-full bg-amber-100 px-3 py-1.5 text-amber-800' : 'rounded-full bg-slate-100 px-3 py-1.5 text-slate-600'; }
                const preview = document.getElementById('menu-preview');
                if (preview) { const visible = moduleRows().filter((row) => moduleBoxes(row).some((box) => box.checked && box.dataset.category === 'view')); preview.innerHTML = visible.length ? visible.map((row) => '<span class="block rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800">✓ ' + row.querySelector('p').textContent + '</span>').join('') : '<span class="block rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">Belum ada menu yang terlihat.</span>'; }
                const warning = document.getElementById('action-warning');
                if (warning) { const warnings = moduleRows().filter((row) => { const boxesIn = moduleBoxes(row); return boxesIn.some((box) => box.checked && box.dataset.category !== 'view') && !boxesIn.some((box) => box.checked && box.dataset.category === 'view'); }); warning.textContent = warnings.length ? 'Ada ' + warnings.length + ' modul dengan permission tindakan aktif tetapi permission view belum aktif. Menu tetap tidak terlihat sampai view diberikan.' : ''; warning.classList.toggle('hidden', warnings.length === 0); }
            }
            function applyFilters() {
                const needle = (search?.value || '').trim().toLowerCase();
                const selectedCategory = category?.value || '';
                const selectedStatus = access?.value || '';
                let shown = 0;
                moduleRows().forEach((row) => {
                    const moduleMatch = !needle || row.dataset.moduleSearch.includes(needle);
                    let visibleItems = 0;
                    row.querySelectorAll('[data-permission-item]').forEach((item) => {
                        const match = moduleMatch || !needle || item.dataset.search.includes(needle);
                        const visible = match && (!selectedCategory || item.dataset.category === selectedCategory) && (!selectedStatus || item.dataset.status === selectedStatus);
                        item.classList.toggle('hidden', !visible);
                        if (visible) visibleItems++;
                    });
                    const visible = visibleItems > 0;
                    row.classList.toggle('hidden', !visible);
                    if (visible) shown++;
                });
                setText('filter-result', shown + ' modul ditampilkan');
            }
             function draftPayload() {
                 const desired = {};
                 checkboxes().forEach((box) => {
                     const baseline = box.dataset.baseline === '1';
                     if (box.checked !== baseline) desired[box.dataset.key] = box.checked;
                 });
                 return desired;
             }
             function changedSummary() {
                 return Object.values(draftPayload()).reduce((summary, granted) => { granted ? summary.grants++ : summary.revokes++; return summary; }, { grants: 0, revokes: 0 });
             }
             function setSaveStatus(message, kind) {
                 const node = document.getElementById('save-status');
                 if (!node) return;
                 node.textContent = message;
                 node.className = 'mt-3 rounded-xl px-3 py-2 text-xs font-semibold ' + (kind === 'success' ? 'bg-emerald-100 text-emerald-800' : kind === 'conflict' ? 'bg-amber-100 text-amber-900' : 'bg-rose-100 text-rose-800');
             }
             function setConflictAction(visible) {
                 const node = document.getElementById('reload-permissions');
                 if (!node) return;
                 node.classList.toggle('hidden', !visible);
                 node.classList.toggle('inline-flex', visible);
             }
             function updateSaveButton() {
                 const button = document.getElementById('save-permissions');
                 if (button) button.disabled = root.dataset.protected === '1' || Object.keys(draftPayload()).length === 0;
             }
             function refresh() { updateItemStatuses(); moduleRows().forEach(updateModuleToggle); updateSummary(); applyFilters(); updateSaveButton(); }
            root.querySelectorAll('[data-module-toggle]').forEach((toggle) => toggle.addEventListener('change', function () { const row = this.closest('[data-module-row]'); moduleBoxes(row).filter((box) => !box.disabled).forEach((box) => { box.checked = this.checked; }); refresh(); }));
            checkboxes().forEach((box) => box.addEventListener('change', refresh));
            [search, category, access].forEach((control) => control?.addEventListener('input', applyFilters));
            document.getElementById('reset-filters')?.addEventListener('click', function () { if (search) search.value = ''; if (category) category.value = ''; if (access) access.value = ''; applyFilters(); });
             document.getElementById('reset-draft')?.addEventListener('click', function () { checkboxes().forEach((box) => { box.checked = box.dataset.baseline === '1'; }); refresh(); });
             document.getElementById('save-permissions')?.addEventListener('click', async function () {
                 const button = this;
                 if (saving) return;
                 const reasonNode = document.getElementById('permission-reason');
                 const reason = reasonNode?.value.trim() || '';
                 const desired = draftPayload();
                 const summary = changedSummary();
                 if (!Object.keys(desired).length) return;
                 if (!reason) { setSaveStatus('Alasan perubahan wajib diisi.', 'error'); reasonNode?.focus(); return; }
                 if (!window.confirm('Simpan ' + summary.grants + ' grant dan ' + summary.revokes + ' revoke untuk role ' + root.dataset.role + '?')) return;
                 saving = true;
                 setConflictAction(false);
                 button.disabled = true;
                 button.textContent = 'Menyimpan...';
                 setSaveStatus('Menyimpan perubahan...', 'success');
                 try {
                     const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                     const response = await fetch(root.dataset.saveUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ target_role: root.dataset.role, desired_permissions: desired, expected_version: Number(root.dataset.version), reason }) });
                     const data = await response.json().catch(() => ({}));
                     if (!response.ok) {
                         if (response.status === 409 && data.conflict) { setSaveStatus(data.message || 'Data berubah. Muat ulang sebelum menyimpan lagi.', 'conflict'); setConflictAction(true); return; }
                         setSaveStatus(data.message || 'Perubahan tidak dapat disimpan.', 'error');
                         return;
                     }
                     root.dataset.version = String(data.version);
                     checkboxes().forEach((box) => { box.dataset.baseline = box.checked ? '1' : '0'; });
                     if (reasonNode) reasonNode.value = '';
                     setSaveStatus(data.message || 'Perubahan berhasil disimpan.', 'success');
                     setConflictAction(false);
                     refresh();
                 } catch (error) {
                     setSaveStatus('Koneksi gagal. Draft tetap dipertahankan.', 'error');
                 } finally {
                     saving = false;
                     button.textContent = 'Simpan Pengaturan';
                     updateSaveButton();
                 }
             });
             document.getElementById('reload-permissions')?.addEventListener('click', function () { window.location.reload(); });
            document.querySelectorAll('.leave-link').forEach((link) => link.addEventListener('click', function (event) { if (dirty() && !window.confirm('Draft belum disimpan. Tinggalkan halaman?')) event.preventDefault(); }));
            window.addEventListener('beforeunload', function (event) { if (dirty()) { event.preventDefault(); event.returnValue = ''; } });
            refresh();
        })();
    </script>
</x-app-layout>
