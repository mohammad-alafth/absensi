<x-app-layout>

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-28">

        <div class="max-w-5xl mx-auto">

            <!-- NAV -->
            <div class="flex justify-between items-center mb-6">

                <a href="{{ route('lembur') }}"
                    class="text-[#1E40AF] font-semibold text-sm">
                    ← Kembali
                </a>

            </div>

            <!-- TITLE -->
            <div class="mb-6">

                <h1 class="text-2xl font-extrabold text-[#1E40AF]">
                    History Lembur
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Daftar pengajuan lembur yang pernah diajukan
                </p>

            </div>

            <!-- LIST -->
            <div class="space-y-4">

                @forelse($overtimes as $item)

                <div class="bg-white rounded-3xl p-5 shadow-sm border border-blue-50">

                    <div class="flex justify-between items-start gap-4">

                        <!-- LEFT -->
                        <div class="flex-1">

                            <p class="font-bold text-lg text-gray-800">
                                {{ \Carbon\Carbon::parse($item->overtime_date)->translatedFormat('d F Y') }}
                            </p>

                            <p class="text-sm text-gray-500 mt-1">
                                {{ $item->start_time }}
                                -
                                {{ $item->end_time }}
                            </p>

                            <div class="mt-4 bg-blue-50 rounded-2xl p-4">

                                <p class="text-xs text-gray-500 mb-1">
                                    Uraian Lembur
                                </p>

                                <p class="text-sm text-gray-700">
                                    {{ $item->reason }}
                                </p>

                            </div>

                        </div>

                        <!-- STATUS -->
                        <div>

                            @if($item->status == 'pending')

                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm">
                                Pending
                            </span>

                            @elseif($item->status == 'approved')

                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                                Approved
                            </span>

                            @elseif($item->status == 'waiting_hrd')

                            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                                Waiting HRD
                            </span>

                            @else

                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm">
                                Rejected
                            </span>

                            @endif

                        </div>

                    </div>

                </div>

                @empty

                <div class="bg-white rounded-3xl p-10 text-center text-gray-500 shadow-sm border border-blue-50">

                    Belum ada history lembur

                </div>

                @endforelse

            </div>

        </div>

    </div>

</x-app-layout>