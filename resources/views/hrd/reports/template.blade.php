<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">
        <div class="w-full max-w-6xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-6 sm:p-8 space-y-6">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-gray-100">
                    <div>
                        <a href="{{ route('hrd.rekap') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline transition mb-2">
                            ← Kembali ke Pusat Rekap HRD
                        </a>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                            {{ $title }}
                        </h1>
                    </div>
                </div>

                <form method="GET" class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center gap-4">
                    <label class="text-xs font-bold text-gray-600">Filter:</label>
                    @if(isset($filter_date))
                    <input type="date" name="date" value="{{ $filter_date }}" onchange="this.form.submit()" class="text-xs rounded-xl border-gray-200 shadow-sm focus:ring-0">
                    @elseif(isset($filter_month))
                    <input type="month" name="month" value="{{ $filter_month }}" onchange="this.form.submit()" class="text-xs rounded-xl border-gray-200 shadow-sm focus:ring-0">
                    @endif
                </form>

                <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 font-bold text-gray-500 uppercase tracking-wider">Nama Pegawai</th>
                                <th class="p-4 font-bold text-gray-500 uppercase tracking-wider">Waktu/Tanggal</th>
                                <th class="p-4 font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($data as $item)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4 font-bold text-gray-800">{{ $item->user->name ?? 'N/A' }}</td>
                                <td class="p-4 text-gray-600">
                                    {{ isset($item->jam_masuk) ? \Carbon\Carbon::parse($item->jam_masuk)->format('H:i') : ($item->start_date ?? $item->tanggal ?? 'N/A') }}
                                </td>
                                <td class="p-4">
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 rounded-lg font-bold border border-emerald-100">
                                        {{ $item->status ?? 'Terverifikasi' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="p-8 text-center text-gray-400 font-medium">Data tidak ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>