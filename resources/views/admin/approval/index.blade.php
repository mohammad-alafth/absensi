{{-- Menu Admin: Konfigurasi Alur Approval --}}
<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-4 py-8 pb-28">
        <div class="max-w-6xl mx-auto space-y-8">

            {{-- Header --}}
            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-100">
                    <div>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline transition mb-2">
                            &larr; Kembali ke Dashboard
                        </a>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Alur Approval</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Tree approval (Cuti / Izin / Lembur) dikelola dari sini &mdash; tanpa ubah kode.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.approval-flows.sync-defaults') }}"
                        onsubmit="return confirm('Pulihkan konfigurasi alur ke bawaan sistem? Pengaturan manual akan ditimpa.')">
                        @csrf
                        <button class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition">
                            Pulihkan Default Sistem
                        </button>
                    </form>
                </div>

                @if(session('success'))
                    <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold rounded-xl px-4 py-3">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mt-6 bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold rounded-xl px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="mt-6 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-xl px-4 py-3">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-6 text-sm rounded-xl px-4 py-3 border {{ $configEnabled ? 'bg-indigo-50 border-indigo-200 text-indigo-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                    @if($configEnabled)
                        <strong>Konfigurasi aktif.</strong> Alur approval dibaca dari database
                        ({{ count($stages) }} tahap, {{ count($flows) }} alur).
                    @else
                        <strong>Masih memakai alur bawaan kode.</strong> Tekan <em>Pulihkan Default Sistem</em>
                        atau tambahkan tahap &amp; alur untuk mengaktifkan pengaturan dari menu ini.
                    @endif
                </div>
            </div>

            {{-- Pratinjau alur per jenis pengajuan --}}
            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <h2 class="font-bold text-lg text-gray-800 mb-1">Pratinjau Alur Saat Ini</h2>
                <p class="text-xs text-gray-500 mb-6">Dasar perhitungan pengajuan baru per role pengaju.</p>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    @foreach($types as $type => $typeLabel)
                        <div class="bg-slate-50 rounded-3xl p-5 border border-slate-100">
                            <h3 class="font-bold text-sm text-gray-800 mb-4">Pengajuan {{ $typeLabel }}</h3>

                            <div class="space-y-3 text-xs">
                                @forelse($preview[$type] ?? [] as $role => $info)
                                    <div class="bg-white rounded-2xl border border-slate-100 px-3 py-2">
                                        <p class="font-bold text-gray-700 mb-1">{{ strtoupper($role) }}</p>
                                        <div class="flex flex-wrap items-center gap-1 text-[10px]">
                                            @if($info['steps'] === [])
                                                <span class="text-gray-400">langsung disetujui</span>
                                            @else
                                                @foreach($info['steps'] as $index => $stageKey)
                                                    @if($index > 0)<span class="text-gray-300">&rarr;</span>@endif
                                                    <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold">
                                                        {{ \App\Services\ApprovalFlowService::labelForStage($stageKey) }}
                                                    </span>
                                                @endforeach
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-1">status awal: {{ $info['initial_status'] }}</p>
                                    </div>
                                @empty
                                    <p class="text-gray-400 italic">Belum ada role contoh.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
{{-- ============ KATALOG TAHAP: TABEL ============ --}}
            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <h2 class="font-bold text-lg text-gray-800 mb-1">Katalog Tahap</h2>
                <p class="text-xs text-gray-500 mb-6">
                    Tahap = approver dalam alur. Menambah tahap baru otomatis membuat kolomnya di tabel
                    cuti, izin, dan lembur.
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="p-3 font-bold text-gray-500 uppercase">Key</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Label</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Role Approver</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Urutan</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Status</th>
                                <th class="p-3 font-bold text-gray-500 uppercase text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($stages as $stage)
                                <tr>
                                    <td class="p-3 font-mono font-bold text-gray-700">{{ $stage->key }}</td>
                                    <td class="p-3 text-gray-700">
                                        {{ $stage->label }}
                                        @if($stage->is_pj)
                                            <span class="ml-1 text-[9px] font-black uppercase text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">PJ</span>
                                        @endif
                                    </td>
                                    <td class="p-3 font-mono text-gray-600">{{ $stage->role }}</td>
                                    <td class="p-3 text-gray-600">{{ $stage->sort_order }}</td>
                                    <td class="p-3">
                                        <span class="text-[10px] font-black uppercase px-2 py-1 rounded-lg {{ $stage->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $stage->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" onclick="document.getElementById('stage-edit-{{ $stage->id }}').showModal()"
                                                class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold transition">
                                                Ubah
                                            </button>

                                            <form method="POST" action="{{ route('admin.approval-flows.stages.toggle', $stage->key) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold transition">
                                                    {{ $stage->is_active ? 'Nonaktif' : 'Aktif' }}
                                                </button>
                                            </form>

                                            @if($stage->key !== \App\Services\ApprovalFlowConfig::PJ_STAGE)
                                                <form method="POST" action="{{ route('admin.approval-flows.stages.destroy', $stage->key) }}"
                                                    onsubmit="return confirm('Hapus/nonaktifkan tahap {{ $stage->label }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold transition">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
