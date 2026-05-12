<x-app-layout>

    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-indigo-50 to-cyan-50 pb-28">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <!-- HEADER -->
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 mb-6">

                <!-- LEFT -->
                <div>

                    <a href="{{ route('dashboard') }}"
                        class="text-[#1E40AF] text-sm font-semibold">

                        ← Kembali

                    </a>

                    <h1 class="text-3xl font-extrabold text-[#1E40AF] mt-2">
                        Rekap Absensi HRD
                    </h1>

                    <p class="text-gray-500 mt-1">
                        Monitoring kehadiran dan performa pegawai
                    </p>

                </div>

                <!-- RIGHT -->
                <div class="w-full lg:w-auto flex justify-end">

                    <div class="flex flex-col sm:flex-row gap-3">

                        <!-- SEARCH -->
                        <div
                            class="bg-white/80 backdrop-blur-md border border-white/40
                            rounded-2xl shadow-xl px-4 py-3">

                            <input
                                type="text"
                                id="searchInput"
                                placeholder="Cari nama / role..."
                                class="border-0 bg-transparent focus:ring-0 text-sm w-64">

                        </div>

                        <!-- FILTER BULAN -->
                        <div
                            class="bg-white/80 backdrop-blur-md border border-white/40
                            rounded-2xl shadow-xl p-3">

                            <form method="GET">

                                <input
                                    type="month"
                                    name="month"
                                    value="{{ $month }}"
                                    onchange="this.form.submit()"
                                    class="rounded-xl border-gray-300 shadow-sm text-sm">

                            </form>

                        </div>

                    </div>

                </div>

            </div>

            <!-- SUMMARY -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                <!-- TOTAL PEGAWAI -->
                <div
                    class="bg-white/80 backdrop-blur-md border border-white/40
                    rounded-3xl shadow-xl p-5">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                Total Pegawai
                            </p>

                            <h2 class="text-3xl font-extrabold text-[#1E40AF] mt-2">

                                {{ count($recaps) }}

                            </h2>

                        </div>

                        <div class="text-4xl">
                            👥
                        </div>

                    </div>

                </div>

                <!-- TOTAL HADIR -->
                <div
                    class="bg-white/80 backdrop-blur-md border border-white/40
                    rounded-3xl shadow-xl p-5">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                Total Hadir
                            </p>

                            <h2 class="text-3xl font-extrabold text-emerald-500 mt-2">

                                {{ collect($recaps)->sum('hadir') }}

                            </h2>

                        </div>

                        <div class="text-4xl">
                            ✅
                        </div>

                    </div>

                </div>

                <!-- TOTAL TELAT -->
                <div
                    class="bg-white/80 backdrop-blur-md border border-white/40
                    rounded-3xl shadow-xl p-5">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                Total Telat
                            </p>

                            <h2 class="text-3xl font-extrabold text-yellow-500 mt-2">

                                {{ collect($recaps)->sum('telat') }}

                            </h2>

                        </div>

                        <div class="text-4xl">
                            ⏰
                        </div>

                    </div>

                </div>

                <!-- TOTAL JAM -->
                <div
                    class="bg-white/80 backdrop-blur-md border border-white/40
                    rounded-3xl shadow-xl p-5">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                Total Jam Kerja
                            </p>

                            <h2 class="text-3xl font-extrabold text-cyan-500 mt-2">

                                {{ collect($recaps)->sum('total_jam') }}

                            </h2>

                        </div>

                        <div class="text-4xl">
                            📊
                        </div>

                    </div>

                </div>

            </div>

            <!-- ROLE FILTER -->
            <div
                class="bg-white/80 backdrop-blur-md border border-white/40
                rounded-3xl shadow-xl p-5 mb-6">

                <div class="flex flex-wrap gap-3">

                    <!-- ALL -->
                    <button
                        onclick="filterRole('all', this)"
                        class="role-btn active-role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-gradient-to-r from-indigo-500 to-cyan-500
                        text-white shadow-lg">

                        Semua

                    </button>

                    <!-- NURSE -->
                    <button
                        onclick="filterRole('nurse', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Nurse

                    </button>

                    <!-- SECURITY -->
                    <button
                        onclick="filterRole('security', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Security

                    </button>

                    <!-- DOKTER -->
                    <button
                        onclick="filterRole('dokter', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Dokter

                    </button>

                    <!-- ADMIN -->
                    <button
                        onclick="filterRole('admin', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Admin

                    </button>

                    <!-- STAFF -->
                    <button
                        onclick="filterRole('staff', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Staff

                    </button>

                    <!-- KASIR -->
                    <button
                        onclick="filterRole('kasir', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Kasir

                    </button>

                    <!-- FARMASI -->
                    <button
                        onclick="filterRole('farmasi', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Farmasi

                    </button>

                    <!-- LAB -->
                    <button
                        onclick="filterRole('lab', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Lab

                    </button>

                    <!-- RADIOLOGI -->
                    <button
                        onclick="filterRole('radiologi', this)"
                        class="role-btn
                        px-5 py-2 rounded-2xl text-sm font-bold
                        bg-slate-100 text-slate-700">

                        Radiologi

                    </button>

                </div>

            </div>

            <!-- LIST -->
            <div
                id="employeeList"
                class="grid grid-cols-1 xl:grid-cols-2 gap-5">

                @foreach($recaps as $recap)

                <div
                    class="employee-card
                    bg-white/80 backdrop-blur-md border border-white/40
                    rounded-3xl shadow-xl p-5 hover:shadow-2xl
                    hover:-translate-y-1 transition duration-300"

                    data-role="{{ strtolower($recap['employee']->role) }}">

                    <!-- TOP -->
                    <div class="flex justify-between items-start gap-4">

                        <!-- PROFILE -->
                        <div class="flex items-center gap-4">

                            <div
                                class="w-16 h-16 rounded-2xl
                                bg-gradient-to-r from-indigo-500 via-blue-500 to-cyan-500
                                text-white flex items-center justify-center
                                text-2xl font-bold shadow-lg">

                                {{ strtoupper(substr($recap['employee']->name, 0, 1)) }}

                            </div>

                            <div>

                                <h2
                                    class="employee-name text-xl font-bold text-gray-800">

                                    {{ $recap['employee']->name }}

                                </h2>

                                <p
                                    class="employee-role text-sm text-gray-500 mt-1">

                                    {{ $recap['employee']->role }}

                                </p>

                            </div>

                        </div>

                        <!-- STATUS -->
                        <div>

                            @if($recap['telat'] > 5)

                            <div
                                class="bg-red-100 text-red-700
                                px-4 py-2 rounded-2xl text-sm font-bold">

                                ⚠️ Evaluasi

                            </div>

                            @else

                            <div
                                class="bg-green-100 text-green-700
                                px-4 py-2 rounded-2xl text-sm font-bold">

                                ✅ Baik

                            </div>

                            @endif

                        </div>

                    </div>

                    <!-- STATS -->
                    <div class="grid grid-cols-3 gap-4 mt-6">

                        <!-- HADIR -->
                        <div
                            class="bg-emerald-50 rounded-2xl p-4 text-center">

                            <p class="text-sm text-gray-500">
                                Hadir
                            </p>

                            <h3
                                class="text-2xl font-extrabold text-emerald-600 mt-2">

                                {{ $recap['hadir'] }}

                            </h3>

                        </div>

                        <!-- TELAT -->
                        <div
                            class="bg-yellow-50 rounded-2xl p-4 text-center">

                            <p class="text-sm text-gray-500">
                                Telat
                            </p>

                            <h3
                                class="text-2xl font-extrabold text-yellow-600 mt-2">

                                {{ $recap['telat'] }}

                            </h3>

                        </div>

                        <!-- JAM -->
                        <div
                            class="bg-cyan-50 rounded-2xl p-4 text-center">

                            <p class="text-sm text-gray-500">
                                Jam
                            </p>

                            <h3
                                class="text-2xl font-extrabold text-cyan-600 mt-2">

                                {{ $recap['total_jam'] }}

                            </h3>

                        </div>

                    </div>

                </div>

                @endforeach

            </div>

        </div>

    </div>

    <!-- SCRIPT -->
    <script>
        const searchInput = document.getElementById('searchInput');

        const cards = document.querySelectorAll('.employee-card');

        // SEARCH
        searchInput.addEventListener('keyup', function() {

            const keyword = this.value.toLowerCase();

            cards.forEach(card => {

                const name = card.querySelector('.employee-name')
                    .innerText.toLowerCase();

                const role = card.querySelector('.employee-role')
                    .innerText.toLowerCase();

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

        // ROLE FILTER
        function filterRole(role, button) {

            cards.forEach(card => {

                const employeeRole = card.dataset.role;

                if (
                    role === 'all' ||
                    employeeRole.includes(role) ||
                    employeeRole.includes('pj_' + role)
                ) {

                    card.style.display = 'block';

                } else {

                    card.style.display = 'none';

                }

            });

            // ACTIVE BUTTON STYLE
            document.querySelectorAll('.role-btn')
                .forEach(btn => {

                    btn.classList.remove(
                        'active-role-btn',
                        'bg-gradient-to-r',
                        'from-indigo-500',
                        'to-cyan-500',
                        'text-white',
                        'shadow-lg'
                    );

                    btn.classList.add(
                        'bg-slate-100',
                        'text-slate-700'
                    );

                });

            button.classList.remove(
                'bg-slate-100',
                'text-slate-700'
            );

            button.classList.add(
                'active-role-btn',
                'bg-gradient-to-r',
                'from-indigo-500',
                'to-cyan-500',
                'text-white',
                'shadow-lg'
            );

        }
    </script>

</x-app-layout>