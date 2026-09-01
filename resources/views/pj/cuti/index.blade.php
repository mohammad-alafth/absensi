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
                                x-data="{ rejectModal: false, approveModal: false }"
                                class="flex flex-col gap-3 lg:w-[180px] w-full">

                                <!-- DETAIL -->
                                <button
                                    @click="showDetail = true"
                                    type="button"
                                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-medium transition text-sm shadow-sm active:scale-95">

                                    Detail

                                </button>

                                <!-- PDF -->
                                @if($leave->pdf_file)

                                <a
                                    href="{{ asset('storage/' . $leave->pdf_file) }}"
                                    target="_blank"
                                    class="bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-xl text-center font-medium transition text-sm shadow-sm active:scale-95">

                                    Lihat PDF

                                </a>

                                @endif

                                <!-- APPROVE -->
                                <button
                                    type="button"
                                    @click="approveModal = true; initPad({{ $leave->id }});"
                                    class="bg-green-500 hover:bg-green-600 text-white px-5 py-2.5 rounded-xl font-medium transition text-sm shadow-sm active:scale-95">

                                    Approve

                                </button>

                                <!-- MODAL APPROVE TANDA TANGAN -->
                                <div
                                    x-show="approveModal"
                                    x-transition
                                    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                                    style="display:none;">

                                    <div
                                        @click.away="approveModal = false"
                                        class="bg-white rounded-3xl p-6 w-full max-w-md shadow-2xl border border-gray-100">

                                        <div class="flex justify-between items-center mb-3">
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="approveModal = false" class="text-xs font-bold text-gray-500 hover:text-indigo-600 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                                    <span>←</span> Kembali
                                                </button>
                                                <h2 class="text-lg font-bold text-gray-800">
                                                    ✍️ Tanda Tangan Approval
                                                </h2>
                                            </div>
                                            <button @click="approveModal = false" class="text-2xl text-gray-400 hover:text-gray-700">
                                                ×
                                            </button>
                                        </div>

                                        <form
                                            id="approve-form-{{ $leave->id }}"
                                            method="POST"
                                            action="{{ route('pj.cuti.approve', $leave->id) }}">

                                            @csrf

                                            <div class="relative border-2 border-dashed border-gray-300 rounded-2xl overflow-hidden bg-gray-50/50 shadow-inner">
                                                <canvas
                                                    id="signature-pad-{{ $leave->id }}"
                                                    class="w-full h-48 bg-white cursor-crosshair touch-none"
                                                    style="touch-action: none;">
                                                </canvas>
                                                <div class="absolute bottom-2 right-3 pointer-events-none text-[11px] text-gray-400 font-medium">
                                                    Goreskan tanda tangan Anda
                                                </div>
                                            </div>

                                            <input
                                                type="hidden"
                                                name="signature"
                                                id="signature-input-{{ $leave->id }}">

                                            <div class="grid grid-cols-3 gap-2 mt-5">

                                                <button
                                                    type="button"
                                                    onclick="clearPad({{ $leave->id }})"
                                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-2xl text-xs font-bold transition active:scale-95">

                                                    🔄 Bersihkan

                                                </button>

                                                <button
                                                    type="button"
                                                    @click="approveModal = false"
                                                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-2xl text-xs font-bold transition active:scale-95">

                                                    ← Kembali

                                                </button>

                                                <button
                                                    type="button"
                                                    onclick="submitApprove({{ $leave->id }})"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-2xl text-xs font-bold shadow-md shadow-emerald-200 transition active:scale-95">

                                                    ✓ Simpan

                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                                <!-- REJECT -->
                                <button
                                    @click="rejectModal = true"
                                    type="button"
                                    class="bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-xl font-medium transition text-sm shadow-sm active:scale-95">

                                    Reject

                                </button>

                                <!-- MODAL REJECT -->
                                <div
                                    x-show="rejectModal"
                                    x-transition
                                    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                                    style="display:none;">

                                    <div
                                        @click.away="rejectModal = false"
                                        class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6">

                                        <h2 class="text-xl font-bold text-red-500 mb-2">
                                            Catatan Penolakan Cuti
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
                                                class="w-full border border-gray-200 rounded-2xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                                                placeholder="Contoh: Pengajuan tidak sesuai ketentuan..."></textarea>

                                            <div class="grid grid-cols-2 gap-3 mt-5">

                                                <button
                                                    type="button"
                                                    @click="rejectModal = false"
                                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-2xl text-sm font-semibold transition">

                                                    Batal

                                                </button>

                                                <button
                                                    type="submit"
                                                    class="bg-red-500 hover:bg-red-600 text-white py-3 rounded-2xl text-sm font-semibold shadow-md transition">

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

    <!-- SCRIPT SIGNATURE -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

    <script>
        let signaturePads = {};

        function initPad(id) {
            setTimeout(() => {
                const canvas = document.getElementById('signature-pad-' + id);
                if (!canvas) return;

                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = 192 * ratio;
                canvas.getContext("2d").scale(ratio, ratio);

                if (signaturePads[id]) {
                    signaturePads[id].off();
                }

                signaturePads[id] = new SignaturePad(canvas, {
                    backgroundColor: 'white',
                    penColor: 'black'
                });
            }, 200);
        }

        function clearPad(id) {
            if (signaturePads[id]) {
                signaturePads[id].clear();
            }
        }

        function submitApprove(id) {
            const pad = signaturePads[id];

            if (!pad || pad.isEmpty()) {
                alert('Tanda tangan wajib diisi sebelum menyimpan approval!');
                return;
            }

            const signature = pad.toDataURL('image/png');
            document.getElementById('signature-input-' + id).value = signature;
            document.getElementById('approve-form-' + id).submit();
        }
    </script>

</x-app-layout>