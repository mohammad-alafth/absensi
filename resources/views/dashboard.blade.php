<x-app-layout>

    <div class="min-h-screen bg-gray-100 pb-20">

        <!-- HEADER -->

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- CLOCK CARD -->
            <div class="-mt-5 bg-white rounded-3xl shadow p-5 text-center">

                <h1
                    id="clock"
                    class="text-3xl font-bold text-indigo-600">
                </h1>

                <p class="text-gray-500 text-sm mt-2">
                    {{ now()->translatedFormat('l, d F Y') }}
                </p>

                <hr class="my-4">

                <p class="text-gray-500 text-sm">
                    Jadwal Anda Hari Ini
                </p>

                <p class="text-xl font-bold mt-1">
                    {{ $schedule }}
                </p>

            </div>

            <!-- BANNER -->
            <div class="mt-5 bg-indigo-500 rounded-3xl p-5 text-white">

                <div class="flex justify-between items-center">

                    <div>
                        <p class="text-lg font-bold">
                            Jangan lupa Absen masuk
                        </p>

                        <p class="text-lg font-bold">
                            dan Absen pulang ya!
                        </p>
                    </div>

                    <div class="text-4xl">
                        👩‍⚕️
                    </div>

                </div>

            </div>

            @if($latestPermission)

            <div class="mt-5 bg-white rounded-3xl p-5 shadow">

                <h3 class="font-bold text-gray-700 mb-2">
                    Status Izin Terakhir
                </h3>

                <div class="flex justify-between items-center">

                    <div>
                        <p class="text-sm text-gray-500">
                            {{ $latestPermission->jenis }}
                        </p>

                        <p class="font-semibold">
                            {{ $latestPermission->tanggal }}
                        </p>
                    </div>

                    <div>
                        @if($latestPermission->status == 'pending')
                        <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm">
                            Pending
                        </span>

                        @elseif($latestPermission->status == 'approved')
                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                            Approved
                        </span>

                        @elseif($latestPermission->status == 'rejected')
                        <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm">
                            Rejected
                        </span>
                        @endif
                    </div>

                </div>

            </div>

            @endif

            <div class="max-w-7xl mx-auto px-10 sm:px-10 lg:px-10">

                @if(session('success'))
                <div
                    id="success-alert"
                    class="mt-4 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-2xl shadow">

                    {{ session('success') }}

                </div>
                @endif


                <!-- MENU -->
                <div class="mt-6 grid grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-4">

                    <a href="/face" class="bg-white rounded-2xl p-4 shadow text-center">
                        <div class="text-3xl">📥</div>
                        <p class="text-sm mt-2">Datang</p>
                    </a>

                    <a href="/face" class="bg-white rounded-2xl p-4 shadow text-center">
                        <div class="text-3xl">📤</div>
                        <p class="text-sm mt-2">Pulang</p>
                    </a>

                    <a href="/izin" class="relative bg-white rounded-2xl p-4 shadow text-center">

                        @if($pendingPermissionCount > 0)
                        <span class="absolute top-2 right-2 min-w-[22px] h-[22px] flex items-center justify-center bg-red-500 text-white text-xs rounded-full">
                            {{ $pendingPermissionCount }}
                        </span>
                        @endif

                        <div class="text-3xl">📝</div>
                        <p class="text-sm mt-2">Izin</p>

                    </a>

                    <a href="/cuti" class="relative bg-white rounded-2xl p-4 shadow text-center">

                        @if($pendingLeaveCount > 0)
                        <span class="absolute top-2 right-2 min-w-[22px] h-[22px] flex items-center justify-center bg-red-500 text-white text-xs rounded-full">
                            {{ $pendingLeaveCount }}
                        </span>
                        @endif

                        <div class="text-3xl">📅</div>
                        <p class="text-sm mt-2">Cuti</p>

                    </a>
                    <!-- <a href="/fingerprint" class="bg-white rounded-2xl p-4 shadow text-center">
                        <div class="text-3xl">👆</div>
                        <p class="text-sm mt-2">Finger</p>
                    </a> -->

                    <a href="/register-face" class="bg-white rounded-2xl p-4 shadow text-center">
                        <div class="text-3xl">👤</div>
                        <p class="text-sm mt-2">Register</p>
                    </a>

                    <a href="/history" class="bg-white rounded-2xl p-4 shadow text-center">
                        <div class="text-3xl">📊</div>
                        <p class="text-sm mt-2">History</p>
                    </a>

                    <!-- <a href="/profile" class="bg-white rounded-2xl p-4 shadow text-center">
                        <div class="text-3xl">⚙️</div>
                        <p class="text-sm mt-2">Profile</p>
                    </a> -->

                </div>

            </div>

            <!-- MOBILE NAV -->
            <div class="fixed bottom-0 left-0 right-0 bg-white shadow-lg md:hidden">

                <div class="grid grid-cols-3 text-center py-3">

                    <a href="/dashboard">
                        🏠
                        <p class="text-xs">Home</p>
                    </a>

                    <a href="/history">
                        📋
                        <p class="text-xs">History</p>
                    </a>

                    <a href="/profile">
                        👤
                        <p class="text-xs">Profile</p>
                    </a>

                </div>

            </div>

        </div>

        <script>
            setTimeout(() => {
                const alertBox = document.getElementById('success-alert');

                if (alertBox) {
                    alertBox.style.display = 'none';
                }
            }, 3000);

            function updateClock() {

                const now = new Date();

                const clock = document.getElementById("clock");

                if (clock) {
                    clock.innerHTML =
                        now.toLocaleTimeString('id-ID') + " WIB";
                }
                now.toLocaleTimeString('id-ID') + " WIB";

            }

            setInterval(updateClock, 1000);

            updateClock();
        </script>

</x-app-layout>