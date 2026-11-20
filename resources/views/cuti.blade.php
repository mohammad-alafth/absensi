@extends('layouts.submission')

@section('content')

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

        .c3-page {
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        }
    </style>

    <div class="c3-page min-h-screen bg-[#f8f9ff] px-3 py-4 sm:px-4">

        <div class="w-full max-w-lg mx-auto space-y-4">
            <div class="flex items-center justify-between gap-2 px-1">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-[#1D4ED8] text-xs font-semibold hover:underline">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span> Kembali ke Dashboard
                </a>
                <a href="{{ route('cuti.history') }}" class="text-[#1D4ED8] text-xs font-semibold hover:underline">Riwayat Pengajuan Cuti</a>
            </div>

            <x-flash-message />

            {{-- Kuota aktual dari controller; jangan tampilkan angka pengganti bila data tidak tersedia. --}}
            @php
                $leaveRemaining = $remainingLeave ?? null;
                $leaveUsed = $usedLeave ?? null;
                $leaveTotal = is_numeric($leaveRemaining) && is_numeric($leaveUsed) ? $leaveRemaining + $leaveUsed : null;
                $leavePercent = $leaveTotal && $leaveTotal > 0 ? min(100, round(($leaveRemaining / $leaveTotal) * 100, 1)) : null;
            @endphp
            <section class="bg-white rounded-xl p-4 border border-[#c4c5d7]/50 shadow-sm relative overflow-hidden">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-1.5 text-[#2151da] mb-1">
                            <span class="material-symbols-outlined text-[18px]">medical_information</span>
                            <p class="text-[10px] uppercase tracking-wider font-bold">Ringkasan hak personel</p>
                        </div>
                        <h2 class="text-sm font-semibold text-[#0b1c30]">Ketersediaan Kuota Cuti Tahunan</h2>
                    </div>
                    <span class="material-symbols-outlined text-[#2151da] text-[22px]" aria-hidden="true">event_available</span>
                </div>
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-3xl font-black text-[#0037b0]">{{ $leaveRemaining ?? '—' }} <span class="text-sm font-semibold text-[#434655]">Hari Tersisa</span></h2>
                        <p class="text-[11px] text-[#565e74] mt-1">Terpakai: {{ $leaveUsed ?? '—' }} Hari</p>
                    </div>
                    @if($leavePercent !== null)
                    <div class="w-14 h-14 rounded-full border-4 border-[#e5eeff] flex items-center justify-center text-[11px] font-bold text-[#2151da]">{{ $leavePercent }}%</div>
                    @endif
                </div>
                @if($leavePercent !== null)
                <div class="w-full bg-[#e5eeff] h-2 rounded-full overflow-hidden mt-3"><div class="bg-[#1d4ed8] h-full rounded-full" style="width: {{ $leavePercent }}%"></div></div>
                <div class="grid grid-cols-2 gap-2 mt-3 pt-2 border-t border-[#c4c5d7]/40 text-center">
                    <div><span class="block text-[10px] text-[#565e74]">Terpakai Berjalan</span><strong class="text-xs text-[#0b1c30]">{{ $leaveUsed ?? '—' }} Hari</strong></div>
                    <div class="border-l border-[#c4c5d7]/40"><span class="block text-[10px] text-[#565e74]">Total Hak Terhitung</span><strong class="text-xs text-[#0b1c30]">{{ $leaveTotal ?? '—' }} Hari</strong></div>
                </div>
                @endif
            </section>

            <form id="leaveForm" method="POST" action="{{ route('cuti.store') }}">
                @csrf

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

                    {{-- Form header --}}
                    <div class="border-b border-slate-200 px-4 py-4 bg-white flex items-center justify-between gap-3">
                        <div>
                        <h1 class="text-base font-bold text-slate-800 tracking-wide">Form Permohonan Cuti / Izin</h1>
                            <p class="text-[11px] text-slate-500">RS Mata PBEC</p>
                        </div>
                        <span class="bg-blue-100 text-[#1D4ED8] text-[10px] font-bold px-2.5 py-1 rounded-full">Pengajuan Baru</span>
                    </div>

                    <div class="p-4 sm:p-5 space-y-4">

                        {{-- Info karyawan --}}
                        <section class="rounded-xl border border-[#c4c5d7]/50 bg-white p-3.5 shadow-sm">
                            <div class="flex items-center gap-2 pb-2 mb-3 border-b border-[#c4c5d7]/40">
                                <span class="material-symbols-outlined text-[#2151da] text-[20px]">badge</span>
                                <h2 class="text-xs font-bold text-slate-800">Identitas Pemohon</h2>
                            </div>
                            <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kepada Yth.</label>
                    <input type="text" name="recipient" value="{{ old('recipient') }}" placeholder="Tujuan surat (Contoh: Direktur / HRD)" class="w-full min-h-12 border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-blue-500/10 focus:border-[#1D4ED8] focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pegawai</label>
                                <input type="text" value="{{ auth()->user()->name }}" readonly class="w-full bg-slate-50 border border-slate-200 text-slate-500 rounded-xl px-3 py-2 text-xs cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Induk Karyawan (NIK)</label>
                                <input type="text" value="{{ auth()->user()->nik ?? '-' }}" readonly class="w-full bg-slate-50 border border-slate-200 text-slate-500 rounded-xl px-3 py-2 text-xs cursor-not-allowed">
                            </div>
                            </div>
                        </section>

                        {{-- Unit & Jenis Cuti --}}
                        @php
                            $leaveIcons = ['Tahunan' => 'event_note', 'Besar' => 'calendar_month', 'Sakit' => 'medical_services', 'Melahirkan' => 'child_friendly', 'Menikah' => 'celebration', 'DLL' => 'category'];
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-white p-3.5 grid grid-cols-1 gap-3">
                            <div class="md:col-span-1">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Unit Bagian / Role</label>
                                <input type="text" value="{{ auth()->user()->role_label }}" readonly class="w-full bg-slate-50 border border-slate-200 text-slate-500 rounded-xl px-3 py-2 text-xs cursor-not-allowed">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-2 flex items-center gap-1.5"><span class="material-symbols-outlined text-[#2151da] text-[20px]">category</span>Jenis Cuti / Izin Pendukung</label>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach(['Tahunan', 'Besar', 'Sakit', 'Melahirkan', 'Menikah', 'DLL'] as $type)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="leave_type" value="{{ $type }}" class="hidden peer" {{ old('leave_type') == $type ? 'checked' : '' }}>
                                        <div class="flex items-center gap-2 border border-slate-200 rounded-xl px-2 py-2 hover:border-[#1D4ED8] cursor-pointer transition bg-white text-xs font-medium text-slate-700 peer-checked:bg-[#1D4ED8] peer-checked:border-[#1D4ED8] peer-checked:text-white">
                                            <span class="material-symbols-outlined text-[18px] shrink-0">{{ $leaveIcons[$type] }}</span><span class="truncate">{{ $type }}</span>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Periode Tanggal --}}
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 shadow-sm">
                            <h3 class="text-xs font-bold text-slate-800 mb-3 flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px] text-[#2151da]">calendar_month</span>Periode Tanggal Kalender</h3>

                            <div class="grid grid-cols-2 gap-2.5 items-stretch">

                                <div class="flex flex-col justify-between rounded-xl border border-slate-300 bg-white p-3">
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
                                <input type="date" id="start_date" name="start_date" class="w-full min-h-12 border border-slate-300 rounded-xl px-3 py-2 text-xs bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-[#1D4ED8] focus:outline-none transition">
                                </div>

                                <div class="flex flex-col justify-between rounded-xl border border-slate-300 bg-white p-3">
                                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
                                    <input type="date" id="end_date" name="end_date" class="w-full min-h-12 border border-slate-300 rounded-xl px-3 py-2 text-xs bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-[#1D4ED8] focus:outline-none transition">
                                </div>

                                <div class="col-span-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2.5 flex items-center gap-2">
                                    <span class="text-sm">⏱️</span>
                                    <div class="text-[11px] flex items-center gap-1.5 w-full justify-between">
                                        <span class="text-blue-600 font-semibold whitespace-nowrap">Estimasi Pengajuan:</span>
                                        <span id="hari_output" class="font-black text-slate-800">0 Hari Kerja</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- Alasan --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alasan Keperluan Pengajuan</label>
                            <textarea name="reason" rows="3" placeholder="Tuliskan detail permohonan alasan cuti..." class="w-full border border-slate-300 rounded-xl p-3 text-xs resize-none focus:ring-4 focus:ring-blue-500/10 focus:border-[#1D4ED8] focus:outline-none transition">{{ old('reason') }}</textarea>
                        </div>

                        {{-- Delegasi & Kontak --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-3.5 grid grid-cols-1 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Penerima Delegasi Tugas</label>
                                <input type="text" name="delegate_name" value="{{ old('delegate_name') }}" placeholder="Nama rekan kerja" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-blue-500/10 focus:border-[#1D4ED8] focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">NIK Penerima Delegasi</label>
                                <input type="text" name="delegate_nik" value="{{ old('delegate_nik') }}" placeholder="NIK rekan kerja" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-blue-500/10 focus:border-[#1D4ED8] focus:outline-none transition">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Kontak Darurat</label>
                                <input type="text" name="emergency_contact" value="{{ old('emergency_contact') }}" placeholder="Contoh: 0812XXXXXXXX" class="w-full border border-slate-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-blue-500/10 focus:border-[#1D4ED8] focus:outline-none transition">
                            </div>
                        </div>

                        {{-- Tanda Tangan --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm flex flex-col items-center justify-center">
                            <div class="w-full flex items-center justify-between gap-2 mb-3">
                                <p class="text-xs font-semibold text-slate-700">Tanda Tangan Digital Pemohon</p>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-0.5">Wajib</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mb-3">Klik area di bawah untuk membubuhkan tanda tangan digital Anda</p>
                            <div onclick="openSignatureModal()" class="cursor-pointer group">
                                <img id="signature-preview" src="" class="hidden mx-auto border border-slate-200 rounded-xl bg-white shadow-sm" width="180">
                                <div id="signature-placeholder" class="w-[200px] h-[90px] border-2 border-dashed border-slate-300 rounded-xl flex flex-col items-center justify-center text-xs text-slate-400 bg-slate-50/50 group-hover:bg-blue-50/50 group-hover:border-[#1D4ED8] transition gap-1">
                                    <span class="text-lg">✍️</span>
                                    <span>Klik di sini untuk TTD</span>
                                </div>
                            </div>
                            <input type="hidden" name="employee_signature" id="employee_signature">
                            <p class="mt-3 font-bold text-slate-800 text-xs border-t border-dashed border-slate-200 px-6 pt-1.5">{{ auth()->user()->name }}</p>
                        </div>

                    </div>

                    {{-- Submit button --}}
                    <div class="bg-slate-50/70 border-t border-slate-200 px-4 py-4">
                        <button type="button" id="leaveSubmit" onclick="submitLeave()" class="w-full bg-[#1D4ED8] hover:bg-[#1E40AF] text-white py-3 rounded-xl text-xs font-bold shadow-md transition tracking-wide disabled:opacity-60 disabled:cursor-not-allowed">
                            Kirim Berkas Permohonan Cuti Resmi
                        </button>
                    </div>

                </div>
            </form>

            <x-my-submissions type="leave" :submissions="$leaves ?? []" />

        </div>
    </div>

    {{-- Signature Modal --}}
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
            const form = document.getElementById('leaveForm');
            const submitButton = document.getElementById('leaveSubmit');

            // Jangan buka dialog/kirim ulang saat request masih berjalan.
            if (!form || form.dataset.submitting === '1' || form.dataset.confirming === '1') {
                return;
            }

            const signature = document.getElementById('employee_signature').value;

            if (!signature) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tanda tangan wajib diisi',
                    confirmButtonColor: '#1E40AF'
                });
                return;
            }

            form.dataset.confirming = '1';

            Swal.fire({
                title: 'Kirim pengajuan?',
                    html: `Sisa hak cuti Anda saat ini: <b>{{ $remainingLeave ?? '—' }} Hari</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1E40AF',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) {
                    delete form.dataset.confirming;
                    return;
                }

                // Kunci form segera setelah konfirmasi agar tidak terkirim ganda.
                form.dataset.submitting = '1';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Mengirim...';
                }

                form.submit();
            });
        }

        // Guard tambahan untuk submit implisit (mis. tombol Enter).
        const leaveFormGuard = document.getElementById('leaveForm');

        if (leaveFormGuard) {
            leaveFormGuard.addEventListener('submit', function(event) {
                if (leaveFormGuard.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                leaveFormGuard.dataset.submitting = '1';

                const submitButton = document.getElementById('leaveSubmit');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Mengirim...';
                }
            });
        }

        // Pulihkan tombol bila halaman dibuka kembali dari bfcache.
        window.addEventListener('pageshow', function() {
            const form = document.getElementById('leaveForm');
            const button = document.getElementById('leaveSubmit');

            if (form) {
                delete form.dataset.submitting;
                delete form.dataset.confirming;
            }

            if (button) {
                button.disabled = false;
                button.textContent = 'Kirim Berkas Permohonan Cuti Resmi';
            }
        });

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

@endsection
