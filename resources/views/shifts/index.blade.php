<x-app-layout>

    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-indigo-50 to-cyan-50 p-4 sm:p-5 pb-28">

        <div class="max-w-7xl mx-auto">

            <!-- ================= HEADER ================= -->
            <div class="flex items-center justify-between mb-6">

                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2
                    text-[#1E40AF] font-semibold text-sm
                    hover:translate-x-1 transition">

                    ← Kembali

                </a>

                <div
                    class="bg-gradient-to-r from-[#1E40AF] to-blue-500
                    text-white px-5 py-2 rounded-2xl shadow-lg text-sm font-semibold">

                    Jadwal Shift

                </div>

            </div>

            <!-- ================= FILTER ================= -->
            <div
                class="bg-white/80 backdrop-blur-md border border-white/50
                rounded-3xl p-5 shadow-xl mb-6">

                <form method="GET">

                    <label class="text-sm text-gray-500 block mb-3">
                        Pilih Tanggal Shift
                    </label>

                    <input
                        type="date"
                        name="date"
                        value="{{ $date }}"
                        onchange="this.form.submit()"
                        class="w-full sm:w-auto rounded-2xl border-gray-300 shadow-sm">

                </form>

            </div>

            <!-- =======================================================
                DESKTOP
            ======================================================== -->
            <div class="hidden xl:grid grid-cols-4 gap-5">

                <!-- ================= UNASSIGNED ================= -->
                <div
                    class="bg-white/80 backdrop-blur-md border border-white/50
                    rounded-3xl shadow-xl overflow-hidden">

                    <div
                        class="bg-gradient-to-r from-gray-700 to-gray-900
                        text-white px-5 py-4">

                        <h2 class="font-bold text-lg">
                            👥 Belum Dijadwalkan
                        </h2>

                        <p class="text-sm opacity-80 mt-1">
                            Drag pegawai ke shift
                        </p>

                    </div>

                    <div
                        id="unassigned"
                        class="p-5 space-y-4 min-h-[650px] bg-gradient-to-b from-white to-slate-50">

                        @foreach($employees as $employee)

                        @php
                        $assigned = $employeeShifts
                        ->where('user_id', $employee->id)
                        ->first();
                        @endphp

                        @if(!$assigned)

                        <div
                            class="employee-card group
                            bg-white border border-blue-100 rounded-3xl
                            p-4 cursor-move shadow-sm hover:shadow-xl
                            hover:-translate-y-1 transition duration-300"
                            data-user="{{ $employee->id }}">

                            <div class="flex items-center justify-between">

                                <div>

                                    <p class="font-bold text-gray-800">
                                        {{ $employee->name }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-1">
                                        Belum memiliki shift
                                    </p>

                                </div>

                                <div
                                    class="w-11 h-11 rounded-2xl
                                    bg-gray-100 flex items-center justify-center
                                    text-xl group-hover:scale-110 transition">

                                    ↕️

                                </div>

                            </div>

                        </div>

                        @endif

                        @endforeach

                    </div>

                </div>

                <!-- ================= SHIFT ================= -->
                @foreach($shifts as $shift)

                <div
                    class="bg-white/80 backdrop-blur-md border border-white/50
                    rounded-3xl shadow-xl overflow-hidden">

                    <!-- HEADER -->
                    <div
                        class="
                        @if(strtolower($shift->name) == 'pagi')
                        bg-gradient-to-r from-amber-400 to-orange-500
                        @elseif(strtolower($shift->name) == 'malam')
                        bg-gradient-to-r from-indigo-700 to-blue-900
                        @else
                        bg-gradient-to-r from-cyan-500 to-blue-600
                        @endif
                        text-white px-5 py-5">

                        <div class="flex justify-between items-start">

                            <div>

                                <h2 class="font-bold text-xl">

                                    {{ $shift->name }}

                                </h2>

                                <p class="text-sm opacity-90 mt-1">

                                    {{ $shift->start_time }}
                                    -
                                    {{ $shift->end_time }}

                                </p>

                            </div>

                            <div class="text-3xl">

                                @if(strtolower($shift->name) == 'pagi')
                                🌤️
                                @elseif(strtolower($shift->name) == 'malam')
                                🌙
                                @else
                                ⏰
                                @endif

                            </div>

                        </div>

                    </div>

                    <!-- BODY -->
                    <div
                        class="shift-column
                        p-5 space-y-4 min-h-[650px]
                        bg-gradient-to-b from-white to-blue-50/40"
                        data-shift="{{ $shift->id }}">

                        @forelse($employeeShifts->where('shift_id', $shift->id) as $item)

                        <div
                            class="employee-card group
                            bg-white border border-blue-100 rounded-3xl
                            p-4 cursor-move shadow-sm
                            hover:shadow-xl hover:-translate-y-1
                            transition duration-300"
                            data-user="{{ $item->user->id }}">

                            <div class="flex items-center justify-between">

                                <div>

                                    <p class="font-bold text-gray-800">
                                        {{ $item->user->name }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-1">
                                        Shift {{ $shift->name }}
                                    </p>

                                </div>

                                <div
                                    class="w-11 h-11 rounded-2xl
                                    bg-blue-50 flex items-center justify-center
                                    text-xl group-hover:scale-110 transition">

                                    ↕️

                                </div>

                            </div>

                        </div>

                        @empty

                        <div
                            class="border-2 border-dashed border-blue-100
                            rounded-3xl p-10 text-center text-gray-400
                            bg-white/50">

                            <div class="text-5xl mb-3">
                                👨‍⚕️
                            </div>

                            <p class="text-sm">
                                Belum ada pegawai
                            </p>

                        </div>

                        @endforelse

                    </div>

                </div>

                @endforeach

            </div>

            <!-- =======================================================
                MOBILE
            ======================================================== -->
            <div class="xl:hidden space-y-6">

                <!-- ================= UNASSIGNED ================= -->
                <div
                    class="bg-white rounded-[30px]
                    shadow-lg overflow-hidden">

                    <div
                        class="bg-gradient-to-r from-gray-700 to-gray-900
                        text-white px-5 py-5">

                        <h2 class="font-bold text-lg">
                            👥 Belum Dijadwalkan
                        </h2>

                        <p class="text-sm opacity-80 mt-1">
                            Drag ke shift tujuan
                        </p>

                    </div>

                    <div
                        id="unassigned-mobile"
                        class="p-4 space-y-4 min-h-[120px] bg-[#f8f9ff]">

                        @foreach($employees as $employee)

                        @php
                        $assigned = $employeeShifts
                        ->where('user_id', $employee->id)
                        ->first();
                        @endphp

                        @if(!$assigned)

                        <div
                            class="employee-card
                            bg-white rounded-3xl p-4
                            border border-gray-100 shadow-sm
                            cursor-move"
                            data-user="{{ $employee->id }}">

                            <div class="flex justify-between items-center">

                                <div>

                                    <p class="font-bold text-gray-800">
                                        {{ $employee->name }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-1">
                                        Belum ada jadwal
                                    </p>

                                </div>

                                <div
                                    class="w-12 h-12 rounded-2xl
                                    bg-gray-100 flex items-center justify-center text-xl">

                                    ↕️

                                </div>

                            </div>

                        </div>

                        @endif

                        @endforeach

                    </div>

                </div>

                <!-- ================= SHIFT MOBILE ================= -->
                @foreach($shifts as $shift)

                <div
                    class="bg-white rounded-[30px]
                    shadow-lg overflow-hidden">

                    <!-- HEADER -->
                    <div
                        class="
                        @if(strtolower($shift->name) == 'pagi')
                        bg-gradient-to-r from-orange-400 to-amber-500
                        @elseif(strtolower($shift->name) == 'malam')
                        bg-gradient-to-r from-indigo-700 to-blue-900
                        @else
                        bg-gradient-to-r from-cyan-500 to-blue-600
                        @endif
                        text-white px-5 py-5">

                        <div class="flex justify-between items-center">

                            <div>

                                <h2 class="font-bold text-xl">

                                    {{ $shift->name }}

                                </h2>

                                <p class="text-sm opacity-90 mt-1">

                                    {{ $shift->start_time }}
                                    -
                                    {{ $shift->end_time }}

                                </p>

                            </div>

                            <div class="text-4xl">

                                @if(strtolower($shift->name) == 'pagi')
                                🌤️
                                @elseif(strtolower($shift->name) == 'malam')
                                🌙
                                @else
                                ⏰
                                @endif

                            </div>

                        </div>

                    </div>

                    <!-- BODY -->
                    <div
                        class="shift-column p-4 space-y-4 bg-[#f8f9ff]"
                        data-shift="{{ $shift->id }}">

                        @forelse($employeeShifts->where('shift_id', $shift->id) as $item)

                        <div
                            class="employee-card
                            bg-white rounded-3xl
                            p-4 border border-blue-100
                            shadow-sm cursor-move"
                            data-user="{{ $item->user->id }}">

                            <div class="flex items-center justify-between">

                                <div>

                                    <p class="font-bold text-gray-800">
                                        {{ $item->user->name }}
                                    </p>

                                    <div class="flex items-center gap-2 mt-2">

                                        <span
                                            class="text-[11px]
                                            bg-blue-100 text-blue-700
                                            px-3 py-1 rounded-full">

                                            {{ $shift->name }}

                                        </span>

                                        <span class="text-[11px] text-gray-500">

                                            {{ $shift->start_time }}
                                            -
                                            {{ $shift->end_time }}

                                        </span>

                                    </div>

                                </div>

                                <div
                                    class="w-12 h-12 rounded-2xl
                                    bg-blue-50 flex items-center justify-center text-xl">

                                    ↕️

                                </div>

                            </div>

                        </div>

                        @empty

                        <div
                            class="border-2 border-dashed border-gray-200
                            rounded-3xl p-8 text-center bg-white">

                            <div class="text-5xl mb-3">
                                💤
                            </div>

                            <p class="text-sm text-gray-400">
                                Belum ada pegawai
                            </p>

                        </div>

                        @endforelse

                    </div>

                </div>

                @endforeach

            </div>

        </div>

    </div>

    <!-- SORTABLE -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script>
        const containers = document.querySelectorAll(
            '.shift-column, #unassigned, #unassigned-mobile'
        );

        containers.forEach(container => {

            new Sortable(container, {

                group: 'shared',

                animation: 180,

                ghostClass: 'opacity-50',

                onAdd: function(evt) {

                    /*
                    |--------------------------------------------------------------------------
                    | PINDAH KE UNASSIGNED
                    |--------------------------------------------------------------------------
                    */

                    if (
                        evt.to.id === 'unassigned' ||
                        evt.to.id === 'unassigned-mobile'
                    ) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | DATA
                    |--------------------------------------------------------------------------
                    */

                    let userId = evt.item.dataset.user;

                    let shiftId = evt.to.dataset.shift;

                    /*
                    |--------------------------------------------------------------------------
                    | AJAX
                    |--------------------------------------------------------------------------
                    */

                    fetch("{{ route('shift.assign') }}", {

                            method: "POST",

                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },

                            body: JSON.stringify({

                                user_id: userId,
                                shift_id: shiftId,
                                shift_date: "{{ $date }}"

                            })

                        })
                        .then(res => res.json())
                        .then(data => {

                            console.log(data);

                        });

                }

            });

        });
    </script>

</x-app-layout>