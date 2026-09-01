<x-app-layout>

    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-indigo-50 to-cyan-50 p-4 pb-28">

        <div class="max-w-4xl mx-auto">

            <!-- HEADER BAR -->
            <div class="flex items-center justify-between mb-5">

                <a href="{{ route('shift-change.create') }}"
                    class="inline-flex items-center gap-2
                    bg-white/80 backdrop-blur-md border border-white/50
                    hover:border-indigo-300 hover:bg-indigo-50
                    text-gray-700 hover:text-indigo-700
                    px-4 py-2 rounded-2xl
                    shadow-sm transition text-sm font-semibold">

                    <span class="text-base">←</span>
                    Form Pengajuan
                </a>

                <div class="bg-gradient-to-r from-indigo-600 to-blue-500 text-white px-4 py-2 rounded-2xl shadow-md text-xs font-bold">
                    Total: {{ $requests->count() }} Pengajuan
                </div>

            </div>

            <!-- TITLE CARD -->
            <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-xl border border-white/50 p-6 mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    Riwayat Perubahan Shift
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Daftar riwayat pengajuan perubahan shift Anda beserta status verifikasi PJ
                </p>
            </div>

            @if(session('success'))
            <div class="mb-5 bg-emerald-100/90 backdrop-blur border border-emerald-300 text-emerald-700 px-5 py-4 rounded-2xl shadow-sm text-sm font-medium">
                {{ session('success') }}
            </div>
            @endif

            <!-- LIST REQUESTS -->
            <div class="space-y-4">
                @forelse($requests as $item)
                <div class="bg-white/90 backdrop-blur-md rounded-3xl shadow-lg border border-white/60 p-5 hover:shadow-xl transition-all duration-300">
                    <div class="flex flex-col sm:flex-row justify-between gap-4">

                        <div class="flex-1 space-y-3">

                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-bold">
                                    🔄
                                </div>
                                <div>
                                    <h2 class="font-bold text-gray-800 text-base">
                                        Tanggal Shift: {{ \Carbon\Carbon::parse($item->shift_date)->translatedFormat('l, d F Y') }}
                                    </h2>
                                    <p class="text-xs text-gray-400">
                                        Diajukan pada: {{ $item->created_at->translatedFormat('d M Y, H:i') }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3">
                                    <p class="text-[11px] font-semibold text-gray-400 uppercase">Shift Asal</p>
                                    <p class="text-sm font-bold text-gray-700 mt-0.5">
                                        {{ $item->currentShift->name ?? 'Tidak Ada Shift / Off' }}
                                    </p>
                                </div>

                                <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-3">
                                    <p class="text-[11px] font-semibold text-indigo-400 uppercase">Shift Yang Diminta</p>
                                    <p class="text-sm font-bold text-indigo-800 mt-0.5">
                                        {{ $item->requestedShift->name ?? '-' }}
                                        <span class="text-xs font-normal text-indigo-600">
                                            ({{ \Carbon\Carbon::parse($item->requestedShift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($item->requestedShift->end_time)->format('H:i') }})
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3">
                                <p class="text-xs font-semibold text-gray-400 mb-1">Alasan Pengajuan:</p>
                                <p class="text-sm text-gray-700 leading-relaxed font-medium">{{ $item->reason }}</p>
                            </div>

                            @if($item->pj_note)
                            <div class="bg-rose-50 border border-rose-100 rounded-2xl p-3">
                                <p class="text-xs font-semibold text-rose-500 mb-1">Catatan Penolakan PJ:</p>
                                <p class="text-sm text-rose-700 leading-relaxed font-medium">{{ $item->pj_note }}</p>
                            </div>
                            @endif

                        </div>

                        <!-- STATUS BADGE -->
                        <div class="flex sm:flex-col items-end justify-between sm:justify-start gap-2">
                            @if($item->status === 'pending')
                            <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 px-4 py-2 rounded-2xl text-xs font-bold shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span> Menunggu PJ
                            </span>
                            @elseif($item->status === 'approved')
                            <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-2 rounded-2xl text-xs font-bold shadow-xs">
                                <span>✅</span> Disetujui PJ
                            </span>
                            @elseif($item->status === 'rejected')
                            <span class="inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 border border-rose-200 px-4 py-2 rounded-2xl text-xs font-bold shadow-xs">
                                <span>❌</span> Ditolak PJ
                            </span>
                            @endif

                            @if($item->pj_approved_at)
                            <p class="text-[10px] text-gray-400 text-right">
                                Diproses: {{ \Carbon\Carbon::parse($item->pj_approved_at)->translatedFormat('d M Y, H:i') }}
                            </p>
                            @endif
                        </div>

                    </div>
                </div>
                @empty
                <div class="bg-white/80 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="text-6xl mb-4">📄</div>
                    <h2 class="text-xl font-bold text-gray-700">Belum Ada Pengajuan</h2>
                    <p class="text-gray-500 mt-2 text-sm">Anda belum pernah mengajukan perubahan shift.</p>
                    <a href="{{ route('shift-change.create') }}" class="inline-block mt-4 bg-indigo-600 text-white px-6 py-2.5 rounded-2xl font-bold text-sm hover:bg-indigo-700 transition">
                        Buat Pengajuan Sekarang
                    </a>
                </div>
                @endforelse
            </div>

        </div>

    </div>

</x-app-layout>
