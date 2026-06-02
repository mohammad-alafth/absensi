<x-app-layout>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f9;
            /* Sedikit lebih gelap agar kotak putih menonjol */
        }
    </style>

    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-6xl mx-auto space-y-6">

            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-6 sm:p-8">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 pb-6 border-b border-gray-100">
                    <div>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                            <span>🛡️</span> Dashboard Penanggung Jawab (PJ)
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Divisi Otoritas:
                            <span class="font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100 ml-1">
                                {{ strtoupper($divisionRole) }}
                            </span>
                        </p>
                    </div>

                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 px-5 py-2.5 rounded-xl shadow-2xs text-sm font-bold transition whitespace-nowrap">
                        ← Kembali ke Beranda
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">

                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-100 rounded-2xl p-5 flex flex-col justify-between h-[110px] shadow-sm shadow-amber-100/50">
                        <div class="flex items-center justify-between text-amber-800/80">
                            <span class="text-xs font-semibold tracking-wide">Pending Cuti</span>
                            <span class="text-lg">📅</span>
                        </div>
                        <h2 class="text-3xl font-black text-amber-950">{{ $pendingLeave }}</h2>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-5 flex flex-col justify-between h-[110px] shadow-sm shadow-blue-100/50">
                        <div class="flex items-center justify-between text-blue-800/80">
                            <span class="text-xs font-semibold tracking-wide">Pending Lembur</span>
                            <span class="text-lg">⏰</span>
                        </div>
                        <h2 class="text-3xl font-black text-blue-950">{{ $pendingOvertime }}</h2>
                    </div>

                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-100 rounded-2xl p-5 flex flex-col justify-between h-[110px] shadow-sm shadow-purple-100/50">
                        <div class="flex items-center justify-between text-purple-800/80">
                            <span class="text-xs font-semibold tracking-wide">Pending Izin</span>
                            <span class="text-lg">📝</span>
                        </div>
                        <h2 class="text-3xl font-black text-purple-950">{{ $pendingPermission }}</h2>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100 rounded-2xl p-5 flex flex-col justify-between h-[110px] shadow-sm shadow-emerald-100/50">
                        <div class="flex items-center justify-between text-emerald-800/80">
                            <span class="text-xs font-semibold tracking-wide">Total Approved</span>
                            <span class="text-lg">✅</span>
                        </div>
                        <h2 class="text-3xl font-black text-emerald-950">{{ $approvedLeave }}</h2>
                    </div>

                    <div class="bg-gradient-to-br from-rose-50 to-red-50 border border-rose-100 rounded-2xl p-5 flex flex-col justify-between h-[110px] shadow-sm shadow-rose-100/50">
                        <div class="flex items-center justify-between text-rose-800/80">
                            <span class="text-xs font-semibold tracking-wide">Total Rejected</span>
                            <span class="text-lg">❌</span>
                        </div>
                        <h2 class="text-3xl font-black text-rose-950">{{ $rejectedLeave }}</h2>
                    </div>

                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 zone-menu">

                    <a href="{{ route('pj.cuti') }}" class="bg-white border border-gray-100 rounded-2xl p-5 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-100/40 hover:-translate-y-1 transition-all duration-300 flex items-center justify-between group shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold group-hover:scale-105 transition">📅</span>
                            <p class="text-sm font-bold text-gray-700 group-hover:text-blue-800">Otorisasi Berkas Cuti</p>
                        </div>
                        <span class="text-xs text-gray-300 group-hover:text-blue-500 transition group-hover:translate-x-1">→</span>
                    </a>

                    <a href="{{ route('pj.izin') }}" class="bg-white border border-gray-100 rounded-2xl p-5 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-100/40 hover:-translate-y-1 transition-all duration-300 flex items-center justify-between group shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl font-bold group-hover:scale-105 transition">📝</span>
                            <p class="text-sm font-bold text-gray-700 group-hover:text-blue-800">Otorisasi Berkas Izin</p>
                        </div>
                        <span class="text-xs text-gray-300 group-hover:text-blue-500 transition group-hover:translate-x-1">→</span>
                    </a>

                    <a href="{{ route('pj.lembur') }}" class="bg-white border border-gray-100 rounded-2xl p-5 hover:border-blue-300 hover:shadow-lg hover:shadow-blue-100/40 hover:-translate-y-1 transition-all duration-300 flex items-center justify-between group shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold group-hover:scale-105 transition">⏰</span>
                            <p class="text-sm font-bold text-gray-700 group-hover:text-blue-800">Otorisasi Surat Lembur</p>
                        </div>
                        <span class="text-xs text-gray-300 group-hover:text-blue-500 transition group-hover:translate-x-1">→</span>
                    </a>

                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                    <div class="border-b px-5 py-4 bg-gray-50 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-800 tracking-wide flex items-center gap-2">
                            <span>📋</span> Log Berkas Masuk Terbaru Karyawan Divisi
                        </h2>
                        <span class="text-[11px] bg-white border border-gray-200 px-2.5 py-1 rounded-lg font-bold text-gray-500 shadow-3xs">Live Monitoring</span>
                    </div>

                    <div class="divide-y divide-gray-100 bg-white list-log">

                        @forelse($recentSubmissions as $item)
                        <div x-data="{ showReject: false }" class="p-5 hover:bg-slate-50 transition-colors duration-150">

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

                                <div class="flex items-center gap-2 ml-auto sm:ml-0 zone-status">

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
                            <div x-show="showReject" x-transition x-cloak class="mt-4 bg-rose-50/50 border border-rose-100 rounded-2xl p-4 zone-note">
                                <h4 class="font-bold text-rose-800 text-[11px] uppercase tracking-wider mb-1">Alasan Penolakan Tim Verifikator:</h4>
                                <p class="text-xs text-rose-700 leading-relaxed font-medium">{{ $item->note ?? 'Tidak ada catatan tertulis.' }}</p>
                            </div>
                            @endif

                        </div>
                        @empty
                        <div class="text-center py-16 bg-slate-50/30 zone-empty">
                            <span class="text-3xl opacity-30">📥</span>
                            <p class="text-xs text-gray-400 font-medium mt-2">Belum ada pengajuan masuk dari karyawan divisi Anda saat ini.</p>
                        </div>
                        @endforelse

                    </div>

                </div>

            </div>
        </div>
    </div>

</x-app-layout>