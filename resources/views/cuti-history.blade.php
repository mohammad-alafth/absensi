<x-app-layout>

    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-indigo-50 to-cyan-50 pb-28">

        <!-- HEADER -->


        <div class="max-w-5xl mx-auto px-4 sm:px-6 -mt-5">

            @if($leaves->count() > 0)

            <div class="space-y-4">

                <div class="flex justify-between items-center mb-6">
                    <a href="{{ route('cuti') }}"
                        class="text-[#1E40AF] font-semibold text-sm">
                        ← Kembali
                    </a>
                </div>
                @foreach($leaves as $leave)

                <div
                    class="bg-white/90 backdrop-blur-md border border-white/40 rounded-3xl shadow-lg overflow-hidden">

                    <!-- TOP -->
                    <div class="p-5">

                        <div class="flex justify-between items-start gap-3">

                            <div>

                                <div class="flex items-center gap-2">

                                    <div class="text-2xl">
                                        📅
                                    </div>

                                    <div>

                                        <h2 class="font-bold text-gray-800 text-lg">
                                            {{ $leave->leave_type }}
                                        </h2>

                                        <p class="text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                                            -
                                            {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                                        </p>

                                    </div>

                                </div>

                            </div>

                            <!-- STATUS -->
                            <div>

                                @if($leave->status == 'pending')

                                <span
                                    class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Pending PJ
                                </span>

                                @elseif($leave->status == 'waiting_hrd')

                                <span
                                    class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Menunggu HRD
                                </span>

                                @elseif($leave->status == 'approved')

                                <span
                                    class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Approved
                                </span>

                                @elseif($leave->status == 'rejected')

                                <span
                                    class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Rejected
                                </span>

                                @endif

                            </div>

                        </div>

                        <!-- DETAIL -->
                        <div class="mt-5 grid grid-cols-2 gap-4">

                            <div
                                class="bg-slate-50 rounded-2xl p-4 border border-slate-100">

                                <p class="text-xs text-gray-500">
                                    Total Hari
                                </p>

                                <h3 class="font-bold text-lg mt-1">
                                    {{ $leave->total_days }} Hari
                                </h3>

                            </div>

                            <div
                                class="bg-slate-50 rounded-2xl p-4 border border-slate-100">

                                <p class="text-xs text-gray-500">
                                    Kontak Darurat
                                </p>

                                <h3 class="font-bold text-sm mt-1">
                                    {{ $leave->emergency_contact ?? '-' }}
                                </h3>

                            </div>

                        </div>

                        <!-- ALASAN -->
                        <div
                            class="mt-4 bg-indigo-50 border border-indigo-100 rounded-2xl p-4">

                            <p class="text-xs font-semibold text-indigo-500 mb-1">
                                ALASAN CUTI
                            </p>

                            <p class="text-sm text-gray-700 leading-relaxed">
                                {{ $leave->reason }}
                            </p>

                        </div>

                        <!-- APPROVAL -->
                        <!-- APPROVAL PJ -->
                        @if($leave->pj_status)

                        <div
                            class="mt-4 bg-blue-50 border border-blue-100 rounded-2xl p-4">

                            <div class="flex items-center justify-between">

                                <div>

                                    <p class="text-xs text-blue-500">
                                        Approval PJ
                                    </p>

                                    <h3 class="font-semibold text-gray-800 mt-1">

                                        {{ $leave->pjApprover->name ?? '-' }}

                                    </h3>

                                </div>

                                <div class="text-right">

                                    <p class="text-xs text-blue-500">
                                        Tanggal
                                    </p>

                                    <h3 class="font-semibold text-gray-800 mt-1">

                                        @if($leave->pj_approved_at)

                                        {{ \Carbon\Carbon::parse($leave->pj_approved_at)->format('d M Y H:i') }}

                                        @else
                                        -
                                        @endif

                                    </h3>

                                </div>

                            </div>

                            <div class="mt-3">

                                @if($leave->pj_status == 'approved')

                                <span
                                    class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Approved PJ
                                </span>

                                @elseif($leave->pj_status == 'rejected')

                                <span
                                    class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Rejected PJ
                                </span>

                                @endif

                            </div>

                        </div>

                        @endif


                        <!-- APPROVAL HRD -->
                        @if($leave->pj_status != 'pending')

                        <div
                            class="mt-4 bg-emerald-50 border border-emerald-100 rounded-2xl p-4">

                            <div class="flex items-center justify-between">

                                <div>

                                    <p class="text-xs text-emerald-500">
                                        Approval HRD
                                    </p>

                                    <h3 class="font-semibold text-gray-800 mt-1">

                                        {{ $leave->hrdApprover->name ?? '-' }}

                                    </h3>

                                </div>

                                <div class="text-right">

                                    <p class="text-xs text-emerald-500">
                                        Tanggal
                                    </p>

                                    <h3 class="font-semibold text-gray-800 mt-1">

                                        @if($leave->hrd_approved_at)

                                        {{ \Carbon\Carbon::parse($leave->hrd_approved_at)->format('d M Y H:i') }}

                                        @else
                                        -
                                        @endif

                                    </h3>

                                </div>

                            </div>

                            <div class="mt-3">

                                @if($leave->status == 'approved')

                                <span
                                    class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Approved HRD
                                </span>

                                @elseif($leave->status == 'rejected')

                                <span
                                    class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Rejected HRD
                                </span>

                                @elseif($leave->status == 'waiting_hrd')

                                <span
                                    class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold">
                                    Menunggu Approval HRD
                                </span>

                                @endif

                            </div>

                        </div>

                        @endif

                    </div>

                </div>

                @endforeach

            </div>

            @else

            <!-- EMPTY -->
            <div
                class="bg-white rounded-3xl shadow-xl p-10 text-center mt-4">

                <div class="text-6xl mb-4">
                    📅
                </div>

                <h2 class="text-xl font-bold text-gray-700">
                    Belum Ada Riwayat
                </h2>

                <p class="text-gray-500 text-sm mt-2">
                    Pengajuan cuti Anda akan muncul di sini
                </p>

            </div>

            @endif

        </div>

    </div>

</x-app-layout>