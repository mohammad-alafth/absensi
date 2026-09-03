<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-5xl mx-auto">

            <!-- CARD -->
            <!-- TOP NAV -->
            <div class="flex items-center justify-between mb-5">

                <!-- BACK -->
                <a href="{{ route('pj.dashboard') }}"
                    class="inline-flex items-center gap-2
                    bg-white border border-gray-200
                    hover:border-blue-300 hover:bg-blue-50
                    text-gray-700 hover:text-blue-700
                    px-4 py-2 rounded-xl
                    shadow-sm transition text-sm font-semibold">

                    <span class="text-base">←</span>

                    Kembali

                </a>

                <!-- BADGE -->
                <div
                    class="bg-gradient-to-r from-[#1E40AF] to-blue-500
                    text-white px-5 py-2 rounded-2xl
                    shadow-lg text-sm font-semibold">

                    {{ $overtimes->count() }} Pengajuan

                </div>

            </div>

            <!-- HEADER -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-5">

                <h1 class="text-2xl font-bold text-gray-800">
                    Approval Lembur PJ
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Pengajuan Lembur berdasarkan divisi PJ
                </p>

            </div>

            @forelse($overtimes as $item)

            <div
                x-data="{ showDetail: false }"
                x-effect="document.body.classList.toggle('overflow-hidden', showDetail)"
                class="mb-4">

                <!-- DETAIL MODAL -->
                <div
                    x-show="showDetail"
                    x-transition
                    class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm p-4"
                    style="display:none;">

                    <div
                        @click.away="showDetail = false"
                        class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">

                        <!-- HEADER -->
                        <div class="flex justify-between items-center mb-5">

                            <div>

                                <h2 class="text-2xl font-bold text-indigo-600">
                                    Detail Pengajuan Lembur
                                </h2>

                                <p class="text-sm text-gray-500 mt-1">
                                    Informasi lengkap pengajuan lembur
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

                                <p class="text-gray-500">
                                    Nama
                                </p>

                                <p class="font-semibold">
                                    {{ $item->user->name }}
                                </p>

                            </div>

                            <div>

                                <p class="text-gray-500">
                                    Departemen
                                </p>

                                <p class="font-semibold">
                                    {{ strtoupper(str_replace('_', ' ', $item->department)) }}
                                </p>

                            </div>

                            <div>

                                <p class="text-gray-500">
                                    Tanggal Lembur
                                </p>

                                <p class="font-semibold">
                                    {{ \Carbon\Carbon::parse($item->overtime_date)->format('d M Y') }}
                                </p>

                            </div>

                            <div>

                                <p class="text-gray-500">
                                    Jenis Hari
                                </p>

                                <p class="font-semibold">
                                    {{ $item->day_type == 'hari_libur'
                            ? 'Hari Libur'
                            : 'Hari Kerja'
                        }}
                                </p>

                            </div>

                            <div>

                                <p class="text-gray-500">
                                    Jam Mulai
                                </p>

                                <p class="font-semibold">
                                    {{ $item->start_time }}
                                </p>

                            </div>

                            <div>

                                <p class="text-gray-500">
                                    Jam Selesai
                                </p>

                                <p class="font-semibold">
                                    {{ $item->end_time }}
                                </p>

                            </div>

                            <div>

                                <p class="text-gray-500">
                                    Total Jam
                                </p>

                                <p class="font-semibold">
                                    {{ $item->total_hours }} Jam
                                </p>

                            </div>

                        </div>

                        <!-- URAIAN -->
                        <div class="mt-5 bg-slate-50 border rounded-2xl p-4">

                            <p class="text-sm text-gray-500 mb-2">
                                Uraian Tugas
                            </p>

                            <p class="text-sm text-gray-700 leading-relaxed">
                                {{ $item->reason }}
                            </p>

                        </div>

                    </div>

                </div>

                <!-- CARD -->
                <div class="border rounded-2xl p-5 bg-white shadow-sm">

                    <div class="flex flex-col lg:flex-row justify-between gap-4">

                        <!-- LEFT -->
                        <div class="flex-1">

                            <div class="flex items-center gap-3">

                                <div
                                    class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-xl">

                                    ⏰

                                </div>

                                <div>

                                    <h2 class="font-bold text-lg">
                                        {{ $item->user->name }}
                                    </h2>

                                    <p class="text-sm text-gray-500">
                                        {{ strtoupper(str_replace('_', ' ', $item->department)) }}
                                    </p>

                                </div>

                            </div>

                            <div class="mt-4 space-y-2">

                                <p class="text-sm">

                                    <span class="font-semibold">
                                        Tanggal:
                                    </span>

                                    {{ \Carbon\Carbon::parse($item->overtime_date)->format('d M Y') }}

                                </p>

                                <p class="text-sm">

                                    <span class="font-semibold">
                                        Jam:
                                    </span>

                                    {{ $item->start_time }}
                                    -
                                    {{ $item->end_time }}

                                </p>

                                <p class="text-sm">

                                    <span class="font-semibold">
                                        Total:
                                    </span>

                                    {{ $item->total_hours }} Jam

                                </p>

                                <p class="text-sm">

                                    <span class="font-semibold">
                                        Jenis:
                                    </span>

                                    {{ $item->day_type == 'hari_libur'
                            ? 'Hari Libur'
                            : 'Hari Kerja'
                        }}

                                </p>

                            </div>

                            <!-- URAIAN -->
                            <div class="mt-4 bg-slate-50 border rounded-2xl px-6 py-4">

                                <p class="text-xs text-gray-500 mb-2">
                                    Uraian Tugas
                                </p>

                                <p class="text-sm text-gray-700">
                                    {{ $item->reason }}
                                </p>

                            </div>

                        </div>

                        <!-- ACTION -->
                        <div
                            x-data="{
                    rejectModal:false,
                    approveModal:false
                }"
                            class="flex flex-col gap-3 lg:w-[180px]">

                            <!-- DETAIL -->
                            <button
                                @click="showDetail = true"
                                type="button"
                                class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2 rounded-xl">

                                Detail

                            </button>

                            <!-- PDF -->
                            @if($item->pdf_file)

                            <a
                                href="{{ asset('storage/' . $item->pdf_file) }}"
                                target="_blank"
                                class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl text-center">

                                Lihat PDF

                            </a>

                            @endif

                            <!-- APPROVE -->
                            <button
                                type="button"
                                @click="
                        approveModal = true;
                        initPad({{ $item->id }});
                    "
                                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-xl">

                                Approve

                            </button>

                            <!-- MODAL APPROVE -->
                            <div
                                x-show="approveModal"
                                x-transition
                                class="fixed inset-0 z-[999] overflow-y-auto bg-black/60 backdrop-blur-sm p-4"
                                style="display:none;">

                                <div
                                    @click.away="approveModal = false"
                                    class="bg-white rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-100 mx-auto my-8">

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
                                        id="approve-form-{{ $item->id }}"
                                        method="POST"
                                        action="{{ route('pj.lembur.approve', $item->id) }}">

                                        @csrf

                                        <div class="relative border-2 border-dashed border-gray-300 rounded-2xl overflow-hidden bg-gray-50/50 shadow-inner">
                                            <canvas
                                                id="signature-pad-{{ $item->id }}"
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
                                            id="signature-input-{{ $item->id }}">

                                        <div class="grid grid-cols-3 gap-2 mt-5">

                                            <button
                                                type="button"
                                                onclick="clearPad({{ $item->id }})"
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
                                                onclick="submitApprove({{ $item->id }})"
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
                                class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl">

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
                                    class="bg-white rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto mx-auto my-8">

                                    <h2 class="text-xl font-bold text-red-500 mb-4">
                                        Catatan Penolakan PJ
                                    </h2>

                                    <form
                                        method="POST"
                                        action="{{ route('pj.lembur.reject', $item->id) }}">

                                        @csrf

                                        <textarea
                                            name="note"
                                            rows="4"
                                            required
                                            class="w-full border rounded-2xl p-4"
                                            placeholder="Masukkan alasan penolakan..."></textarea>

                                        <div class="grid grid-cols-2 gap-3 mt-4">

                                            <button
                                                type="button"
                                                @click="rejectModal = false"
                                                class="bg-gray-200 py-3 rounded-xl">

                                                Batal

                                            </button>

                                            <button
                                                type="submit"
                                                class="bg-red-500 text-white py-3 rounded-xl">

                                                Submit

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

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center">

                <div class="text-6xl mb-4">
                    📄
                </div>

                <h2 class="text-xl font-bold text-gray-700">
                    Tidak Ada Approval
                </h2>

                <p class="text-gray-500 mt-2">
                    Belum ada pengajuan lembur yang menunggu approval PJ
                </p>

            </div>

            @endforelse

        </div>

    </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

    <script>
        let signaturePads = {};

        function initPad(id) {

            setTimeout(() => {

                const canvas =
                    document.getElementById(
                        'signature-pad-' + id
                    );

                if (!canvas) return;

                /*
                |--------------------------------------------------
                | FIX DPI & RESPONSIVE CANVAS
                |--------------------------------------------------
                */
                const ratio =
                    Math.max(window.devicePixelRatio || 1, 1);

                canvas.width =
                    canvas.offsetWidth * ratio;

                canvas.height =
                    192 * ratio;

                canvas.getContext("2d")
                    .scale(ratio, ratio);

                /*
                |--------------------------------------------------
                | DESTROY OLD
                |--------------------------------------------------
                */
                if (signaturePads[id]) {

                    signaturePads[id].off();

                }

                /*
                |--------------------------------------------------
                | INIT
                |--------------------------------------------------
                */
                signaturePads[id] =
                    new SignaturePad(canvas, {

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

                alert('Tanda tangan wajib diisi');

                return;

            }

            /*
            |--------------------------------------------------
            | SAVE BASE64
            |--------------------------------------------------
            */
            const signature =
                pad.toDataURL('image/png');

            document.getElementById(
                'signature-input-' + id
            ).value = signature;

            /*
            |--------------------------------------------------
            | SUBMIT FORM
            |--------------------------------------------------
            */
            document.getElementById(
                'approve-form-' + id
            ).submit();

        }
    </script>

</x-app-layout>