{{-- Jejak audit data biometrik (keputusan B3): hanya role admin. --}}
<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-4 py-8 pb-28">
        <div class="max-w-6xl mx-auto space-y-6">

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <a href="{{ route('admin.face-settings.index') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline mb-2">
                    &larr; Kembali ke Pengaturan Wajah
                </a>

                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Log Akses Wajah</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Jejak registrasi, verifikasi, spoof check, persetujuan, dan perubahan pengaturan.
                    Baris lama dihapus otomatis setelah {{ $retentionDays }} hari (2 bulan).
                </p>

                <div class="mt-4 text-[11px] text-gray-500 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                    Catatan: log ini membantu membuktikan PROSES, bukan otomatis membuktikan kepatuhan UU PDP.
                    Kepatuhan tetap bergantung pada keseluruhan proses dan dasar pemrosesan yang dipilih
                    organisasi, yang menjadi keputusan legal/compliance.
                </div>

                <form method="GET" class="mt-5 flex flex-wrap items-end gap-3">
                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Aksi</span>
                        <select name="action" class="mt-1 rounded-xl border-slate-200 bg-white text-xs py-2">
                            <option value="">Semua</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                    </label>

                    <button class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 text-white">Terapkan</button>
                </form>
            </div>

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="p-3 font-bold text-gray-500 uppercase">Waktu</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Aksi</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Pelaku</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Pengguna</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Hasil</th>
                                <th class="p-3 font-bold text-gray-500 uppercase">Konteks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="p-3 text-gray-600 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                    <td class="p-3 font-mono text-gray-700">{{ $log->action }}</td>
                                    <td class="p-3 text-gray-600">{{ $log->actor->name ?? '-' }} ({{ $log->actor_role ?? '-' }})</td>
                                    <td class="p-3 text-gray-600">{{ $log->subject->name ?? '-' }}</td>
                                    <td class="p-3 text-gray-600">{{ $log->result ?? '-' }}</td>
                                    <td class="p-3 text-gray-500 font-mono text-[10px]">{{ $log->context ? json_encode($log->context) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-gray-400 italic">Belum ada aktivitas tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>