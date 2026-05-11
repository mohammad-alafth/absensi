<x-app-layout>

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-3xl mx-auto">

            <!-- NAV -->
            <div class="flex justify-between items-center mb-6">

                <a href="{{ route('dashboard') }}"
                    class="text-[#1E40AF] font-semibold text-sm">

                    ← Kembali

                </a>

                <a href="{{ route('izin.history') }}"
                    class="bg-[#1E40AF] text-white px-4 py-2 rounded-xl text-sm shadow">

                    Riwayat

                </a>

            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl shadow-sm border border-blue-50 p-5 sm:p-7">

                <!-- TITLE -->
                <h2 class="text-xl sm:text-2xl font-extrabold text-[#1E40AF] mb-6">

                    Form Pengajuan Izin

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

                <!-- FORM -->
                <form
                    action="{{ route('izin.store') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-5">

                    @csrf

                    <!-- TANGGAL -->
                    <div>

                        <label class="text-xs font-bold text-gray-700 mb-2 block">
                            Tanggal
                        </label>

                        <input
                            type="date"
                            name="tanggal"
                            value="{{ old('tanggal') }}"
                            class="w-full border border-blue-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400 outline-none">

                    </div>

                    <!-- JENIS IZIN -->
                    <div>

                        <label class="text-xs font-bold text-gray-700 mb-3 block">
                            Jenis Izin
                        </label>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">

                            @foreach([
                            'izin pribadi' => '📝',
                            'sakit' => '🤒',
                            'keperluan keluarga' => '👨‍👩‍👧',
                            'terlambat masuk' => '⏰',
                            'pulang lebih awal' => '🏠',
                            'dinas luar' => '🚗'
                            ] as $jenis => $icon)

                            <label class="cursor-pointer">

                                <input
                                    type="radio"
                                    name="jenis"
                                    value="{{ $jenis }}"
                                    class="hidden peer"
                                    {{ old('jenis') == $jenis ? 'checked' : '' }}>

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

                                    <span class="capitalize">
                                        {{ $jenis }}
                                    </span>

                                </div>

                            </label>

                            @endforeach

                        </div>

                    </div>

                    <!-- JAM -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>

                            <label class="text-xs font-bold text-gray-700 mb-2 block">
                                Jam Mulai
                            </label>

                            <input
                                type="time"
                                name="jam_mulai"
                                value="{{ old('jam_mulai') }}"
                                class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">

                        </div>

                        <div>

                            <label class="text-xs font-bold text-gray-700 mb-2 block">
                                Jam Selesai
                            </label>

                            <input
                                type="time"
                                name="jam_selesai"
                                value="{{ old('jam_selesai') }}"
                                class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">

                        </div>

                    </div>

                    <!-- ALASAN -->
                    <div>

                        <label class="text-xs font-bold text-gray-700 mb-2 block">
                            Alasan / Keperluan
                        </label>

                        <textarea
                            name="alasan"
                            rows="4"
                            placeholder="Tuliskan alasan pengajuan izin..."
                            class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400 resize-none">{{ old('alasan') }}</textarea>

                    </div>

                    <!-- LAMPIRAN -->
                    <div>

                        <label class="text-xs font-bold text-gray-700 mb-2 block">
                            Lampiran
                        </label>

                        <input
                            type="file"
                            name="lampiran"
                            class="w-full border rounded-xl p-3 text-sm bg-white">

                        <p class="text-xs text-gray-400 mt-2">
                            Format: JPG, PNG, PDF (Max 2MB)
                        </p>

                    </div>

                    <!-- BUTTON -->
                    <button
                        type="submit"
                        class="w-full bg-[#1E40AF] hover:bg-[#1e3a8a]
                        text-white py-3 rounded-xl font-semibold
                        text-sm sm:text-base shadow transition">

                        Kirim Pengajuan

                    </button>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>