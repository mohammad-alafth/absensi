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

                <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-gray-100">
                    <h1 class="text-lg sm:text-xl font-extrabold text-gray-900">{{ $title }}</h1>
                    <a href="{{ route('hrd.rekap') }}" class="text-xs font-bold text-[#1E40AF] hover:underline">← Kembali</a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    @forelse($data as $employee)
                    <div class="employee-card p-4 bg-rose-50 border border-rose-100 rounded-2xl text-center shadow-sm">
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
                        Semua pegawai hadir hari ini.
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

        searchInput.addEventListener('keyup', function() {

            const keyword = this.value.toLowerCase();
            let visibleCount = 0;

            cards.forEach(card => {

                const name = card.dataset.name;

                if (name.includes(keyword)) {

                    card.style.display = '';
                    visibleCount++;

                } else {

                    card.style.display = 'none';

                }

            });

            countElement.innerText = visibleCount;
        });

    });
</script>