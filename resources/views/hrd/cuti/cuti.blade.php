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


                <div
                    x-data="{ showDetail: false }"
                    x-effect="document.body.classList.toggle('overflow-hidden', showDetail)"
                    class="mb-4">

                    <!-- MODAL -->
                    <div
                        x-show="showDetail"
                        x-transition
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
                                    class="text-2xl text-gray-500">

                                    ×

                                </button>

                            </div>

                            <!-- CONTENT -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                                <div>
                                    <p class="text-gray-500">Nama</p>
                                    <p class="font-semibold">
                                        {{ $leave->user->name }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Role</p>
                                    <p class="font-semibold">
                                        {{ $leave->user->role }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-500">Jenis Cuti</p>
                                    <p class="font-semibold">
                                        {{ $leave->leave_type }}
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
                                    <p class="text-gray-500">Total Hari</p>
                                    <p class="font-semibold">
                                        {{ $leave->total_days }} Hari
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

                                <div>
                                    <p class="text-gray-500">Kontak Darurat</p>
                                    <p class="font-semibold">
                                        {{ $leave->emergency_contact ?? '-' }}
                                    </p>
                                </div>

                            </div>

                            <!-- ALASAN -->
                            <div class="mt-5">

                                <p class="text-gray-500 text-sm mb-2">
                                    Alasan Cuti
                                </p>

                                <div class="bg-slate-50 rounded-2xl p-4">
                                    {{ $leave->reason }}
                                </div>

                            </div>

                            <!-- ALAMAT -->
                            <div class="mt-4">

                                <p class="text-gray-500 text-sm mb-2">
                                    Alamat Selama Cuti
                                </p>

                                <div class="bg-slate-50 rounded-2xl p-4">
                                    {{ $leave->address_during_leave ?? '-' }}
                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- CARD -->
                    <div class="border rounded-2xl p-5">

                        <div class="flex justify-between items-start gap-6 w-full">
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
                                    class="mt-4 w-full bg-slate-50 border rounded-2xl p-4">
                                    <p class="text-xs text-gray-500 mb-1">
                                        Alasan Cuti
                                    </p>

                                    <p class="text-sm text-gray-700">
                                        {{ $leave->reason }}
                                    </p>

                                </div>

                                <!-- APPROVAL PJ -->
                                <div
                                    class="mt-4 w-full bg-blue-50 border border-blue-100 rounded-2xl p-4">
                                    <div class="flex justify-between items-start gap-4 flex-wrap sm:flex-nowrap">
                                        <div class="flex-1 w-full min-w-0">

                                            <p class="text-xs text-blue-500">
                                                Approved PJ
                                            </p>

                                            <h3 class="font-semibold mt-1">
                                                {{ $leave->pjApprover->name ?? '-' }}
                                            </h3>

                                        </div>

                                        <div class="flex-1 text-right">

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

                                <!-- DETAIL -->
                                <button
                                    @click="showDetail = true"
                                    type="button"
                                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2 rounded-xl w-full">

                                    Detail

                                </button>

                                <form method="POST"
                                    action="{{ route('hrd.cuti.approve', $leave->id) }}">

                                    @csrf

                                    <button
                                        class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-xl w-full">

                                        Approve

                                    </button>

                                </form>

                                <form method="POST"
                                    action="{{ route('hrd.cuti.reject', $leave->id) }}">

                                    @csrf

                                    <button
                                        class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl w-full">

                                        Reject

                                    </button>

                                </form>

                            </div>

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