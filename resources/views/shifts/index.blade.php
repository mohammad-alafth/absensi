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

            <!-- Header Navigasi & Tombol Aksi Utama -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-3xl shadow-sm border border-slate-200">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-[#1E40AF] font-bold text-xs hover:underline transition">← Kembali</a>
                    <h1 class="text-lg font-extrabold text-slate-800 tracking-tight">📅 Kelola Jadwal Shift Divisi</h1>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button @click="openWeeklyModal()" class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white px-4 py-2.5 rounded-xl text-xs font-bold shadow-md hover:opacity-95 transition flex items-center gap-2">
                        <span class="text-sm">🗓️</span> Penjadwalan Mingguan (Beda-Beda Shift / Hari)
                    </button>

                    <!-- <button @click="showBulkModal = true" class="bg-slate-700 hover:bg-slate-800 text-white px-3.5 py-2.5 rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                        <span>⚡</span> Same-Shift Multi-Days
                    </button> -->
                </div>
            </div>

            <!-- Petunjuk Pengisian -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 p-4 rounded-3xl flex items-center justify-between gap-3 shadow-2xs">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">💡</span>
                    <p class="text-xs text-blue-900 font-semibold leading-relaxed">
                        <span class="font-bold text-indigo-800">Petunjuk Penjadwalan:</span> Klik tanggal mana saja di kalender atau tombol <span class="font-bold text-indigo-700 underline cursor-pointer" @click="openWeeklyModal()">"🗓️ Penjadwalan Mingguan"</span> untuk langsung mengisi shift 7 hari berturut-turut untuk pegawai!
                    </p>
                </div>
            </div>

            <!-- Kalender Utama -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-4">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Modal Penjadwalan Mingguan (Beda Shift Per-Hari) -->
        <div
            x-show="showWeeklyModal"
            x-cloak
            class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm
           overflow-y-auto p-2 sm:p-4">
            <div
                @click.away="closeWeeklyModal()"
                class="bg-white rounded-2xl sm:rounded-3xl max-h-[90vh] overflow-y-auto
               w-full max-w-2xl
               h-[calc(100vh-16px)] sm:h-auto
               sm:max-h-[calc(100vh-32px)]
               shadow-2xl border border-gray-100
               flex flex-col overflow-hidden">

                <!-- HEADER -->
                <div class="shrink-0 px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-100">
                    <div class="flex items-center justify-between gap-3">

                        <div class="min-w-0">
                            <h3 class="font-extrabold text-base sm:text-lg text-slate-900 flex items-center gap-2">
                                <span>🗓️</span>
                                <span>Penjadwalan Mingguan</span>
                            </h3>

                            <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5 truncate">
                                Atur shift berbeda untuk setiap hari selama 7 hari
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeWeeklyModal()"
                            class="shrink-0 w-8 h-8 rounded-full bg-slate-100
                           hover:bg-slate-200 text-slate-500 font-bold
                           flex items-center justify-center transition">
                            ✕
                        </button>

                    </div>
                </div>


                <!-- FORM -->
                <form
                    id="weeklyAssignForm"
                    class="flex flex-col flex-1 min-h-0">

                    @csrf

                    <!-- Hidden users -->
                    <template x-for="id in selectedWeeklyUsers" :key="id">
                        <input
                            type="hidden"
                            name="user_ids[]"
                            :value="id">
                    </template>


                    <!-- CONTENT -->
                    <div
                        class="flex-1 min-h-0 overflow-y-auto
                       px-4 py-3 sm:px-6 sm:py-4
                       custom-scrollbar">

                        <div class="space-y-3">


                            <!-- ================================= -->
                            <!-- PILIH PEGAWAI -->
                            <!-- ================================= -->
                            <div class="relative">

                                <div class="flex items-center justify-between mb-1.5">

                                    <label class="text-[10px] sm:text-xs font-bold
                                          text-slate-700 uppercase tracking-wider">
                                        Pilih Pegawai Divisi:
                                    </label>

                                    <span
                                        class="text-[9px] sm:text-[11px]
                                       text-blue-700 font-bold bg-blue-50
                                       px-2 py-0.5 rounded-lg border border-blue-100"
                                        x-text="selectedWeeklyUsers.length + ' Pegawai Terpilih'"></span>

                                </div>


                                <!-- Selected employees -->
                                <div
                                    class="flex flex-wrap gap-1 mb-1.5
                                   max-h-14 overflow-y-auto custom-scrollbar"
                                    x-show="selectedWeeklyUsers.length > 0">

                                    <template
                                        x-for="id in selectedWeeklyUsers"
                                        :key="id">

                                        <span
                                            class="bg-blue-100 text-[#1E40AF]
                                           border border-blue-200
                                           text-[10px] sm:text-xs
                                           px-2 py-0.5 rounded-lg
                                           font-bold flex items-center gap-1">

                                            <span x-text="getEmpName(id)"></span>

                                            <button
                                                type="button"
                                                @click.stop="removeWeeklyUser(id)"
                                                class="hover:text-rose-600 font-black">
                                                ✕
                                            </button>

                                        </span>

                                    </template>

                                </div>


                                <!-- Search -->
                                <div class="relative">

                                    <input
                                        type="text"
                                        x-model="searchWeeklyUser"
                                        @click="openWeeklyDropdown = true"
                                        @input="openWeeklyDropdown = true"
                                        placeholder="🔍 Ketik nama pegawai untuk mencari..."
                                        class="w-full text-[11px] sm:text-xs
                                       rounded-xl border-slate-300
                                       focus:ring-[#1E40AF]
                                       focus:border-[#1E40AF]
                                       pl-8 py-2">

                                    <span
                                        class="absolute left-2.5 top-2.5
                                       text-slate-400 text-xs">
                                        🔍
                                    </span>

                                </div>


                                <!-- Dropdown -->
                                <div
                                    x-show="openWeeklyDropdown"
                                    @click.away="openWeeklyDropdown = false"
                                    class="absolute z-40 w-full mt-1
                                   bg-white border border-slate-200
                                   rounded-xl shadow-xl
                                   max-h-40 overflow-y-auto
                                   custom-scrollbar p-1.5">

                                    <div
                                        class="flex items-center justify-between
                                       px-2 py-1 border-b border-slate-100
                                       bg-slate-50 rounded-lg mb-1">

                                        <span class="text-[9px] font-bold text-slate-400">
                                            PILIH PEGAWAI
                                        </span>

                                        <div class="flex items-center gap-2">

                                            <button
                                                type="button"
                                                @click.stop="selectAllWeekly(true)"
                                                class="text-[9px] font-bold text-blue-700">
                                                Pilih Semua
                                            </button>

                                            <span class="text-slate-300">|</span>

                                            <button
                                                type="button"
                                                @click.stop="selectAllWeekly(false)"
                                                class="text-[9px] font-bold text-rose-600">
                                                Reset
                                            </button>

                                        </div>

                                    </div>


                                    <template
                                        x-for="emp in filteredEmployeesWeekly"
                                        :key="emp.id">

                                        <div
                                            @click.stop="toggleWeeklyUser(emp.id)"
                                            :class="
                                        isWeeklyUserSelected(emp.id)
                                        ? 'bg-blue-50 text-[#1E40AF] font-bold border-blue-200'
                                        : 'text-slate-700 hover:bg-slate-50 border-transparent'
                                    "
                                            class="p-2 rounded-lg cursor-pointer
                                           text-[10px] sm:text-xs
                                           flex items-center justify-between
                                           border">

                                            <span x-text="emp.name"></span>

                                            <span
                                                x-show="isWeeklyUserSelected(emp.id)"
                                                class="text-blue-700 font-black">
                                                ✓
                                            </span>

                                        </div>

                                    </template>

                                </div>

                            </div>


                            <!-- ================================= -->
                            <!-- TANGGAL -->
                            <!-- ================================= -->
                            <div>

                                <label
                                    class="block text-[10px] sm:text-xs
                                   font-bold text-slate-700
                                   uppercase tracking-wider mb-1">
                                    Tanggal Mulai (Hari Ke-1):
                                </label>

                                <input
                                    type="date"
                                    x-model="weeklyStartDate"
                                    @change="generateWeeklyDays()"
                                    required
                                    class="w-full text-xs rounded-xl
                                   border-slate-300
                                   focus:ring-[#1E40AF]
                                   focus:border-[#1E40AF]
                                   py-2">

                            </div>


                            <!-- ================================= -->
                            <!-- SHIFT 7 HARI -->
                            <!-- ================================= -->
                            <div>

                                <div class="flex items-center justify-between mb-1.5">

                                    <label
                                        class="text-[10px] sm:text-xs
                                       font-bold text-slate-700
                                       uppercase tracking-wider">
                                        Atur Shift Per-Hari (7 Hari):
                                    </label>

                                    <div class="flex items-center gap-2">
                                        <span
                                            x-show="loadingWeeklySchedules"
                                            class="text-[9px] text-indigo-600 font-bold">
                                            Memuat jadwal...
                                        </span>

                                        <span
                                            class="hidden sm:block
                                           text-[9px] text-slate-400 font-bold uppercase">
                                            Pilih shift
                                        </span>
                                    </div>

                                </div>


                                <!-- 2 KOLOM SEMUA DEVICE -->
                                <div
                                    class="grid grid-cols-2
                                   gap-1.5 sm:gap-2
                                   bg-slate-50
                                   p-2 sm:p-3
                                   rounded-xl sm:rounded-2xl
                                   border border-slate-200">

                                    <template
                                        x-for="(day, index) in weeklyDays"
                                        :key="day.date">

                                        <div
                                            class="bg-white
                                           px-2 py-2 sm:p-3
                                           rounded-lg sm:rounded-xl
                                           border border-slate-200
                                           flex flex-col gap-1
                                           shadow-2xs">

                                            <!-- DAY HEADER -->
                                            <div
                                                class="flex items-center
                                               justify-between gap-1">

                                                <span
                                                    class="text-[9px] sm:text-[11px]
                                                   font-extrabold
                                                   text-blue-900 truncate"
                                                    x-text="day.label"></span>

                                                <span
                                                    class="shrink-0
                                                   text-[7px] sm:text-[9px]
                                                   font-bold text-indigo-600
                                                   bg-indigo-50
                                                   px-1 py-0.5
                                                   rounded border border-indigo-100"
                                                    x-text="'#' + (index + 1)"></span>

                                            </div>


                                            <!-- SHIFT -->
                                            <select
                                                :name="'schedule[' + day.date + ']'"
                                                x-model="weeklySchedules[day.date]"
                                                :disabled="loadingWeeklySchedules"
                                                class="w-full text-[9px] sm:text-xs
                                               rounded-lg
                                               border-slate-300
                                               focus:ring-[#1E40AF]
                                               focus:border-[#1E40AF]
                                               py-1.5 sm:py-2">

                                                <option value="">
                                                    -- Libur / Kosong --
                                                </option>

                                                @foreach($shifts as $s)

                                                <option value="{{ $s->id }}">
                                                    {{ $s->name }}
                                                    ({{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}
                                                    -
                                                    {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }})
                                                </option>

                                                @endforeach

                                            </select>

                                        </div>

                                    </template>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ================================= -->
                    <!-- FOOTER -->
                    <!-- ================================= -->
                    <div
                        class="shrink-0
                       flex gap-2 sm:gap-3
                       px-4 py-3 sm:px-6 sm:py-3
                       border-t border-slate-100
                       bg-white">

                        <button
                            type="button"
                            @click="closeWeeklyModal()"
                            class="flex-1 py-2.5 sm:py-3
                           rounded-xl bg-slate-100
                           text-slate-600
                           text-[10px] sm:text-xs
                           font-bold hover:bg-slate-200 transition">
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="selectedWeeklyUsers.length === 0"
                            class="flex-1 py-2.5 sm:py-3
                           rounded-xl bg-indigo-600
                           text-white
                           text-[10px] sm:text-xs
                           font-bold hover:bg-indigo-700
                           disabled:opacity-50 transition shadow-md">
                            Simpan Shift Mingguan
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

    </div>

    <script>
        const employeesData = @json($employees);

        function shiftPage() {
            return {
                showBulkModal: false,
                showWeeklyModal: false,
                openWeeklyDropdown: false,

                searchWeeklyUser: '',
                searchBulkUser: '',

                selectedWeeklyUsers: [],
                selectedBulkUsers: [],

                weeklyStartDate: new Date().toLocaleDateString('en-CA'),
                weeklyDays: [],
                weeklySchedules: {},
                loadingWeeklySchedules: false,

                get filteredEmployeesWeekly() {
                    if (!this.searchWeeklyUser) return employeesData;

                    return employeesData.filter(e =>
                        e.name.toLowerCase().includes(
                            this.searchWeeklyUser.toLowerCase()
                        )
                    );
                },

                get filteredEmployeesBulk() {
                    if (!this.searchBulkUser) return employeesData;

                    return employeesData.filter(e =>
                        e.name.toLowerCase().includes(
                            this.searchBulkUser.toLowerCase()
                        )
                    );
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
                        this.selectedWeeklyUsers =
                            this.selectedWeeklyUsers.filter(i => i != id);
                    } else {
                        this.selectedWeeklyUsers.push(id);
                    }

                    // User berubah → ambil ulang jadwal database.
                    this.loadWeeklySchedules();
                },

                removeWeeklyUser(id) {
                    this.selectedWeeklyUsers =
                        this.selectedWeeklyUsers.filter(i => i != id);

                    // User berubah → ambil ulang jadwal database.
                    this.loadWeeklySchedules();
                },

                selectAllWeekly(status) {
                    if (status) {
                        this.selectedWeeklyUsers = employeesData.map(e => e.id);
                    } else {
                        this.selectedWeeklyUsers = [];
                    }

                    // User berubah → ambil ulang jadwal database.
                    this.loadWeeklySchedules();
                },

                selectAllBulk(status) {
                    if (status) {
                        this.selectedBulkUsers = employeesData.map(e => e.id);
                    } else {
                        this.selectedBulkUsers = [];
                    }
                },

                openWeeklyModal(startDateStr = null) {
                    if (startDateStr) {
                        this.weeklyStartDate = startDateStr;
                    } else if (!this.weeklyStartDate) {
                        this.weeklyStartDate =
                            new Date().toLocaleDateString('en-CA');
                    }

                    // Reset state setiap kali modal dibuka.
                    this.weeklySchedules = {};

                    this.generateWeeklyDays();

                    this.showWeeklyModal = true;
                    this.openWeeklyDropdown = false;
                    this.searchWeeklyUser = '';
                },

                closeWeeklyModal() {
                    this.showWeeklyModal = false;
                    this.openWeeklyDropdown = false;

                    // Jangan membawa state minggu sebelumnya
                    // ketika modal dibuka lagi.
                    this.weeklySchedules = {};
                    this.weeklyDays = [];
                },

                async generateWeeklyDays() {
                    if (!this.weeklyStartDate) {
                        this.weeklyDays = [];
                        this.weeklySchedules = {};
                        return;
                    }

                    const days = [];

                    // Gunakan local date agar tidak bergeser satu hari
                    // karena timezone UTC dari toISOString().
                    const parts = this.weeklyStartDate.split('-').map(Number);
                    const start = new Date(
                        parts[0],
                        parts[1] - 1,
                        parts[2]
                    );

                    const dayNames = [
                        'Minggu',
                        'Senin',
                        'Selasa',
                        'Rabu',
                        'Kamis',
                        'Jumat',
                        'Sabtu'
                    ];

                    for (let i = 0; i < 7; i++) {
                        const curr = new Date(start);
                        curr.setDate(start.getDate() + i);

                        const yyyy = curr.getFullYear();
                        const mm = String(curr.getMonth() + 1).padStart(2, '0');
                        const dd = String(curr.getDate()).padStart(2, '0');

                        const dateStr = `${yyyy}-${mm}-${dd}`;

                        const formattedDate =
                            curr.toLocaleDateString('id-ID', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            });

                        days.push({
                            date: dateStr,
                            label: `${dayNames[curr.getDay()]}, ${formattedDate}`
                        });
                    }

                    this.weeklyDays = days;

                    // PENTING:
                    // Tanggal berubah → state lama dibuang terlebih dahulu.
                    this.weeklySchedules = {};

                    // Kemudian ambil jadwal yang benar dari database.
                    await this.loadWeeklySchedules();
                },

                async loadWeeklySchedules() {
                    // Tidak ada user → tidak ada jadwal yang perlu dimuat.
                    if (
                        !this.weeklyStartDate ||
                        this.selectedWeeklyUsers.length === 0
                    ) {
                        this.weeklySchedules = {};
                        return;
                    }

                    this.loadingWeeklySchedules = true;

                    // Buang data minggu/user sebelumnya SEBELUM request.
                    this.weeklySchedules = {};

                    try {
                        const params = new URLSearchParams();

                        this.selectedWeeklyUsers.forEach(id => {
                            params.append('user_ids[]', id);
                        });

                        params.append('start_date', this.weeklyStartDate);

                        const response = await fetch(
                            weeklySchedulesUrl + '?' + params.toString(),
                            {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }
                        );

                        if (!response.ok) {
                            throw new Error(
                                `HTTP ${response.status}`
                            );
                        }

                        const data = await response.json();

                        /*
                         * Endpoint mengembalikan:
                         *
                         * {
                         *   success: true,
                         *   schedules: {
                         *      "2026-09-01": "1",
                         *      "2026-09-02": "2"
                         *   }
                         * }
                         *
                         * Jika backend mengembalikan nested berdasarkan user:
                         *
                         * {
                         *   schedules: {
                         *      "1": {
                         *          "2026-09-01": "1"
                         *      }
                         *   }
                         * }
                         *
                         * Untuk modal multi-user, backend idealnya hanya
                         * mengembalikan jadwal apabila seluruh user yang
                         * dipilih memiliki shift yang sama pada tanggal itu.
                         */

                        this.weeklySchedules = data.schedules || {};

                    } catch (error) {
                        console.error(
                            'Gagal mengambil jadwal mingguan:',
                            error
                        );

                        this.weeklySchedules = {};

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat Jadwal',
                            text: 'Jadwal yang sudah tersimpan tidak dapat dimuat.'
                        });

                    } finally {
                        this.loadingWeeklySchedules = false;
                    }
                }
            };
        }

        const csrfToken = "{{ csrf_token() }}";
        const assignUrl = "{{ route('shift.assign') }}";
        const bulkAssignUrl = "{{ route('shift.bulk-assign') }}";
        const weeklyAssignUrl = "{{ route('shift.weekly-assign') }}";
        const weeklySchedulesUrl = "{{ route('shift.weekly-schedules') }}";
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
                    // Klik tanggal di kalender langsung membuka Penjadwalan Mingguan beda-shift
                    const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                    if (alpineData) {
                        alpineData.openWeeklyModal(info.dateStr);
                    }
                },

                eventClick: function(info) {
                    const event = info.event;

                    Swal.fire({
                        title: 'Hapus Shift Pegawai',
                        html: `<p class="text-sm font-bold text-slate-700 mb-1">${event.title}</p>
                               <p class="text-xs text-slate-500">Tanggal: ${event.startStr}</p>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '🗑️ Ya, Hapus Shift',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b'
                    }).then((result) => {
                        if (result.isConfirmed) {
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
                            Swal.fire({
                                icon: 'success',
                                title: 'Shift Dihapus',
                                timer: 1200,
                                showConfirmButton: false
                            });
                        }
                    });
            }

            // Weekly Assign Form
            const weeklyForm = document.getElementById('weeklyAssignForm');
            if (weeklyForm) {
                weeklyForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    fetch(weeklyAssignUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
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
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: data.message
                                });
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
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: new FormData(this)
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                calendar.refetchEvents();
                                const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                                if (alpineData) alpineData.showBulkModal = false;
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: data.message
                                });
                            } else {
                                Swal.fire('Gagal', data.message || 'Terjadi kesalahan.', 'error');
                            }
                        });
                });
            }

        });
    </script>
</x-app-layout>