<x-app-layout>

<div class="min-h-screen bg-gray-100 p-4 pb-24">

    <div class="max-w-5xl mx-auto">

        <div class="bg-white rounded-3xl shadow p-5">

            <div class="flex items-center justify-between mb-5">

                <div>

                    <h1 class="text-2xl font-bold">
                        Approval HRD
                    </h1>

                    <p class="text-sm text-gray-500 mt-1">
                        Pengajuan cuti yang telah disetujui PJ
                    </p>

                </div>

                <div
                    class="bg-blue-100 text-blue-700 px-4 py-2 rounded-2xl text-sm font-semibold">

                    {{ $leaves->count() }} Pengajuan

                </div>

            </div>

            @forelse($leaves as $leave)

            <div class="border rounded-2xl p-5 mb-4">

                <div class="flex justify-between gap-4">

                    <!-- LEFT -->
                    <div class="flex-1">

                        <div class="flex items-center gap-3">

                            <div
                                class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-xl">

                                📅

                            </div>

                            <div>

                                <h2 class="font-bold text-lg">
                                    {{ $leave->user->name }}
                                </h2>

                                <p class="text-sm text-gray-500">
                                    {{ $leave->user->role }}
                                </p>

                            </div>

                        </div>

                        <div class="mt-4 space-y-2">

                            <p class="text-sm">
                                <span class="font-semibold">
                                    Jenis:
                                </span>

                                {{ $leave->leave_type }}
                            </p>

                            <p class="text-sm">
                                <span class="font-semibold">
                                    Periode:
                                </span>

                                {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                                -
                                {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                            </p>

                            <p class="text-sm">
                                <span class="font-semibold">
                                    Total:
                                </span>

                                {{ $leave->total_days }} Hari
                            </p>

                        </div>

                        <!-- ALASAN -->
                        <div
                            class="mt-4 bg-slate-50 border rounded-2xl p-4">

                            <p class="text-xs text-gray-500 mb-1">
                                Alasan Cuti
                            </p>

                            <p class="text-sm text-gray-700">
                                {{ $leave->reason }}
                            </p>

                        </div>

                        <!-- APPROVAL PJ -->
                        <div
                            class="mt-4 bg-blue-50 border border-blue-100 rounded-2xl p-4">

                            <div class="flex justify-between">

                                <div>

                                    <p class="text-xs text-blue-500">
                                        Approved PJ
                                    </p>

                                    <h3 class="font-semibold mt-1">
                                        {{ $leave->pjApprover->name ?? '-' }}
                                    </h3>

                                </div>

                                <div class="text-right">

                                    <p class="text-xs text-blue-500">
                                        Tanggal
                                    </p>

                                    <h3 class="font-semibold mt-1">

                                        @if($leave->pj_approved_at)

                                        {{ \Carbon\Carbon::parse($leave->pj_approved_at)->format('d M Y H:i') }}

                                        @else
                                        -
                                        @endif

                                    </h3>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- ACTION -->
                    <div class="flex flex-col gap-2">

                        <form method="POST"
                            action="{{ route('pj.cuti.approve', $leave->id) }}">

                            @csrf

                            <button
                                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-xl w-full">

                                Approve

                            </button>

                        </form>

                        <form method="POST"
                            action="{{ route('pj.cuti.reject', $leave->id) }}">

                            @csrf

                            <button
                                class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl w-full">

                                Reject

                            </button>

                        </form>

                    </div>

                </div>

            </div>

            @empty

            <div class="text-center py-16">

                <div class="text-6xl mb-4">
                    📄
                </div>

                <h2 class="text-xl font-bold text-gray-700">
                    Tidak Ada Approval
                </h2>

                <p class="text-gray-500 mt-2">
                    Belum ada pengajuan cuti yang menunggu approval HRD
                </p>

            </div>

            @endforelse

        </div>

    </div>

</div>

</x-app-layout>