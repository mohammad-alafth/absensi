<x-app-layout>

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-24">

        <div class="w-full max-w-3xl mx-auto">

            <!-- NAV -->
            <div class="flex justify-between items-center mb-6">
                <a href="{{ route('dashboard') }}"
                    class="text-[#1E40AF] font-semibold text-sm">
                    ← Kembali
                </a>

                <a href="{{ route('cuti.history') }}"
                    class="bg-[#1E40AF] text-white px-4 py-2 rounded-xl text-sm shadow">
                    Riwayat
                </a>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-blue-50 p-5 sm:p-7">

                <!-- TITLE -->
                <h2 class="text-xl sm:text-2xl font-extrabold text-[#1E40AF] mb-6">
                    Pengajuan Cuti
                </h2>

                <!-- SUCCESS -->
                @if(session('success'))
                <div class="mb-4 bg-emerald-50 text-emerald-700 p-3 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
                @endif

                <!-- ERROR -->
                @if($errors->any())
                <div class="mb-4 bg-rose-50 text-rose-700 p-3 rounded-xl text-sm">
                    {{ $errors->first() }}
                </div>
                @endif
                <!-- SISA CUTI -->
                <div class="mb-6 grid grid-cols-2 gap-3">

                    <!-- Sisa -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white p-4 rounded-2xl shadow">
                        <p class="text-xs opacity-80">Sisa Cuti</p>
                        <p class="text-2xl font-bold">
                            {{ $remainingLeave ?? 12 }} Hari
                        </p>
                    </div>

                    <!-- Terpakai -->
                    <div class="bg-white border border-blue-100 p-4 rounded-2xl shadow-sm">
                        <p class="text-xs text-gray-500">Terpakai</p>
                        <p class="text-2xl font-bold text-[#1E40AF]">
                            {{ $usedLeave ?? 3 }} Hari
                        </p>
                    </div>

                </div>

                <form method="POST" action="{{ route('cuti.store') }}" class="space-y-5">
                    @csrf

                    <!-- KEPADA -->
                    <div>
                        <label class="text-xs font-bold text-[#1E40AF] mb-2 block">
                            Kepada Yth.
                        </label>
                        <input type="text" name="recipient" value="{{ old('recipient') }}"
                            class="w-full border border-blue-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400 outline-none"
                            placeholder="Nama atasan / HRD">
                    </div>

                    <!-- TANGGAL -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="text-xs font-bold text-gray-700 mb-2 block">
                                Tanggal Mulai
                            </label>
                            <input type="date" name="start_date" value="{{ old('start_date') }}"
                                class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-700 mb-2 block">
                                Tanggal Selesai
                            </label>
                            <input type="date" name="end_date" value="{{ old('end_date') }}"
                                class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">
                        </div>

                    </div>

                    <!-- RETURN -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 mb-2 block">
                            Tanggal Masuk Kembali
                        </label>
                        <input type="date" name="return_date" value="{{ old('return_date') }}"
                            class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">
                    </div>

                    <!-- JENIS CUTI -->
                    <div>

                        <label class="text-xs font-bold text-gray-700 mb-3 block">
                            Jenis Cuti
                        </label>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">

                            @foreach([
                            'Tahunan' => '📅',
                            'Sakit' => '🤒',
                            'Melahirkan' => '🤰',
                            'Menikah' => '💍',
                            'Keluarga' => '👨‍👩‍👧',
                            'Khusus' => '✨'
                            ] as $type => $icon)

                            <label class="cursor-pointer">

                                <input
                                    type="radio"
                                    name="leave_type"
                                    value="{{ $type }}"
                                    class="hidden peer"
                                    {{ old('leave_type') == $type ? 'checked' : '' }}>

                                <div class="
                flex items-center justify-center gap-2
                p-3 rounded-2xl border-2

                bg-white
                border-gray-200
                text-gray-700

                font-medium text-sm

                transition-all duration-300

                hover:border-blue-400

                peer-checked:bg-blue-800
                peer-checked:border-blue-800
                peer-checked:text-white
                peer-checked:shadow-lg
                peer-checked:scale-105
            ">

                                    <span>{{ $icon }}</span>
                                    <span>{{ $type }}</span>

                                </div>

                            </label>

                            @endforeach

                        </div>

                    </div>

                    <!-- ALASAN -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 mb-2 block">
                            Keperluan
                        </label>
                        <textarea name="reason" rows="3"
                            class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400 resize-none">{{ old('reason') }}</textarea>
                    </div>

                    <!-- DELEGASI -->
                    <div class="border-t pt-5">
                        <h3 class="text-sm font-bold text-gray-800 mb-3">
                            Delegasi Pekerjaan
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <input type="text" name="delegate_name"
                                value="{{ old('delegate_name') }}"
                                placeholder="Nama rekan"
                                class="border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">

                            <input type="text" name="delegate_nik"
                                value="{{ old('delegate_nik') }}"
                                placeholder="NIK rekan"
                                class="border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">

                        </div>
                    </div>

                    <!-- KONTAK -->
                    <div>
                        <label class="text-xs font-bold text-gray-700 mb-2 block">
                            Kontak Darurat
                        </label>
                        <input type="text" name="emergency_contact"
                            value="{{ old('emergency_contact') }}"
                            class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400"
                            placeholder="08xxxx">
                    </div>

                    <!-- BUTTON -->
                    <button type="submit"
                        class="w-full bg-[#1E40AF] hover:bg-[#1e3a8a] text-white py-3 rounded-xl font-semibold text-sm sm:text-base shadow">

                        Ajukan Cuti

                    </button>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>