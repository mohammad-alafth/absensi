<x-app-layout>
    <style>
        [x-cloak] { display: none !important; }

        .roster-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.35rem;
        }

        @media (max-width: 640px) {
            .roster-calendar-grid { gap: 0.2rem; }
        }
    </style>

    <div
        x-data="monthlyRosterPage()"
        x-init="init()"
        class="min-h-screen bg-[#f8f9ff] px-3 py-4 pb-24 text-slate-900 sm:px-6 sm:py-6"
    >
        <div class="mx-auto w-full max-w-7xl space-y-4 sm:space-y-5">
            <header class="flex flex-col gap-4 rounded-2xl border border-[#c4c5d7]/70 bg-white px-4 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <a
                        href="{{ route('shift.index') }}"
                        aria-label="Kembali ke manajemen plotting"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#eff4ff] text-[#0037b0] transition hover:bg-[#dce9ff] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0037b0]"
                    >←</a>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#0037b0]">Plotting individual PJ unit</p>
                        <h1 class="truncate font-['Plus_Jakarta_Sans'] text-xl font-bold tracking-tight text-[#0b1c30] sm:text-2xl">Plotting Jadwal Bulanan</h1>
                        <p class="truncate text-xs text-[#565e74]">{{ $employee->name }} · {{ $employee->role }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-[11px] font-bold text-amber-700">Draft frontend</span>
                    <span x-show="dirtyDates.length > 0" x-cloak class="rounded-full bg-[#dce1ff] px-3 py-1.5 text-[11px] font-bold text-[#0037b0]" x-text="dirtyDates.length + ' perubahan'"></span>
                </div>
            </header>

            <section class="rounded-2xl border border-[#c4c5d7]/70 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-[#0037b0]">Karyawan binaan</p>
                        <h2 class="mt-1 truncate font-['Plus_Jakarta_Sans'] text-lg font-bold text-[#0b1c30]">{{ $employee->name }}</h2>
                        <p class="mt-1 text-sm text-[#565e74]">Role: {{ $employee->role }}</p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <label for="roster-period" class="text-xs font-bold text-[#565e74]">Periode</label>
                        <input
                            id="roster-period"
                            type="month"
                            value="{{ sprintf('%04d-%02d', $year, $month) }}"
                            @change="changePeriod($event.target.value)"
                            class="min-h-11 rounded-xl border-[#c4c5d7] text-sm font-bold text-[#0b1c30] focus:border-[#0037b0] focus:ring-[#0037b0]"
                        >
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 border-t border-[#c4c5d7]/60 pt-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-[#eff4ff] p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Tanggal</p>
                        <p class="mt-1 text-xl font-bold text-[#0037b0]">{{ count($calendarDays) }}</p>
                        <p class="text-[11px] text-[#565e74]">dalam periode</p>
                    </div>
                    <div class="rounded-xl bg-[#eff4ff] p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Tersimpan</p>
                        <p class="mt-1 text-xl font-bold text-[#0037b0]">{{ $storedShiftCount }}</p>
                        <p class="text-[11px] text-[#565e74]">record roster</p>
                    </div>
                    <div class="rounded-xl bg-[#fff7ed] p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Belum diatur</p>
                        <p class="mt-1 text-xl font-bold text-orange-700">{{ collect($calendarDays)->where('status', 'unassigned')->count() }}</p>
                        <p class="text-[11px] text-[#565e74]">bukan otomatis OFF</p>
                    </div>
                    <div class="rounded-xl bg-[#f0fdf4] p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Draft berubah</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700" x-text="dirtyDates.length">0</p>
                        <p class="text-[11px] text-[#565e74]">belum tersimpan</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
                <main class="space-y-4">
                    <section class="rounded-2xl border border-[#c4c5d7]/70 bg-white p-3 shadow-sm sm:p-5">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-['Plus_Jakarta_Sans'] text-lg font-bold text-[#0b1c30]">Kalender Matrix</h2>
                                    <span x-show="refreshingMatrix" x-cloak class="rounded-full bg-[#eff4ff] px-2.5 py-1 text-[10px] font-bold text-[#0037b0]">Memuat data terbaru…</span>
                                    <button x-show="refreshError && !refreshingMatrix" x-cloak type="button" @click="refreshMatrix()" class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold text-amber-800 hover:bg-amber-200">Coba refresh</button>
                                </div>
                                <p class="mt-0.5 text-xs text-[#565e74]">{{ $periodStart->format('F Y') }} · pilih tanggal untuk melihat rincian.</p>
                            </div>
                            <div class="flex flex-wrap gap-2 text-[10px] font-bold text-[#565e74]">
                                <span class="rounded-full bg-[#eff4ff] px-2.5 py-1 text-[#0037b0]">Terjadwal</span>
                                <span class="rounded-full bg-orange-50 px-2.5 py-1 text-orange-700">Belum diatur</span>
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">Carry-over</span>
                            </div>
                        </div>

                        <div class="roster-calendar-grid mb-2 text-center text-[10px] font-bold uppercase tracking-wide text-[#565e74] sm:text-xs">
                            <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                        </div>

                        <div class="roster-calendar-grid">
                            @for($i = 0; $i < $leadingBlankCount; $i++)
                                <div class="min-h-[76px] rounded-xl border border-dashed border-[#c4c5d7]/70 bg-slate-50/70 sm:min-h-[94px]"></div>
                            @endfor

                            <template x-for="day in calendarDays" :key="day.date">
                                <button
                                    type="button"
                                    @click="selectDate(day.date)"
                                    class="group min-h-[76px] rounded-xl border p-1.5 text-left transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0037b0] sm:min-h-[94px] sm:p-2"
                                    :class="{
                                        'border-[#0037b0] bg-[#dce1ff] ring-2 ring-[#0037b0]/20': selectedDate === day.date,
                                        'border-emerald-300 bg-emerald-50/70': selectedDate !== day.date && day.status === 'carry_over',
                                         'border-orange-200 bg-orange-50/60': selectedDate !== day.date && day.status === 'unassigned',
                                         'border-red-300 bg-red-50/70': selectedDate !== day.date && day.duplicate_count > 1,
                                         'border-red-500 bg-red-50/80 ring-1 ring-red-300': saveErrors[day.date],
                                         'border-[#c4c5d7]/70 bg-white': selectedDate !== day.date && day.status === 'scheduled' && day.duplicate_count === 0
                                    }"
                                >
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-xs font-bold text-[#0b1c30] sm:text-sm" x-text="day.day"></span>
                                        <span x-show="isDirty(day.date)" x-cloak class="h-2 w-2 rounded-full bg-[#0037b0]" title="Perubahan draft"></span>
                                    </div>
                                    <div class="mt-2 space-y-1">
                                        <template x-if="day.duplicate_count > 1">
                                            <span class="block rounded-md bg-red-600 px-1 py-1 text-center text-[9px] font-bold text-white">ANOMALI</span>
                                        </template>
                                        <template x-if="isDirty(day.date) && getDraftShift(day)">
                                            <span class="block truncate rounded-md bg-indigo-600 px-1 py-1 text-center text-[9px] font-bold text-white" x-text="'DRAFT: ' + getDisplayShift(day)"></span>
                                        </template>
                                        <template x-if="!isDirty(day.date) && day.status === 'scheduled' && day.duplicate_count === 0">
                                            <span class="block truncate rounded-md bg-[#0037b0] px-1 py-1 text-center text-[9px] font-bold text-white" x-text="getDisplayShift(day)"></span>
                                        </template>
                                        <template x-if="!isDirty(day.date) && day.status === 'carry_over'">
                                            <span class="block truncate rounded-md bg-emerald-600 px-1 py-1 text-center text-[9px] font-bold text-white">CARRY-OVER</span>
                                        </template>
                                        <template x-if="!isDirty(day.date) && day.status === 'unassigned'">
                                            <span class="block rounded-md bg-orange-100 px-1 py-1 text-center text-[9px] font-bold text-orange-700">BELUM DIATUR</span>
                                        </template>
                                        <span x-show="day.carry_over && day.status === 'scheduled'" x-cloak class="block truncate text-[9px] font-bold text-emerald-700">↳ carry-over</span>
                                    </div>
                                </button>
                            </template>

                            @for($i = 0; $i < $trailingBlankCount; $i++)
                                <div class="min-h-[76px] rounded-xl border border-dashed border-[#c4c5d7]/70 bg-slate-50/70 sm:min-h-[94px]"></div>
                            @endfor
                        </div>
                    </section>

                    <section class="rounded-2xl border-2 border-[#0037b0]/20 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-[#0037b0]">Tanggal terpilih</p>
                                <h2 class="mt-1 font-['Plus_Jakarta_Sans'] text-lg font-bold text-[#0b1c30]" x-text="selectedDay ? formatSelectedDate(selectedDay.date) : 'Belum ada tanggal dipilih'"></h2>
                            </div>
                            <button
                                type="button"
                                @click="showEditor = true"
                                :disabled="!selectedDate"
                                class="min-h-11 rounded-xl bg-[#1d4ed8] px-4 py-3 text-xs font-bold text-white shadow-sm transition hover:bg-[#0037b0] disabled:cursor-not-allowed disabled:opacity-40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0037b0]"
                            >Ubah Dinas Tanggal Terpilih</button>
                        </div>
                    </section>
                </main>

                <aside class="space-y-4">
                    <section class="rounded-2xl border border-[#c4c5d7]/70 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h2 class="font-['Plus_Jakarta_Sans'] text-base font-bold text-[#0b1c30]">Shift tersedia</h2>
                            <span class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">{{ $shiftOptions->count() }} opsi</span>
                        </div>
                        <div class="space-y-2">
                            @forelse($shiftOptions as $shift)
                                <div class="flex items-center justify-between gap-2 rounded-xl bg-[#eff4ff] px-3 py-2.5">
                                    <span class="truncate text-xs font-bold text-[#0b1c30]">{{ $shift['name'] }}</span>
                                    <span class="shrink-0 text-[10px] font-semibold text-[#565e74]">{{ \Carbon\Carbon::parse($shift['start_time'])->format('H:i') }}–{{ \Carbon\Carbon::parse($shift['end_time'])->format('H:i') }}</span>
                                </div>
                            @empty
                                <p class="rounded-xl bg-slate-50 p-3 text-xs text-[#565e74]">Belum ada shift yang tersedia.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-2xl border border-[#c4c5d7]/70 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="font-['Plus_Jakarta_Sans'] text-base font-bold text-[#0b1c30]">Rincian Harian</h2>
                            <span x-show="selectedDay?.status === 'carry_over'" x-cloak class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">Carry-over</span>
                        </div>

                        <template x-if="!selectedDay">
                            <p class="rounded-xl bg-slate-50 p-3 text-xs leading-relaxed text-[#565e74]">Pilih satu tanggal pada Kalender Matrix untuk melihat detail jadwal.</p>
                        </template>
                        <template x-if="selectedDay">
                            <div class="space-y-3">
                                <div class="rounded-xl bg-[#eff4ff] p-3">
                                    <p class="text-[10px] font-bold uppercase tracking-wide text-[#565e74]">Status</p>
                                    <p class="mt-1 text-sm font-bold text-[#0037b0]" x-text="detailStatus(selectedDay)"></p>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="rounded-xl border border-[#c4c5d7]/60 p-3">
                                        <p class="text-[10px] font-bold uppercase text-[#565e74]">Shift</p>
                                        <p class="mt-1 font-bold text-[#0b1c30]" x-text="getDisplayShift(selectedDay)"></p>
                                    </div>
                                    <div class="rounded-xl border border-[#c4c5d7]/60 p-3">
                                        <p class="text-[10px] font-bold uppercase text-[#565e74]">Jam</p>
                                        <p class="mt-1 font-bold text-[#0b1c30]" x-text="displayTime(selectedDay)"></p>
                                    </div>
                                </div>
                                 <p x-show="selectedDay.duplicate_count > 1" class="rounded-xl bg-red-50 p-3 text-xs font-semibold leading-relaxed text-red-700">Tanggal ini memiliki data jadwal ganda. Data tidak dipilih otomatis.</p>
                                 <p x-show="selectedDay.attendance" x-cloak class="rounded-xl bg-slate-50 p-3 text-xs leading-relaxed text-[#565e74]" x-text="attendanceLabel(selectedDay)"></p>
                                 <p x-show="saveErrors[selectedDay.date]" x-cloak class="rounded-xl bg-red-50 p-3 text-xs font-semibold leading-relaxed text-red-700" x-text="saveErrors[selectedDay.date]?.join(' ')"></p>
                            </div>
                        </template>
                    </section>

                    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                        <p class="text-xs font-bold text-amber-800">Penyimpanan roster</p>
                        <p class="mt-1 text-xs leading-relaxed text-amber-700">Simpan seluruh perubahan bulan ini dalam satu proses atomik.</p>
                        <p x-show="saveMessage" x-cloak class="mt-2 rounded-xl bg-white/70 p-3 text-xs font-semibold leading-relaxed" :class="saveStatus === 'success' ? 'text-emerald-700' : 'text-red-700'" x-text="saveMessage"></p>
                        <p x-show="clearBlockedDates.length > 0" x-cloak class="mt-2 rounded-xl bg-white/70 p-3 text-xs font-semibold leading-relaxed text-amber-800">Pengosongan roster yang sudah tersimpan belum didukung. Kembalikan tanggal: <span x-text="clearBlockedDates.join(', ')"></span></p>
                        <button
                            type="button"
                            @click="saveRoster()"
                            :disabled="saveDisabled"
                            :aria-busy="saving.toString()"
                            class="mt-3 min-h-11 w-full rounded-xl px-4 py-3 text-xs font-bold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0037b0]"
                            :class="saveDisabled ? 'cursor-not-allowed bg-slate-300 text-slate-600' : 'bg-[#0037b0] text-white shadow-sm hover:bg-[#002b8a]'"
                        >
                            <span x-show="!saving">Simpan Seluruh Jadwal<span x-show="dirtyDates.length > 0" x-text="` (${dirtyDates.length})`"></span></span>
                            <span x-show="saving" x-cloak>Menyimpan…</span>
                        </button>
                    </section>
                </aside>
            </div>
        </div>

        <div x-show="showEditor" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-3 backdrop-blur-sm sm:p-6" @keydown.escape.window="showEditor = false">
            <div class="mx-auto flex min-h-full max-w-2xl items-center justify-center">
                <section @click.outside="showEditor = false" class="w-full rounded-2xl bg-white p-4 shadow-2xl sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-[#0037b0]">Draft frontend</p>
                            <h2 class="mt-1 font-['Plus_Jakarta_Sans'] text-lg font-bold text-[#0b1c30]">Ubah Dinas Tanggal Terpilih</h2>
                            <p class="mt-1 text-xs text-[#565e74]" x-text="selectedDay ? formatSelectedDate(selectedDay.date) : ''"></p>
                        </div>
                        <button type="button" @click="showEditor = false" aria-label="Tutup modal" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600">✕</button>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <template x-for="shift in shiftOptions" :key="shift.id">
                            <button type="button" @click="applyDraft(shift.id)" class="rounded-xl border p-3 text-left transition hover:border-[#0037b0] hover:bg-[#eff4ff] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0037b0]" :class="String(draftByDate[selectedDate]) === String(shift.id) ? 'border-[#0037b0] bg-[#dce1ff]' : 'border-[#c4c5d7]/70 bg-white'">
                                <span class="block text-sm font-bold text-[#0b1c30]" x-text="shift.name"></span>
                                <span class="mt-1 block text-xs text-[#565e74]" x-text="formatShiftTime(shift)"></span>
                                <span x-show="shift.is_overnight" class="mt-1 inline-block text-[10px] font-bold text-indigo-700">Overnight</span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                        <button type="button" @click="resetSelectedDraft(); showEditor = false" :disabled="!selectedDate" class="min-h-11 rounded-xl border border-[#c4c5d7] px-4 py-3 text-xs font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Kembalikan nilai tersimpan</button>
                        <button type="button" @click="showEditor = false" class="min-h-11 rounded-xl bg-slate-100 px-4 py-3 text-xs font-bold text-slate-700">Tutup</button>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        const initialCalendarDays = @json($calendarDays);
        const initialRosterDraft = @json($initialDraft);
        const rosterShiftOptions = @json($shiftOptions);
        const initialRosterRevision = @js($baseRevision);
        const rosterSaveUrl = @js(route('shift.employee.save', ['user' => $employee->id, 'year' => $year, 'month' => $month]));
        const rosterDataUrl = @js(route('shift.employee.data', ['user' => $employee->id, 'year' => $year, 'month' => $month]));
        const rosterCsrfToken = @js(csrf_token());
        const rosterPeriodUrlTemplate = @js(route('shift.employee', ['user' => $employee->id, 'year' => 2000, 'month' => 1]));

        function monthlyRosterPage() {
            return {
                calendarDays: initialCalendarDays,
                shiftOptions: rosterShiftOptions,
                initialByDate: initialRosterDraft,
                draftByDate: { ...initialRosterDraft },
                baseRevision: initialRosterRevision,
                selectedDate: null,
                showEditor: false,
                beforeUnloadHandler: null,
                saving: false,
                saveMessage: '',
                saveStatus: '',
                saveErrors: {},
                needsReload: false,
                refreshingMatrix: false,
                refreshError: false,

                init() {
                    if (this.beforeUnloadHandler) return;

                    this.beforeUnloadHandler = (event) => {
                        if (this.dirtyDates.length === 0) return;
                        event.preventDefault();
                        event.returnValue = '';
                    };

                    window.addEventListener('beforeunload', this.beforeUnloadHandler);
                },

                destroy() {
                    if (this.beforeUnloadHandler) {
                        window.removeEventListener('beforeunload', this.beforeUnloadHandler);
                    }
                },

                get dirtyDates() {
                    return this.calendarDays
                        .map(day => day.date)
                        .filter(date => String(this.draftByDate[date] ?? '') !== String(this.initialByDate[date] ?? ''));
                },

                get clearBlockedDates() {
                    return this.calendarDays
                        .map(day => day.date)
                        .filter(date => {
                            const draft = this.draftByDate[date];
                            const initial = this.initialByDate[date];
                            return (draft === null || draft === undefined || draft === '')
                                && initial !== null
                                && initial !== undefined
                                && initial !== '';
                        });
                },

                get saveDisabled() {
                    return this.saving || this.dirtyDates.length === 0 || this.needsReload || this.clearBlockedDates.length > 0;
                },

                get selectedDay() {
                    return this.calendarDays.find(day => day.date === this.selectedDate) || null;
                },

                selectDate(date) {
                    this.selectedDate = date;
                    this.showEditor = false;
                },

                applyDraft(shiftId) {
                    if (!this.selectedDate) return;
                    this.draftByDate[this.selectedDate] = shiftId;
                    delete this.saveErrors[this.selectedDate];
                    this.showEditor = false;
                },

                resetSelectedDraft() {
                    if (!this.selectedDate) return;
                    this.draftByDate[this.selectedDate] = this.initialByDate[this.selectedDate] ?? null;
                    delete this.saveErrors[this.selectedDate];
                },

                buildSaveEntries() {
                    return this.calendarDays.map(day => {
                        const currentShiftId = this.draftByDate[day.date];
                        const initialShiftId = this.initialByDate[day.date];
                        return {
                            date: day.date,
                            operation: String(currentShiftId) === String(initialShiftId) ? 'unchanged' : 'upsert',
                            shift_id: currentShiftId,
                        };
                    });
                },

                extractDateErrors(errors) {
                    const byDate = {};
                    Object.entries(errors || {}).forEach(([key, messages]) => {
                        const dateMatch = key.match(/\d{4}-\d{2}-\d{2}/);
                        const entryMatch = key.match(/^entries\.(\d+)/);
                        const date = dateMatch
                            ? dateMatch[0]
                            : (entryMatch && this.calendarDays[Number(entryMatch[1])] ? this.calendarDays[Number(entryMatch[1])].date : null);
                        if (date) {
                            byDate[date] = Array.isArray(messages) ? messages : [String(messages)];
                        }
                    });
                    return byDate;
                },

                updateCalendarAfterSave() {
                    this.calendarDays = this.calendarDays.map(day => {
                        const shift = this.getDraftShift(day);
                        if (!shift) return day;
                        return {
                            ...day,
                            status: 'scheduled',
                            shift_id: shift.id,
                            shift_name: shift.name,
                            start_time: shift.start_time,
                            end_time: shift.end_time,
                            is_overnight: Boolean(shift.is_overnight),
                            carry_over: null,
                            duplicate_count: 0,
                        };
                    });
                },

                async saveRoster() {
                    if (this.saveDisabled) return;

                    this.saving = true;
                    this.saveMessage = '';
                    this.saveStatus = '';
                    this.saveErrors = {};

                    try {
                        const response = await fetch(rosterSaveUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': rosterCsrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                employee_id: {{ $employee->id }},
                                year: {{ $year }},
                                month: {{ $month }},
                                base_revision: this.baseRevision,
                                entries: this.buildSaveEntries(),
                            }),
                        });

                        const body = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            this.saveErrors = this.extractDateErrors(body.errors);
                            this.saveStatus = response.status === 409 ? 'conflict' : 'error';
                            this.needsReload = response.status === 409;
                            this.saveMessage = body.message || 'Roster tidak dapat disimpan. Draft tetap dipertahankan.';
                            return;
                        }

                        this.initialByDate = { ...this.draftByDate };
                        this.baseRevision = body.revision || this.baseRevision;
                        this.updateCalendarAfterSave();
                        this.saveErrors = {};
                        this.needsReload = false;
                        this.saveStatus = 'success';
                        this.saveMessage = 'Seluruh jadwal berhasil disimpan.';
                        await this.refreshMatrix();
                    } catch (error) {
                        this.saveStatus = 'error';
                        this.saveMessage = 'Koneksi gagal. Draft tetap dipertahankan dan belum dianggap tersimpan.';
                    } finally {
                        this.saving = false;
                    }
                },

                async refreshMatrix() {
                    if (this.refreshingMatrix) return;

                    this.refreshingMatrix = true;
                    this.refreshError = false;

                    try {
                        const response = await fetch(rosterDataUrl, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const body = await response.json().catch(() => ({}));
                        if (!response.ok || body.success !== true) {
                            throw new Error(body.message || `HTTP ${response.status}`);
                        }

                        this.calendarDays = body.calendarDays || [];
                        this.initialByDate = { ...(body.initialDraft || {}) };
                        this.draftByDate = { ...(body.initialDraft || {}) };
                        this.baseRevision = body.revision || this.baseRevision;
                        this.saveErrors = {};
                        this.needsReload = false;
                    } catch (error) {
                        this.refreshError = true;
                        this.saveStatus = 'success';
                        this.saveMessage = 'Jadwal sudah tersimpan, tetapi Kalender Matrix gagal diperbarui. Gunakan Coba refresh.';
                    } finally {
                        this.refreshingMatrix = false;
                    }
                },

                isDirty(date) {
                    return String(this.draftByDate[date] ?? '') !== String(this.initialByDate[date] ?? '');
                },

                getDraftShift(day) {
                    if (!day || this.draftByDate[day.date] === null || this.draftByDate[day.date] === undefined) return null;
                    return this.shiftOptions.find(shift => String(shift.id) === String(this.draftByDate[day.date])) || null;
                },

                getDisplayShift(day) {
                    const draftShift = this.getDraftShift(day);
                    if (draftShift) return draftShift.name;
                    if (day.shift_name) return day.shift_name;
                    if (day.carry_over?.shift_name) return day.carry_over.shift_name;
                    return day.status === 'unassigned' ? 'Belum diatur' : '-';
                },

                displayTime(day) {
                    const draftShift = this.getDraftShift(day);
                    const start = draftShift?.start_time || day.start_time || day.carry_over?.start_time;
                    const end = draftShift?.end_time || day.end_time || day.carry_over?.end_time;
                    return start && end ? `${start.slice(0, 5)}–${end.slice(0, 5)}` : '-';
                },

                detailStatus(day) {
                    if (this.saveErrors[day.date]) return 'Perlu diperbaiki';
                    if (this.isDirty(day.date)) return 'Draft berubah';
                    if (day.duplicate_count > 1) return 'Anomali: jadwal ganda';
                    if (day.status === 'carry_over') return 'Carry-over dari hari sebelumnya';
                    if (day.status === 'scheduled') return 'Tersimpan';
                    return 'Belum diatur';
                },

                attendanceLabel(day) {
                    const attendance = day.attendance;
                    return `Absensi historis: masuk ${attendance?.check_in || '-'} · pulang ${attendance?.check_out || '-'}`;
                },

                formatSelectedDate(date) {
                    return new Intl.DateTimeFormat('id-ID', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    }).format(new Date(`${date}T00:00:00`));
                },

                formatShiftTime(shift) {
                    return `${shift.start_time.slice(0, 5)}–${shift.end_time.slice(0, 5)}`;
                },

                changePeriod(value) {
                    if (!/^\d{4}-\d{2}$/.test(value)) return;
                    const [year, month] = value.split('-');
                    window.location.assign(
                        rosterPeriodUrlTemplate.replace('/2000/1', `/${year}/${Number(month)}`)
                    );
                }
            };
        }
    </script>
</x-app-layout>
