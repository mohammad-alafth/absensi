<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9ff;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-animate {
            animation: fadeIn 0.2s ease-out forwards;
        }
    </style>

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-6xl mx-auto">

            <div class="flex justify-between items-center mb-6">
                <a href="{{ route('cuti') }}" class="text-[#1E40AF] font-semibold text-sm flex items-center gap-1 hover:underline">
                    ← Kembali
                </a>
                <div class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-5 py-2 rounded-2xl shadow-lg text-sm font-semibold tracking-wide">
                    Riwayat Pengajuan Cuti
                </div>
            </div>

            @if($leaves->count() > 0)

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-6">
                
                <div class="border-b pb-4 mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Log Aktivitas Cuti</h2>
                        <p class="text-sm text-gray-500 mt-1">Daftar rekaman riwayat logistik pengajuan cuti resmi mandiri Anda</p>
                    </div>
                    <span class="bg-blue-50 text-[#1E40AF] text-xs px-3 py-1 rounded-full font-bold border border-blue-100">
                        {{ $leaves->count() }} Dokumen
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                    @foreach($leaves as $leave)
                    <div x-data="{ showDetail: false }" class="border border-blue-50 rounded-2xl p-4 bg-[#f8f9ff] hover:shadow-md transition flex flex-col justify-between min-h-[150px]">
                        
                        <div>
                            <div class="flex justify-between items-start mb-2 gap-2">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm">
                                        {{ $leave->leave_type }}
                                    </h3>
                                    <p class="text-[11px] text-gray-400 font-medium mt-1 bg-white border px-2 py-0.5 rounded-lg inline-block">
                                        📅 {{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}
                                    </p>
                                </div>

                                @if($leave->status == 'pending')
                                    <span class="bg-yellow-100 text-yellow-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">Pending PJ</span>
                                @elseif($leave->status == 'waiting_hrd')
                                    <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">Waiting HRD</span>
                                @elseif($leave->status == 'waiting_head')
                                    <span class="bg-orange-100 text-orange-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">PJS Head</span>
                                @elseif($leave->status == 'waiting_director')
                                    <span class="bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">PJS Direktur</span>
                                @elseif($leave->status == 'waiting_medical_service')
                                    <span class="bg-cyan-100 text-cyan-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">PJS Medis</span>
                                @elseif($leave->status == 'approved')
                                    <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">Approved</span>
                                @elseif($leave->status == 'rejected')
                                    <span class="bg-red-100 text-red-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">Rejected</span>
                                @endif
                            </div>

                            <div class="text-[11px] text-gray-500 mb-3">
                                Durasi Hak: <span class="font-bold text-gray-800">{{ $leave->total_days }} Hari Kerja</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <button @click="showDetail = true" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1">
                                📋 Detail
                            </button>
                            
                            @if($leave->pdf_file)
                            <a href="{{ asset('storage/' . $leave->pdf_file) }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1">
                                🖨️ Cetak PDF
                            </a>
                            @else
                            <button disabled class="bg-gray-100 text-gray-400 py-2 rounded-xl text-xs font-bold cursor-not-allowed flex items-center justify-center gap-1">
                                🔒 No PDF
                            </button>
                            @endif
                        </div>

                        <div x-show="showDetail" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4 backdrop-blur-xs" style="display:none;">
                            <div @click.away="showDetail = false" class="bg-white rounded-3xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 relative shadow-2xl modal-animate border mx-auto my-8">
                                <button @click="showDetail = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold">✕</button>
                                
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
                                        <p class="font-bold text-gray-800 mt-0.5">{{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d M Y') }}</p>
                                    </div>
                                    <div class="bg-slate-50 p-3 rounded-xl border">
                                        <p class="text-gray-400 font-medium">Tanggal Kembali Aktif</p>
                                        <p class="font-bold text-emerald-600 mt-0.5">{{ $leave->return_date ? \Carbon\Carbon::parse($leave->return_date)->translatedFormat('d M Y') : '-' }}</p>
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
                                    @if($leave->address_during_leave)
                                    <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                        <p class="text-gray-400 font-medium">Alamat Selama Masa Cuti</p>
                                        <p class="font-medium text-gray-700 mt-0.5">{{ $leave->address_during_leave }}</p>
                                    </div>
                                    @endif
                                </div>

                                @if($leave->pj_note || $leave->hrd_note)
                                <div class="mt-4 grid grid-cols-1 gap-3 text-xs">
                                    @if($leave->pj_note)
                                    <div class="p-3 rounded-xl border {{ $leave->pj_status == 'rejected' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-blue-50 border-blue-100 text-blue-700' }}">
                                        <p class="font-bold mb-1">💬 {{ $leave->pj_status == 'rejected' ? 'Alasan Penolakan PJ' : 'Catatan Penanggung Jawab' }}</p>
                                        <p class="text-gray-700 italic">"{{ $leave->pj_note }}"</p>
                                        <p class="text-[10px] text-gray-400 mt-1.5 font-semibold">Oleh: {{ $leave->pjApprover->name ?? '-' }}</p>
                                    </div>
                                    @endif

                                    @if($leave->hrd_note)
                                    <div class="p-3 rounded-xl border {{ $leave->hrd_status == 'rejected' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-emerald-50 border-emerald-100 text-emerald-700' }}">
                                        <p class="font-bold mb-1">💬 {{ $leave->hrd_status == 'rejected' ? 'Alasan Penolakan HRD' : 'Catatan HRD Verifikator' }}</p>
                                        <p class="text-gray-700 italic">"{{ $leave->hrd_note }}"</p>
                                        <p class="text-[10px] text-gray-400 mt-1.5 font-semibold">Oleh: {{ $leave->hrdApprover->name ?? '-' }}</p>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <div class="mt-6 pt-3 border-t flex gap-2">
                                    @if($leave->pdf_file)
                                    <a href="{{ asset('storage/' . $leave->pdf_file) }}" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 rounded-xl text-xs font-bold transition shadow-sm">
                                        ⬇ Download Dokumen PDF Resmi
                                    </a>
                                    @endif
                                    <button @click="showDetail = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-bold transition {{ !$leave->pdf_file ? 'w-full' : '' }}">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                    @endforeach

                </div>

            </div>

            @else

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center max-w-md mx-auto mt-12">
                <div class="text-5xl mb-4">📅</div>
                <h3 class="text-base font-bold text-gray-800">Belum Ada Riwayat Cuti</h3>
                <p class="text-xs text-gray-400 mt-1 mb-5">Anda belum memiliki berkas log transaksional pengajuan jatah cuti pada sistem.</p>
                <a href="{{ route('cuti') }}" class="inline-block bg-[#1E40AF] hover:bg-[#1e3a8a] text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                    🚀 Buat Pengajuan Sekarang
                </a>
            </div>

            @endif

        </div>
    </div>

</x-app-layout>