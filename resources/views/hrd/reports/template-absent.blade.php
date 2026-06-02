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
                    <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-[11px] font-bold text-rose-800 text-center shadow-sm truncate">
                        {{ $employee->name }}
                    </div>
                    @empty
                    <div class="col-span-full p-8 text-center text-gray-400 text-xs">Semua pegawai hadir hari ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>