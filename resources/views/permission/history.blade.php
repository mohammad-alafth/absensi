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
                <a href="{{ route('izin') }}" class="text-[#1E40AF] font-semibold text-sm flex items-center gap-1 hover:underline">
                    ← Kembali
                </a>
                <div class="bg-gradient-to-r from-[#1E40AF] to-blue-500 text-white px-5 py-2 rounded-2xl shadow-lg text-sm font-semibold tracking-wide">
                    Riwayat Pengajuan Izin
                </div>
            </div>

            @if($permissions->count() > 0)

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-6">

                <div class="border-b pb-4 mb-5 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Log Aktivitas Izin</h2>
                        <p class="text-sm text-gray-500 mt-1">Daftar rekaman riwayat logistik pengajuan izin formal mandiri Anda</p>
                    </div>
                    <span class="bg-indigo-50 text-indigo-700 text-xs px-3 py-1 rounded-full font-bold border border-indigo-100">
                        {{ $permissions->count() }} Dokumen
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                    @foreach($permissions as $item)
                    <div x-data="{ showDetail: false }" class="border border-blue-50 rounded-2xl p-4 bg-[#f8f9ff] hover:shadow-md transition flex flex-col justify-between min-h-[150px]">

                        <div>
                            <div class="flex justify-between items-start mb-2 gap-2">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm capitalize">
                                        {{ $item->jenis }}
                                    </h3>
                                    <p class="text-[11px] text-gray-400 font-medium mt-1 bg-white border px-2 py-0.5 rounded-lg inline-block">
                                        📅 {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                    </p>
                                </div>

                                @if($item->status == 'pending')
                                <span class="bg-yellow-100 text-yellow-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">Pending PJ</span>
                                @elseif($item->status == 'waiting_hrd')
                                <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">Waiting HRD</span>
                                @elseif($item->status == 'waiting_head')
                                <span class="bg-orange-100 text-orange-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">PJS Head</span>
                                @elseif($item->status == 'waiting_director')
                                <span class="bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">PJS Direktur</span>
                                @elseif($item->status == 'waiting_medical_service')
                                <span class="bg-cyan-100 text-cyan-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">PJS Medis</span>
                                @elseif($item->status == 'approved')
                                <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">Approved</span>
                                @elseif($item->status == 'rejected')
                                <span class="bg-red-100 text-red-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase whitespace-nowrap">Rejected</span>
                                @endif
                            </div>

                            <div class="text-[11px] text-gray-500 mb-3">
                                Alokasi Waktu: <span class="font-bold text-gray-800">{{ $item->jam_mulai ?? '-' }} s/d {{ $item->jam_selesai ?? '-' }} WIB</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <button @click="showDetail = true" class="bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1">
                                📋 Detail
                            </button>

                            @if($item->pdf_file)
                            <a href="{{ asset('storage/' . $item->pdf_file) }}" target="_blank" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1">
                                🖨️ Cetak PDF
                            </a>
                            @else
                            <button disabled class="bg-gray-100 text-gray-400 py-2 rounded-xl text-xs font-bold cursor-not-allowed flex items-center justify-center gap-1">
                                🔒 No PDF
                            </button>
                            @endif
                        </div>

                        <div x-show="showDetail" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs" style="display:none;">
                            <div @click.away="showDetail = false" class="bg-white rounded-3xl w-full max-w-lg p-6 relative shadow-2xl modal-animate border">
                                <button @click="showDetail = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-lg transition font-bold">✕</button>

                                <h2 class="text-xl font-black text-indigo-600 border-b pb-3 mb-4 flex items-center gap-1.5">📂 Rincian Form Surat Keperluan Izin</h2>

                                <div class="grid grid-cols-2 gap-4 text-xs leading-relaxed">
                                    <div class="bg-slate-50 p-3 rounded-xl border">
                                        <p class="text-gray-400 font-medium">Hari / Tanggal Izin</p>
                                        <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}</p>
                                    </div>
                                    <div class="bg-slate-50 p-3 rounded-xl border">
                                        <p class="text-gray-400 font-medium">Jenis Klasifikasi Pengajuan</p>
                                        <p class="font-bold text-indigo-600 mt-0.5 text-sm capitalize">{{ $item->jenis }}</p>
                                    </div>
                                    <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                        <p class="text-gray-400 font-medium">Waktu Durasi Izin</p>
                                        <p class="font-bold text-gray-800 mt-0.5 text-sm">{{ $item->jam_mulai ?? '-' }} s/d {{ $item->jam_selesai ?? '-' }} WIB</p>
                                    </div>
                                    <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                        <p class="text-gray-400 font-medium">Lampiran Berkas / Surat Keterangan Pendukung</p>
                                        <div class="mt-1.5">
                                            @if($item->lampiran)
                                            <a href="{{ asset('storage/'.$item->lampiran) }}" target="_blank" class="inline-flex items-center gap-1 bg-white border px-3 py-1.5 rounded-lg font-bold text-indigo-600 hover:bg-indigo-50 shadow-xs transition">
                                                📁 Lihat Lembar Lampiran Dokumen
                                            </a>
                                            @else
                                            <p class="font-semibold text-gray-400 italic">Berkas berkas lampiran tidak disertakan</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-span-2 bg-slate-50 p-3 rounded-xl border">
                                        <p class="text-gray-400 font-medium">Alasan Konteks Keperluan Izin</p>
                                        <p class="font-medium text-gray-700 italic mt-1 bg-white p-2 rounded-lg border-dashed border">{{ $item->alasan }}</p>
                                    </div>
                                </div>

                                @if(
                                $item->pj_note ||
                                $item->hrd_note ||
                                $item->head_note ||
                                $item->director_note
                                )
                                <div class="mt-4 grid grid-cols-1 gap-3 text-xs">
                                    @if($item->pj_note)
                                    <div class="p-3 rounded-xl border {{ $item->pj_status == 'rejected' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-blue-50 border-blue-100 text-blue-700' }}">
                                        <p class="font-bold mb-1">💬 {{ $item->pj_status == 'rejected' ? 'Alasan Penolakan PJ' : 'Catatan Penanggung Jawab' }}</p>
                                        <p class="text-gray-700 italic">"{{ $item->pj_note }}"</p>
                                        <p class="text-[10px] text-gray-400 mt-1.5 font-semibold">Oleh: {{ $item->pjApprover->name ?? '-' }}</p>
                                    </div>
                                    @endif

                                    @if($item->hrd_note)
                                    <div class="p-3 rounded-xl border {{ $item->hrd_status == 'rejected' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-emerald-50 border-emerald-100 text-emerald-700' }}">
                                        <p class="font-bold mb-1">💬 {{ $item->hrd_status == 'rejected' ? 'Alasan Penolakan HRD' : 'Catatan HRD Verifikator' }}</p>
                                        <p class="text-gray-700 italic">"{{ $item->hrd_note }}"</p>
                                        <p class="text-[10px] text-gray-400 mt-1.5 font-semibold">Oleh: {{ $item->hrdApprover->name ?? '-' }}</p>
                                    </div>
                                    @endif
                                    @if($item->head_note)
                                    <div class="p-3 rounded-xl border {{ $item->head_status == 'rejected' ? 'bg-red-50 border-red-100 text-red-700' : 'bg-orange-50 border-orange-100 text-orange-700' }}">
                                        <p class="font-bold mb-1">💬 {{ $item->head_status == 'rejected' ? 'Alasan Penolakan Head' : 'Catatan Head' }} </p>
                                        <p class="text-gray-700 italic">"{{ $item->head_note }}"</p>
                                        <p class="text-[10px] text-gray-400 mt-1.5 font-semibold">Oleh : {{ $item->headApprover->name ?? '-' }}</p>
                                    </div>
                                    @endif
                                    @if($item->director_note)
                                    <div class="p-3 rounded-xl border {{ $item->director_status == 'rejected' ? 'bg-red-50 border-red-100 text-red-700': 'bg-purple-50 border-purple-100 text-purple-700' }}">
                                        <p class="font-bold mb-1"> 💬 {{ $item->director_status == 'rejected' ? 'Alasan Penolakan Direktur' : 'Catatan Direktur' }}</p>
                                        <p class="text-gray-700 italic">"{{ $item->director_note }}"</p>
                                        <p class="text-[10px] text-gray-400 mt-1.5 font-semibold">Oleh : {{ $item->directorApprover->name ?? '-' }}</p>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <div class="mt-6 pt-3 border-t flex gap-2">
                                    @if($item->pdf_file)
                                    <a href="{{ asset('storage/' . $item->pdf_file) }}" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 rounded-xl text-xs font-bold transition shadow-sm">
                                        ⬇ Download Dokumen PDF Resmi
                                    </a>
                                    @endif
                                    <button @click="showDetail = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-3 rounded-xl text-xs font-bold transition {{ !$item->pdf_file ? 'w-full' : '' }}">
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
                <div class="text-5xl mb-4">📝</div>
                <h3 class="text-base font-bold text-gray-800">Belum Ada Riwayat Izin</h3>
                <p class="text-xs text-gray-400 mt-1 mb-5">Anda belum memiliki berkas log transaksional pengajuan izin pada sistem.</p>
                <a href="{{ route('izin') }}" class="inline-block bg-[#1E40AF] hover:bg-[#1e3a8a] text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                    🚀 Buat Pengajuan Sekarang
                </a>
            </div>

            @endif

        </div>
    </div>

</x-app-layout>