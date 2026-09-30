@php
$pjRole = 'pj_' . auth()->user()->role;
$pjUsers = \App\Models\User::where('role', $pjRole)->get();

/*
|--------------------------------------------------------------------------
| MODE "SAMPAI SELESAI" (LEMBUR HARI LIBUR)
|--------------------------------------------------------------------------
| Controller mengirim $openEnded dari OvertimePunchService::openEndedAvailability().
| Mode ini hanya tersedia saat hari ini benar-benar tanpa jadwal kerja reguler,
| karena pengiriman pengajuan langsung mencatat absen mulai (GPS + selfie).
*/
$openEnded = $openEnded ?? ['available' => false, 'reason' => '', 'date' => null];
@endphp

<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9ff;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-animate {
            animation: fadeIn 0.2s ease-out forwards;
        }
    </style>

    <div class="min-h-screen bg-[#f8f9ff] py-4 px-3 pb-24">

        <div class="max-w-4xl mx-auto">

            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('dashboard') }}" class="text-[#1E40AF] font-semibold text-xs hover:underline">
                    ← Kembali
                </a>
                <a href="{{ route('lembur.history') }}" class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-4 py-2 rounded-xl shadow text-xs font-semibold transition">
                    Riwayat Pengajuan Lembur
                </a>
            </div>

            <x-flash-message />

            <form id="overtimeForm" action="{{ route('lembur.store') }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                @csrf

                <div class="border-b bg-gray-50/50 px-5 py-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-base font-bold text-gray-800 tracking-wide">Surat Perintah Lembur (SPL)</h1>
                        <p class="text-[11px] text-gray-400">RS Mata Pekanbaru Eye Center</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 font-medium">Unit Departemen</p>
                        <p class="text-xs font-bold text-[#1E40AF] capitalize">{{ auth()->user()->role_label }}</p>
                    </div>
                </div>

                <div class="p-5 md:p-6 space-y-5">

                    <div>
                        <h3 class="text-xs font-bold text-gray-800 mb-2.5">Informasi Acuan Lembur</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">

                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Tanggal SPL</label>
                                <input type="date" name="overtime_date"
                                       value="{{ old('overtime_date', $openEnded['available'] ? $openEnded['date'] : '') }}"
                                       class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                                @if($openEnded['available'])
                                <p class="text-[10px] text-amber-600 mt-1 leading-tight">Mode "sampai selesai" hanya berlaku untuk lembur hari ini.</p>
                                @endif
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Klasifikasi Hari</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 hover:border-blue-500 cursor-pointer transition bg-white text-xs h-[38px]">
                                        <input type="radio" name="day_type" value="hari_kerja" @checked(old('day_type') === 'hari_kerja') class="text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                                        <span class="font-medium text-gray-700">Hari Kerja Aktif</span>
                                    </label>
                                    <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 hover:border-blue-500 cursor-pointer transition bg-white text-xs h-[38px]">
                                        <input type="radio" name="day_type" value="hari_libur" @checked(old('day_type') === 'hari_libur') class="text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                                        <span class="font-medium text-gray-700">Hari Libur / Off</span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <input type="hidden" name="department" value="{{ auth()->user()->role_label }}">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Uraian Komitmen Tugas Lembur</label>
                        <textarea name="reason" rows="3" placeholder="Tuliskan rincian uraian pekerjaan lembur..." class="w-full border border-gray-300 rounded-xl p-3 text-xs resize-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">{{ old('reason') }}</textarea>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-800 mb-2">Alokasi Waktu Jam Karyawan</h3>

                        {{-- Pilihan mode pengisian: rencana jam selesai, atau "sampai selesai"
                             (khusus hari tanpa jadwal kerja; absen mulai tercatat saat kirim). --}}
                        @if($openEnded['available'])
                        <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50/70 px-3 py-3 space-y-2">
                            <p class="text-[11px] font-medium text-amber-800 leading-relaxed">🕒 {{ $openEnded['reason'] }}</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <label class="flex items-start gap-2 border border-emerald-200 rounded-xl px-3 py-2 bg-white cursor-pointer text-[11px] transition">
                                    <input type="radio" name="pengisian_mode" value="sampai_selesai" checked
                                           onchange="setPengisianMode('sampai_selesai')"
                                           class="mt-0.5 text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5">
                                    <span>
                                        <span class="block font-bold text-gray-800">Sampai selesai (absen awal tercatat saat kirim)</span>
                                        <span class="block text-gray-500 mt-0.5">Tanpa Jam Berakhir. Absen mulai (GPS + selfie) tercatat saat pengajuan dikirim, jam selesai diambil dari absen pulang.</span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-2 border border-gray-200 rounded-xl px-3 py-2 bg-white cursor-pointer text-[11px] transition">
                                    <input type="radio" name="pengisian_mode" value="rencana"
                                           onchange="setPengisianMode('rencana')"
                                           class="mt-0.5 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                                    <span>
                                        <span class="block font-bold text-gray-800">Rencanakan jam selesai</span>
                                        <span class="block text-gray-500 mt-0.5">Isi Jam Mulai & Jam Berakhir seperti biasa, misalnya lembur yang memang berakhir pada jam tertentu.</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        @else
                        <p class="mb-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-[11px] text-gray-500 leading-relaxed">
                            ℹ️ {{ $openEnded['reason'] }}
                        </p>
                        @endif

                        <div class="overflow-x-auto border border-gray-200 rounded-xl bg-white shadow-2xs">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2.5 text-center font-bold w-12">No</th>
                                        <th class="px-3 py-2.5 text-left font-bold">Nama & NIK</th>
                                        <th class="px-3 py-2.5 text-left font-bold">Jabatan / Role</th>
                                        <th class="px-3 py-2.5 text-center font-bold w-32">Jam Mulai</th>
                                        <th class="px-3 py-2.5 text-center font-bold w-32">Jam Berakhir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700">
                                    <tr>
                                        <td class="px-3 py-3 text-center font-medium">1</td>
                                        <td class="px-3 py-3 font-semibold text-gray-900">
                                            {{ auth()->user()->name }}
                                            <span class="block text-[10px] font-normal text-gray-400 mt-0.5">NIK: {{ auth()->user()->nik ?? '-' }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-gray-500 font-medium">{{ auth()->user()->role_label }}</td>
                                        <td class="px-3 py-3">
                                            <input type="text" id="start_time" name="start_time" value="{{ old('start_time') }}" placeholder="--:--" class="timepicker w-full text-center border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                                        </td>
                                        <td class="px-3 py-3">
                                            <input type="text" id="end_time" name="end_time" value="{{ $openEnded['available'] ? '' : old('end_time') }}" placeholder="--:--" class="timepicker w-full text-center border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                                            <span id="end_time_locked" class="hidden mt-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-center text-[10px] font-bold text-emerald-700">
                                                sampai selesai
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-blue-50/80 border border-blue-100 rounded-xl px-4 py-2 flex items-center justify-between h-[38px]">
                        <div class="flex items-center gap-2 text-[11px]">
                            <span>⏱️</span>
                            <span class="text-blue-600 font-bold">Estimasi Durasi Lembur:</span>
                        </div>
                        <span id="durasi_output" class="font-black text-slate-800 text-xs">0 Jam 0 Menit</span>
                    </div>

                    {{-- Bukti absen mulai: wajib pada mode "sampai selesai" karena
                         pengiriman pengajuan sekaligus mencatat absen mulai. --}}
                    <x-overtime-capture />

                    <p id="openEndedNote" class="hidden text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-3 py-2 leading-relaxed">
                        Volume jam lembur dihitung dari jam nyata: mulai saat pengajuan dikirim, selesai saat Anda
                        menekan "Selesai Lembur" pada kartu absen di bawah. Bila lupa absen pulang, PJ/HRD dapat
                        melakukan koreksi.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">

                        <div class="flex flex-col items-center justify-center border-r border-dashed border-gray-100 last:border-0">
                            <p class="text-[11px] font-medium text-gray-500 mb-1.5">Hormat Saya (Pemohon)</p>
                            <div onclick="openSignatureModal()" class="cursor-pointer group">
                                <img id="signature-preview" src="" class="hidden mx-auto border rounded-xl bg-white shadow-2xs" width="160">
                                <div id="signature-placeholder" class="w-[160px] h-[75px] border border-dashed border-gray-300 rounded-xl flex items-center justify-center text-[11px] text-gray-400 bg-gray-50/50 group-hover:bg-gray-50 group-hover:border-blue-400 transition">
                                    Goreskan TTD
                                </div>
                            </div>
                            <input type="hidden" name="employee_signature" id="employee_signature">
                            <p class="mt-1.5 font-bold text-gray-800 text-xs">{{ auth()->user()->name }}</p>
                        </div>

                        <div class="flex flex-col items-center justify-center">
                            <p class="text-[11px] font-medium text-gray-500 mb-1.5">Disetujui Oleh (Verifikator)</p>
                            <div class="w-[160px] h-[75px] border border-gray-200 rounded-xl bg-gray-50/50 flex items-center justify-center text-[10px] font-medium text-gray-400 italic">
                                Waiting Flow Approval
                            </div>
                            <div class="mt-1.5 text-center">
                                @foreach($pjUsers as $pj)
                                <p class="font-bold text-gray-700 text-xs inline-block mx-0.5">[{{ $pj->name }}]</p>
                                @endforeach
                            </div>
                        </div>

                    </div>

                </div>

                <div class="bg-gray-50/50 border-t px-5 py-3.5">
                    <button type="button" onclick="submitOvertime()" class="w-full bg-[#1E40AF] hover:bg-blue-800 text-white py-3 rounded-xl text-xs font-bold shadow-xs transition tracking-wide">
                        Kirim Formulir Pengajuan Lembur Resmi
                    </button>
                </div>

            </form>

            {{-- Absen lembur realtime: bukti GPS + selfie, volume jam dari jam nyata --}}
            <x-overtime-punch-card :state="$punchState ?? null" />

            <x-my-submissions type="overtime" :submissions="$overtimes ?? []" />

        </div>
    </div>

    <div id="signatureModal" class="hidden fixed inset-0 bg-black/50 z-50 overflow-y-auto p-4 backdrop-blur-xs">
        <div class="bg-white rounded-3xl w-full max-w-sm max-h-[90vh] overflow-y-auto p-5 shadow-2xl modal-animate border border-gray-100 mx-auto my-8">
            <div class="flex justify-between items-center mb-3">
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeSignatureModal()" class="text-xs font-bold text-gray-500 hover:text-indigo-600 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                        <span>←</span> Kembali
                    </button>
                    <h3 class="font-bold text-sm text-gray-800">Tanda Tangan Pemohon</h3>
                </div>
                <button type="button" onclick="closeSignatureModal()" class="text-xl text-gray-400 hover:text-gray-700">
                    ×
                </button>
            </div>
            <div class="border-2 border-dashed border-gray-300 rounded-2xl overflow-hidden bg-white shadow-inner">
                <canvas id="signature-pad" class="w-full h-44 bg-white touch-none" style="touch-action: none;"></canvas>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-4">
                <button type="button" onclick="clearSignature()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold text-xs transition active:scale-95">
                    🔄 Reset
                </button>
                <button type="button" onclick="closeSignatureModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2.5 rounded-xl font-bold text-xs transition active:scale-95">
                    ← Kembali
                </button>
                <button type="button" onclick="saveSignature()" class="bg-[#1E40AF] hover:bg-blue-800 text-white py-2.5 rounded-xl font-bold text-xs transition shadow-md active:scale-95">
                    ✓ Simpan
                </button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>


    <script>
        /*
        |--------------------------------------------------------------------------
        | MODE PENGISIAN SPL
        |--------------------------------------------------------------------------
        | "sampai selesai" (hari tanpa jadwal kerja) mengosongkan Jam Berakhir dan
        | mewajibkan bukti GPS + selfie sebelum form dikirim, karena pengiriman
        | pengajuan sekaligus mencatat absen mulai di jam server.
        */
        const openEndedAllowed = @json($openEnded['available']);

        let overtimePunchMode = openEndedAllowed ? 'sampai_selesai' : 'rencana';

        function isOpenEndedMode() {
            return overtimePunchMode === 'sampai_selesai';
        }

        function hitungDurasiLembur() {
            const awal = document.getElementById('start_time').value;
            const akhir = document.getElementById('end_time').value;
            const output = document.getElementById('durasi_output');

            if (isOpenEndedMode()) {
                output.className = "font-black text-emerald-700 text-xs";
                output.innerText = "Sampai selesai (dari absen pulang)";
                return;
            }

            output.className = "font-black text-slate-800 text-xs";

            if (!awal || !akhir) {
                output.innerText = "0 Jam 0 Menit";
                return;
            }

            const [jamAwal, menitAwal] = awal.split(':').map(Number);
            const [jamAkhir, menitAkhir] = akhir.split(':').map(Number);

            let totalMenitAwal = (jamAwal * 60) + menitAwal;
            let totalMenitAkhir = (jamAkhir * 60) + menitAkhir;

            // Jika jam lembur melewati tengah malam (cross-day overtime)
            if (totalMenitAkhir < totalMenitAwal) {
                totalMenitAkhir += 24 * 60;
            }

            const selisihMenit = totalMenitAkhir - totalMenitAwal;
            const hasilJam = Math.floor(selisihMenit / 60);
            const hasilMenit = selisihMenit % 60;

            output.innerText = `${hasilJam} Jam ${hasilMenit} Menit`;
        }

        function setPengisianMode(mode) {
            overtimePunchMode = (mode === 'sampai_selesai' && openEndedAllowed) ? 'sampai_selesai' : 'rencana';

            const openEnded = isOpenEndedMode();
            const endTime = document.getElementById('end_time');

            // Jam Berakhir tidak dikirim pada mode "sampai selesai" (disabled = diabaikan browser)
            endTime.disabled = openEnded;
            endTime.classList.toggle('bg-gray-100', openEnded);
            endTime.classList.toggle('text-gray-400', openEnded);

            if (openEnded) {
                endTime.value = '';
            }

            document.getElementById('end_time_locked').classList.toggle('hidden', !openEnded);
            document.getElementById('openEndedNote').classList.toggle('hidden', !openEnded);

            if (openEnded) {
                overtimeCapture.show();
            } else {
                overtimeCapture.hide();
                overtimeCapture.reset();
            }

            hitungDurasiLembur();
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Konfigurasi Flatpickr Korporat 24 Jam
            const pickerConfig = {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: 15,
                onChange: hitungDurasiLembur
            };

            flatpickr("#start_time", pickerConfig);
            flatpickr("#end_time", pickerConfig);

            // Kondisi awal mengikuti mode default: hari libur tanpa jadwal = "sampai selesai".
            setPengisianMode(overtimePunchMode);
        });

        function submitOvertime() {
            const signature = document.getElementById('employee_signature').value;
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;

            if (!isOpenEndedMode() && (!startTime || !endTime)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Jam lembur belum lengkap',
                    text: 'Isi Jam Mulai dan Jam Berakhir rencana lembur anda.',
                    confirmButtonColor: '#1E40AF'
                });
                return;
            }

            if (isOpenEndedMode() && !startTime) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Jam Mulai wajib diisi',
                    text: 'Absen mulai akan dicatat pada jam server saat pengajuan dikirim.',
                    confirmButtonColor: '#1E40AF'
                });
                return;
            }

            if (isOpenEndedMode() && !overtimeCapture.ready()) {
                Swal.fire({
                    icon: 'error',
                    title: 'GPS + selfie wajib lengkap',
                    text: 'Pengajuan hari libur langsung mencatat absen mulai. Tunggu lokasi terbaca lalu ambil selfie bukti terlebih dahulu.',
                    confirmButtonColor: '#1E40AF'
                });
                return;
            }

            if (!signature) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tanda tangan wajib diisi',
                    confirmButtonColor: '#1E40AF'
                });
                return;
            }

            Swal.fire({
                title: isOpenEndedMode() ? 'Catat absen awal sekarang?' : 'Kirim lembur?',
                text: isOpenEndedMode()
                    ? 'Absen mulai tercatat memakai jam server saat ini dan jam selesai diambil dari absen pulang Anda.'
                    : 'Pastikan kesesuaian jam dinas lembur Anda sudah benar',
                icon: isOpenEndedMode() ? 'info' : 'question',
                showCancelButton: true,
                confirmButtonColor: '#1E40AF',
                cancelButtonColor: '#ef4444',
                confirmButtonText: isOpenEndedMode() ? 'Ya, Catat Absen Awal' : 'Ya, Ajukan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('overtimeForm').submit();
                }
            });
        }

        let signaturePad;

        function openSignatureModal() {
            const modal = document.getElementById('signatureModal');
            modal.classList.remove('hidden');
            const canvas = document.getElementById('signature-pad');

            setTimeout(() => {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = 176 * ratio;
                canvas.getContext("2d").scale(ratio, ratio);

                if (!signaturePad) {
                    signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgba(255, 255, 255, 0)',
                        penColor: 'rgb(0, 0, 0)'
                    });
                } else {
                    signaturePad.clear();
                }
            }, 100);
        }

        function closeSignatureModal() {
            document.getElementById('signatureModal').classList.add('hidden');
        }

        function clearSignature() {
            if (signaturePad) signaturePad.clear();
        }

        function saveSignature() {
            if (!signaturePad || signaturePad.isEmpty()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kanvas masih kosong',
                    confirmButtonColor: '#1E40AF'
                });
                return;
            }

            const signatureData = signaturePad.toDataURL();
            document.getElementById('employee_signature').value = signatureData;

            const preview = document.getElementById('signature-preview');
            preview.src = signatureData;
            preview.classList.remove('hidden');

            document.getElementById('signature-placeholder').classList.add('hidden');
            document.getElementById('signatureModal').classList.add('hidden');
        }
    </script>

</x-app-layout>