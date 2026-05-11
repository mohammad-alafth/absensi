<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-7xl mx-auto">

            <!-- HEADER -->
            <div class="mb-6">

                <h1 class="text-3xl font-bold text-gray-800">
                    Dashboard HRD
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Monitoring approval cuti, izin, dan lembur
                </p>

            </div>

            <!-- ALERT -->
            @if(session('success'))

            <div class="mb-5 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-2xl">

                {{ session('success') }}

            </div>

            @endif

            <!-- STATISTIC -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

                <!-- CUTI -->
                <a href="{{ route('hrd.cuti') }}"
                    class="bg-white rounded-3xl shadow p-6 hover:shadow-xl transition">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                Pending Cuti
                            </p>

                            <h2 class="text-4xl font-bold text-indigo-600 mt-2">
                                {{ $pendingLeave }}
                            </h2>

                        </div>

                        <div class="text-5xl">
                            📅
                        </div>

                    </div>

                </a>

                <!-- IZIN -->
                <a href="{{ route('hrd.izin') }}"
                    class="bg-white rounded-3xl shadow p-6 hover:shadow-xl transition">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                Pending Izin
                            </p>

                            <h2 class="text-4xl font-bold text-orange-500 mt-2">
                                {{ $pendingPermission }}
                            </h2>

                        </div>

                        <div class="text-5xl">
                            📝
                        </div>

                    </div>

                </a>

                <!-- LEMBUR -->
                <a href="{{ route('hrd.lembur') }}"
                    class="bg-white rounded-3xl shadow p-6 hover:shadow-xl transition">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                Pending Lembur
                            </p>

                            <h2 class="text-4xl font-bold text-green-500 mt-2">
                                {{ $pendingOvertime }}
                            </h2>

                        </div>

                        <div class="text-5xl">
                            ⏰
                        </div>

                    </div>

                </a>

            </div>

            <!-- MENU CEPAT -->
            <div class="bg-white rounded-3xl shadow p-6">

                <h2 class="text-xl font-bold text-gray-800 mb-5">
                    Menu Approval HRD
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <!-- CUTI -->
                    <a href="{{ route('hrd.cuti') }}"
                        class="bg-indigo-500 hover:bg-indigo-600 text-white rounded-3xl p-6 transition shadow-lg">

                        <div class="text-4xl mb-3">
                            📅
                        </div>

                        <h3 class="text-lg font-bold">
                            Approval Cuti
                        </h3>

                        <p class="text-sm opacity-90 mt-1">
                            Kelola approval pengajuan cuti
                        </p>

                    </a>

                    <!-- IZIN -->
                    <a href="{{ route('hrd.izin') }}"
                        class="bg-orange-500 hover:bg-orange-600 text-white rounded-3xl p-6 transition shadow-lg">

                        <div class="text-4xl mb-3">
                            📝
                        </div>

                        <h3 class="text-lg font-bold">
                            Approval Izin
                        </h3>

                        <p class="text-sm opacity-90 mt-1">
                            Kelola approval pengajuan izin
                        </p>

                    </a>

                    <!-- LEMBUR -->
                    <a href="{{ route('hrd.lembur') }}"
                        class="bg-green-500 hover:bg-green-600 text-white rounded-3xl p-6 transition shadow-lg">

                        <div class="text-4xl mb-3">
                            ⏰
                        </div>

                        <h3 class="text-lg font-bold">
                            Approval Lembur
                        </h3>

                        <p class="text-sm opacity-90 mt-1">
                            Kelola approval pengajuan lembur
                        </p>

                    </a>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>