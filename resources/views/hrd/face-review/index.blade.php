{{-- Antrean verifikasi wajah oleh HRD: foto, skor, setujui / tolak. --}}
<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-4 py-8 pb-28">
        <div class="max-w-6xl mx-auto space-y-6">

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline mb-2">
                    &larr; Kembali ke Dashboard
                </a>

                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Verifikasi Wajah</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Absen yang wajahnya belum pasti (zona abu, tidak dikenali, atau layanan tidak tersedia).
                </p>

                <div class="mt-5 flex items-center gap-3 text-xs">
                    <a href="{{ route('hrd.face.index', ['status' => 'pending']) }}"
                        class="px-4 py-2 rounded-xl font-bold {{ $status === 'pending' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' }}">Menunggu</a>
                    <a href="{{ route('hrd.face.index', ['status' => 'all']) }}"
                        class="px-4 py-2 rounded-xl font-bold {{ $status === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600' }}">Semua</a>
                </div>
            </div>

            <div class="space-y-4">
                @forelse($attendances as $attendance)
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 flex flex-col sm:flex-row gap-5">
                        {{-- Keputusan B1: foto bukti TIDAK ditampilkan maupun
                             dapat diunduh. Yang dilihat HRD hanya skor, metode,
                             dan waktu verifikasi. --}}
                        <div class="w-full sm:w-28 shrink-0">
                            <div class="w-full aspect-square rounded-2xl bg-slate-100 text-slate-400 text-[10px] flex items-center justify-center text-center px-2">
                                Bukti foto disimpan internal
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-sm text-gray-800">{{ $attendance->user->name ?? '-' }}</p>
                                @php
                                    // face_match NULL + manual_fallback berarti wajah
                                    // TIDAK PERNAH dibandingkan sama sekali (layanan
                                    // mati). Ini kondisi paling berisiko: bisa wajah
                                    // orang lain, jadi HRD harus prioritize.
                                    $neverChecked = $attendance->face_method === 'manual_fallback'
                                        && $attendance->face_score === null;
                                @endphp

                                <span class="text-[10px] font-black uppercase px-2 py-1 rounded-lg
                                    @if($attendance->face_match === true)
                                        bg-emerald-50 text-emerald-700
                                    @elseif($neverChecked)
                                        bg-rose-50 text-rose-700 border border-rose-300
                                    @else
                                        bg-amber-50 text-amber-700
                                    @endif">
                                    @if($attendance->face_match === true)
                                        Disetujui HRD
                                    @elseif($neverChecked)
                                        ⚠ Tidak Dicek
                                    @else
                                        Perlu Verifikasi
                                    @endif
                                </span>
                            </div>

                            @if($neverChecked)
                                <p class="text-[11px] font-bold text-rose-700 mt-1">
                                    ⚠ Wajah tidak pernah dibandingkan (layanan pengenalan wajah mati). Periksa foto bukti.
                                </p>
                            @endif

                            <p class="text-xs text-gray-500 mt-1">
                                {{ $attendance->tanggal }}
                                &middot; masuk {{ $attendance->jam_masuk }}
                                &middot; skor {{ $attendance->face_score !== null ? number_format((float) $attendance->face_score, 3) : '-' }}
                                &middot; metode {{ $attendance->face_method ?? '-' }}
                            </p>

                            @if($attendance->face_review_note)
                                <p class="text-xs text-gray-600 mt-1 italic">Catatan: {{ $attendance->face_review_note }}</p>
                            @endif
                        </div>

                        @if(! $attendance->face_reviewed_by)
                            <div class="flex sm:flex-col gap-2 shrink-0">
                                <form method="POST" action="{{ route('hrd.face.approve', $attendance->id) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="Disetujui HRD">
                                    <button class="w-full px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition">Setujui</button>
                                </form>

                                <form method="POST" action="{{ route('hrd.face.reject', $attendance->id) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="Ditolak HRD">
                                    <button class="w-full px-4 py-2 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition">Tolak</button>
                                </form>
                            </div>
                        @else
                            <div class="text-[10px] font-bold uppercase text-gray-400 shrink-0 self-center">Sudah ditinjau</div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic text-center py-10">Tidak ada absen yang menunggu verifikasi.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>