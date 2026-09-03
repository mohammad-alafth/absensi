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
                        class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4"
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
                            <div class="flex flex-col gap-3">

                                <!-- DETAIL -->
                                <button
                                    @click="showDetail = true"
                                    type="button"
                                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2 rounded-xl w-full">

                                    Detail

                                </button>
                                @if($leave->pdf_file)
                                <a href="{{ asset('storage/' . $leave->pdf_file) }}"
                                    target="_blank"
                                    class="mt-2 inline-block bg-red-500 text-white px-4 py-2 rounded-xl">
                                    Lihat PDF
                                </a>
                                @endif

                                    <div x-data="{ approveModal:false }">

                                        <button
                                            type="button"
                                            onclick="openHRDSignatureModal({{ $leave->id }})"
                                            class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-xl w-full">

                                            Approve

                                        </button>

                                        <form
                                            id="hrdApproveForm{{ $leave->id }}"
                                            method="POST"
                                            action="{{ route('hrd.cuti.approve', $leave->id) }}"
                                            class="hidden">

                                            @csrf

                                            <input
                                                type="hidden"
                                                name="signature"
                                                id="hrdSignatureInput{{ $leave->id }}">

                                        </form>

                                    </div>

                                <div x-data="{ rejectModal:false }">

                                    <!-- BUTTON REJECT -->
                                    <button
                                        @click="rejectModal = true"
                                        type="button"
                                        class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl w-full">

                                        Reject

                                    </button>

                                    <!-- MODAL REJECT -->
                                    <div
                                        x-show="rejectModal"
                                        x-transition
                                        class="fixed inset-0 z-[999] overflow-y-auto bg-black/50 p-4"
                                        style="display:none;">

                                        <div
                                            @click.away="rejectModal = false"
                                            class="bg-white rounded-3xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto p-6 mx-auto my-8">

                                            <h2 class="text-xl font-bold text-red-500 mb-2">
                                                Reject Pengajuan
                                            </h2>

                                            <p class="text-sm text-gray-500 mb-4">
                                                Berikan alasan penolakan dari HRD
                                            </p>

                                            <form
                                                method="POST"
                                                action="{{ route('hrd.cuti.reject', $leave->id) }}">

                                                @csrf

                                                <textarea
                                                    name="note"
                                                    rows="4"
                                                    required
                                                    class="w-full border rounded-2xl p-4 focus:outline-none focus:ring-2 focus:ring-red-400"
                                                    placeholder="Contoh: Pengajuan tidak sesuai ketentuan..."></textarea>

                                                @error('note')
                                                <p class="text-red-500 text-xs mt-2">
                                                    {{ $message }}
                                                </p>
                                                @enderror

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
                <div
                    id="hrdSignatureModal"
                    class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[999] overflow-y-auto p-4">

                    <div class="bg-white rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-100 modal-animate mx-auto my-8">

                        <div class="flex justify-between items-center mb-3">
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="closeHRDSignatureModal()" class="text-xs font-bold text-gray-500 hover:text-indigo-600 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                    <span>←</span> Kembali
                                </button>
                                <h2 class="font-bold text-lg text-gray-800">
                                    ✍️ Tanda Tangan HRD
                                </h2>
                            </div>
                            <button type="button" onclick="closeHRDSignatureModal()" class="text-2xl text-gray-400 hover:text-gray-700">
                                ×
                            </button>
                        </div>

                        <div class="relative border-2 border-dashed border-gray-300 rounded-2xl overflow-hidden bg-gray-50/50 shadow-inner">
                            <canvas
                                id="hrd-signature-pad"
                                class="w-full h-48 bg-white cursor-crosshair touch-none"
                                style="touch-action: none;">
                            </canvas>
                            <div class="absolute bottom-2 right-3 pointer-events-none text-[11px] text-gray-400 font-medium">
                                Goreskan tanda tangan Anda
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mt-5">
                            <button
                                type="button"
                                onclick="clearHRDSignature()"
                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-2xl text-xs font-bold transition active:scale-95">
                                🔄 Bersihkan
                            </button>

                            <button
                                type="button"
                                onclick="closeHRDSignatureModal()"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-2xl text-xs font-bold transition active:scale-95">
                                ← Kembali
                            </button>

                            <button
                                type="button"
                                onclick="saveHRDSignature()"
                                class="bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-2xl text-xs font-bold shadow-md shadow-blue-200 transition active:scale-95">
                                ✓ Simpan
                            </button>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

    <script>
        let currentHRDLeaveId = null;
        let hrdPad = null;

        function openHRDSignatureModal(leaveId) {
            currentHRDLeaveId = leaveId;
            document.getElementById('hrdSignatureModal').classList.remove('hidden');

            const hrdCanvas = document.getElementById('hrd-signature-pad');

            setTimeout(() => {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                hrdCanvas.width = hrdCanvas.offsetWidth * ratio;
                hrdCanvas.height = 192 * ratio;
                hrdCanvas.getContext("2d").scale(ratio, ratio);

                if (!hrdPad) {
                    hrdPad = new SignaturePad(hrdCanvas, {
                        backgroundColor: 'white',
                        penColor: 'black'
                    });
                } else {
                    hrdPad.clear();
                }
            }, 100);
        }

        function closeHRDSignatureModal() {
            document.getElementById('hrdSignatureModal').classList.add('hidden');
        }

        function clearHRDSignature() {
            if (hrdPad) hrdPad.clear();
        }

        function saveHRDSignature() {
            if (!hrdPad || hrdPad.isEmpty()) {
                alert('Tanda tangan masih kosong');
                return;
            }

            const signature = hrdPad.toDataURL('image/png');
            document.getElementById('hrdSignatureInput' + currentHRDLeaveId).value = signature;
            closeHRDSignatureModal();
            document.getElementById('hrdApproveForm' + currentHRDLeaveId).submit();
        }
    </script>
</x-app-layout>