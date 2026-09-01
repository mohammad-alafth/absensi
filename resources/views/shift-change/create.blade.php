<x-app-layout>

    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-indigo-50 to-cyan-50 p-4 pb-28">

        <div class="max-w-2xl mx-auto">

            <!-- HEADER BAR -->
            <div class="flex items-center justify-between mb-5">

                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2
                    bg-white/80 backdrop-blur-md border border-white/50
                    hover:border-indigo-300 hover:bg-indigo-50
                    text-gray-700 hover:text-indigo-700
                    px-4 py-2 rounded-2xl
                    shadow-sm transition text-sm font-semibold">

                    <span class="text-base">←</span>
                    Kembali
                </a>

                <a href="{{ route('shift-change.history') }}"
                    class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500
                    hover:from-indigo-700 hover:via-purple-700 hover:to-pink-600
                    text-white px-5 py-2 rounded-2xl
                    shadow-lg text-sm font-semibold transition active:scale-95 flex items-center gap-2">
                    <span>📋</span> Riwayat Pengajuan
                </a>

            </div>

            <!-- TITLE CARD -->
            <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-xl border border-white/50 p-6 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center text-3xl shadow-lg text-white">
                        🔄
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            Pengajuan Perubahan Shift
                        </h1>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Isi formulir berikut untuk mengujikan perubahan jadwal shift Anda
                        </p>
                    </div>
                </div>
            </div>

            <!-- ALERT ERROR / SUCCESS -->
            @if(session('error'))
            <div class="mb-5 bg-red-100/90 backdrop-blur border border-red-300 text-red-700 px-5 py-4 rounded-2xl shadow-sm text-sm font-medium">
                {{ session('error') }}
            </div>
            @endif

            @if(session('success'))
            <div class="mb-5 bg-emerald-100/90 backdrop-blur border border-emerald-300 text-emerald-700 px-5 py-4 rounded-2xl shadow-sm text-sm font-medium">
                {{ session('success') }}
            </div>
            @endif

            <!-- FORM CARD -->
            <div class="bg-white/90 backdrop-blur-md rounded-3xl shadow-xl border border-white/60 p-6 sm:p-8">

                <form action="{{ route('shift-change.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- TANGGAL SHIFT -->
                    <div>
                        <label for="shift_date" class="block text-sm font-bold text-gray-700 mb-2">
                            📅 Tanggal Shift Yang Ingin Diubah <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                            id="shift_date"
                            name="shift_date"
                            value="{{ old('shift_date', now()->format('Y-m-d')) }}"
                            min="{{ now()->format('Y-m-d') }}"
                            required
                            class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition text-sm bg-gray-50/50">
                        @error('shift_date')
                        <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- DISPLAY CURRENT SHIFT -->
                    <div id="current_shift_box" class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 transition-all">
                        <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider">Jadwal Shift Saat Ini</p>
                        <p id="current_shift_name" class="text-lg font-bold text-indigo-900 mt-1">
                            Pilih tanggal di atas untuk melihat shift
                        </p>
                    </div>

                    <!-- REQUESTED SHIFT -->
                    <div>
                        <label for="requested_shift_id" class="block text-sm font-bold text-gray-700 mb-2">
                            🔄 Pilih Shift Baru Yang Diinginkan <span class="text-red-500">*</span>
                        </label>
                        <select id="requested_shift_id"
                            name="requested_shift_id"
                            required
                            class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition text-sm bg-gray-50/50">
                            <option value="" disabled selected>-- Pilih Shift Baru --</option>
                            @foreach($availableShifts as $shift)
                            <option value="{{ $shift->id }}" {{ old('requested_shift_id') == $shift->id ? 'selected' : '' }}>
                                {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}) {{ $shift->is_overnight ? '🌙' : '☀️' }}
                            </option>
                            @endforeach
                        </select>
                        @error('requested_shift_id')
                        <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- ALASAN PERUBAHAN SHIFT -->
                    <div>
                        <label for="reason" class="block text-sm font-bold text-gray-700 mb-2">
                            📝 Alasan Perubahan Shift <span class="text-red-500">*</span>
                        </label>
                        <textarea id="reason"
                            name="reason"
                            rows="4"
                            required
                            placeholder="Tuliskan alasan lengkap pengajuan perubahan shift Anda..."
                            class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition text-sm bg-gray-50/50 resize-none">{{ old('reason') }}</textarea>
                        @error('reason')
                        <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="pt-2">
                        <button type="submit"
                            class="w-full py-4 bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 hover:from-indigo-700 hover:via-blue-700 hover:to-cyan-600 text-white font-bold rounded-2xl shadow-xl shadow-indigo-200 hover:shadow-2xl transition duration-300 active:scale-[0.98] text-base flex items-center justify-center gap-2">
                            <span>🚀</span> Kirim Pengajuan Perubahan Shift
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shiftDateInput = document.getElementById('shift_date');
            const currentShiftName = document.getElementById('current_shift_name');

            function checkShift() {
                const dateVal = shiftDateInput.value;
                if (!dateVal) return;

                currentShiftName.innerHTML = '<span class="animate-pulse">Memuat data shift...</span>';

                fetch(`{{ route('shift-change.get-shift') }}?date=${dateVal}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.has_shift) {
                        currentShiftName.innerHTML = `<span class="text-indigo-900">${data.shift_name}</span> <span class="text-xs font-normal text-indigo-600">(${data.start_time} - ${data.end_time} WIB)</span>`;
                    } else {
                        currentShiftName.innerHTML = `<span class="text-amber-700">${data.shift_name}</span>`;
                    }
                })
                .catch(() => {
                    currentShiftName.innerHTML = '<span class="text-gray-500">Belum ada data shift di tanggal ini</span>';
                });
            }

            shiftDateInput.addEventListener('change', checkShift);
            checkShift();
        });
    </script>

</x-app-layout>
