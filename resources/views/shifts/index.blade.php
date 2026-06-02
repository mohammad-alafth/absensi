<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9ff;
        }
    </style>

    <div class="min-h-screen bg-[#f8f9ff] p-3 sm:p-4 pb-20">

        <div class="w-full max-w-[95%] mx-auto">

            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline transition">
                    ← Kembali
                </a>
                <div class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-4 py-1.5 rounded-xl shadow-xs text-xs font-bold">
                    Papan Penjadwalan Shift
                </div>
            </div>

            <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="text-xs">
                    <span class="text-gray-400 font-medium">Lokasi Operasional:</span>
                    <span class="text-slate-800 font-bold ml-1">RS Mata Pekanbaru Eye Center</span>
                </div>
                <form method="GET" class="flex items-center gap-2">
                    <label class="text-xs font-bold text-gray-500 whitespace-nowrap">Tanggal Kontrol:</label>
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="rounded-xl border-gray-300 shadow-2xs text-xs py-1 px-2.5 focus:border-[#1E40AF] focus:ring-1 focus:ring-[#1E40AF]/20">
                </form>
            </div>

            <div class="hidden xl:grid grid-cols-5 gap-4">

                <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden flex flex-col">
                    <div class="bg-slate-800 text-white px-4 py-3 flex items-center justify-between">
                        <h2 class="font-bold text-xs tracking-wide">👥 Belum Dijadwalkan</h2>
                        <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-md font-bold">Pool</span>
                    </div>

                    <div id="unassigned" class="p-3 space-y-2 min-h-[480px] bg-slate-50/50 flex-1 overflow-y-auto">
                        @foreach($employees as $employee)
                        @php
                        $assigned = $employeeShifts->where('user_id', $employee->id)->first();
                        @endphp

                        @if(!$assigned)
                        <div class="employee-card group bg-white border border-slate-200 rounded-xl p-2.5 cursor-move shadow-2xs hover:border-blue-400 hover:shadow-xs transition duration-200" data-user="{{ $employee->id }}">
                            <div class="flex items-center justify-between gap-1.5">
                                <div class="truncate">
                                    <p class="font-bold text-gray-800 text-xs truncate">{{ $employee->name }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Ready to deploy</p>
                                </div>
                                <span class="text-gray-300 text-xs select-none">☰</span>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>

                @foreach($shifts as $shift)
                <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden flex flex-col">

                    <div class="text-white px-4 py-2.5 flex items-center justify-between 
                        @if(strtolower($shift->name) == 'pagi') bg-gradient-to-r from-amber-500 to-orange-500
                        @elseif(strtolower($shift->name) == 'malam') bg-gradient-to-r from-indigo-800 to-blue-950
                        @else bg-gradient-to-r from-cyan-600 to-blue-600 @endif">
                        <div>
                            <h2 class="font-black text-xs tracking-wide capitalize flex items-center gap-1">
                                <span>{{ strtolower($shift->name) == 'pagi' ? '🌤️' : (strtolower($shift->name) == 'malam' ? '🌙' : '⏰') }}</span>
                                {{ $shift->name }}
                            </h2>
                            <p class="text-[10px] opacity-85 font-mono mt-0.5">
                                {{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}
                            </p>
                        </div>
                    </div>

                    <div class="shift-column p-3 space-y-2 min-h-[480px] bg-slate-50/20 flex-1 overflow-y-auto" data-shift="{{ $shift->id }}">
                        @forelse($employeeShifts->where('shift_id', $shift->id) as $item)
                        <div class="employee-card group bg-white border border-blue-50 rounded-xl p-2.5 cursor-move shadow-2xs hover:border-blue-400 transition duration-200" data-user="{{ $item->user->id }}">
                            <div class="flex items-center justify-between gap-1">
                                <div class="truncate">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <p class="font-bold text-gray-800 text-xs truncate max-w-[100px]">{{ $item->user->name }}</p>

                                        @if(isset($approvedPermissions[$item->user->id]))
                                        <span class="text-[8px] bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded font-black">IZIN</span>
                                        @endif

                                        @if(isset($approvedLeaves[$item->user->id]))
                                        <span class="text-[8px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-black">CUTI</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5 capitalize">Shift {{ $shift->name }}</p>
                                </div>
                                <span class="text-gray-300 text-xs select-none">☰</span>
                            </div>
                        </div>
                        @empty
                        <div class="border border-dashed border-slate-200 rounded-xl py-8 text-center text-gray-300 bg-slate-50/30 flex flex-col items-center justify-center h-full my-auto">
                            <span class="text-2xl mb-1 opacity-50">👥</span>
                            <p class="text-[10px] font-medium">Kosong</p>
                        </div>
                        @endforelse
                    </div>

                </div>
                @endforeach

            </div>

            <div class="xl:hidden space-y-3">

                <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
                    <div class="bg-slate-800 text-white px-4 py-2.5 flex items-center justify-between">
                        <h2 class="font-bold text-xs">👥 Belum Dijadwalkan (Pool)</h2>
                        <span class="text-[10px] opacity-70">Geser ke bawah</span>
                    </div>

                    <div id="unassigned-mobile" class="p-3 flex flex-row gap-2 overflow-x-auto min-h-[70px] bg-slate-50/50">
                        @foreach($employees as $employee)
                        @php
                        $assigned = $employeeShifts->where('user_id', $employee->id)->first();
                        @endphp

                        @if(!$assigned)
                        <div class="employee-card bg-white border border-slate-200 rounded-xl p-2 flex-shrink-0 min-w-[130px] cursor-move shadow-2xs" data-user="{{ $employee->id }}">
                            <p class="font-bold text-gray-800 text-xs truncate">{{ $employee->name }}</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Unassigned</p>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>

                @foreach($shifts as $shift)
                <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">

                    <div class="text-white px-4 py-2 flex items-center justify-between
                        @if(strtolower($shift->name) == 'pagi') bg-gradient-to-r from-orange-400 to-amber-500
                        @elseif(strtolower($shift->name) == 'malam') bg-gradient-to-r from-indigo-700 to-blue-900
                        @else bg-gradient-to-r from-cyan-500 to-blue-600 @endif">
                        <h2 class="font-bold text-xs capitalize">{{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})</h2>
                    </div>

                    <div class="shift-column p-3 bg-slate-50/30 grid grid-cols-2 gap-2 min-h-[90px]" data-shift="{{ $shift->id }}">
                        @forelse($employeeShifts->where('shift_id', $shift->id) as $item)
                        <div class="employee-card bg-white border border-slate-200 rounded-xl p-2 cursor-move shadow-2xs" data-user="{{ $item->user->id }}">
                            <div class="flex items-center gap-1 justify-between">
                                <span class="font-bold text-gray-800 text-xs truncate">{{ $item->user->name }}</span>
                                @if(isset($approvedPermissions[$item->user->id])) <span class="text-[7px] bg-yellow-100 text-yellow-700 px-1 rounded font-bold">IZIN</span> @endif
                                @if(isset($approvedLeaves[$item->user->id])) <span class="text-[7px] bg-red-100 text-red-700 px-1 rounded font-bold">CUTI</span> @endif
                            </div>
                        </div>
                        @empty
                        <p class="text-[10px] text-gray-400 italic col-span-2 text-center my-auto py-2">Tidak ada penugasan pegawai</p>
                        @endforelse
                    </div>

                </div>
                @endforeach

            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        async function loadShiftData() {
            try {
                const response = await fetch("{{ route('shift.data') }}?date={{ $date }}");
                const data = await response.json();

                // Kosongkan semua kolom shift
                document.querySelectorAll('.shift-column').forEach(column => {
                    column.innerHTML = '';
                });

                data.employeeShifts.forEach(item => {
                    let badge = '';
                    if (data.permissions[item.user_id]) {
                        badge = `<span class="text-[8px] bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded font-black">IZIN</span>`;
                    }
                    if (data.leaves[item.user_id]) {
                        badge = `<span class="text-[8px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-black">CUTI</span>`;
                    }

                    const card = `
                <div class="employee-card group bg-white border border-blue-50 rounded-xl p-2.5 cursor-move shadow-2xs hover:border-blue-400 transition duration-200" data-user="${item.user.id}">
                    <div class="flex items-center justify-between gap-1">
                        <div class="truncate">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <p class="font-bold text-gray-800 text-xs truncate max-w-[100px]">${item.user.name}</p>
                                ${badge}
                            </div>
                            <p class="text-[10px] text-gray-400 mt-0.5 capitalize">Shift ${item.shift.name}</p>
                        </div>
                        <span class="text-gray-300 text-xs select-none">☰</span>
                    </div>
                </div>`;

                    const column = document.querySelector(`.shift-column[data-shift="${item.shift_id}"]`);
                    if (column) {
                        column.innerHTML += card;
                    }
                });

                initSortable();
            } catch (error) {
                console.error("Error loading shift data:", error);
            }
        }

        function initSortable() {
            const containers = document.querySelectorAll('.shift-column, #unassigned, #unassigned-mobile');
            containers.forEach(container => {
                if (container.sortableInitialized) return;
                container.sortableInitialized = true;

                new Sortable(container, {
                    group: 'shared',
                    animation: 150,
                    ghostClass: 'opacity-40',
                    onEnd: async function(evt) {
                        const item = evt.item;
                        const to = evt.to;
                        const from = evt.from;

                        // 1. Jika tidak pindah tempat, tidak perlu request
                        if (to === from) return;

                        let userId = item.dataset.user;
                        let shiftId = to.dataset.shift || null; // null jika ke Pool

                        try {
                            // 2. Efek visual loading
                            to.style.opacity = '0.5';

                            // 3. Kirim request ke server
                            const response = await fetch("{{ route('shift.assign') }}", {
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
                            });

                            const result = await response.json();

                            // 4. Refresh data setelah sukses
                            if (result.success) {
                                await loadShiftData();
                            } else {
                                alert('Gagal menyimpan perubahan');
                            }
                        } catch (error) {
                            console.error(error);
                            alert('Terjadi kesalahan koneksi');
                        } finally {
                            // 5. Kembalikan opasitas
                            to.style.opacity = '1';
                        }
                    }
                });
            });
        }

        initSortable();
    </script>
</x-app-layout>