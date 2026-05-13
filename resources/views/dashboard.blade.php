<x-app-layout>

    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-indigo-50 to-cyan-50 pb-28">

        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

            <!-- CLOCK -->
            <div class="-mt-5 bg-white/80 backdrop-blur-md border border-white/40 rounded-3xl shadow-xl p-5 text-center">

                <h1
                    id="clock"
                    class="text-3xl font-extrabold bg-gradient-to-r from-indigo-500 via-cyan-500 to-blue-600 bg-clip-text text-transparent">
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

            <!-- SHIFT CARD -->
            @if($scheduleData)

            <div class="mt-5 bg-white rounded-3xl p-5 shadow-xl border border-blue-100">

                <div class="flex justify-between items-center">

                    <div>

                        <p class="text-sm text-gray-500">
                            Jadwal Hari Ini
                        </p>

                        <h2 class="text-2xl font-bold text-[#1E40AF] mt-1">

                            {{ $scheduleData['shift_name'] }}

                        </h2>

                    </div>

                    <div class="text-right">

                        <p class="text-sm text-gray-500">
                            Jam Kerja
                        </p>

                        <p class="font-bold text-lg text-gray-800">

                            {{ \Carbon\Carbon::parse($scheduleData['start_time'])->format('H:i') }}
                            -
                            {{ \Carbon\Carbon::parse($scheduleData['end_time'])->format('H:i') }}

                        </p>

                    </div>

                </div>

            </div>

            @endif

            <!-- BANNER -->
            <div class="mt-5 bg-gradient-to-r from-indigo-600 via-blue-500 to-cyan-500 rounded-3xl p-5 text-white shadow-2xl">

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

            <div class="mt-5 bg-white/80 backdrop-blur-md border border-white/50 rounded-3xl p-5 shadow-xl">

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

            @php

            $todayAttendance = \App\Models\Attendance::where('user_id', auth()->id())
            ->whereDate('tanggal', now()->toDateString())
            ->first();

            $alreadyCheckin = $todayAttendance && $todayAttendance->jam_masuk;
            $alreadyCheckout = $todayAttendance && $todayAttendance->jam_keluar;

            @endphp

            <div class="max-w-full mx-auto px-2 sm:px-6 lg:px-8">

                @if(session('success'))

                <div
                    id="success-alert"
                    class="mt-4 bg-emerald-100/80 backdrop-blur border border-emerald-300 text-emerald-700 px-4 py-3 rounded-2xl shadow">

                    {{ session('success') }}

                </div>

                @endif

                <!-- MENU -->
                <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">

                    <!-- DATANG -->
                    @if($alreadyCheckin && !$alreadyCheckout)

                    <button
                        onclick="alreadyCheckinAlert()"
                        class="relative bg-gray-200 cursor-not-allowed rounded-3xl
                        min-h-[110px]
                        flex flex-col items-center justify-center
                        shadow-lg w-full opacity-80">

                        <div class="text-4xl">
                            ✅
                        </div>

                        <p class="text-sm sm:text-base mt-2 font-semibold text-gray-600">
                            Sudah Absen
                        </p>

                    </button>

                    @else

                    <a href="/face"
                        class="relative bg-white/80 backdrop-blur-md border border-white/40 rounded-3xl
                        min-h-[110px]
                        flex flex-col items-center justify-center
                        shadow-lg hover:shadow-2xl hover:-translate-y-1
                        transition duration-300 active:scale-95">

                        <div class="text-4xl drop-shadow-md">
                            📥
                        </div>

                        <p class="text-sm sm:text-base mt-2 font-semibold text-gray-700">
                            Datang
                        </p>

                    </a>

                    @endif

                    <!-- PULANG -->
                    <a href="/face"
                        class="relative bg-white/80 backdrop-blur-md border border-white/40 rounded-3xl
                        min-h-[110px]
                        flex flex-col items-center justify-center
                        shadow-lg hover:shadow-2xl hover:-translate-y-1
                        transition duration-300 active:scale-95">

                        <div class="text-4xl drop-shadow-md">
                            📤
                        </div>

                        <p class="text-sm sm:text-base mt-2 font-semibold text-gray-700">
                            Pulang
                        </p>

                    </a>

                    <!-- IZIN -->
                    <a href="/izin"
                        class="relative bg-white/80 backdrop-blur-md border border-white/40 rounded-3xl
                        min-h-[110px]
                        flex flex-col items-center justify-center
                        shadow-lg hover:shadow-2xl hover:-translate-y-1
                        transition duration-300 active:scale-95">

                        @if($pendingPermissionCount > 0)

                        <span
                            class="absolute top-3 right-3 min-w-[24px] h-[24px]
                            flex items-center justify-center
                            bg-gradient-to-r from-pink-500 to-red-500
                            text-white text-xs font-bold rounded-full shadow-lg">

                            {{ $pendingPermissionCount }}

                        </span>

                        @endif

                        <div class="text-4xl drop-shadow-md">
                            📝
                        </div>

                        <p class="text-sm sm:text-base mt-2 font-semibold text-gray-700">
                            Izin
                        </p>

                    </a>

                    <!-- CUTI -->
                    <a href="/cuti"
                        class="relative bg-white/80 backdrop-blur-md border border-white/40 rounded-3xl
                        min-h-[110px]
                        flex flex-col items-center justify-center
                        shadow-lg hover:shadow-2xl hover:-translate-y-1
                        transition duration-300 active:scale-95">

                        @if($pendingLeaveCount > 0)

                        <span
                            class="absolute top-3 right-3 min-w-[24px] h-[24px]
                            flex items-center justify-center
                            bg-gradient-to-r from-pink-500 to-red-500
                            text-white text-xs font-bold rounded-full shadow-lg">

                            {{ $pendingLeaveCount }}

                        </span>

                        @endif

                        <div class="text-4xl drop-shadow-md">
                            📅
                        </div>

                        <p class="text-sm sm:text-base mt-2 font-semibold text-gray-700">
                            Cuti
                        </p>

                    </a>

                    <!-- LEMBUR -->
                    <a href="/lembur"
                        class="relative bg-white/80 backdrop-blur-md border border-white/40 rounded-3xl
                        min-h-[110px]
                        flex flex-col items-center justify-center
                        shadow-lg hover:shadow-2xl hover:-translate-y-1
                        transition duration-300 active:scale-95">

                        @if($pendingOvertimeCount > 0)

                        <span
                            class="absolute top-3 right-3 min-w-[24px] h-[24px]
                            flex items-center justify-center
                            bg-gradient-to-r from-pink-500 to-red-500
                            text-white text-xs font-bold rounded-full shadow-lg">

                            {{ $pendingOvertimeCount }}

                        </span>

                        @endif

                        <div class="text-4xl drop-shadow-md">
                            ⏰
                        </div>

                        <p class="text-sm sm:text-base mt-2 font-semibold text-gray-700">
                            Lembur
                        </p>

                    </a>

                    <!-- REGISTER -->
                    @if(auth()->user()->role === 'admin')

                    <a href="/register-face"
                        class="relative bg-white/80 backdrop-blur-md border border-white/40 rounded-3xl
                        min-h-[110px]
                        flex flex-col items-center justify-center
                        shadow-lg hover:shadow-2xl hover:-translate-y-1
                        transition duration-300 active:scale-95">

                        <div class="text-4xl drop-shadow-md">
                            👤
                        </div>

                        <p class="text-sm sm:text-base mt-2 font-semibold text-gray-700">
                            Register
                        </p>

                    </a>

                    @endif

                    <!-- HISTORY -->
                    <a href="/history"
                        class="hidden sm:flex relative bg-white/80 backdrop-blur-md border border-white/40 rounded-3xl
                        min-h-[110px]
                        flex-col items-center justify-center
                        shadow-lg hover:shadow-2xl hover:-translate-y-1
                        transition duration-300 active:scale-95">

                        <div class="text-4xl drop-shadow-md">
                            📊
                        </div>

                        <p class="text-sm sm:text-base mt-2 font-semibold text-gray-700">
                            History
                        </p>

                    </a>

                    <!-- APPROVAL PJ -->
                    @if(str_starts_with(auth()->user()->role, 'pj_'))

                    <a href="{{ route('pj.dashboard') }}"
                        class="relative 
    bg-gradient-to-r from-orange-500 via-amber-500 to-yellow-400
    rounded-3xl
    min-h-[110px]
    flex flex-col items-center justify-center
    shadow-xl hover:shadow-2xl hover:-translate-y-1
    transition duration-300 active:scale-95 text-white overflow-hidden">

                        <div class="absolute inset-0 opacity-20 bg-white blur-2xl"></div>

                        <div class="relative text-4xl drop-shadow-lg">
                            🧑‍💼
                        </div>

                        <p class="relative text-sm sm:text-base mt-2 font-bold">
                            Approval PJ
                        </p>

                    </a>
                    <!-- SHIFT -->

                    <a href="{{ route('shift.index') }}"
                        class="relative bg-gradient-to-r from-cyan-500 via-blue-500 to-indigo-600
    rounded-3xl
    min-h-[110px]
    flex flex-col items-center justify-center
    shadow-xl hover:shadow-2xl hover:-translate-y-1
    transition duration-300 active:scale-95 text-white overflow-hidden">

                        <div class="absolute inset-0 opacity-20 bg-white blur-2xl"></div>

                        <div class="relative text-4xl drop-shadow-lg">
                            📅
                        </div>

                        <p class="relative text-sm sm:text-base mt-2 font-bold">
                            Jadwal Shift
                        </p>

                    </a>

                    @endif


                    <!-- HRD MENU -->
                    @if(auth()->user()->role === 'hrd')

                    <!-- APPROVAL HRD -->
                    <a href="{{ route('hrd.dashboard') }}"
                        class="relative 
    bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500
    rounded-3xl
    min-h-[110px]
    flex flex-col items-center justify-center
    shadow-xl hover:shadow-2xl hover:-translate-y-1
    transition duration-300 active:scale-95 text-white overflow-hidden">

                        <div class="absolute inset-0 opacity-20 bg-white blur-2xl"></div>

                        <div class="relative text-4xl drop-shadow-lg">
                            🏢
                        </div>

                        <p class="relative text-sm sm:text-base mt-2 font-bold">
                            Approval HRD
                        </p>

                    </a>

                    <!-- REKAP HRD -->
                    <a href="{{ route('hrd.rekap') }}"
                        class="relative bg-gradient-to-r from-slate-500 via-blue-500 to-indigo-600
    rounded-3xl min-h-[110px]
    flex flex-col items-center justify-center
    shadow-xl hover:shadow-2xl hover:-translate-y-1
    transition duration-300 active:scale-95 text-white overflow-hidden">

                        <div class="absolute inset-0 opacity-15 bg-white blur-2xl"></div>

                        <div class="relative text-4xl drop-shadow-lg">
                            📊
                        </div>

                        <p class="relative text-sm sm:text-base mt-2 font-bold">
                            Rekap HRD
                        </p>

                    </a>

                    @endif

                </div>

            </div>

        </div>

    </div>

    <!-- SWEET ALERT -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // AUTO HIDE ALERT
        setTimeout(() => {
            const alertBox = document.getElementById('success-alert');
            if (alertBox) {
                alertBox.style.display = 'none';
            }
        }, 3000);

        // CLOCK
        function updateClock() {
            const now = new Date();
            const clock = document.getElementById("clock");

            if (clock) {
                clock.innerHTML = now.toLocaleTimeString('id-ID') + " WIB";
            }
        }

        setInterval(updateClock, 1000);
        updateClock();

        // ALERT ABSEN
        function alreadyCheckinAlert() {
            Swal.fire({
                icon: 'info',
                title: 'Sudah Absen Masuk',
                text: 'Anda sudah melakukan absen hari ini, tunggu jam pulang untuk checkout.',
                confirmButtonColor: '#4F46E5',
                confirmButtonText: 'OK',
                background: '#fff',
                borderRadius: '20px'
            });
        }

        // ==============================
        // SHIFT REMINDER (TAMBAHAN BARU)
        // ==============================

        const showShiftReminder = @json($showShiftReminder);
        const shiftReminderMessage = @json($shiftReminderMessage);

        if (showShiftReminder) {

            window.addEventListener('load', function() {

                Swal.fire({
                    icon: 'warning',
                    title: 'Jadwal Shift Akan Berakhir',
                    html: `<div style="font-size:15px; line-height:1.7">${shiftReminderMessage}</div>`,
                    confirmButtonText: 'Atur Shift Sekarang',
                    confirmButtonColor: '#2563EB',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    background: '#ffffff'
                }).then((result) => {

                    if (result.isConfirmed) {
                        window.location.href = "{{ route('shift.index') }}";
                    }

                });

            });

        }
    </script>

</x-app-layout>