<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f9;
            /* Membuat warna latar belakang luar lebih gelap agar elemen putih menonjol */
        }
    </style>

    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-6xl mx-auto">

            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-5 sm:p-7 space-y-5">

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-5 border-b border-gray-100">

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


                    <div class="flex flex-col sm:flex-row items-center gap-2 w-full lg:w-auto">

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
                                <select name="role" class="rounded-lg border-gray-200 bg-white text-xs py-1 px-2 focus:border-indigo-500 focus:ring-0 text-gray-600 font-medium h-[30px]">
                                    <option value="all">Semua Unit</option>
                                    <option value="nurse">Nurse / Perawat</option>
                                    <option value="security">Security</option>
                                    <option value="cs">Customer Service</option>
                                    <option value="administrasi">Administrasi</option>
                                    <option value="ro">Reverse Osmosis</option>
                                    <option value="kasir">Kasir</option>
                                    <option value="finance">Finance / Keuangan</option>
                                    <option value="pharmacist">Apoteker</option>
                                    <option value="casemix">K3</option>
                                    <option value="ipsrs">IPSRS</option>
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

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50/60 border border-blue-100 rounded-2xl p-4 flex items-center justify-between shadow-2xs h-[75px]">
                        <div>
                            <p class="text-[11px] text-blue-800 font-semibold tracking-wide">Total Kontrol Pegawai</p>
                            <h2 class="text-xl font-black text-blue-950 mt-0.5">{{ count($recaps) }} Orang</h2>
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

                    <div class="bg-gradient-to-br from-amber-50 to-orange-50/60 border border-amber-100 rounded-2xl p-4 flex items-center justify-between shadow-2xs h-[75px] col-span-2 lg:col-span-1">
                        <div>
                            <p class="text-[11px] text-amber-800 font-semibold tracking-wide">Total Kasus Keterlambatan</p>
                            <h2 class="text-xl font-black text-amber-950 mt-0.5">{{ collect($recaps)->sum('telat') }} Insiden</h2>
                        </div>
                        <div class="text-2xl">⏰</div>
                    </div>

                </div>

                <div class="bg-slate-50/50 rounded-2xl p-2.5 border border-slate-200/60 flex flex-wrap gap-1.5">
                    <button onclick="filterRole('all', this)" class="role-btn active-role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow-2xs transition">Semua</button>
                    <button onclick="filterRole('perawat', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Perawat</button>
                    <button onclick="filterRole('security', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Security</button>
                    <button onclick="filterRole('customer service', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">CS</button>
                    <button onclick="filterRole('administrasi', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Administrasi</button>
                    <button onclick="filterRole('reverse osmosis', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">RO</button>
                    <button onclick="filterRole('kasir', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Kasir</button>
                    <button onclick="filterRole('keuangan', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Keuangan</button>
                    <button onclick="filterRole('apoteker', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Apoteker</button>
                    <button onclick="filterRole('k3', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">K3</button>
                    <button onclick="filterRole('ipsrs', this)" class="role-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">IPSRS</button>
                </div>

                <div id="employeeList" class="grid grid-cols-1 xl:grid-cols-2 gap-4 items-start">
                    @foreach($recaps as $index => $recap)
                    <div class="employee-card self-start bg-slate-50/50 border border-slate-200/80 rounded-2xl p-4 shadow-3xs hover:border-blue-400 hover:bg-white transition duration-200"
                        data-role="{{ strtolower($recap['employee']->role_label) }}">

                        <div class="flex justify-between items-start gap-3">
                            <div class="flex items-center gap-3 truncate">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center text-sm font-black shadow-2xs shrink-0">
                                    {{ strtoupper(substr($recap['employee']->name, 0, 1)) }}
                                </div>
                                <div class="truncate">
                                    <h2 class="employee-name text-xs font-bold text-gray-800 truncate tracking-wide">{{ $recap['employee']->name }}</h2>
                                    <p class="employee-role text-[10px] text-indigo-700 font-bold tracking-wider mt-0.5 bg-indigo-50 border border-indigo-100/60 px-1.5 py-0.5 rounded-md inline-block capitalize">
                                        {{ $recap['employee']->role_label }}
                                    </p>
                                </div>
                            </div>

                            <div class="shrink-0">
                                @if($recap['telat'] > 5)
                                <span class="bg-rose-50 text-rose-700 border border-rose-100 px-2 py-0.5 rounded-md text-[10px] font-black tracking-wide">⚠️ Evaluasi</span>
                                @else
                                <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-md text-[10px] font-black tracking-wide">✅ Normal</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-4 gap-2 mt-4">

                            <div class="bg-white border border-slate-200 rounded-xl p-2 text-center shadow-3xs">
                                <p class="text-[10px] text-gray-400 font-bold">Hadir</p>
                                <h3 class="text-sm font-black text-emerald-600 mt-0.5">{{ $recap['hadir'] }}x</h3>
                            </div>

                            <div class="bg-white border border-slate-200 rounded-xl p-1.5 text-center shadow-3xs flex flex-col justify-between min-h-[50px]">
                                <div>
                                    <p class="text-[10px] text-gray-400 font-bold">Telat</p>
                                    <h3 class="text-sm font-black text-amber-600 mt-0.5">{{ $recap['telat'] }}x</h3>
                                </div>
                                <p class="text-[8px] text-slate-400 font-semibold bg-slate-50 rounded py-0.5 mt-0.5 truncate" title="{{ $recap['late_formatted'] }}">
                                    {{ $recap['late_minutes'] > 0 ? $recap['late_formatted'] : '0 Menit' }}
                                </p>
                            </div>

                            <div class="bg-white border border-slate-200 rounded-xl p-2 text-center shadow-3xs">
                                <p class="text-[10px] text-gray-400 font-bold">Jam Kerja</p>
                                <h3 class="text-sm font-black text-cyan-600 mt-0.5">{{ $recap['total_jam'] }} J</h3>
                            </div>

                            <div class="bg-white border border-slate-200 rounded-xl p-2 text-center shadow-3xs flex flex-col justify-center items-center overflow-hidden">
                                <p class="text-[10px] text-gray-400 font-bold">Jam Lembur</p>
                                <h3 class="text-[10px] font-black text-indigo-700 mt-1 bg-indigo-50 border border-indigo-100/50 px-1 py-0.5 rounded w-full truncate" title="{{ $recap['overtimes'] }}">
                                    {{ $recap['overtimes'] ?: '0 Menit' }}
                                </h3>
                            </div>

                        </div>

                        <div class="mt-3">
                            <button onclick="toggleDetail({{ $index }})" class="w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold py-1.5 rounded-xl text-xs transition shadow-3xs flex items-center justify-center gap-1">
                                <span>📋</span> Opsi Manajemen Kuota Cuti
                            </button>
                        </div>

                        <div id="detail-{{ $index }}" class="hidden mt-2 bg-white border border-slate-200 rounded-xl p-3 shadow-inner">
                            <p class="text-[10px] text-gray-400 font-bold mb-1.5 uppercase tracking-wide">Sesuaikan Kuota Cuti Tahunan Pegawai</p>
                            <form action="{{ route('hrd.update.leave.quota', $recap['employee']->id) }}" method="POST" class="flex gap-2 m-0">
                                @csrf
                                <input type="number" name="leave_quota" min="0" value="{{ $recap['employee']->leave_quota ?? 0 }}"
                                    class="flex-1 rounded-xl border-gray-300 text-xs py-1.5 px-3 focus:border-indigo-500 focus:ring-0 shadow-3xs text-gray-700 font-bold">
                                <button type="submit" class="px-4 py-1.5 bg-[#1E40AF] hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow-2xs transition">
                                    Simpan
                                </button>
                            </form>
                        </div>

                    </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const cards = document.querySelectorAll('.employee-card');

        // LIVE FILTER KEYBOARD SEARCH SINKRONISASI NAMA DAN ROLE LABEL
        searchInput.addEventListener('keyup', function() {
            const keyword = this.value.toLowerCase();
            cards.forEach(card => {
                const name = card.querySelector('.employee-name').innerText.toLowerCase();
                const roleLabel = card.querySelector('.employee-role').innerText.toLowerCase();

                if (name.includes(keyword) || roleLabel.includes(keyword)) {
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
    </script>

</x-app-layout>