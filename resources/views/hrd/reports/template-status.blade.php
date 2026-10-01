<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">
        <div class="w-full max-w-6xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-5 sm:p-8 space-y-6">

                <div class="flex flex-col gap-4 pb-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('hrd.rekap') }}" class="text-xs font-bold text-[#1E40AF] hover:underline whitespace-nowrap">← Kembali</a>

                        <a href="{{ route('hrd.reports.export', ['type' => $reportType, 'start_date' => $start_date ?? now()->startOfMonth()->format('Y-m-d'), 'end_date' => $end_date ?? now()->format('Y-m-d')]) }}"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm">
                            <span>📥</span> Export Excel
                        </a>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">
                        {{ $title }}
                    </h1>
                </div>

                <form method="GET" class="flex flex-wrap items-center gap-3 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Dari:</label>
                        <input
                            type="date"
                            name="start_date"
                            value="{{ $start_date ?? now()->startOfMonth()->format('Y-m-d') }}"
                            class="text-xs rounded-xl border-gray-300 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Sampai:</label>
                        <input
                            type="date"
                            name="end_date"
                            value="{{ $end_date ?? now()->format('Y-m-d') }}"
                            class="text-xs rounded-xl border-gray-300 focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                    </div>

                    <button type="submit" class="bg-[#1E40AF] hover:bg-blue-800 text-white px-3 py-2 rounded-xl text-xs font-bold transition shadow-sm">
                        🔍 Filter
                    </button>
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
                                <td class="p-4 text-gray-600">
                                    @if(isset($item->start_date))
                                        {{ Carbon\Carbon::parse($item->start_date)->format('d/m/Y') }}
                                        @if($item->end_date)
                                            s/d {{ Carbon\Carbon::parse($item->end_date)->format('d/m/Y') }}
                                        @endif
                                    @else
                                        {{ Carbon\Carbon::parse($item->tanggal ?? $item->overtime_date)->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="p-4 text-gray-600 italic">{{ $item->reason ?? $item->alasan ?? 'Tidak ada keterangan' }}</td>

                                <td class="p-4">
                                    {{--
                                        Klik badge Status untuk membuka detail pengajuan
                                        (termasuk surat PDF & bukti) lewat modal AJAX.
                                    --}}
                                    <button type="button"
                                        onclick="openReportDetail('{{ route('hrd.reports.detail', ['type' => $reportType, 'id' => '__ID__']) }}', {{ $item->id }})"
                                        title="Klik untuk melihat detail, surat, dan bukti"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl font-bold text-[10px] uppercase tracking-wide cursor-pointer transition shadow-sm ring-1 ring-inset ring-black/5
                                            {{ $item->status == 'approved' ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' :
                                               ($item->status == 'rejected' ? 'bg-rose-100 text-rose-700 hover:bg-rose-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200') }}">
                                        <span>{{ str_replace('_', ' ', $item->status ?? 'Pending') }}</span>
                                        <span aria-hidden="true">👁 Detail</span>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-400 font-medium">Data tidak ditemukan pada rentang tanggal ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- --}}
    {{-- MODAL DETAIL (diisi lewat AJAX oleh tombol Status pada tabel) --}}
    {{-- --}}
    <div id="reportDetailModal" class="hidden fixed inset-0 z-[60] bg-black/60 px-4 py-8 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-auto overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
                <p id="reportDetailHeading" class="text-sm font-extrabold text-gray-900">Detail Pengajuan</p>
                <button type="button" onclick="closeReportDetail()"
                    class="w-8 h-8 shrink-0 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold transition"
                    aria-label="Tutup">✕</button>
            </div>

            <div id="reportDetailBody" class="p-5">
                <p class="text-xs text-gray-400 text-center py-6">Memuat detail...</p>
            </div>
        </div>
    </div>

    <script>
        const reportDetailModal = document.getElementById('reportDetailModal');
        const reportDetailBody = document.getElementById('reportDetailBody');
        const reportDetailHeading = document.getElementById('reportDetailHeading');

        /*
        |------------------------------------------------------------------
        | DETAIL REKAP (CUTI / IZIN / LEMBUR)
        |------------------------------------------------------------------
        | Template URL berisi placeholder __ID__ yang diganti dengan id baris
        | yang diklik. Server merender partial hrd.reports.detail berisi
        | detail pengajuan + surat PDF + bukti (tanda tangan / foto absen).
        */
        async function openReportDetail(templateUrl, id) {
            reportDetailModal.classList.remove('hidden');
            reportDetailHeading.textContent = 'Detail Pengajuan';
            reportDetailBody.innerHTML = '<p class="text-xs text-gray-400 text-center py-6">Memuat detail...</p>';

            try {
                const response = await fetch(templateUrl.replace('__ID__', id), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                reportDetailBody.innerHTML = await response.text();

                // Judul diambil dari partial detail agar modal ikut berganti judul.
                const heading = reportDetailBody.querySelector('p.text-sm.font-extrabold');

                if (heading) {
                    reportDetailHeading.textContent = heading.textContent.trim();
                }
            } catch (error) {
                reportDetailBody.innerHTML =
                    '<p class="text-xs text-rose-600 text-center py-6 font-semibold">' +
                    'Detail tidak dapat dimuat. Silakan coba lagi.</p>';
            }
        }

        function closeReportDetail() {
            reportDetailModal.classList.add('hidden');
            reportDetailBody.innerHTML = '';
        }

        // Klik area gelap di luar panel menutup modal.
        reportDetailModal.addEventListener('click', (event) => {
            if (event.target === reportDetailModal) {
                closeReportDetail();
            }
        });

        // Tombol Esc menutup modal.
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !reportDetailModal.classList.contains('hidden')) {
                closeReportDetail();
            }
        });
    </script>
</x-app-layout>
