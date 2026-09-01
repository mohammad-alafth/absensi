<x-app-layout>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9ff;
        }

        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: #e2e8f0;
        }

        .fc-col-header-cell {
            background-color: #1E40AF !important;
            color: white !important;
            padding: 10px 0 !important;
            font-size: 13px !important;
            text-transform: uppercase;
        }

        .fc-col-header-cell a {
            color: white !important;
            text-decoration: none;
        }

        .fc .fc-button-primary {
            background-color: #1E40AF !important;
            border-color: #1E40AF !important;
            text-transform: capitalize !important;
            font-weight: 600 !important;
            font-size: 12px !important;
            border-radius: 12px !important;
        }

        .fc .fc-button-primary:not(:disabled):active,
        .fc .fc-button-primary:not(:disabled).fc-button-active {
            background-color: #152c7a !important;
            border-color: #152c7a !important;
        }

        .fc-daygrid-day-number {
            font-size: 12px;
            font-weight: bold;
            color: #475569;
        }

        .fc-daygrid-day {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .fc-daygrid-day:hover {
            background-color: #f1f5f9 !important;
        }

        .fc-event {
            cursor: pointer;
            padding: 4px 6px;
            font-size: 11px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div x-data="shiftPage()" class="min-h-screen p-3 sm:p-5 pb-24">
        <div class="w-full max-w-[98%] mx-auto space-y-4">

            <!-- Header Navigasi & Tombol Aksi -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-3xl shadow-sm border border-slate-200">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-[#1E40AF] font-bold text-xs hover:underline transition">← Kembali</a>
                    <h1 class="text-lg font-extrabold text-slate-800 tracking-tight">📅 Kelola Jadwal Shift Divisi</h1>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button @click="openWeeklyModal()" class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm hover:opacity-95 transition flex items-center gap-1.5">
                        <span>🗓️</span> Penjadwalan Mingguan (Beda-Beda Shift / Hari)
                    </button>

                    <button @click="showBulkModal = true" class="bg-slate-700 hover:bg-slate-800 text-white px-3 py-2 rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                        <span>⚡</span> Same-Shift Multi-Days
                    </button>
                </div>
            </div>

            <!-- Petunjuk Pengisian -->
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-3xl flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">💡</span>
                    <p class="text-xs text-blue-900 font-semibold">
                        <span class="font-bold">Tips Cepat:</span> Gunakan tombol <span class="font-bold text-indigo-700 underline">"🗓️ Penjadwalan Mingguan"</span> di atas untuk mengatur shift yang berbeda-beda setiap harinya (Tanggal 1 s/d 7) untuk pegawai! Atau <span class="font-bold underline">klik tanggal</span> di kalender bawah.
                    </p>
                </div>
            </div>

            <!-- Kalender Utama -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-4">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Modal Set Shift Single Click -->
        <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm">
            <div @click.away="showAssignModal = false" class="bg-white rounded-3xl p-6 sm:p-8 w-full max-w-md shadow-2xl border border-gray-100 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
                            <span>📌</span> Set Shift Tanggal
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Tanggal: <span class="font-bold text-blue-700" x-text="selectedDate"></span></p>
                    </div>
                    <button type="button" @click="showAssignModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
                </div>

                <form id="singleAssignForm" class="space-y-4">
                    @csrf
                    <input type="hidden" name="shift_date" :value="selectedDate">
                    <input type="hidden" name="user_id" :value="selectedUserId">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Pegawai:</label>
                        <div class="relative mb-2">
                            <input
                                type="text"
                                x-model="searchSingleUser"
                                placeholder="🔍 Cari nama pegawai..."
                                class="w-full text-xs rounded-xl border-slate-300 focus:ring-[#1E40AF] focus:border-[#1E40AF] pl-8">
                            <span class="absolute left-2.5 top-2.5 text-slate-400 text-xs">🔍</span>
                        </div>

                        <div class="max-h-44 overflow-y-auto custom-scrollbar border border-slate-200 rounded-2xl p-2 bg-slate-50 space-y-1">
                            <template x-for="emp in filteredEmployeesSingle" :key="emp.id">
                                <div
                                    @click="selectedUserId = emp.id"
                                    :class="selectedUserId == emp.id ? 'bg-[#1E40AF] text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100'"
                                    class="p-2.5 rounded-xl cursor-pointer text-xs flex items-center justify-between transition-colors">
                                    <span x-text="emp.name"></span>
                                    <span x-show="selectedUserId == emp.id" class="text-xs">✓</span>
                                </div>
                            </template>
                            <div x-show="filteredEmployeesSingle.length === 0" class="p-3 text-center text-xs text-slate-400 italic">
                                Pegawai tidak ditemukan.
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Shift:</label>
                        <select name="shift_id" x-model="selectedShiftId" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                            <option value="">-- Pilih Shift --</option>
                            @foreach($shifts as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="showAssignModal = false" class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">Batal</button>
                        <button type="submit" :disabled="!selectedUserId || !selectedShiftId" class="flex-1 py-3 rounded-xl bg-[#1E40AF] text-white text-xs font-bold hover:bg-blue-800 disabled:opacity-50 transition shadow-sm">Simpan Shift</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Penjadwalan Mingguan (Beda Shift Per-Hari + Search Fix) -->
        <div x-show="showWeeklyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm overflow-y-auto">
            <div @click.away="closeWeeklyModal()" class="bg-white rounded-3xl p-6 sm:p-8 w-full max-w-2xl shadow-2xl border border-gray-100 space-y-5 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="font-extrabold text-lg text-slate-900 flex items-center gap-2">
                            <span>🗓️</span> Penjadwalan Mingguan (Beda Shift Per-Hari)
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Atur shift yang berbeda untuk setiap hari (7 Hari Berturut-turut)</p>
                    </div>
                    <button type="button" @click="closeWeeklyModal()" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
                </div>

                <form id="weeklyAssignForm" class="space-y-5">
                    @csrf

                    <!-- Hidden inputs untuk pegawai terpilih -->
                    <template x-for="id in selectedWeeklyUsers" :key="id">
                        <input type="hidden" name="user_ids[]" :value="id">
                    </template>

                    <!-- Pemilih Pegawai Searchable Dropdown Ringkas -->
                    <div class="relative">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Pegawai:</label>
                            <span class="text-[11px] text-blue-700 font-bold" x-text="selectedWeeklyUsers.length + ' Pegawai Terpilih'"></span>
                        </div>

                        <!-- Badge Pegawai Terpilih -->
                        <div class="flex flex-wrap gap-1.5 mb-2 max-h-24 overflow-y-auto custom-scrollbar" x-show="selectedWeeklyUsers.length > 0">
                            <template x-for="id in selectedWeeklyUsers" :key="id">
                                <span class="bg-blue-100 text-[#1E40AF] border border-blue-200 text-xs px-2.5 py-1 rounded-xl font-bold flex items-center gap-1.5">
                                    <span x-text="getEmpName(id)"></span>
                                    <button type="button" @click.stop="removeWeeklyUser(id)" class="hover:text-rose-600 font-black">✕</button>
                                </span>
                            </template>
                        </div>

                        <!-- Search Input Dropdown Box -->
                        <div class="relative">
                            <input
                                type="text"
                                x-model="searchWeeklyUser"
                                @click="openWeeklyDropdown = true"
                                @input="openWeeklyDropdown = true"
                                placeholder="🔍 Ketik nama pegawai untuk memilih..."
                                class="w-full text-xs rounded-xl border-slate-300 focus:ring-[#1E40AF] focus:border-[#1E40AF] pl-8 py-2.5">
                            <span class="absolute left-2.5 top-3 text-slate-400 text-xs">🔍</span>
                        </div>

                        <!-- Floating Dropdown Result -->
                        <div
                            x-show="openWeeklyDropdown"
                            @click.away="openWeeklyDropdown = false"
                            class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-52 overflow-y-auto custom-scrollbar p-2 space-y-1">
                            <div class="flex items-center justify-between px-2 py-1 border-b border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Klik nama untuk memilih/batal</span>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click.stop="selectAllWeekly(true)" class="text-[10px] font-bold text-blue-700 hover:underline">Pilih Semua</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" @click.stop="selectAllWeekly(false)" class="text-[10px] font-bold text-rose-600 hover:underline">Reset</button>
                                </div>
                            </div>

                            <template x-for="emp in filteredEmployeesWeekly" :key="emp.id">
                                <div
                                    @click.stop="toggleWeeklyUser(emp.id)"
                                    :class="isWeeklyUserSelected(emp.id) ? 'bg-blue-50 text-[#1E40AF] font-bold' : 'text-slate-700 hover:bg-slate-50'"
                                    class="p-2.5 rounded-xl cursor-pointer text-xs flex items-center justify-between transition-colors">
                                    <span x-text="emp.name"></span>
                                    <span x-show="isWeeklyUserSelected(emp.id)" class="text-blue-700 font-bold">✓</span>
                                </div>
                            </template>

                            <div x-show="filteredEmployeesWeekly.length === 0" class="p-3 text-center text-xs text-slate-400 italic">
                                Pegawai tidak ditemukan.
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Mulai (Hari Ke-1):</label>
                        <input type="date" x-model="weeklyStartDate" @change="generateWeeklyDays()" required class="w-full text-xs rounded-xl border-slate-300 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                    </div>

                    <!-- Matrix 7 Hari -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Atur Shift Per-Hari:</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                            <template x-for="(day, index) in weeklyDays" :key="index">
                                <div class="bg-white p-2.5 rounded-xl border border-slate-200 flex flex-col gap-1 shadow-xs">
                                    <span class="text-[11px] font-bold text-blue-900" x-text="day.label"></span>
                                    <select :name="'schedule[' + day.date + ']'" class="w-full text-xs rounded-lg border-slate-300 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                                        <option value="">-- Libur / Kosong --</option>
                                        @foreach($shifts as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="closeWeeklyModal()" class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">Batal</button>
                        <button type="submit" :disabled="selectedWeeklyUsers.length === 0" class="flex-1 py-3 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 disabled:opacity-50 transition shadow-sm">Simpan Shift Mingguan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Bulk Assign (Same Shift Multi Days) -->
        <div x-show="showBulkModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm">
            <div @click.away="showBulkModal = false" class="bg-white rounded-3xl p-6 sm:p-8 w-full max-w-lg shadow-2xl border border-gray-100 space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="font-extrabold text-lg text-slate-900 flex items-center gap-2">
                        <span>⚡</span> Input Masal (Satu Shift Untuk Beberapa Hari)
                    </h3>
                    <button type="button" @click="showBulkModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
                </div>

                <form id="bulkAssignForm" class="space-y-4">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Pilih Pegawai:</label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectAllBulk(true)" class="text-[10px] font-bold text-blue-700 hover:underline">Pilih Semua</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" @click="selectAllBulk(false)" class="text-[10px] font-bold text-rose-600 hover:underline">Reset</button>
                            </div>
                        </div>

                        <input
                            type="text"
                            x-model="searchBulkUser"
                            placeholder="🔍 Cari nama pegawai..."
                            class="w-full text-xs rounded-xl border-slate-300 focus:ring-[#1E40AF] focus:border-[#1E40AF] mb-2">

                        <div class="max-h-40 overflow-y-auto custom-scrollbar border border-slate-200 rounded-2xl p-2.5 space-y-1 bg-slate-50">
                            <template x-for="emp in filteredEmployeesBulk" :key="emp.id">
                                <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-700 cursor-pointer hover:bg-white p-2 rounded-xl transition-colors border border-transparent hover:border-slate-200">
                                    <input type="checkbox" name="user_ids[]" :value="emp.id" x-model="selectedBulkUsers" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    <span x-text="emp.name"></span>
                                </label>
                            </template>
                            <div x-show="filteredEmployeesBulk.length === 0" class="p-3 text-center text-xs text-slate-400 italic">
                                Pegawai tidak ditemukan.
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Dari Tanggal:</label>
                            <input type="date" name="start_date" required value="{{ now()->format('Y-m-d') }}" class="w-full text-xs rounded-xl border-slate-300">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Sampai Tanggal:</label>
                            <input type="date" name="end_date" required value="{{ now()->addDays(6)->format('Y-m-d') }}" class="w-full text-xs rounded-xl border-slate-300">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Shift:</label>
                        <select name="shift_id" required class="w-full text-xs rounded-xl border-slate-300">
                            <option value="">-- Pilih Shift --</option>
                            @foreach($shifts as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="showBulkModal = false" class="flex-1 py-3 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition">Batal</button>
                        <button type="submit" :disabled="selectedBulkUsers.length === 0" class="flex-1 py-3 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 disabled:opacity-50 transition shadow-sm">Terapkan Shift</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const employeesData = @json($employees);

        function shiftPage() {
            return {
                showBulkModal: false,
                showAssignModal: false,
                showWeeklyModal: false,
                openWeeklyDropdown: false,

                selectedDate: '',
                selectedUserId: '',
                selectedShiftId: '',

                searchSingleUser: '',
                searchWeeklyUser: '',
                searchBulkUser: '',

                selectedWeeklyUsers: [],
                selectedBulkUsers: [],

                weeklyStartDate: new Date().toISOString().split('T')[0],
                weeklyDays: [],

                get filteredEmployeesSingle() {
                    if (!this.searchSingleUser) return employeesData;
                    return employeesData.filter(e => e.name.toLowerCase().includes(this.searchSingleUser.toLowerCase()));
                },

                get filteredEmployeesWeekly() {
                    if (!this.searchWeeklyUser) return employeesData;
                    return employeesData.filter(e => e.name.toLowerCase().includes(this.searchWeeklyUser.toLowerCase()));
                },

                get filteredEmployeesBulk() {
                    if (!this.searchBulkUser) return employeesData;
                    return employeesData.filter(e => e.name.toLowerCase().includes(this.searchBulkUser.toLowerCase()));
                },

                getEmpName(id) {
                    const emp = employeesData.find(e => e.id == id);
                    return emp ? emp.name : 'User ' + id;
                },

                isWeeklyUserSelected(id) {
                    return this.selectedWeeklyUsers.some(i => i == id);
                },

                toggleWeeklyUser(id) {
                    if (this.isWeeklyUserSelected(id)) {
                        this.selectedWeeklyUsers = this.selectedWeeklyUsers.filter(i => i != id);
                    } else {
                        this.selectedWeeklyUsers.push(id);
                    }
                },

                removeWeeklyUser(id) {
                    this.selectedWeeklyUsers = this.selectedWeeklyUsers.filter(i => i != id);
                },

                selectAllWeekly(status) {
                    if (status) {
                        this.selectedWeeklyUsers = employeesData.map(e => e.id);
                    } else {
                        this.selectedWeeklyUsers = [];
                    }
                },

                selectAllBulk(status) {
                    if (status) {
                        this.selectedBulkUsers = employeesData.map(e => e.id);
                    } else {
                        this.selectedBulkUsers = [];
                    }
                },

                openWeeklyModal() {
                    this.generateWeeklyDays();
                    this.showWeeklyModal = true;
                    this.openWeeklyDropdown = false;
                    this.searchWeeklyUser = '';
                },

                closeWeeklyModal() {
                    this.showWeeklyModal = false;
                    this.openWeeklyDropdown = false;
                },

                generateWeeklyDays() {
                    if (!this.weeklyStartDate) return;
                    const days = [];
                    const start = new Date(this.weeklyStartDate);
                    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

                    for (let i = 0; i < 7; i++) {
                        const curr = new Date(start);
                        curr.setDate(start.getDate() + i);
                        const dateStr = curr.toISOString().split('T')[0];
                        const dayName = dayNames[curr.getDay()];
                        const formattedDate = curr.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

                        days.push({
                            date: dateStr,
                            label: `${dayName}, ${formattedDate}`
                        });
                    }
                    this.weeklyDays = days;
                }
            };
        }

        const csrfToken = "{{ csrf_token() }}";
        const assignUrl = "{{ route('shift.assign') }}";
        const bulkAssignUrl = "{{ route('shift.bulk-assign') }}";
        const weeklyAssignUrl = "{{ route('shift.weekly-assign') }}";
        const eventsUrl = "{{ route('shift.calendar') }}";

        document.addEventListener('DOMContentLoaded', function() {

            const calendarEl = document.getElementById('calendar');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                editable: false,
                selectable: true,
                events: eventsUrl,

                dateClick: function(info) {
                    const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                    if (alpineData) {
                        alpineData.selectedDate = info.dateStr;
                        alpineData.selectedUserId = '';
                        alpineData.selectedShiftId = '';
                        alpineData.searchSingleUser = '';
                        alpineData.showAssignModal = true;
                    }
                },

                eventClick: function(info) {
                    const event = info.event;
                    const alpineData = Alpine.$data(document.querySelector('[x-data]'));

                    Swal.fire({
                        title: 'Kelola Shift Pegawai',
                        html: `<p class="text-sm font-bold text-slate-700 mb-1">${event.title}</p>
                               <p class="text-xs text-slate-500">Tanggal: ${event.startStr}</p>`,
                        icon: 'question',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: '✏️ Ubah Shift',
                        denyButtonText: '🗑️ Hapus Shift',
                        cancelButtonText: 'Tutup',
                        confirmButtonColor: '#1E40AF',
                        denyButtonColor: '#ef4444'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (alpineData) {
                                alpineData.selectedDate = event.startStr;
                                alpineData.selectedUserId = event.extendedProps.user_id;
                                alpineData.selectedShiftId = '';
                                alpineData.searchSingleUser = '';
                                alpineData.showAssignModal = true;
                            }
                        } else if (result.isDenied) {
                            deleteShift(event.extendedProps.user_id, event.startStr, event);
                        }
                    });
                }
            });

            calendar.render();

            function deleteShift(userId, dateStr, eventObj) {
                fetch(assignUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        shift_date: dateStr,
                        is_delete: true
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        eventObj.remove();
                        Swal.fire({ icon: 'success', title: 'Shift Dihapus', timer: 1200, showConfirmButton: false });
                    }
                });
            }

            // Single Assign Form
            const singleForm = document.getElementById('singleAssignForm');
            if (singleForm) {
                singleForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    fetch(assignUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: new FormData(this)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            calendar.refetchEvents();
                            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                            if (alpineData) alpineData.showAssignModal = false;
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Jadwal shift berhasil disimpan.', timer: 1200, showConfirmButton: false });
                        } else {
                            Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    });
                });
            }

            // Weekly Assign Form
            const weeklyForm = document.getElementById('weeklyAssignForm');
            if (weeklyForm) {
                weeklyForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    fetch(weeklyAssignUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: new FormData(this)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            calendar.refetchEvents();
                            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                            if (alpineData) {
                                alpineData.showWeeklyModal = false;
                                alpineData.openWeeklyDropdown = false;
                            }
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message });
                        } else {
                            Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    });
                });
            }

            // Bulk Assign Form
            const bulkForm = document.getElementById('bulkAssignForm');
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    fetch(bulkAssignUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: new FormData(this)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            calendar.refetchEvents();
                            const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                            if (alpineData) alpineData.showBulkModal = false;
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message });
                        } else {
                            Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                        }
                    });
                });
            }

        });
    </script>
</x-app-layout>
