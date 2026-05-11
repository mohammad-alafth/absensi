<x-app-layout>

    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-indigo-50 to-cyan-50 pb-28">

        <div class="max-w-5xl mx-auto px-4 sm:px-6 -mt-5">

            @if($leaves->count() > 0)

            <div class="space-y-4">

                <!-- BACK -->
                <div class="flex justify-between items-center mb-6">

                    <a href="{{ route('cuti') }}"
                        class="text-[#1E40AF] font-semibold text-sm">

                        ← Kembali

                    </a>

                </div>

                @foreach($leaves as $leave)

                <div x-data="{ showDetail: false }">

                    <!-- MODAL DETAIL -->
                    <div
                        x-show="showDetail"
                        x-transition.opacity
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                        style="display:none;">

                        <div
                            @click.away="showDetail = false"
                            class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">

                            <!-- HEADER -->
                            <div class="flex justify-between items-center mb-5">

                                <h2 class="text-xl font-bold text-indigo-600">
                                    Detail Pengajuan Cuti
                                </h2>

                                <button
                                    @click="showDetail = false"
                                    class="text-gray-500 text-2xl">

                                    ×

                                </button>

                            </div>

                            <!-- CONTENT -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                                <div>
                                    <p class="text-gray-500">Jenis Cuti</p>
                                    <p class="font-semibold">
                                        {{ $leave->leave_type }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Total Hari</p>
                                    <p class="font-semibold">
                                        {{ $leave->total_days }} Hari
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Tanggal Mulai</p>
                                    <p class="font-semibold">
                                        {{ $leave->start_date }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Tanggal Selesai</p>
                                    <p class="font-semibold">
                                        {{ $leave->end_date }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Tanggal Kembali</p>
                                    <p class="font-semibold">
                                        {{ $leave->return_date ?? '-' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Kontak Darurat</p>
                                    <p class="font-semibold">
                                        {{ $leave->emergency_contact ?? '-' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Delegasi</p>
                                    <p class="font-semibold">
                                        {{ $leave->delegate_name ?? '-' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">NIK Delegasi</p>
                                    <p class="font-semibold">
                                        {{ $leave->delegate_nik ?? '-' }}
                                    </p>
                                </div>

                            </div>

                            <!-- ALASAN -->
                            <div class="mt-5">

                                <p class="text-gray-500 text-sm mb-2">
                                    Alasan Cuti
                                </p>

                                <div class="bg-slate-50 rounded-2xl p-4 text-sm">

                                    {{ $leave->reason }}

                                </div>

                            </div>

                            <!-- ALAMAT -->
                            <div class="mt-4">

                                <p class="text-gray-500 text-sm mb-2">
                                    Alamat Selama Cuti
                                </p>

                                <div class="bg-slate-50 rounded-2xl p-4 text-sm">

                                    {{ $leave->address_during_leave ?? '-' }}

                                </div>

                            </div>

                            <!-- CATATAN -->
                            @if($leave->pj_note || $leave->hrd_note)

                            <div class="grid md:grid-cols-2 gap-4 mt-4">

                                <div class="bg-blue-50 rounded-2xl p-4">

                                    <p class="text-blue-500 text-sm mb-2">
                                        Catatan PJ
                                    </p>

                                    <p class="text-sm">
                                        {{ $leave->pj_note ?? '-' }}
                                    </p>

                                </div>

                                <div class="bg-emerald-50 rounded-2xl p-4">

                                    <p class="text-emerald-500 text-sm mb-2">
                                        Catatan HRD
                                    </p>

                                    <p class="text-sm">
                                        {{ $leave->hrd_note ?? '-' }}
                                    </p>

                                </div>

                            </div>

                            @endif

                        </div>

                    </div>


                    <!-- CARD -->
                    <div
                        class="bg-white/90 backdrop-blur-md border border-white/40 rounded-3xl shadow-lg overflow-hidden">

                        <div class="p-5">

                            <!-- TOP -->
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

                            <!-- SUMMARY -->
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

                            <!-- BUTTON DETAIL -->
                            <button
                                @click="showDetail = true"
                                class="mt-4 w-full bg-indigo-500 hover:bg-indigo-600 text-white py-3 rounded-2xl font-semibold transition">

                                Lihat Detail

                            </button>

                        </div>

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