{{--
    Editor pohon langkah alur approval.

    Dipakai bersama oleh form "Ubah Alur" dan "Tambah Alur Baru".
    Data ke server tetap [{stage_key, parent_index}], tapi di layar dikelola
    sebagai daftar datar + kedalaman (depth) supaya pohonnya terlihat jelas:
    indentasi, konektor, tombol anak, dan seret-lepas.

    Props:
      - $steps : payload awal [{stage_key, parent_index}]
      - $stages: katalog tahap
--}}
@props([
    'steps' => [],
    'stages',
])

@php
    // Payload dari server (parent_index) diubah menjadi daftar datar + depth.
    $keys = [];
    $depth = [];

    foreach (array_values($steps) as $step) {
        $stageKey = is_array($step) ? ($step['stage_key'] ?? null) : $step;
        $parentIndex = is_array($step) ? ($step['parent_index'] ?? null) : null;

        $keys[] = $stageKey;
        $depth[] = ($parentIndex === null || !isset($keys[$parentIndex]))
            ? 0
            : $depth[$parentIndex] + 1;
    }

    // Normalisasi: langkah pertama selalu root, kedalaman tidak boleh melonjak naik > 1.
    $prev = -1;

    foreach ($depth as $i => $value) {
        $depth[$i] = $value === 0 ? 0 : min($value, $prev + 1);
        $prev = $depth[$i];
    }
@endphp

<div class="flow-tree"
     x-data="{
        rows: {{ Js::from($keys) }},
        depth: {{ Js::from($depth) }},
        labels: {{ Js::from($stages->pluck('label', 'key')->toArray()) }},
        newStage: '',
        dragFrom: null,
        dropOn: null,

        subtreeSize(tree, i) {
            let size = 1;
            const base = tree.depth[i];
            for (let k = i + 1; k < tree.rows.length && tree.depth[k] > base; k++) size++;
            return size;
        },

        payload() {
            const out = [];
            const stack = [];
            this.rows.forEach((key, i) => {
                const d = this.depth[i];
                while (stack.length && stack[stack.length - 1].depth >= d) stack.pop();
                out.push({ stage_key: key, parent_index: stack.length ? stack[stack.length - 1].index : null });
                stack.push({ depth: d, index: i });
            });
            return out;
        },

        label(key) { return this.labels[key] || key; },

        /* Kedalaman harus selalu turun paling banyak 1 tingkat dari induknya. */
        normalize() {
            if (this.rows.length === 0) return;
            this.depth[0] = 0;
            for (let i = 1; i < this.rows.length; i++) {
                this.depth[i] = Math.max(0, Math.min(this.depth[i], this.depth[i - 1] + 1));
            }
        },

        canIndent(i) { return i > 0 && this.depth[i] <= this.depth[i - 1]; },
        canOutdent(i) { return this.depth[i] > 0; },
