<x-app-layout>
    <main class="min-h-screen bg-[#f8f9ff] px-4 pb-28 pt-5 sm:px-6 lg:px-8 lg:pt-8">
        <div class="mx-auto w-full max-w-5xl space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[.14em] text-[#0037b0]">HRD · Presensi</p>
                        <h1 class="mt-1 font-display text-2xl font-bold text-slate-900">Rekap Telat</h1>
                        <p class="mt-1 text-sm text-slate-500">Total keterlambatan dan denda pegawai pada periode terpilih.</p>
                    </div>
                    <a href="{{ route('hrd.rekap.telat.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#0037b0] px-4 text-xs font-bold text-white hover:bg-blue-800">
                        <span class="material-symbols-outlined text-base">download</span>Export Excel
                    </a>
                </div>
                <form method="GET" action="{{ route('hrd.rekap.telat') }}" class="mt-5 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <label class="text-xs font-semibold text-slate-600">Mulai<input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="mt-1 block h-9 rounded-lg border-slate-200 bg-white text-xs"></label>
                    <label class="text-xs font-semibold text-slate-600">Selesai<input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="mt-1 block h-9 rounded-lg border-slate-200 bg-white text-xs"></label>
                    <button type="submit" class="h-9 rounded-lg bg-slate-800 px-4 text-xs font-bold text-white">Terapkan</button>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-display text-sm font-bold text-slate-900">Daftar Keterlambatan</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ $startDate->format('d M Y') }} – {{ $endDate->format('d M Y') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Nama Karyawan</th><th class="px-5 py-3 text-right">Total Menit</th><th class="px-5 py-3 text-right">Total Denda Telat</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($lateRows as $row)
                                <tr class="hover:bg-slate-50"><td class="px-5 py-3 font-semibold text-slate-800">{{ $row['name'] }}</td><td class="px-5 py-3 text-right text-slate-700">{{ number_format($row['total_minutes']) }}</td><td class="px-5 py-3 text-right font-semibold text-amber-700">Rp {{ number_format($row['total_fine'], 0, ',', '.') }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada data karyawan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</x-app-layout>
