@extends('layouts.submission')

@section('content')

    <style>
        .c3-page {
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        }
    </style>

    <div class="c3-page min-h-screen bg-[#f8f9ff] px-3 py-4 pb-24 sm:px-5">

        <div class="w-full max-w-lg mx-auto space-y-4">

            <div class="flex items-center justify-between gap-2 px-1">
                <a href="{{ route('dashboard') }}" class="text-[#1D4ED8] font-semibold text-xs hover:underline">← Kembali ke Dashboard</a>
                <a href="{{ route('shift-change.history') }}" class="text-[#1D4ED8] font-semibold text-xs hover:underline">Riwayat Pengajuan</a>
            </div>

            <!-- TITLE CARD -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#1D4ED8] flex items-center justify-center text-2xl shadow-sm text-white">
                        🔄
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">
                            Pengajuan Perubahan Shift
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
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
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5">

                <form action="{{ route('shift-change.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- TANGGAL SHIFT -->
                    <div class="rounded-xl border border-slate-200 bg-white p-3.5">
                        <label for="shift_date" class="block text-sm font-bold text-gray-700 mb-2">
                            📅 Tanggal Shift Yang Ingin Diubah <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                            id="shift_date"
                            name="shift_date"
                            value="{{ old('shift_date', now()->format('Y-m-d')) }}"
                            min="{{ now()->format('Y-m-d') }}"
                            required
                            class="w-full min-h-12 px-4 py-3 rounded-xl border border-slate-300 focus:border-[#1D4ED8] focus:ring-2 focus:ring-blue-200 transition text-sm bg-white">
                        @error('shift_date')
                        <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- DISPLAY CURRENT SHIFT -->
                    <div id="current_shift_box" class="bg-blue-50/70 border border-blue-100 rounded-xl p-3.5 transition-all">
                        <p class="text-[11px] font-semibold text-[#1D4ED8] uppercase tracking-wider">Jadwal Shift Saat Ini</p>
                        <p id="current_shift_name" class="text-base font-bold text-slate-900 mt-1">
                            Pilih tanggal di atas untuk melihat shift
                        </p>
                    </div>

                    <!-- REQUESTED SHIFT -->
                    <div class="rounded-xl border border-slate-200 bg-white p-3.5">
                        <label for="requested_shift_id" class="block text-sm font-bold text-gray-700 mb-2">
                            🔄 Pilih Shift Baru Yang Diinginkan <span class="text-red-500">*</span>
                        </label>
                        <select id="requested_shift_id"
                            name="requested_shift_id"
                            required
                            class="w-full min-h-12 px-4 py-3 rounded-xl border border-slate-300 focus:border-[#1D4ED8] focus:ring-2 focus:ring-blue-200 transition text-sm bg-white">
                            <option value="" disabled selected>-- Pilih Shift Baru --</option>
                            <option value="off" {{ old('requested_shift_id') == 'off' ? 'selected' : '' }} class="font-semibold text-amber-700">
                                Hari Libur / Tidak Ada Shift (Jadwal Kosong)
                            </option>
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
                    <div class="rounded-xl border border-slate-200 bg-white p-3.5">
                        <label for="reason" class="block text-sm font-bold text-gray-700 mb-2">
                            📝 Alasan Perubahan Shift <span class="text-red-500">*</span>
                        </label>
                        <textarea id="reason"
                            name="reason"
                            rows="4"
                            required
                            placeholder="Tuliskan alasan lengkap pengajuan perubahan shift Anda..."
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-[#1D4ED8] focus:ring-2 focus:ring-blue-200 transition text-sm bg-white resize-none">{{ old('reason') }}</textarea>
                        @error('reason')
                        <p class="text-xs text-red-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <div class="pt-2">
                        <button type="submit"
                            class="w-full min-h-14 py-4 bg-[#1D4ED8] hover:bg-[#1E40AF] text-white font-bold rounded-xl shadow-md transition duration-300 active:scale-[0.98] text-sm flex items-center justify-center gap-2">
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
                         currentShiftName.innerHTML = `<span class="text-slate-900">${data.shift_name}</span> <span class="text-xs font-normal text-[#1D4ED8]">(${data.start_time} - ${data.end_time} WIB)</span>`;
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

@endsection
