<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">
        <div class="w-full max-w-6xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-5 sm:p-8 space-y-6">

                <div class="flex flex-col gap-4 pb-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('hrd.rekap') }}" class="text-xs font-bold text-[#1E40AF] hover:underline whitespace-nowrap">← Kembali</a>

                        <a href="{{ route('hrd.reports.export', array_merge(request()->query(), ['type' => $reportType])) }}"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm">
                            <span>📥</span> Export Excel
                        </a>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">
                        {{ $title }}
                    </h1>
                </div>

                <form method="GET" class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Filter:</label>
                    @if(isset($filter_date))
                    <input type="date" name="date" value="{{ $filter_date }}" onchange="this.form.submit()" class="text-xs rounded-xl border-gray-200 focus:ring-blue-500">
                    @elseif(isset($filter_month))
                    <input type="month" name="month" value="{{ $filter_month }}" onchange="this.form.submit()" class="text-xs rounded-xl border-gray-200 focus:ring-blue-500">
                    @endif
                </form>

                <div class="bg-white border border-gray-100 rounded-2xl overflow-x-auto shadow-sm">
                    <table class="w-full text-xs text-left min-w-[600px]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 font-bold text-gray-500 uppercase tracking-wider">Nama Pegawai</th>
                                <th class="p-4 font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="p-4 font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th class="p-4 font-bold text-gray-500 uppercase tracking-wider">Status & Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($data as $item)
                            <tr class="hover:bg-slate-50 transition-colors duration-150">
                                <td class="p-4 font-bold text-gray-800">{{ $item->user->name ?? 'N/A' }}</td>
                                <td class="p-4 text-gray-600">{{ $item->start_date ?? $item->tanggal ?? $item->overtime_date ?? 'N/A' }}</td>
                                <td class="p-4 text-gray-600 italic">{{ $item->reason ?? 'Tidak ada keterangan' }}</td>

                                <td class="p-4">
                                    @if($item->status == 'waiting_hrd')
                                    {{-- Link ke route sesuai tipe laporan yang aktif --}}
                                    <a href="{{ route('hrd.' . ($reportType == 'leave' ? 'cuti' : ($reportType == 'permission' ? 'izin' : 'lembur')), ['id' => $item->id]) }}"
                                        class="inline-block bg-blue-600 text-white px-4 py-1.5 rounded-xl font-bold hover:bg-blue-700 transition shadow-sm">
                                        Proses HRD
                                    </a>
                                    @else
                                    <span class="inline-block px-3 py-1 rounded-xl font-bold text-[10px] uppercase tracking-wide
                                            {{ $item->status == 'approved' ? 'bg-emerald-100 text-emerald-700' : 
                                               ($item->status == 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ str_replace('_', ' ', $item->status ?? 'Pending') }}
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-400 font-medium">Data tidak ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>