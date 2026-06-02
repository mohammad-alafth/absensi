<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f9;
            /* Latar belakang luar agar kotak putih menonjol */
        }
    </style>

    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-6xl mx-auto">

            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-6 sm:p-8 space-y-6">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-gray-100">
                    <div>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline transition mb-2">
                            ← Kembali ke Dashboard
                        </a>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                            <span>🏢</span> Dashboard HRD
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Sistem Monitoring Pusat Approval Cuti, Izin, dan Lembur Karyawan
                        </p>
                    </div>

                    <span class="self-start sm:self-center text-xs font-bold text-blue-700 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100">
                        RS Mata Pekanbaru Eye Center
                    </span>
                </div>

                @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-sm font-medium">
                    {{ session('success') }}
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    <a href="{{ route('hrd.cuti') }}" class="bg-gradient-to-br from-indigo-50 to-blue-50/60 border border-indigo-100 rounded-2xl p-5 flex items-center justify-between shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group">
                        <div>
                            <p class="text-xs font-bold text-indigo-800/80 tracking-wide">Pending Cuti</p>
                            <h2 class="text-3xl font-black text-indigo-950 mt-2">
                                {{ $pendingLeave }}
                            </h2>
                        </div>
                        <div class="text-4xl group-hover:scale-105 transition-transform duration-200">📅</div>
                    </a>

                    <a href="{{ route('hrd.izin') }}" class="bg-gradient-to-br from-amber-50 to-orange-50/60 border border-amber-100 rounded-2xl p-5 flex items-center justify-between shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group">
                        <div>
                            <p class="text-xs font-bold text-amber-800/80 tracking-wide">Pending Izin</p>
                            <h2 class="text-3xl font-black text-amber-950 mt-2">
                                {{ $pendingPermission }}
                            </h2>
                        </div>
                        <div class="text-4xl group-hover:scale-105 transition-transform duration-200">📝</div>
                    </a>

                    <a href="{{ route('hrd.lembur') }}" class="bg-gradient-to-br from-emerald-50 to-teal-50/60 border border-emerald-100 rounded-2xl p-5 flex items-center justify-between shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 group">
                        <div>
                            <p class="text-xs font-bold text-emerald-800/80 tracking-wide">Pending Lembur</p>
                            <h2 class="text-3xl font-black text-emerald-950 mt-2">
                                {{ $pendingOvertime }}
                            </h2>
                        </div>
                        <div class="text-4xl group-hover:scale-105 transition-transform duration-200">⏰</div>
                    </a>

                </div>

                <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-100">
                    <h2 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider mb-4">
                        Akses Cepat Panel Verifikasi
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('hrd.cuti') }}" class="bg-white border border-gray-200/80 rounded-2xl p-5 hover:border-indigo-400 hover:shadow-lg hover:shadow-indigo-100/40 hover:-translate-y-1 transition-all duration-300 flex items-center justify-between group">
                            <div class="flex items-center gap-3 truncate">
                                <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold group-hover:scale-105 transition shrink-0">📅</span>
                                <div class="truncate">
                                    <h3 class="text-sm font-bold text-gray-800 group-hover:text-indigo-900">Approval Cuti</h3>
                                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">Kelola izin istirahat & libur tahunan</p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-300 group-hover:text-indigo-500 transition group-hover:translate-x-1 pl-2">→</span>
                        </a>

                        <a href="{{ route('hrd.izin') }}" class="bg-white border border-gray-200/80 rounded-2xl p-5 hover:border-amber-400 hover:shadow-lg hover:shadow-amber-100/40 hover:-translate-y-1 transition-all duration-300 flex items-center justify-between group">
                            <div class="flex items-center gap-3 truncate">
                                <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold group-hover:scale-105 transition shrink-0">📝</span>
                                <div class="truncate">
                                    <h3 class="text-sm font-bold text-gray-800 group-hover:text-amber-900">Approval Izin</h3>
                                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">Kelola dispensasi berkas absensi harian</p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-300 group-hover:text-amber-500 transition group-hover:translate-x-1 pl-2">→</span>
                        </a>

                        <a href="{{ route('hrd.lembur') }}" class="bg-white border border-gray-200/80 rounded-2xl p-5 hover:border-emerald-400 hover:shadow-lg hover:shadow-emerald-100/40 hover:-translate-y-1 transition-all duration-300 flex items-center justify-between group">
                            <div class="flex items-center gap-3 truncate">
                                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold group-hover:scale-105 transition shrink-0">⏰</span>
                                <div class="truncate">
                                    <h3 class="text-sm font-bold text-gray-800 group-hover:text-emerald-900">Approval Lembur</h3>
                                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">Validasi penugasan & jam kerja ekstra</p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-300 group-hover:text-emerald-500 transition group-hover:translate-x-1 pl-2">→</span>
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden mt-6">

                    <div class="border-b px-5 py-4 bg-gray-50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-800 tracking-wide flex items-center gap-2">
                            <span>📋</span> Log Riwayat Masuk Terbaru (Seluruh Karyawan)
                        </h2>
                        <span class="text-[11px] bg-white border border-gray-200 px-2.5 py-1 rounded-lg font-bold text-gray-500 shadow-3xs">Pusat Informasi</span>
                    </div>

                    <div class="divide-y divide-gray-100 bg-white">

                        @forelse($recentSubmissions as $item)
                        <div x-data|="{ showReject: false }" class="p-5 hover:bg-slate-50 transition-colors duration-150">

                            <div class="flex items-center justify-between gap-4 flex-wrap sm:flex-nowrap">

                                <div class="flex items-center gap-4 truncate">
                                    <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#1E40AF] border border-blue-100 flex items-center justify-center font-black text-sm shrink-0 shadow-3xs">
                                        {{ strtoupper(substr($item->user->name, 0, 1)) }}
                                    </div>

                                    <div class="truncate">
                                        <h3 class="font-bold text-gray-800 text-sm truncate tracking-wide">{{ $item->user->name }}</h3>
                                        <div class="flex items-center gap-2.5 mt-1 text-[11px] text-gray-400 font-medium">
                                            <span class="text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md capitalize font-semibold border border-slate-200">{{ $item->leave_type }}</span>
                                            <span>•</span>
                                            <span>Masuk: {{ $item->created_at->format('d M Y, H:i') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 ml-auto sm:ml-0">

                                    @if($item->status == 'pending')
                                    <span class="bg-amber-50 text-amber-700 border border-amber-100 px-3 py-1.5 rounded-xl text-[11px] font-bold">Pending PJ</span>

                                    @elseif($item->status == 'waiting_head')
                                    <span class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-3 py-1.5 rounded-xl text-[11px] font-bold">Waiting Head</span>

                                    @elseif($item->status == 'waiting_hrd')
                                    <span class="bg-blue-50 text-blue-700 border border-blue-100 px-3 py-1.5 rounded-xl text-[11px] font-bold">Waiting HRD</span>

                                    @elseif($item->status == 'waiting_director')
                                    <span class="bg-purple-50 text-purple-700 border border-purple-100 px-3 py-1.5 rounded-xl text-[11px] font-bold">Waiting Direktur</span>

                                    @elseif($item->status == 'waiting_medical_service')
                                    <span class="bg-cyan-50 text-cyan-700 border border-cyan-100 px-3 py-1.5 rounded-xl text-[11px] font-bold">Medical Service</span>

                                    @elseif($item->status == 'approved')
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-3 py-1.5 rounded-xl text-[11px] font-bold">Approved</span>

                                    @elseif($item->status == 'rejected')
                                    <span class="bg-rose-50 text-rose-700 border border-rose-100 px-3 py-1.5 rounded-xl text-[11px] font-bold">Rejected</span>
                                    <button @click="showReject = !showReject" type="button" class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition shadow-3xs flex items-center gap-1">
                                        <span>📝</span> Catatan
                                    </button>
                                    @endif

                                </div>

                            </div>

                            @if($item->status == 'rejected')
                            <div x-show="showReject" x-transition x-cloak class="mt-4 bg-rose-50/50 border border-rose-100 rounded-2xl p-4">
                                <h4 class="font-bold text-rose-800 text-[11px] uppercase tracking-wider mb-1">Alasan Penolakan:</h4>
                                <p class="text-xs text-rose-700 leading-relaxed font-medium">{{ $item->note ?? 'Tidak ada catatan tertulis.' }}</p>
                            </div>
                            @endif

                        </div>
                        @empty
                        <div class="text-center py-16 bg-slate-50/30">
                            <span class="text-3xl opacity-30">📥</span>
                            <p class="text-xs text-gray-400 font-medium mt-2">Belum ada data pengajuan masuk ke sistem HRD saat ini.</p>
                        </div>
                        @endforelse

                    </div>

                </div>

            </div>
        </div>
    </div>

</x-app-layout>