<style>
    .employee-name-wrapper {
        position: relative;
        min-height: 18px;
    }

    .employee-name {
        display: block;
    }

    .employee-alpha {
        display: none;
    }

    .employee-card:hover .employee-name {
        display: none;
    }

    .employee-card:hover .employee-alpha {
        display: block;
    }

    .employee-card {
        transition: all .25s ease;
        cursor: pointer;
    }

    .employee-card:hover {
        transform: translateY(-2px);
        background: #fff7ed;
        border-color: #fdba74;
    }
</style>

<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-3 sm:px-5 py-6 pb-28">
        <div class="w-full max-w-6xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-100/70 border border-white p-5 sm:p-8 space-y-6">

                <div class="flex flex-col gap-4 pb-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('hrd.rekap') }}" class="text-xs font-bold text-[#1E40AF] hover:underline whitespace-nowrap">← Kembali</a>

                        <a href="{{ route('hrd.reports.export', ['type' => 'absent', 'start_date' => $start_date ?? now()->startOfMonth()->format('Y-m-d'), 'end_date' => $end_date ?? now()->format('Y-m-d')]) }}"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm">
                            <span>📥</span> Export Excel
                        </a>
                    </div>

                    <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">
                        {{ $title }}
                    </h1>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <form method="GET" action="{{ route('hrd.reports.absent.daily') }}" class="flex flex-wrap items-center gap-3">
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

                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="text-xs font-semibold text-gray-500 whitespace-nowrap">
                            Total Tidak Hadir :
                            <span id="employeeCount" class="font-bold text-rose-600">{{ count($data ?? []) }}</span> Pegawai
                        </div>

                        <div class="w-full sm:w-64">
                            <input
                                type="text"
                                id="searchEmployee"
                                placeholder="Cari nama pegawai..."
                                class="w-full rounded-xl border-gray-300 text-xs focus:ring-[#1E40AF] focus:border-[#1E40AF]">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">

                    @forelse($data as $employee)

                    <div
                        class="employee-card p-4 bg-rose-50 border border-rose-100 rounded-2xl text-center shadow-sm"
                        data-name="{{ strtolower($employee->name) }}">

                        <div class="employee-name-wrapper">

                            <span class="employee-name text-[11px] font-bold text-rose-800">
                                {{ $employee->name }}
                            </span>

                            <span class="employee-alpha text-[11px] font-bold text-amber-700">
                                Tidak Hadir {{ $employee->alpha_days ?? 0 }} Hari
                            </span>

                        </div>

                    </div>

                    @empty

                    <div class="col-span-full p-8 text-center text-gray-400 text-xs">
                        Tidak ada pegawai yang tercatat tidak hadir pada periode rentang tanggal ini.
                    </div>

                    @endforelse

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const searchInput = document.getElementById('searchEmployee');
        const cards = document.querySelectorAll('.employee-card');
        const countElement = document.getElementById('employeeCount');

        if (!searchInput) return;

        searchInput.addEventListener('keyup', function() {

            const keyword = this.value.toLowerCase();
            let visibleCount = 0;

            cards.forEach(card => {

                const name = card.dataset.name || '';

                if (name.includes(keyword)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }

            });

            if (countElement) {
                countElement.textContent = visibleCount;
            }

        });

    });
</script>
