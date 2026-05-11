<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-7xl mx-auto">

            <!-- HEADER -->
            <div class="mb-6">

                <h1 class="text-2xl font-bold text-gray-800">
                    Approval Lembur HRD
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Daftar pengajuan lembur yang sudah disetujui PJ
                </p>

            </div>

            <!-- ALERT -->
            @if(session('success'))

            <div class="mb-5 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-2xl">

                {{ session('success') }}

            </div>

            @endif

            <!-- LIST -->
            <div class="space-y-4">

                @forelse($overtimes as $overtime)

                <div class="bg-white rounded-3xl shadow p-5">

                    <!-- TOP -->
                    <div class="flex justify-between items-start">

                        <div>

                            <h2 class="text-lg font-bold text-gray-800">
                                {{ $overtime->user->name }}
                            </h2>

                            <p class="text-sm text-gray-500">
                                Tanggal:
                                {{ \Carbon\Carbon::parse($overtime->overtime_date)->translatedFormat('d F Y') }}
                            </p>

                        </div>

                        <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm">
                            Menunggu HRD
                        </span>

                    </div>

                    <!-- CONTENT -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5">

                        <div class="bg-gray-50 rounded-2xl p-4">

                            <p class="text-sm text-gray-500">
                                Jam Mulai
                            </p>

                            <p class="font-semibold text-gray-800">
                                {{ $overtime->start_time }}
                            </p>

                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4">

                            <p class="text-sm text-gray-500">
                                Jam Selesai
                            </p>

                            <p class="font-semibold text-gray-800">
                                {{ $overtime->end_time }}
                            </p>

                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4">

                            <p class="text-sm text-gray-500">
                                Total Jam
                            </p>

                            <p class="font-semibold text-gray-800">
                                {{ $overtime->total_hours }} Jam
                            </p>

                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4">

                            <p class="text-sm text-gray-500">
                                Disetujui PJ Oleh
                            </p>

                            <p class="font-semibold text-gray-800">
                                {{ $overtime->pjApprover->name ?? '-' }}
                            </p>

                        </div>

                    </div>

                    <!-- REASON -->
                    <div class="mt-4 bg-blue-50 rounded-2xl p-4">

                        <p class="text-sm text-gray-500 mb-1">
                            Alasan / Uraian Lembur
                        </p>

                        <p class="text-gray-700">
                            {{ $overtime->reason }}
                        </p>

                    </div>

                    <!-- ACTION -->
                    <div class="flex gap-3 mt-5">

                        <!-- APPROVE -->
                        <form action="{{ route('hrd.lembur.approve', $overtime->id) }}"
                            method="POST"
                            class="flex-1">

                            @csrf

                            <button
                                type="submit"
                                class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-2xl transition">

                                Approve

                            </button>

                        </form>

                        <!-- REJECT -->
                        <form action="{{ route('hrd.lembur.reject', $overtime->id) }}"
                            method="POST"
                            class="flex-1">

                            @csrf

                            <button
                                type="submit"
                                class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-3 rounded-2xl transition">

                                Reject

                            </button>

                        </form>

                    </div>

                </div>

                @empty

                <div class="bg-white rounded-3xl shadow p-10 text-center text-gray-500">

                    Belum ada pengajuan lembur untuk HRD

                </div>

                @endforelse

            </div>

        </div>

    </div>

</x-app-layout>