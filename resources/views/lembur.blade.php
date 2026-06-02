@php
$pjRole = 'pj_' . auth()->user()->role;
$pjUsers = \App\Models\User::where('role', $pjRole)->get();
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
                                <input type="date" name="overtime_date" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Klasifikasi Hari</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 hover:border-blue-500 cursor-pointer transition bg-white text-xs h-[38px]">
                                        <input type="radio" name="day_type" value="hari_kerja" class="text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                                        <span class="font-medium text-gray-700">Hari Kerja Aktif</span>
                                    </label>
                                    <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 hover:border-blue-500 cursor-pointer transition bg-white text-xs h-[38px]">
                                        <input type="radio" name="day_type" value="hari_libur" class="text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                                        <span class="font-medium text-gray-700">Hari Libur / Off</span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <input type="hidden" name="department" value="{{ auth()->user()->role_label }}">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Uraian Komitmen Tugas Lembur</label>
                        <textarea name="reason" rows="3" placeholder="Tuliskan rincian uraian pekerjaan lembur..." class="w-full border border-gray-300 rounded-xl p-3 text-xs resize-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none"></textarea>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-800 mb-2">Alokasi Waktu Jam Karyawan</h3>
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
                                            <input type="text" id="start_time" name="start_time" placeholder="--:--" class="timepicker w-full text-center border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                                        </td>
                                        <td class="px-3 py-3">
                                            <input type="text" id="end_time" name="end_time" placeholder="--:--" class="timepicker w-full text-center border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
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

        </div>
    </div>

    <div id="signatureModal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4 backdrop-blur-xs">
        <div class="bg-white rounded-2xl w-full max-w-sm p-5 shadow-2xl modal-animate border">
            <h3 class="font-bold text-sm text-center mb-3 text-gray-800">Tulis Tanda Tangan Digital</h3>
            <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                <canvas id="signature-pad" width="340" height="160" class="w-full bg-white"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4">
                <button type="button" onclick="clearSignature()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg font-semibold text-xs transition">
                    Reset Pad
                </button>
                <button type="button" onclick="saveSignature()" class="bg-[#1E40AF] hover:bg-blue-800 text-white py-2 rounded-lg font-semibold text-xs transition shadow-xs">
                    Kunci & Simpan
                </button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

    <script>
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

            function hitungDurasiLembur() {
                const awal = document.getElementById('start_time').value;
                const akhir = document.getElementById('end_time').value;
                const output = document.getElementById('durasi_output');

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

                output.className = "font-black text-slate-800 text-xs";
                output.innerText = `${hasilJam} Jam ${hasilMenit} Menit`;
            }
        });

        function submitOvertime() {
            const signature = document.getElementById('employee_signature').value;

            if (!signature) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tanda tangan wajib diisi',
                    confirmButtonColor: '#1E40AF'
                });
                return;
            }

            Swal.fire({
                title: 'Kirim lembur?',
                text: 'Pastikan kesesuaian jam dinas lembur Anda sudah benar',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1E40AF',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Ajukan',
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

            if (!signaturePad) {
                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgba(255, 255, 255, 0)',
                    penColor: 'rgb(0, 0, 0)'
                });
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear();
            }
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