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

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-4 pb-24">

        <div class="w-full max-w-4xl mx-auto">

            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('dashboard') }}" class="text-[#1E40AF] font-semibold text-xs hover:underline flex items-center gap-1">
                    ← Kembali
                </a>
                <a href="{{ route('cuti.history') }}" class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-4 py-2 rounded-xl shadow text-xs font-semibold transition">
                    Riwayat Pengajuan Cuti
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white rounded-xl p-4 shadow-sm flex items-center justify-between">
                    <p class="text-xs opacity-90 font-medium">Sisa Kuota Cuti</p>
                    <h2 class="text-2xl font-black">{{ $remainingLeave ?? 12 }} <span class="text-sm font-normal opacity-80">Hari</span></h2>
                </div>

                <div class="bg-white rounded-xl border p-4 shadow-xs border-gray-100 flex items-center justify-between">
                    <p class="text-xs font-medium text-gray-500">Total Cuti Terpakai</p>
                    <h2 class="text-2xl font-black text-gray-800">{{ $usedLeave ?? 0 }} <span class="text-sm font-normal text-gray-400">Hari</span></h2>
                </div>
            </div>

            <form id="leaveForm" method="POST" action="{{ route('cuti.store') }}">
                @csrf

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="border-b px-5 py-4 bg-gray-50/50 flex items-center justify-between">
                        <div>
                            <h1 class="text-base font-bold text-gray-800 tracking-wide">FORM PERMOHONAN CUTI / IZIN</h1>
                            <p class="text-[11px] text-gray-400">RS Mata Pekanbaru Eye Center</p>
                        </div>
                    </div>

                    <div class="p-5 md:p-6 space-y-5">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-gray-50 pb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Kepada Yth.</label>
                                <input type="text" name="recipient" value="{{ old('recipient') }}" placeholder="Tujuan surat (Contoh: Direktur / HRD)" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">Nama Pegawai</label>
                                <input type="text" value="{{ auth()->user()->name }}" readonly class="w-full bg-gray-50 border border-gray-200 text-gray-600 rounded-xl px-3 py-2 text-xs cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">Nomor Induk Karyawan (NIK)</label>
                                <input type="text" value="{{ auth()->user()->nik ?? '-' }}" readonly class="w-full bg-gray-50 border border-gray-200 text-gray-600 rounded-xl px-3 py-2 text-xs cursor-not-allowed">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                            <div class="md:col-span-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1.5">Unit Bagian / Role</label>
                                <input type="text" value="{{ auth()->user()->role_label }}" readonly class="w-full bg-gray-50 border border-gray-200 text-gray-600 rounded-xl px-3 py-2 text-xs cursor-not-allowed">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-2">Jenis Cuti / Izin Pendukung</label>
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach(['Tahunan', 'Besar', 'Sakit', 'Melahirkan', 'Menikah', 'DLL'] as $type)
                                    <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 hover:border-blue-500 cursor-pointer transition bg-white text-xs">
                                        <input type="radio" name="leave_type" value="{{ $type }}" class="text-blue-600 focus:ring-blue-500 w-3.5 h-3.5" {{ old('leave_type') == $type ? 'checked' : '' }}>
                                        <span class="font-medium text-gray-700">{{ $type }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="bg-[#f8f9ff] p-3.5 rounded-xl border border-blue-50/50">
                            <h3 class="text-xs font-bold text-gray-800 mb-2 flex items-center gap-1">📅 Periode Tanggal Kalender</h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-stretch">

                                <div class="flex flex-col justify-between">
                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Tanggal Mulai</label>
                                    <input type="date" id="start_date" name="start_date" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                                </div>

                                <div class="flex flex-col justify-between">
                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Tanggal Selesai</label>
                                    <input type="date" id="end_date" name="end_date" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                                </div>

                                <div class="bg-blue-50/80 border border-blue-100 rounded-xl px-3 flex items-center gap-2 self-end h-[38px] mt-2 md:mt-0">
                                    <span class="text-sm">⏱️</span>
                                    <div class="text-[11px] flex items-center gap-1.5 w-full justify-between">
                                        <span class="text-blue-600 font-semibold whitespace-nowrap">Estimasi Pengajuan:</span>
                                        <span id="hari_output" class="font-black text-slate-800">0 Hari Kerja</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Alasan Keperluan Pengajuan</label>
                            <textarea name="reason" rows="3" placeholder="Tuliskan detail permohonan alasan cuti..." class="w-full border border-gray-300 rounded-xl p-3 text-xs resize-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">{{ old('reason') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">Nama Penerima Delegasi Tugas</label>
                                <input type="text" name="delegate_name" value="{{ old('delegate_name') }}" placeholder="Nama rekan kerja" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1.5">NIK Penerima Delegasi</label>
                                <input type="text" name="delegate_nik" value="{{ old('delegate_nik') }}" placeholder="NIK rekan kerja" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Nomor Kontak Darurat</label>
                                <input type="text" name="emergency_contact" value="{{ old('emergency_contact') }}" placeholder="Contoh: 0812XXXXXXXX" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-4 flex flex-col items-center justify-center">
                            <p class="text-xs font-medium text-gray-500 mb-2">Lembar Pengesahan Tanda Tangan Digital Karyawan</p>
                            <div onclick="openSignatureModal()" class="cursor-pointer group">
                                <img id="signature-preview" src="" class="hidden mx-auto border rounded-xl bg-white shadow-xs" width="180">
                                <div id="signature-placeholder" class="w-[180px] h-[80px] border border-dashed border-gray-300 rounded-xl flex items-center justify-center text-xs text-gray-400 bg-gray-50/50 group-hover:bg-gray-50 group-hover:border-blue-400 transition">
                                    Klik di sini untuk TTD
                                </div>
                            </div>
                            <input type="hidden" name="employee_signature" id="employee_signature">
                            <p class="mt-2 font-bold text-gray-800 text-xs border-t px-4 pt-1 border-dashed border-gray-200">{{ auth()->user()->name }}</p>
                        </div>

                    </div>

                    <div class="bg-gray-50/50 border-t px-5 py-3.5">
                        <button type="button" onclick="submitLeave()" class="w-full bg-[#1E40AF] hover:bg-blue-800 text-white py-3 rounded-xl text-xs font-bold shadow-xs transition tracking-wide">
                            Kirim Berkas Permohonan Cuti Resmi
                        </button>
                    </div>

                </div>
            </form>

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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            startDateInput.addEventListener('change', hitungHariCuti);
            endDateInput.addEventListener('change', hitungHariCuti);

            function hitungHariCuti() {
                const startVal = startDateInput.value;
                const endVal = endDateInput.value;
                const output = document.getElementById('hari_output');

                if (!startVal || !endVal) {
                    output.innerText = "0 Hari Kerja";
                    return;
                }

                const tanggalMulai = new Date(startVal);
                const tanggalSelesai = new Date(endVal);

                if (tanggalSelesai < tanggalMulai) {
                    output.className = "font-bold text-red-600 text-[10px]";
                    output.innerText = "Tanggal tidak valid";
                    return;
                }

                const selisihWaktu = tanggalSelesai.getTime() - tanggalMulai.getTime();
                const totalHari = Math.ceil(selisihWaktu / (1000 * 3600 * 24)) + 1;

                output.className = "font-black text-slate-800 text-xs";
                output.innerText = `${totalHari} Hari Kerja`;
            }
        });

        function submitLeave() {
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
                title: 'Kirim pengajuan?',
                html: `Sisa hak cuti Anda saat ini: <b>{{ $remainingLeave ?? 12 }} Hari</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1E40AF',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('leaveForm').submit();
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
            if (signaturePad) {
                signaturePad.clear();
            }
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