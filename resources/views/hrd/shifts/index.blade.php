<x-app-layout>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f9;
        }
    </style>

    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">
        <div class="w-full max-w-6xl mx-auto">

            <!-- CONTAINER UTAMA -->
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-6 sm:p-8 space-y-6">

                <!-- HEADER AREA -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-gray-100">
                    <div>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline transition mb-2">
                            ← Kembali ke Dashboard
                        </a>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                            <span>🕒</span> Management Shift Kerja
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Konfigurasi master jam operasional RS Mata Pekanbaru</p>
                    </div>

                    <button onclick="document.getElementById('modal-tambah').classList.remove('hidden')"
                        class="self-start sm:self-center bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-200 transition-all active:scale-95">
                        + Tambah Master Shift
                    </button>
                </div>

                @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-sm font-medium">
                    {{ session('success') }}
                </div>
                @endif

                <!-- LIST SHIFT -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    @foreach($shifts as $shift)
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all">
                        <form action="{{ route('hrd.shifts.update', $shift->id) }}" method="POST">
                            @csrf @method('PUT')

                            <div class="flex flex-col gap-6">
                                <div>
                                    <!-- <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-lg uppercase tracking-wider">ID: {{ $shift->id }}</span> -->
                                    <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-tight">
                                        {{ $shift->name }}
                                    </h3>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 items-end">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase ml-1 mb-1 block">
                                            Masuk
                                        </label>
                                        <input type="text"
                                            name="start_time"
                                            value="{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}"
                                            class="timepicker w-full rounded-xl border-gray-200 bg-gray-50 text-sm font-bold py-2.5 text-center focus:bg-white focus:ring-2 focus:ring-blue-100 transition">
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase ml-1 mb-1 block">
                                            Pulang
                                        </label>
                                        <input type="text"
                                            name="end_time"
                                            value="{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}"
                                            class="timepicker w-full rounded-xl border-gray-200 bg-gray-50 text-sm font-bold py-2.5 text-center focus:bg-white focus:ring-2 focus:ring-blue-100 transition">
                                    </div>

                                    <div class="flex justify-center pb-2">
                                        <label class="flex items-center gap-2 text-xs font-bold text-gray-600 cursor-pointer">
                                            <input type="checkbox"
                                                name="is_overnight"
                                                {{ $shift->is_overnight ? 'checked' : '' }}
                                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-0">
                                            Cross-Day
                                        </label>
                                    </div>

                                    <button type="submit"
                                        class="bg-gray-900 hover:bg-blue-600 text-white rounded-xl text-xs font-bold py-3 transition shadow-md">
                                        Update Shift
                                    </button>
                                </div>

                                <div class="pt-5 border-t border-gray-100">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3 ml-1">
                                        Otoritas Role Pengguna
                                    </p>

                                    <div class="flex flex-wrap gap-2">
                                        @foreach($roles as $role)
                                        <label class="flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all">
                                            <input type="checkbox"
                                                name="roles[]"
                                                value="{{ $role }}"
                                                {{ in_array($role, (array)$shift->allowed_roles) ? 'checked' : '' }}
                                                class="w-3.5 h-3.5 text-blue-600 border-gray-300 rounded focus:ring-0">

                                            <span class="text-xs font-bold text-gray-700 capitalize">
                                                {{ str_replace('_', ' ', str_replace('pj_', '', $role)) }}
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div id="modal-tambah" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50">
        <form action="{{ route('hrd.shifts.store') }}" method="POST" class="bg-white p-8 rounded-3xl w-full max-w-sm shadow-2xl border border-gray-100">
            @csrf

            <div class="mb-6">
                <h2 class="text-lg font-black text-gray-900">Tambah Master Shift</h2>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mt-1">Konfigurasi baru untuk sistem</p>
            </div>

            <div class="space-y-4">
                <input type="text"
                    name="name"
                    placeholder="Nama Shift (Contoh: Shift Siang)"
                    class="w-full border-gray-200 rounded-xl text-sm py-3 px-4 focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition"
                    required>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[9px] font-bold text-gray-400 uppercase ml-1 mb-1 block">Jam Masuk</label>
                        <input type="time" name="start_time" class="w-full border-gray-200 rounded-xl text-sm py-3 px-4" required>
                    </div>
                    <div>
                        <label class="text-[9px] font-bold text-gray-400 uppercase ml-1 mb-1 block">Jam Pulang</label>
                        <input type="time" name="end_time" class="w-full border-gray-200 rounded-xl text-sm py-3 px-4" required>
                    </div>
                </div>

                <div class="pt-2">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Otoritas Role Pengguna</p>
                    <div class="flex flex-wrap gap-2 max-h-[150px] overflow-y-auto p-1">
                        @foreach($roles as $role)
                        <label class="flex items-center gap-2 bg-gray-50 border border-gray-200 px-3 py-2 rounded-xl cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-all">
                            <input type="checkbox"
                                name="roles[]"
                                value="{{ $role }}"
                                class="w-3.5 h-3.5 text-blue-600 border-gray-300 rounded focus:ring-0">
                            <span class="text-xs font-bold text-gray-700 capitalize">
                                {{ str_replace('_', ' ', str_replace('pj_', '', $role)) }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition-all">
                        Simpan Shift Baru
                    </button>
                    <button type="button"
                        onclick="document.getElementById('modal-tambah').classList.add('hidden')"
                        class="w-full text-gray-400 text-xs font-bold hover:text-gray-600 py-3 mt-1">
                        Batal
                    </button>
                </div>
            </div>
        </form>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr(".timepicker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 15
        });
    </script>
</x-app-layout>