<dialog id="stage-edit-{{ $stage->id }}" class="rounded-2xl border border-slate-200 p-0 w-full max-w-md">
                                            <form method="POST" action="{{ route('admin.approval-flows.stages.update', $stage->key) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="p-6 space-y-4">
                                                    <h3 class="font-bold text-gray-800">Ubah Tahap {{ $stage->label }}</h3>

                                                    <label class="block">
                                                        <span class="text-xs font-bold text-gray-600">Key (nama kolom)</span>
                                                        <input name="key" value="{{ old('key', $stage->key) }}" required pattern="[a-z][a-z0-9_]*"
                                                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                    </label>

                                                    <label class="block">
                                                        <span class="text-xs font-bold text-gray-600">Label</span>
                                                        <input name="label" value="{{ old('label', $stage->label) }}" required
                                                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                    </label>

                                                    <label class="block">
                                                        <span class="text-xs font-bold text-gray-600">Role Approver</span>
                                                        <input name="role" value="{{ old('role', $stage->role) }}" required
                                                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                    </label>

                                                    <label class="block">
                                                        <span class="text-xs font-bold text-gray-600">Urutan</span>
                                                        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $stage->sort_order) }}"
                                                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                                    </label>

                                                    <label class="block">
                                                        <span class="text-xs font-bold text-gray-600">Keterangan</span>
                                                        <textarea name="description" rows="2"
                                                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ old('description', $stage->description) }}</textarea>
                                                    </label>

                                                    <div class="flex items-center gap-4 text-xs font-semibold text-gray-600">
                                                        <label class="flex items-center gap-2">
                                                            <input type="checkbox" name="is_pj" value="1" @checked($stage->is_pj)> Tahap PJ
                                                        </label>
                                                        <label class="flex items-center gap-2">
                                                            <input type="checkbox" name="is_active" value="1" @checked($stage->is_active)> Aktif
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="bg-slate-50 px-6 py-4 flex justify-end gap-2">
                                                    <button type="button" onclick="document.getElementById('stage-edit-{{ $stage->id }}').close()"
                                                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-200 transition">Batal</button>
                                                    <button class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">Simpan</button>
                                                </div>
                                            </form>
                                        </dialog>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-gray-400 italic">Belum ada tahap.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
