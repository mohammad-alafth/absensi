<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f9;
            /* Membuat warna latar belakang luar lebih gelap agar elemen putih menonjol */
        }

        /* HEADER */
        .fc .fc-toolbar-title {
            color: #0f172a;
            font-weight: 800;
        }

        .fc .fc-button-primary {
            background: #1E40AF !important;
            border: none !important;
        }

        .fc .fc-button-primary:hover {
            background: #1d4ed8 !important;
        }

        .fc .fc-button-primary:disabled {
            background: #94a3b8 !important;
        }

        /* GRID */
        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: #e2e8f0;
        }

        /* HARI */
        .fc-col-header-cell {
            background: #f8fafc;
        }

        /* EVENT */
        .fc-event {
            border-radius: 8px !important;
            border: none !important;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 4px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
        }

        /* TODAY */
        .fc .fc-day-today {
            background: #eff6ff !important;
        }

        /* TIME SLOT */
        .fc-timegrid-slot {
            background: #fff;
        }
    </style>

    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-6xl mx-auto">

            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-5 sm:p-7 space-y-5">

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-1 border-b border-gray-100">

                    <div>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-[#1E40AF] text-xs font-bold hover:underline transition">
                            ← Kembali ke Beranda
                        </a>
                        <h1 class="text-xl font-extrabold text-gray-900 tracking-tight mt-1">
                            Pusat Rekap Absensi HRD
                        </h1>
                        <p class="text-[11px] text-gray-400">
                            Monitoring rekapitulasi kehadiran, akumulasi jam kerja, dan kontrol performa kedisiplinan pegawai.
                        </p>

                    </div>


                    <div class="flex flex-col sm:flex-row items-center gap-1 w-full lg:w-auto">

                        <div class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 flex items-center w-full sm:w-52 h-[38px]">
                            <input type="text" id="searchInput" placeholder="Cari nama atau role..."
                                class="border-0 bg-transparent focus:ring-0 text-xs w-full p-0 text-gray-700 placeholder-gray-400">
                        </div>

                        <div class="bg-white border border-slate-200 rounded-xl px-2 flex items-center justify-center w-full sm:w-auto h-[38px]">
                            <form method="GET" class="m-0 p-0">
                                <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()"
                                    class="border-0 rounded-xl text-xs py-1 px-1 focus:ring-0 text-gray-700 font-semibold cursor-pointer">
                            </form>

                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-1 w-full sm:w-auto flex items-center h-[38px]">
                            <form action="{{ route('hrd.export.excel') }}" method="GET" class="flex gap-1 items-center m-0 w-full">
                                <input type="hidden" name="month" value="{{ $month }}">
                                <select name="role"
                                    class="rounded-lg border-gray-200 bg-white text-xs py-1 px-2">

                                    <option value="all">Semua Unit</option>

                                    @foreach($roles as $role)

                                    @php
                                    $baseRole = str_replace('pj_', '', strtolower($role));
                                    @endphp

                                    <option value="{{ $baseRole }}">
                                        {{ $roleLabels[$baseRole] ?? strtoupper(str_replace('_', ' ', $baseRole)) }}
                                    </option>

                                    @endforeach
                                </select>

                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 h-[30px] rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1 shadow-2xs">
                                    <span>📥</span> Export
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2 flex flex-wrap gap-2 mb-2">

                    <a href="{{ route('hrd.reports.attendance.daily') }}"
                        class="flex items-center gap-2 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 transition shadow-2xs">
                        <span>📋</span>
                        Laporan Hadir Hari Ini
                    </a>

                    <a href="{{ route('hrd.reports.absent.daily') }}"
                        class="flex items-center gap-2 bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-300 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 transition shadow-2xs">
                        <span>❌</span>
                        Tidak Hadir
                    </a>

                    <a href="{{ route('hrd.reports.leave') }}"
                        class="flex items-center gap-2 bg-white hover:bg-amber-50 border border-slate-200 hover:border-amber-300 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 transition shadow-2xs">
                        <span>🏖️</span>
                        Rekap Cuti
                    </a>

                    <a href="{{ route('hrd.reports.permission') }}"
                        class="flex items-center gap-2 bg-white hover:bg-cyan-50 border border-slate-200 hover:border-cyan-300 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 transition shadow-2xs">
                        <span>📝</span>
                        Rekap Izin
                    </a>

                    <a href="{{ route('hrd.reports.overtime') }}"
                        class="flex items-center gap-2 bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-300 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 transition shadow-2xs">
                        <span>⏰</span>
                        Rekap Lembur
                    </a>

                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">

                    <div id="openShiftModal"
                        class="bg-gradient-to-br from-blue-50 to-indigo-50/60 border border-blue-100 rounded-2xl p-4 flex items-center justify-between shadow-2xs h-[75px] cursor-pointer hover:scale-[1.02] transition">

                        <div>
                            <p class="text-[11px] text-blue-800 font-semibold tracking-wide">
                                Total Kontrol Pegawai
                            </p>
                            <h2 class="text-xl font-black text-blue-950 mt-0.5">
                                {{ count($recaps) }} Orang
                            </h2>
                        </div>

                        <div class="text-2xl">👥</div>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50/60 border border-emerald-100 rounded-2xl p-4 flex items-center justify-between shadow-2xs h-[75px]">
                        <div>
                            <p class="text-[11px] text-emerald-800 font-semibold tracking-wide">Akumulasi Hadir Bulan Ini</p>
                            <h2 class="text-xl font-black text-emerald-950 mt-0.5">{{ collect($recaps)->sum('hadir') }} Presensi</h2>
                        </div>
                        <div class="text-2xl">✅</div>
                    </div>

                    <div
                        onclick="showLateRanking()"
                        class="bg-gradient-to-br from-amber-50 to-orange-50/60 border border-amber-100 rounded-2xl p-4 flex items-center justify-between shadow-2xs h-[75px] col-span-2 lg:col-span-1 cursor-pointer hover:shadow-md hover:scale-[1.01] transition">

                        <div>
                            <p class="text-[11px] text-amber-800 font-semibold tracking-wide">
                                Total Kasus Keterlambatan
                            </p>

                            <h2 class="text-xl font-black text-amber-950 mt-0.5">
                                {{ collect($recaps)->sum('telat') }} Insiden
                            </h2>
                        </div>

                        <div class="text-2xl">⏰</div>

                    </div>

                </div>

                <div class="bg-slate-50/50 rounded-2xl p-2.5 border border-slate-200/60 flex flex-wrap gap-1.5">

                    <button onclick="filterRole('all', this)"
                        class="role-btn active-role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-gradient-to-r from-indigo-600 to-blue-600 text-white">
                        Semua
                    </button>

                    @foreach($roles as $role)
                    <button
                        onclick="filterRole('{{ strtolower($roleLabels[$role] ?? $role) }}', this)"
                        class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">
                        {{ $roleLabels[$role] ?? strtoupper(str_replace('_', ' ', $role)) }}
                    </button>
                    @endforeach

                </div>

                <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
                <!-- MODAL SHIFT CALENDAR -->
                <div id="shiftModal"
                    class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">

                    <div class="bg-white rounded-3xl w-[98vw] h-[95vh] shadow-xl flex flex-col">

                        <div class="flex items-center justify-between p-5 border-b">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">
                                    Kalender Shift Bulan
                                </h2>
                                <p class="text-xs text-slate-500">
                                    Jadwal seluruh pegawai
                                </p>
                            </div>

                            <button id="closeShiftModal"
                                class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 font-bold">
                                ✕
                            </button>

                            <div class="flex items-center gap-2 mt-3">
                                <label class="text-xs font-semibold text-slate-600">
                                    Filter Unit:
                                </label>

                                <select id="calendarRoleFilter"
                                    class="border border-slate-200 rounded-lg px-3 py-2 text-xs">

                                    <option value="all">Semua Unit</option>

                                    @foreach($roles as $role)
                                    <option value="{{ $role }}">
                                        {{ $roleLabels[$role] ?? ucfirst($role) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex-1 overflow-hidden p-2">
                            <div id="calendar"></div>
                        </div>

                    </div>
                </div>
                <div id="employeeList" class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
                    @foreach($recaps as $index => $recap)
                    <div class="employee-card self-start bg-white border border-slate-200 rounded-2xl p-4 shadow-3xs transition duration-200"
                        data-role="{{ strtolower($recap['employee']->role_label) }}">

                        <div class="flex justify-between items-center cursor-pointer" onclick="toggleDetail({{ $index }})">
                            <div class="flex items-center gap-3 truncate">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-black shadow-inner">
                                    {{ strtoupper(substr($recap['employee']->name, 0, 1)) }}
                                </div>
                                <div>
                                    <h2 class="employee-name text-xs font-bold text-gray-800">
                                        {{ $recap['employee']->name }}
                                    </h2>
                                    <p class="employee-role text-[10px] text-indigo-600 font-bold uppercase tracking-wide">
                                        {{ $recap['employee']->role_label }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    onclick="showShiftModal({{ $recap['employee']->id }}, '{{ $recap['employee']->name }}')"
                                    class="bg-indigo-50 hover:bg-indigo-100
               text-indigo-700
               border border-indigo-200
               px-3 py-1 rounded-lg
               text-[10px] font-bold">

                                    📅 Shift
                                </button>

                                @if($recap['telat'] > 5)
                                <span class="bg-rose-50 text-rose-700 border border-rose-100 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wide">⚠️ Evaluasi</span>
                                @else
                                <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wide">✅ Normal</span>
                                @endif
                                <span class="text-slate-400 text-xs">▼</span>
                            </div>
                        </div>

                        <div id="detail-{{ $index }}" class="hidden mt-4 pt-4 border-t border-slate-100 animate-in fade-in duration-300">
                            <div class="grid grid-cols-4 gap-2">
                                <div class="bg-slate-50 rounded-xl p-2 text-center">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase">Hadir</p>
                                    <h3 class="text-xs font-black text-emerald-600 mt-0.5">{{ $recap['hadir'] }}x</h3>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-2 text-center">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase">Telat</p>
                                    <h3 class="text-xs font-black text-amber-600 mt-0.5">{{ $recap['telat'] }}x</h3>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-2 text-center">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase">Jam Kerja</p>
                                    <h3 class="text-xs font-black text-cyan-600 mt-0.5">{{ $recap['total_jam'] }} J</h3>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-2 text-center">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase">Lembur</p>
                                    <h3 class="text-xs font-black text-indigo-600 mt-0.5">{{ $recap['overtimes'] ?: '0' }}</h3>
                                </div>
                            </div>

                            <div class="mt-4 bg-slate-50 rounded-xl p-3 border border-slate-200">
                                <p class="text-[10px] text-gray-400 font-bold mb-2 uppercase tracking-wide">Update Kuota Cuti</p>
                                <form action="{{ route('hrd.update.leave.quota', $recap['employee']->id) }}" method="POST" class="flex gap-2">
                                    @csrf
                                    <input type="number" name="leave_quota" min="0" value="{{ $recap['employee']->leave_quota ?? 0 }}"
                                        class="flex-1 rounded-lg border-gray-300 text-xs py-1.5 px-3 shadow-sm text-gray-700 font-bold">
                                    <button type="submit" class="px-4 py-1.5 bg-[#1E40AF] text-white font-bold text-xs rounded-lg hover:bg-blue-800 transition">
                                        Simpan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        const cards = document.querySelectorAll('.employee-card');
        const allEvents = @json($calendarEvents);
        document.addEventListener('DOMContentLoaded', function() {

            const calendarEl = document.getElementById('calendar');
            window.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                locale: 'id',
                allDaySlot: false,
                height: '100%',

                slotMinTime: '00:00:00',
                slotMaxTime: '24:00:00',

                expandRows: true,

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },

                events: allEvents,

                eventClick: function(info) {

                    const start = info.event.start;
                    const end = info.event.end;

                    const hari = start.toLocaleDateString('id-ID', {
                        weekday: 'long'
                    });

                    const jamMulai = start.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    const jamSelesai = end.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    Swal.fire({
                        title: info.event.title,
                        html: `
            <div style="text-align:left">
                <b>${info.event.extendedProps.shift}</b><br>
                ${hari}<br>
                Jam ${jamMulai} - ${jamSelesai}
            </div>
        `
                    });
                }
            });

            window.calendar.render();
            document.getElementById('calendarRoleFilter')
                .addEventListener('change', function() {

                    const role = this.value;

                    const filteredEvents = role === 'all' ?
                        allEvents :
                        allEvents.filter(event =>
                            event.extendedProps?.role === role
                        );

                    window.calendar.removeAllEvents();
                    window.calendar.addEventSource(filteredEvents);
                });
        });

        searchInput.addEventListener('keyup', function() {

            const keyword = this.value.toLowerCase();

            cards.forEach(card => {

                const name =
                    card.querySelector('.employee-name')
                    ?.innerText.toLowerCase() ?? '';

                const role =
                    card.querySelector('.employee-role')
                    ?.innerText.toLowerCase() ?? '';

                if (
                    name.includes(keyword) ||
                    role.includes(keyword)
                ) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        // DIVISION FAST BUTTON FILTER SYNC WITH LOWERCASING CONTEXT
        function filterRole(role, button) {
            cards.forEach(card => {
                const employeeRoleLabel = card.dataset.role;

                if (role === 'all' || employeeRoleLabel.includes(role) || employeeRoleLabel.includes('penanggung jawab ' + role)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            // RESET ACTIVE BUTTON TAB STYLE
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('active-role-btn', 'bg-gradient-to-r', 'from-indigo-600', 'to-blue-600', 'text-white', 'shadow-2xs');
                btn.classList.add('bg-white', 'text-slate-600', 'border-slate-200');
            });

            button.classList.remove('bg-white', 'text-slate-600', 'border-slate-200');
            button.classList.add('active-role-btn', 'bg-gradient-to-r', 'from-indigo-600', 'to-blue-600', 'text-white', 'shadow-2xs');
        }

        // ACCORDION DROPDOWN MANAGEMENT SYSTEM
        function toggleDetail(index) {
            const detail = document.getElementById('detail-' + index);
            detail.classList.toggle('hidden');
        }

        async function showShiftModal(userId, employeeName) {
            try {

                const selectedMonth =
                    document.querySelector(
                        'input[name="month"]'
                    ).value;

                const response = await fetch(
                    `/hrd/employee-shifts/${userId}?month=${selectedMonth}`
                );

                const result = await response.json();

                const shifts = result.data;

                let html = `
            <div class="text-left">

                <div class="mb-3">
                    <h3 class="font-bold text-slate-800">
                        ${employeeName}
                    </h3>

                    <p style="
                    font-size:12px;
                    color:#64748b;
                    ">
                    Bulan :
                    ${new Date(result.month + '-01').toLocaleDateString('id-ID', {
                        month: 'long',
                        year: 'numeric'
                    })}
                    </p>
                </div>

                <div style="
                    max-height:400px;
                    overflow:auto;
                ">
        `;

                shifts.forEach(item => {

                    html += `
                <div style="
                    border:1px solid #e5e7eb;
                    border-radius:10px;
                    padding:10px;
                    margin-bottom:8px;
                ">

                    <div>
                        <strong>
                            ${item.shift_date}
                        </strong>
                    </div>

                    <div>
                        ${item.start_time}
                        -
                        ${item.end_time}
                    </div>

                    <div style="
                        color:#64748b;
                        font-size:12px;
                    ">
                        ${item.shift?.name ?? 'Shift'}
                    </div>

                </div>
            `;
                });

                html += `
                </div>
            </div>
        `;

                Swal.fire({
                    title: 'Jadwal Shift',
                    html: html,
                    width: 700,
                    confirmButtonText: 'Tutup'
                });

            } catch (e) {

                Swal.fire(
                    'Gagal',
                    'Tidak dapat memuat jadwal shift',
                    'error'
                );
            }
        }

        function showLateRanking() {

            let html = `
        <div class="space-y-2 text-left">
    `;

            @foreach($rankingTelat as $index => $item)

            html += `
            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                padding:10px;
                border:1px solid #fcd34d;
                border-radius:10px;
                background:#fffaf0;
            ">

                <div>
                    <div style="
                        font-weight:700;
                        color:#92400e;
                    ">
                        #{{ $index + 1 }}
                        {{ $item['employee']->name }}
                    </div>

                    <div style="
                        font-size:12px;
                        color:#78716c;
                    ">
                        {{ $item['employee']->role_label }}
                    </div>
                </div>

                <div style="
                    font-weight:800;
                    color:#b45309;
                ">
                    {{ $item['telat'] }}x
                    ({{ $item['late_formatted'] }})
                </div>

            </div>
        `;

            @endforeach

            html += `</div>`;

            Swal.fire({
                title: '🏆 Top 5 Keterlambatan',
                html: html,
                width: 700,
                confirmButtonText: 'Tutup'
            });
        }

        document.addEventListener('DOMContentLoaded', function() {

            const modal = document.getElementById('shiftModal');
            const openBtn = document.getElementById('openShiftModal');
            const closeBtn = document.getElementById('closeShiftModal');

            openBtn.addEventListener('click', function() {

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                setTimeout(() => {
                    window.calendar.updateSize();
                }, 300);
            });

            closeBtn.addEventListener('click', function() {

                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            modal.addEventListener('click', function(e) {

                if (e.target === modal) {

                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });

        });
    </script>

</x-app-layout>