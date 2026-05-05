<x-app-layout>

    <div class="min-h-screen bg-gray-100 pb-20">

        <!-- HEADER -->
        <div class="bg-indigo-500 rounded-b-[35px] px-4 py-5 text-white">

            <div class="max-w-6xl mx-auto flex items-center gap-3">

                <!-- Avatar -->
                <div class="w-14 h-14 rounded-full overflow-hidden bg-white">
                    <img
                        src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}"
                        class="w-full h-full object-cover">
                </div>

                <!-- Greeting -->
                <div>
                    <p class="text-sm opacity-90">Hello,</p>
                    <h2 class="font-bold text-lg">
                        {{ auth()->user()->name }}
                    </h2>
                </div>

            </div>

        </div>

        <div class="max-w-6xl mx-auto px-4">

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

                <a href="/izin" class="bg-white rounded-2xl p-4 shadow text-center">
                    <div class="text-3xl">📝</div>
                    <p class="text-sm mt-2">Izin</p>
                </a>

                <a href="/cuti" class="bg-white rounded-2xl p-4 shadow text-center">
                    <div class="text-3xl">📅</div>
                    <p class="text-sm mt-2">Cuti</p>
                </a>

                <a href="/fingerprint" class="bg-white rounded-2xl p-4 shadow text-center">
                    <div class="text-3xl">👆</div>
                    <p class="text-sm mt-2">Finger</p>
                </a>

                <a href="/register-face" class="bg-white rounded-2xl p-4 shadow text-center">
                    <div class="text-3xl">👤</div>
                    <p class="text-sm mt-2">Register</p>
                </a>

                <a href="/history" class="bg-white rounded-2xl p-4 shadow text-center">
                    <div class="text-3xl">📊</div>
                    <p class="text-sm mt-2">History</p>
                </a>

                <a href="/profile" class="bg-white rounded-2xl p-4 shadow text-center">
                    <div class="text-3xl">⚙️</div>
                    <p class="text-sm mt-2">Profile</p>
                </a>

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
        function updateClock() {

            const now = new Date();

            document.getElementById("clock").innerHTML =
                now.toLocaleTimeString('id-ID') + " WIB";

        }

        setInterval(updateClock, 1000);

        updateClock();
    </script>

</x-app-layout>