<div class="mt-8 bg-slate-50 rounded-3xl p-6 border border-slate-100">
                    <h3 class="font-bold text-sm text-gray-800 mb-4">Tambah Tahap Baru</h3>

                    <form method="POST" action="{{ route('admin.approval-flows.stages.store') }}"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @csrf

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Key (nama kolom)</span>
                            <input name="key" value="{{ old('key') }}" required pattern="[a-z][a-z0-9_]*"
                                placeholder="mis. manager_mutu" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Label</span>
                            <input name="label" value="{{ old('label') }}" required
                                placeholder="mis. Manager Mutu" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Role Approver</span>
                            <input name="role" value="{{ old('role') }}" required
                                placeholder="mis. manager_mutu" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Urutan</span>
                            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', 99) }}"
                                class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </label>

                        <label class="block sm:col-span-2">
                            <span class="text-xs font-bold text-gray-600">Keterangan</span>
                            <input name="description" value="{{ old('description') }}"
                                class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </label>

                        <div class="sm:col-span-2 lg:col-span-3 flex items-center gap-4 text-xs font-semibold text-gray-600">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_pj" value="1"> Tahap PJ
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" value="1" checked> Aktif
                            </label>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                            <button class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                Simpan Tahap
                            </button>
                        </div>
                    </form>
                </div>
            </div>
{{-- ============ TREE ALUR ============ --}}
            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <h2 class="font-bold text-lg text-gray-800 mb-1">Tree Alur</h2>
                <p class="text-xs text-gray-500 mb-6">
                    Langkah dibaca berurutan dari atas. Langkah pertama menentukan apakah pengajuan menunggu PJ
                    atau langsung ke approver berikutnya; baris "Posisi" untuk membuat bentuk pohon.
                </p>

                <div class="space-y-4">
                    @forelse($flows as $flow)
                        @php
                            // Payload langkah (stage_key + parent_index) diberikan ke editor pohon.
                            $payload = $flow->stepPayload();
                            $typeOptions = array_merge([\App\Services\ApprovalFlowConfig::ALL_TYPES => 'Semua jenis'], $types);
                        @endphp

                        <details class="bg-slate-50 rounded-3xl border border-slate-100" @open($flow->is_default)>
                            <summary class="cursor-pointer select-none px-5 py-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-bold text-sm text-gray-800">
                                        {{ $flow->name }}
                                        @if($flow->is_default)
                                            <span class="ml-1 text-[9px] font-black uppercase text-indigo-700 bg-indigo-100 px-1.5 py-0.5 rounded">Default</span>
                                        @endif
                                        @unless($flow->is_active)
                                            <span class="ml-1 text-[9px] font-black uppercase text-slate-600 bg-slate-200 px-1.5 py-0.5 rounded">Nonaktif</span>
                                        @endunless
                                    </p>
                                    <p class="text-[11px] text-gray-500 mt-1">
                                        {{ $flow->type_label }} &middot;
                                        {{ collect($flow->orderedStageKeys())->map(fn ($key) => \App\Services\ApprovalFlowService::labelForStage($key))->join(' → ') ?: 'kosong' }}
                                    </p>
                                </div>
                                <span class="text-[10px] font-bold text-gray-400">buka / tutup</span>
                            </summary>
