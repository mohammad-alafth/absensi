<x-app-layout>

    <div class="min-h-screen bg-[#f8f9ff] py-4 px-3 pb-24">

        <div class="max-w-4xl mx-auto">

            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('dashboard') }}" class="text-[#1E40AF] font-semibold text-xs hover:underline">
                    ← Kembali
                </a>
                <a href="{{ route('izin.history') }}" class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-4 py-2 rounded-xl shadow text-xs font-semibold transition">
                    Riwayat Pengajuan Izin
                </a>
            </div>

            <form action="{{ route('izin.store') }}" method="POST" enctype="multipart/form-data" class="bg-white shadow-sm rounded-2xl overflow-hidden border border-slate-200">
                @csrf

                <div class="border-b border-slate-200 px-5 py-4 bg-gray-50/50 flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-bold text-slate-800 tracking-wide">Form Pengajuan Izin</h1>
                        <p class="text-[11px] text-slate-500">Surat permohonan izin karyawan perusahaan</p>
                    </div>
                </div>

                @if(session('success'))
                <div class="px-5 pt-4">
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-2.5 text-xs">
                        {{ session('success') }}
                    </div>
                </div>
                @endif

                @if($errors->any())
                <div class="px-5 pt-4">
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-2.5 text-xs">
                        {{ $errors->first() }}
                    </div>
                </div>
                @endif

                <div class="p-5 md:p-6 space-y-5">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Nama Karyawan</label>
                            <input type="text" value="{{ auth()->user()->name }}" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Departemen</label>
                            <input type="text" value="{{ auth()->user()->role_label }}" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Izin</label>
                            <input type="date" name="tanggal" value="{{ old('tanggal') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Jenis Izin</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                            @foreach([
                            'izin pribadi' => '📝',
                            'sakit' => '🤒',
                            'keperluan keluarga' => '👨‍👩‍👧',
                            'terlambat masuk' => '⏰',
                            'pulang lebih awal' => '🏠',
                            'dinas luar' => '🚗'
                            ] as $jenis => $icon)
                            <label class="cursor-pointer">
                                <input type="radio" name="jenis" value="{{ $jenis }}" class="hidden peer" {{ old('jenis') == $jenis ? 'checked' : '' }}>
                                <div class="rounded-xl border border-slate-200 bg-white p-2.5 flex flex-col items-center justify-center text-center gap-1 transition-all duration-200 hover:border-blue-400 hover:shadow-2xs peer-checked:bg-[#1E40AF] peer-checked:border-[#1E40AF] peer-checked:text-white">
                                    <span class="text-base">{{ $icon }}</span>
                                    <span class="text-[10px] font-bold capitalize leading-tight">{{ $jenis }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-[#f8f9ff] p-3.5 rounded-xl border border-blue-50/50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-stretch">
                            
                            <div class="flex flex-col justify-between">
                                <label class="block text-[11px] font-medium text-slate-700 mb-1">Jam Mulai</label>
                                <input type="text" id="jam_mulai" name="jam_mulai" placeholder="--:--" value="{{ old('jam_mulai') }}" class="timepicker w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 bg-white">
                            </div>

                            <div class="flex flex-col justify-between">
                                <label class="block text-[11px] font-medium text-slate-700 mb-1">Jam Selesai</label>
                                <input type="text" id="jam_selesai" name="jam_selesai" placeholder="--:--" value="{{ old('jam_selesai') }}" class="timepicker w-full rounded-xl border border-slate-300 px-3 py-2 text-xs outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 bg-white">
                            </div>

                            <div class="bg-blue-50/80 border border-blue-100 rounded-xl px-3 flex items-center gap-2 self-end h-[38px] mt-2 md:mt-0">
                                <span class="text-sm">⏱️</span>
                                <div class="text-[11px] flex items-center gap-1.5 w-full justify-between">
                                    <span class="text-blue-600 font-semibold whitespace-nowrap">Estimasi Durasi:</span>
                                    <span id="durasi_output" class="font-black text-slate-800">0 Jam 0 Menit</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Alasan / Keperluan</label>
                        <textarea name="alasan" rows="3" placeholder="Tuliskan alasan pengajuan izin..." class="w-full rounded-xl border border-slate-300 p-3 text-xs resize-none outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">{{ old('alasan') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Berkas Lampiran Pendukung</label>
                        <input type="file" name="lampiran" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs">
                        <p class="text-[10px] text-slate-400 mt-1">Format dokumen: JPG, PNG, PDF (Maksimal ukuran file berkas 2MB)</p>
                    </div>

                </div>

                <div class="border-t border-slate-200 bg-slate-50 px-5 py-3.5">
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-semibold text-center hover:bg-slate-100 transition">
                            Batal
                        </a>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#1E40AF] hover:bg-blue-800 text-white text-xs font-semibold shadow-xs transition">
                            Kirim Pengajuan
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const pickerConfig = {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: 15,
                onChange: hitungDurasi
            };

            const startPicker = flatpickr("#jam_mulai", pickerConfig);
            const endPicker = flatpickr("#jam_selesai", pickerConfig);

            function hitungDurasi() {
                const awal = document.getElementById('jam_mulai').value;
                const akhir = document.getElementById('jam_selesai').value;
                const output = document.getElementById('durasi_output');

                if (!awal || !akhir) {
                    output.innerText = "0 Jam 0 Menit";
                    return;
                }

                const [jamAwal, menitAwal] = awal.split(':').map(Number);
                const [jamAkhir, menitAkhir] = akhir.split(':').map(Number);

                let totalMenitAwal = (jamAwal * 60) + menitAwal;
                let totalMenitAkhir = (jamAkhir * 60) + menitAkhir;

                if (totalMenitAkhir < totalMenitAwal) {
                    output.className = "font-bold text-red-600 text-[10px]";
                    output.innerText = "Jam salah/mundur";
                    return;
                }

                const selisihMenit = totalMenitAkhir - totalMenitAwal;
                const hasilJam = Math.floor(selisihMenit / 60);
                const hasilMenit = selisihMenit % 60;

                output.className = "font-black text-slate-800 text-xs";
                output.innerText = `${hasilJam} Jam ${hasilMenit} Menit`;
            }
        });
    </script>

</x-app-layout>