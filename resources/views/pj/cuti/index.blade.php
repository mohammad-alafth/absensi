<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-5xl mx-auto">

            <!-- HEADER -->
            <div class="bg-white rounded-3xl shadow p-5 mb-5">

                <div class="flex items-center justify-between mb-5">

                    <div>

                        <h1 class="text-2xl font-bold">
                            Approval Cuti PJ
                        </h1>

                        <p class="text-sm text-gray-500 mt-1">
                            Pengajuan cuti berdasarkan divisi PJ
                        </p>

                    </div>

                    <div
                        class="bg-blue-100 text-blue-700 px-4 py-2 rounded-2xl text-sm font-semibold">

                        {{ $leaves->count() }} Pengajuan

                    </div>

                </div>



                <!-- LIST -->
                @forelse($leaves as $leave)

                <div
                    x-data="{ showDetail: false }"
                    x-effect="document.body.classList.toggle('overflow-hidden', showDetail)"
                    class="mb-4">

                    <!-- MODAL DETAIL -->
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

                                <div>

                                    <h2 class="text-2xl font-bold text-indigo-600">
                                        Detail Pengajuan Cuti
                                    </h2>

                                    <p class="text-sm text-gray-500 mt-1">
                                        Informasi lengkap pengajuan cuti
                                    </p>

                                </div>

                                <button
                                    @click="showDetail = false"
                                    class="text-3xl text-gray-400 hover:text-gray-700">

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

                                <p class="text-sm text-gray-500 mb-2">
                                    Alasan Cuti
                                </p>

                                <div class="bg-slate-50 rounded-2xl p-4">
                                    {{ $leave->reason }}
                                </div>

                            </div>

                            <!-- ADDRESS -->
                            <div class="mt-4">

                                <p class="text-sm text-gray-500 mb-2">
                                    Alamat Selama Cuti
                                </p>

                                <div class="bg-slate-50 rounded-2xl p-4">
                                    {{ $leave->address_during_leave ?? '-' }}
                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- CARD -->
                    <div class="border rounded-2xl p-5 bg-white shadow-sm">

                        <div class="flex justify-between items-start gap-6 w-full flex-col lg:flex-row">

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

                                <!-- INFO -->
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

                            </div>

                            <!-- ACTION -->
                            <div
                                x-data="{ rejectModal:false }"
                                class="flex flex-col gap-3 lg:w-[180px] w-full">

                                <!-- DETAIL -->
                                <button
                                    @click="showDetail = true"
                                    type="button"
                                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2 rounded-xl w-full">

                                    Detail

                                </button>

                                <!-- PDF -->
                                @if($leave->pdf_file)

                                <a
                                    href="{{ asset('storage/' . $leave->pdf_file) }}"
                                    target="_blank"
                                    class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl text-center">

                                    Lihat PDF

                                </a>

                                @endif

                                <!-- APPROVE -->
                                <button
                                    type="button"
                                    onclick="openSignatureModal({{ $leave->id }})"
                                    class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-xl">

                                    Approve

                                </button>

                                <form
                                    id="approveForm{{ $leave->id }}"
                                    method="POST"
                                    action="{{ route('pj.cuti.approve', $leave->id) }}"
                                    class="hidden">

                                    @csrf

                                    <input
                                        type="hidden"
                                        name="signature"
                                        id="signatureInput{{ $leave->id }}">

                                </form>

                                <!-- REJECT -->
                                <button
                                    @click="rejectModal = true"
                                    type="button"
                                    class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl">

                                    Reject

                                </button>

                                <!-- MODAL REJECT -->
                                <div
                                    x-show="rejectModal"
                                    x-transition
                                    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 p-4"
                                    style="display:none;">

                                    <div
                                        @click.away="rejectModal = false"
                                        class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6">

                                        <h2 class="text-xl font-bold text-red-500 mb-2">
                                            Reject Pengajuan
                                        </h2>

                                        <p class="text-sm text-gray-500 mb-4">
                                            Berikan alasan penolakan dari PJ
                                        </p>

                                        <form
                                            method="POST"
                                            action="{{ route('pj.cuti.reject', $leave->id) }}">

                                            @csrf

                                            <textarea
                                                name="note"
                                                rows="4"
                                                required
                                                class="w-full border rounded-2xl p-4 focus:outline-none focus:ring-2 focus:ring-red-400"
                                                placeholder="Contoh: Pengajuan tidak sesuai ketentuan..."></textarea>

                                            <div class="grid grid-cols-2 gap-3 mt-5">

                                                <button
                                                    type="button"
                                                    @click="rejectModal = false"
                                                    class="bg-gray-100 hover:bg-gray-200 py-3 rounded-2xl">

                                                    Batal

                                                </button>

                                                <button
                                                    type="submit"
                                                    class="bg-red-500 hover:bg-red-600 text-white py-3 rounded-2xl">

                                                    Submit Reject

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                @empty

                <div class="text-center py-16 bg-white rounded-3xl shadow-sm">

                    <div class="text-6xl mb-4">
                        📄
                    </div>

                    <h2 class="text-xl font-bold text-gray-700">
                        Tidak Ada Approval
                    </h2>

                    <p class="text-gray-500 mt-2">
                        Belum ada pengajuan cuti yang menunggu approval PJ
                    </p>

                </div>

                @endforelse
            </div>
        </div>

    </div>

    <!-- SIGNATURE MODAL -->
    <div
        id="signatureModal"
        class="hidden fixed inset-0 bg-black/50 z-[999] flex items-center justify-center p-4">

        <div class="bg-white rounded-3xl p-6 w-full max-w-md">

            <h2 class="font-bold text-xl mb-4 text-center">
                Tanda Tangan PJ
            </h2>

            <canvas
                id="signature-pad"
                width="350"
                height="180"
                class="border rounded-xl w-full">
            </canvas>

            <div class="grid grid-cols-2 gap-3 mt-4">

                <button
                    type="button"
                    onclick="clearSignature()"
                    class="bg-gray-200 py-2 rounded-xl">

                    Clear

                </button>

                <button
                    type="button"
                    onclick="saveSignature()"
                    class="bg-blue-500 text-white py-2 rounded-xl">

                    Simpan

                </button>

            </div>

        </div>

    </div>

    <!-- SCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

    <script>
        let currentLeaveId = null;

        const canvas =
            document.getElementById('signature-pad');

        const signaturePad =
            new SignaturePad(canvas);

        function openSignatureModal(leaveId) {

            currentLeaveId = leaveId;

            signaturePad.clear();

            document
                .getElementById('signatureModal')
                .classList.remove('hidden');

        }

        function clearSignature() {

            signaturePad.clear();

        }

        function saveSignature() {

            if (signaturePad.isEmpty()) {

                Swal.fire({
                    icon: 'warning',
                    title: 'Oops',
                    text: 'Tanda tangan masih kosong'
                });

                return;

            }

            const signature =
                signaturePad.toDataURL('image/png');

            document.getElementById(
                'signatureInput' + currentLeaveId
            ).value = signature;

            document
                .getElementById('signatureModal')
                .classList.add('hidden');

            document
                .getElementById(
                    'approveForm' + currentLeaveId
                )
                .submit();

        }
    </script>

</x-app-layout>