<form method="POST" action="{{ route('admin.approval-flows.update', $flow) }}" class="px-5 pb-5">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
                                    <label class="block sm:col-span-2">
                                        <span class="text-xs font-bold text-gray-600">Nama Alur</span>
                                        <input name="name" value="{{ old('name', $flow->name) }}" required
                                            class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                                    </label>

                                    <label class="block">
                                        <span class="text-xs font-bold text-gray-600">Berlaku untuk</span>
                                        <select name="submission_type" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                                            @foreach($typeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('submission_type', $flow->submission_type) === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <div class="flex items-end gap-4 text-xs font-semibold text-gray-600 pb-2">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="is_active" value="1" @checked($flow->is_active)> Aktif
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="is_default" value="1" @checked($flow->is_default)> Default
                                        </label>
                                    </div>

                                <x-approval-flow-tree class="mt-4" :steps="$payload" :stages="$stages" />

                                <div class="mt-4 flex items-center gap-3">
                                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                        Simpan Alur
                                    </button>
                                    <span class="text-[11px] text-slate-400">
                                        Langkah dibaca berurutan dari atas; anak selalu didahului induknya.
                                    </span>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.approval-flows.destroy', $flow) }}" class="px-5 pb-5"
                                onsubmit="return confirm('Hapus alur {{ $flow->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition">
                                    Hapus Alur Ini
                                </button>
                            </form>
                        </details>
                    @empty
                        <p class="text-sm text-gray-400 italic text-center py-6">
                            Belum ada alur. Tekan "Pulihkan Default Sistem" atau buat alur baru di bawah.
                        </p>
                    @endforelse
                </div>

            <div class="mt-8 bg-slate-50 rounded-3xl p-6 border border-slate-100">
                <h3 class="font-bold text-sm text-gray-800 mb-4">Tambah Alur Baru</h3>

                <form method="POST" action="{{ route('admin.approval-flows.store') }}">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <label class="block sm:col-span-2">
                            <span class="text-xs font-bold text-gray-600">Nama Alur</span>
                            <input name="name" value="{{ old('name') }}" required placeholder="mis. Alur Keamanan"
                                class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Berlaku untuk</span>
                            <select name="submission_type" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                                @foreach(array_merge([\App\Services\ApprovalFlowConfig::ALL_TYPES => 'Semua jenis'], $types) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="flex items-end pb-2 text-xs font-semibold text-gray-600">
                            <input type="checkbox" name="is_default" value="1" class="mr-2"> Jadikan default
                        </label>
                    </div>

                    <x-approval-flow-tree class="mt-4" :steps="[]" :stages="$stages" />

                    <div class="mt-4">
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                            Simpan Alur Baru
                        </button>
                    </div>
                </form>
            </div>
{{-- ============ PEMETAAN ROLE -> ALUR ============ --}}
            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <h2 class="font-bold text-lg text-gray-800 mb-1">Pemetaan Role Pengaju</h2>
                <p class="text-xs text-gray-500 mb-6">
                    Role yang tidak dipetakan memakai alur default jenis pengajuannya.
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="p-3 font-bold text-gray-500 uppercase">Role</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Jenis Pengajuan</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Alur</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Keterangan</th>
                                <th class="p-3 font-bold text-gray-500 uppercase text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($mappings as $mapping)
                                <tr>
                                    <td class="p-3 font-mono font-bold text-gray-700">{{ $mapping->role }}</td>
                                    <td class="p-3 text-gray-600">
                                        {{ $mapping->submission_type === \App\Services\ApprovalFlowConfig::ALL_TYPES ? 'Semua jenis' : ($types[$mapping->submission_type] ?? $mapping->submission_type) }}
                                    </td>
                                    <td class="p-3 text-gray-700">{{ $mapping->flow->name ?? '-' }}</td>
                                    <td class="p-3 text-gray-500">{{ $mapping->note ?? '-' }}</td>
                                    <td class="p-3 text-right">
                                        <form method="POST" action="{{ route('admin.approval-flows.role-mappings.destroy', $mapping->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold transition">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-gray-400 italic">Belum ada pemetaan role.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 bg-slate-50 rounded-3xl p-6 border border-slate-100">
                    <h3 class="font-bold text-sm text-gray-800 mb-4">Petakan Role ke Alur</h3>

                    <form method="POST" action="{{ route('admin.approval-flows.role-mappings.store') }}"
                        class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        @csrf

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Role pengaju</span>
                            <input name="role" value="{{ old('role') }}" required pattern="[a-z][a-z0-9_]*"
                                placeholder="mis. security" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Jenis pengajuan</span>
                            <select name="submission_type" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                                @foreach(array_merge([\App\Services\ApprovalFlowConfig::ALL_TYPES => 'Semua jenis'], $types) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Alur</span>
                            <select name="approval_flow_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                                <option value="">Ikuti default jenis</option>
                                @foreach($flows as $flow)
                                    <option value="{{ $flow->id }}">{{ $flow->name }} ({{ $flow->is_active ? 'aktif' : 'nonaktif' }})</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-gray-600">Keterangan</span>
                            <input name="note" value="{{ old('note') }}" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                        </label>

                        <div class="sm:col-span-4">
                            <button class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                Simpan Pemetaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