indent(i) {
            if (!this.canIndent(i)) return;
            this.depth[i] = this.depth[i - 1] + 1;
            this.normalize();
        },

        outdent(i) {
            if (!this.canOutdent(i)) return;
            this.depth[i] = this.depth[i - 1];
            this.normalize();
        },

        /* Menggeser satu simpul BESERTA seluruh subtree-nya. */
        move(i, delta) {
            const size = this.subtreeSize(this, i);
            const target = i + delta;
            if (target < 0 || target + size > this.rows.length) return;

            const blockRows = this.rows.splice(i, size);
            const blockDepth = this.depth.splice(i, size);
            const shift = delta < 0
                ? -(blockDepth[0] - this.depth[target])
                : (this.depth[target] - blockDepth[0]);

            this.rows.splice(target, 0, ...blockRows);
            this.depth.splice(target, 0, ...blockDepth.map((d) => Math.max(0, d + shift)));
            this.normalize();
        },

        remove(i) {
            const size = this.subtreeSize(this, i);
            this.rows.splice(i, size);
            this.depth.splice(i, size);
            this.normalize();
        },

        addRoot() {
            if (!this.newStage) return;
            this.rows.push(this.newStage);
            this.depth.push(0);
            this.newStage = '';
        },

        addChild(i) {
            if (!this.newStage) return;
            const at = i + this.subtreeSize(this, i);
            this.rows.splice(at, 0, this.newStage);
            this.depth.splice(at, 0, this.depth[i] + 1);
            this.newStage = '';
        },

        dragStart(i) { this.dragFrom = i; },
        dragEnd() { this.dragFrom = null; this.dropOn = null; },

        /* Seret-lepas: before / after / child. */
        drop(target, mode) {
            const from = this.dragFrom;
            this.dragEnd();

            if (from === null || from === target) return;

            const size = this.subtreeSize(this, from);
            const blockRows = this.rows.splice(from, size);
            const blockDepth = this.depth.splice(from, size);
            const base = blockDepth[0];

            let at;
            let newDepth;

            if (mode === 'child') {
                at = target > from ? target + 1 : target;
                newDepth = this.depth[at - 1] !== undefined ? this.depth[target] + 1 : 0;
            } else {
                const parentDepth = this.depth[target] - (mode === 'before' ? 1 : 0);
                at = target + (mode === 'before' ? 0 : 1);
                newDepth = Math.max(0, parentDepth);
            }

            this.rows.splice(at, 0, ...blockRows);
            this.depth.splice(at, 0, ...blockDepth.map((d) => d - base + newDepth));
            this.normalize();
        }
     }"
     x-cloak>

    {{-- input yang dikirim ke server --}}
    <template x-for="(row, i) in payload()" :key="'step-' + i">
        <div class="hidden">
            <input type="hidden" :name="'steps[' + i + '][stage_key]'" :value="row.stage_key">
            <input type="hidden" :name="'steps[' + i + '][parent_index]'" :value="row.parent_index === null ? '' : row.parent_index">
        </div>
    </template>
{{-- pohon --}}
    <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 bg-slate-50 border-b border-slate-200">
            <p class="text-[11px] font-black uppercase tracking-wide text-slate-500">Pohon Langkah</p>
            <p class="text-[10px] text-slate-400">
                <span class="font-bold">⇥</span> jadi anak
                <span class="font-bold ml-2">⇤</span> naik ke induk
                <span class="font-bold ml-2">+ anak</span> sisip di bawahnya
                <span class="font-bold ml-2">seret baris</span> untuk menggeser
            </p>
        </div>

        <ul class="divide-y divide-slate-100">
            <template x-for="(row, i) in rows" :key="'row-' + i">
                <li class="flex items-center gap-2 px-3 py-2 hover:bg-indigo-50/40 transition"
                    draggable="true"
                    @dragstart="dragStart(i)"
                    @dragover.prevent="dropOn = i"
                    @dragleave="dropOn === i && (dropOn = null)"
                    @drop.prevent="drop(i, $event.offsetY < ($event.currentTarget.offsetHeight * 0.35) ? 'before' : ($event.offsetY > ($event.currentTarget.offsetHeight * 0.65) ? 'after' : 'child'))"
                    @dragend="dragEnd()"
                    :class="dropOn === i ? 'ring-2 ring-inset ring-indigo-300' : ''">

                    <span class="select-none text-slate-300 cursor-grab" title="Seret untuk menggeser">&#x283F;</span>
                    <span class="select-none font-mono text-[10px] text-slate-400 w-5 text-right" x-text="i + 1"></span>

                    {{-- konektor pohon --}}
                    <span class="select-none font-mono text-indigo-300 text-xs whitespace-pre"
                          :style="`padding-left: ${depth[i] * 16}px`"
                          x-text="depth[i] ? '\u2514\u2500 ' : '\u2022 '"></span>

                    <select x-model="rows[i]" class="rounded-lg border-slate-200 text-xs py-1 flex-1 min-w-0">
                        @foreach($stages as $stage)
                            <option value="{{ $stage->key }}">{{ $stage->label }}</option>
                        @endforeach
                    </select>

                    <span class="hidden md:inline text-[10px] font-bold uppercase text-slate-400 w-20 text-right"
                          x-text="depth[i] ? 'level ' + (depth[i] + 1) : 'root'"></span>

                    <span class="flex items-center gap-1">
                        <button type="button" @click="outdent(i)" :disabled="!canOutdent(i)"
                            class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 disabled:opacity-30 hover:bg-slate-200 transition"
                            title="Naikkan ke induk">&#8676;</button>

                        <button type="button" @click="indent(i)" :disabled="!canIndent(i)"
                            class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 disabled:opacity-30 hover:bg-slate-200 transition"
                            title="Jadikan anak dari langkah sebelumnya">&#8677;</button>

                        <button type="button" @click="move(i, -1)" :disabled="i === 0"
                            class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 disabled:opacity-30 hover:bg-slate-200 transition"
                            title="Naikkan urutan beserta anaknya">&#9650;</button>

                        <button type="button" @click="move(i, 1)"
                            class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 disabled:opacity-30 hover:bg-slate-200 transition"
                            title="Turunkan urutan beserta anaknya">&#9660;</button>

                        <button type="button" @click="addChild(i)"
                            class="px-2 py-1 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition"
                            title="Tambah anak di bawah langkah ini">+ anak</button>

                        <button type="button" @click="remove(i)"
                            class="px-2 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition"
                            title="Hapus langkah beserta anaknya">&#10005;</button>
                    </span>
                </li>
            </template>

            <li x-show="rows.length === 0" class="px-4 py-6 text-center text-xs text-slate-400 italic">
                Belum ada langkah. Tambahkan tahap utama di bawah.
            </li>
        </ul>

        <div class="flex flex-wrap items-end gap-2 px-4 py-3 bg-slate-50 border-t border-slate-200">
            <label class="block">
                <span class="text-[11px] font-bold text-slate-600">Tambah tahap</span>
                <select x-model="newStage" class="mt-1 rounded-lg border-slate-200 bg-white text-xs py-1.5">
                    <option value="">-- pilih tahap --</option>
                    @foreach($stages as $stage)
                        <option value="{{ $stage->key }}">{{ $stage->label }}</option>
                    @endforeach
                </select>
            </label>

            <button type="button" @click="addRoot()" class="px-3 py-2 rounded-lg text-xs font-bold bg-slate-800 text-white hover:bg-slate-900 transition">
                + Tambah langkah utama
            </button>

            <p class="text-[10px] text-slate-400 ml-auto">
                Langkah pertama selalu jadi titik awal. Kalau tahap pertamanya <strong>PJ</strong>, pengajuan menunggu PJ.
            </p>
        </div>
    </div>
</div>
