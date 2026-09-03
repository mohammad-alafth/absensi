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

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-6xl mx-auto">

            <div class="flex justify-between items-center mb-6">
                <a href="{{ route('dashboard') }}" class="text-[#1E40AF] font-semibold text-sm flex items-center gap-1 hover:underline">
                    ← Kembali
                </a>
                <div class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-5 py-2 rounded-2xl shadow-lg text-sm font-semibold tracking-wide">
                    Riwayat Pengajuan Personel
                </div>
            </div>

            @forelse($years as $year)

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-6">

                <div class="border-b pb-4 mb-5">
                    <h2 class="text-2xl font-bold text-gray-800">Tahun {{ $year }}</h2>
                    <p class="text-sm text-gray-500 mt-1">Daftar rekaman riwayat logistik cuti, izin formal, dan jam lembur kerja</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    <div class="bg-white rounded-2xl border border-blue-50 overflow-hidden flex flex-col shadow-xs">
                        <div class="bg-[#1E40AF] text-white px-4 py-3 flex items-center justify-between">
                            <h3 class="font-bold text-sm">📅 Riwayat Cuti</h3>
                            <span class="bg-white/20 text-[11px] px-2 py-0.5 rounded-full font-bold">{{ count($leaves[$year] ?? []) }} Data</span>
                        </div>

                        <div class="p-3 space-y-3 flex-1">
                            @forelse($leaves[$year] ?? [] as $leave)
                            <div class="border border-blue-50 rounded-xl p-3 bg-[#f8f9ff] hover:shadow-md transition flex flex-col justify-between min-h-[140px]">
                                <div>
                                    <div class="flex justify-between items-start mb-2 gap-2">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs">{{ $leave->leave_type }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium mt-1 bg-white border px-2 py-0.5 rounded-lg inline-block">
                                                {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}
                                            </p>
                                        </div>

                                        @if($leave->status == 'approved')
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Approved</span>
                                        @elseif($leave->status == 'rejected')
                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Rejected</span>
                                        @elseif($leave->status == 'waiting_head')
                                        <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">PJS Head</span>
                                        @elseif($leave->status == 'waiting_director')
                                        <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">PJS Direktur</span>
                                        @elseif($leave->status == 'waiting_medical_service')
                                        <span class="bg-cyan-100 text-cyan-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">PJS Medis</span>
                                        @else
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Pending</span>
                                        @endif
                                    </div>

                                    <div class="text-[11px] text-gray-500 mb-2">
                                        Durasi Hak: <span class="font-bold text-gray-800">{{ $leave->total_days }} Hari Kerja</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="openModal('leave-{{ $leave->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                        📋 Detail
                                    </button>
                                    <a href="{{ route('leaves.download-pdf', $leave->id) }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                        🖨️ Cetak PDF
                                    </a>
                                </div>
                            </div>

                            <div id="leave-{{ $leave->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 p-4 backdrop-blur-xs">
                                <div class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-2xl modal-animate border mx-auto my-8">
                                    <button onclick="closeModal('leave-{{ $leave->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold">✕</button>
                                    <h2 class="text-xl font-black text-[#1E40AF] border-b pb-3 mb-4 flex items-center gap-1.5">📂 Rincian Form Dokumen Cuti</h2>
                                    <div class="grid grid-cols-2 gap-4 text-xs leading-relaxed">
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Kategori Klasifikasi</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $leave->leave_type }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Total Rentang Cuti</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $leave->total_days }} Hari</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Periode Kalender Efektif</p>
                                            <p class="font-bold text-gray-800 mt-0.5">{{ $leave->start_date }} s/d {{ $leave->end_date }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Tanggal Kembali Aktif</p>
                                            <p class="font-bold text-emerald-600 mt-0.5">{{ $leave->return_date }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Kontak Darurat Panggilan</p>
                                            <p class="font-bold text-gray-800 mt-0.5">{{ $leave->emergency_contact ?? '-' }}</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Delegasi Pelaksana Tugas</p>
                                            <p class="font-bold text-indigo-600 mt-0.5">{{ $leave->delegate_name ?? '-' }} <span class="text-gray-400 text-[11px]">({{ $leave->delegate_nik ?? '-' }})</span></p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Uraian Keperluan / Alasan</p>
                                            <p class="font-medium text-gray-700 italic mt-1 bg-white p-2 rounded-lg border-dashed border">{{ $leave->reason }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-6 pt-3 border-t flex gap-2">
                                        <a href="{{ route('leaves.download-pdf', $leave->id) }}" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 rounded-xl text-xs font-bold transition shadow-sm">
                                            ⬇ Download Dokumen PDF Resmi
                                        </a>
                                        <button onclick="closeModal('leave-{{ $leave->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-bold transition">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8">
                                <div class="text-2xl mb-1 text-slate-300">📅</div>
                                <p class="text-[11px] text-gray-400">Belum ada rekaman data cuti</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-blue-50 overflow-hidden flex flex-col shadow-xs">
                        <div class="bg-indigo-600 text-white px-4 py-3 flex items-center justify-between">
                            <h3 class="font-bold text-sm">📝 Riwayat Izin</h3>
                            <span class="bg-white/20 text-[11px] px-2 py-0.5 rounded-full font-bold">{{ count($permissions[$year] ?? []) }} Data</span>
                        </div>

                        <div class="p-3 space-y-3 flex-1">
                            @forelse($permissions[$year] ?? [] as $permission)
                            <div class="border border-blue-50 rounded-xl p-3 bg-[#f8f9ff] hover:shadow-md transition flex flex-col justify-between min-h-[140px]">
                                <div>
                                    <div class="flex justify-between items-start mb-2 gap-2">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs capitalize">{{ $permission->jenis }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium mt-1 bg-white border px-2 py-0.5 rounded-lg inline-block">
                                                📅 {{ \Carbon\Carbon::parse($permission->tanggal)->translatedFormat('d M Y') }}
                                            </p>
                                        </div>

                                        @if($permission->status == 'approved')
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Approved</span>
                                        @elseif($permission->status == 'rejected')
                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Rejected</span>
                                        @else
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Pending</span>
                                        @endif
                                    </div>

                                    <div class="text-[11px] text-gray-500 mb-2">
                                        Alokasi Waktu: <span class="font-bold text-gray-800">{{ $permission->jam_mulai }} - {{ $permission->jam_selesai }} WIB</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="openModal('permission-{{ $permission->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                        📋 Detail
                                    </button>
                                    <a href="{{ route('permissions.download-pdf', $permission->id) }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                        🖨️ Cetak PDF
                                    </a>
                                </div>
                            </div>

                            <div id="permission-{{ $permission->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 p-4 backdrop-blur-xs">
                                <div class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-2xl modal-animate border mx-auto my-8">
                                    <button onclick="closeModal('permission-{{ $permission->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold">✕</button>
                                    <h2 class="text-xl font-black text-indigo-600 border-b pb-3 mb-4 flex items-center gap-1.5">📂 Rincian Form Surat Keperluan Izin</h2>
                                    <div class="grid grid-cols-2 gap-4 text-xs leading-relaxed">
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Hari / Tanggal Izin</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $permission->tanggal }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Jenis Pengajuan</p>
                                            <p class="font-bold text-indigo-600 mt-0.5 text-sm capitalize">{{ $permission->jenis }}</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Waktu Durasi Izin</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $permission->jam_mulai }} s/d {{ $permission->jam_selesai }} WIB</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Lampiran Berkas / Surat Keterangan Pendukung</p>
                                            <div class="mt-1.5">
                                                @if($permission->lampiran)
                                                <a href="{{ asset('storage/'.$permission->lampiran) }}" target="_blank" class="inline-flex items-center gap-1 bg-white border px-3 py-1.5 rounded-lg font-bold text-indigo-600 hover:bg-indigo-50 shadow-xs transition">
                                                    📁 Lihat Lembar Lampiran Dokumen
                                                </a>
                                                @else
                                                <p class="font-semibold text-gray-400 italic">Berkas berkas lampiran tidak disertakan</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Alasan Konteks Izin</p>
                                            <p class="font-medium text-gray-700 italic mt-1 bg-white p-2 rounded-lg border-dashed border">{{ $permission->alasan }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-6 pt-3 border-t flex gap-2">
                                        <a href="{{ route('permissions.download-pdf', $permission->id) }}" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 rounded-xl text-xs font-bold transition shadow-sm">
                                            ⬇ Download Dokumen PDF Resmi
                                        </a>
                                        <button onclick="closeModal('permission-{{ $permission->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-bold transition">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-10">
                                <div class="text-2xl mb-1 text-slate-300">📝</div>
                                <p class="text-[11px] text-gray-400">Belum ada rekaman data izin</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-blue-50 overflow-hidden flex flex-col shadow-xs">
                        <div class="bg-cyan-600 text-white px-4 py-3 flex items-center justify-between">
                            <h3 class="font-bold text-sm">⏰ Riwayat Lembur</h3>
                            <span class="bg-white/20 text-[11px] px-2 py-0.5 rounded-full font-bold">{{ count($overtimes[$year] ?? []) }} Data</span>
                        </div>

                        <div class="p-3 space-y-3 flex-1">
                            @forelse($overtimes[$year] ?? [] as $overtime)
                            <div class="border border-blue-50 rounded-xl p-3 bg-[#f8f9ff] hover:shadow-md transition flex flex-col justify-between min-h-[140px]">
                                <div>
                                    <div class="flex justify-between items-start mb-2 gap-2">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs">
                                                {{ \Carbon\Carbon::parse($overtime->overtime_date)->translatedFormat('d M Y') }}
                                            </p>
                                            <p class="text-[10px] text-gray-400 font-medium mt-1 bg-white border px-2 py-0.5 rounded-lg inline-block">
                                                ⏱️ {{ $overtime->start_time }} - {{ $overtime->end_time }}
                                            </p>
                                        </div>

                                        @if($overtime->status == 'approved')
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Approved</span>
                                        @elseif($overtime->status == 'rejected')
                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Rejected</span>
                                        @else
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap">Pending</span>
                                        @endif
                                    </div>

                                    <div class="text-[11px] text-gray-500 mb-2 line-clamp-2 bg-white border p-1.5 rounded-lg text-gray-600 font-medium leading-tight">
                                        {{ $overtime->reason }}
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="openModal('overtime-{{ $overtime->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                        📋 Detail
                                    </button>
                                    <a href="{{ route('overtimes.download-pdf', $overtime->id) }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-1.5 rounded-xl text-[11px] font-bold transition flex items-center justify-center gap-1">
                                        🖨️ Cetak PDF
                                    </a>
                                </div>
                            </div>

                            <div id="overtime-{{ $overtime->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/50 p-4 backdrop-blur-xs">
                                <div class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-2xl modal-animate border mx-auto my-8">
                                    <button onclick="closeModal('overtime-{{ $overtime->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold">✕</button>
                                    <h2 class="text-xl font-black text-cyan-600 border-b pb-3 mb-4 flex items-center gap-1.5">📂 Rincian Form Perintah Kerja Lembur</h2>
                                    <div class="grid grid-cols-2 gap-4 text-xs leading-relaxed">
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Hari / Tanggal Lembur</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $overtime->overtime_date }}</p>
                                        </div>
                                        <div class="bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Total Volume Waktu</p>
                                            <p class="font-bold text-cyan-600 mt-0.5 text-sm">{{ $overtime->total_hours }} Jam Kerja</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Alokasi Jam Operasional</p>
                                            <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $overtime->start_time }} s/d {{ $overtime->end_time }} WIB</p>
                                        </div>
                                        <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                            <p class="text-gray-400 font-medium">Uraian Alasan / Konteks Tugas Lembur</p>
                                            <p class="font-medium text-gray-700 italic mt-1 bg-white p-2 rounded-lg border-dashed border">{{ $overtime->reason }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-6 pt-3 border-t flex gap-2">
                                        <a href="{{ route('overtimes.download-pdf', $overtime->id) }}" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 rounded-xl text-xs font-bold transition shadow-sm">
                                            ⬇ Download Dokumen PDF Resmi
                                        </a>
                                        <button onclick="closeModal('overtime-{{ $overtime->id }}')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-bold transition">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-10">
                                <div class="text-2xl mb-1 text-slate-300">⏰</div>
                                <p class="text-[11px] text-gray-400">Belum ada rekaman data lembur</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                </div>

            </div>
            @empty

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center max-w-md mx-auto mt-12">
                <div class="text-5xl mb-4">📂</div>
                <h3 class="text-base font-bold text-gray-800">Riwayat Pengajuan Kosong</h3>
                <p class="text-xs text-gray-400 mt-1">Anda belum memiliki berkas log transaksional cuti, izin, atau lembur pada sistem.</p>
            </div>

            @endforelse

        </div>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
        }

        window.onclick = function(event) {
            document.querySelectorAll('[id^="leave-"], [id^="permission-"], [id^="overtime-"]').forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }
            });
        }
    </script>

</x-app-layout>