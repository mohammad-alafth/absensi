<x-app-layout>

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-3xl mx-auto">

            <!-- NAV -->
            <div class="flex justify-between items-center mb-6">

                <a href="{{ route('dashboard') }}"
                    class="text-[#1E40AF] font-semibold text-sm">
                    ← Kembali
                </a>

                <a href="{{ route('lembur.history') }}"
                    class="bg-[#1E40AF] text-white px-4 py-2 rounded-xl text-sm shadow">
                    Riwayat
                </a>

            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl shadow-sm border border-blue-50 p-5 sm:p-7">

                <!-- TITLE -->
                <h2 class="text-xl sm:text-2xl font-extrabold text-[#1E40AF] mb-2">
                    Pengajuan Lembur
                </h2>

                <p class="text-sm text-gray-500 mb-6">
                    Form Surat Perintah Lembur
                </p>

                <!-- SUCCESS -->
                @if(session('success'))

                <div class="mb-4 bg-emerald-50 text-emerald-700 p-3 rounded-xl text-sm">
                    {{ session('success') }}
                </div>

                @endif

                <!-- ERROR -->
                @if ($errors->any())

                <div class="mb-4 bg-rose-50 text-rose-700 p-3 rounded-xl text-sm">

                    <ul class="list-disc pl-5">

                        @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

                @endif

                <form
                    action="{{ route('lembur.store') }}"
                    method="POST"
                    class="space-y-5">

                    @csrf

                    <!-- DEPARTEMEN -->
                    <div>

                        <label class="text-xs font-bold text-[#1E40AF] mb-2 block">
                            Departemen
                        </label>

                        <input
                            type="text"
                            name="department"
                            value="{{ old('department') }}"
                            placeholder="Contoh: IT Programmer"
                            class="w-full border border-blue-100 rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400 outline-none">

                    </div>

                    <!-- JENIS LEMBUR -->
                    <div>

                        <label class="text-xs font-bold text-gray-700 mb-3 block">
                            Jenis Lembur
                        </label>

                        <div class="grid grid-cols-2 gap-3">

                            <!-- HARI KERJA -->
                            <label class="cursor-pointer">

                                <input
                                    type="radio"
                                    name="day_type"
                                    value="hari_kerja"
                                    class="hidden peer"
                                    {{ old('day_type') == 'hari_kerja' ? 'checked' : '' }}>

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

                                    <span>💼</span>
                                    <span>Hari Kerja</span>

                                </div>

                            </label>

                            <!-- HARI LIBUR -->
                            <label class="cursor-pointer">

                                <input
                                    type="radio"
                                    name="day_type"
                                    value="hari_libur"
                                    class="hidden peer"
                                    {{ old('day_type') == 'hari_libur' ? 'checked' : '' }}>

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

                                    <span>📅</span>
                                    <span>Hari Libur</span>

                                </div>

                            </label>

                        </div>

                    </div>

                    <!-- TANGGAL -->
                    <div>

                        <label class="text-xs font-bold text-gray-700 mb-2 block">
                            Tanggal Lembur
                        </label>

                        <input
                            type="date"
                            name="overtime_date"
                            required
                            value="{{ old('overtime_date') }}"
                            class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">

                    </div>

                    <!-- JAM -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>

                            <label class="text-xs font-bold text-gray-700 mb-2 block">
                                Jam Mulai
                            </label>

                            <input
                                type="time"
                                name="start_time"
                                required
                                value="{{ old('start_time') }}"
                                class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">

                        </div>

                        <div>

                            <label class="text-xs font-bold text-gray-700 mb-2 block">
                                Jam Berakhir
                            </label>

                            <input
                                type="time"
                                name="end_time"
                                required
                                value="{{ old('end_time') }}"
                                class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400">

                        </div>

                    </div>

                    <!-- ALASAN -->
                    <div>

                        <label class="text-xs font-bold text-gray-700 mb-2 block">
                            Uraian Tugas Lembur
                        </label>

                        <textarea
                            name="reason"
                            rows="4"
                            required
                            placeholder="Contoh: Maintenance server, fixing bug, deploy aplikasi, dll"
                            class="w-full border rounded-xl p-3 text-sm focus:ring-2 focus:ring-blue-400 resize-none">{{ old('reason') }}</textarea>

                    </div>

                    <!-- BUTTON -->
                    <button
                        type="submit"
                        class="w-full bg-[#1E40AF] hover:bg-[#1e3a8a] text-white py-3 rounded-xl font-semibold text-sm sm:text-base shadow">

                        Kirim Pengajuan Lembur

                    </button>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>