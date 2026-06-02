<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-5xl mx-auto">

            <div class="flex items-center justify-between mb-5">

                <a href="{{ route('hrd.dashboard') }}"
                    class="inline-flex items-center gap-2
                    bg-white border border-gray-200
                    hover:border-blue-300 hover:bg-blue-50
                    text-gray-700 hover:text-blue-700
                    px-4 py-2 rounded-xl
                    shadow-sm transition text-sm font-semibold">

                    <span class="text-base">←</span>
                    Kembali
                </a>

                <div class="bg-gradient-to-r from-[#1E40AF] to-blue-500
                    text-white px-5 py-2 rounded-2xl
                    shadow-lg text-sm font-semibold">
                    {{ $overtimes->count() }} Pengajuan
                </div>

            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-5">
                <h1 class="text-2xl font-bold text-gray-800">
                    Approval Lembur HRD
                </h1>
                <p class="text-sm text-gray-500 mt-1 mb-5">
                    Daftar pengajuan lembur yang sudah disetujui PJ dan menunggu verifikasi HRD
                </p>
            

            @if(session('success'))
            <div class="mb-5 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-2xl">
                {{ session('success') }}
            </div>
            @endif

            @forelse($overtimes as $item)
            <div x-data="{ showDetail: false, rejectModal: false, approveModal: false }"
                x-effect="document.body.classList.toggle('overflow-hidden', showDetail)"
                class="mb-4">

                <div x-show="showDetail"
                    x-transition
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                    style="display:none;">

                    <div @click.away="showDetail = false"
                        class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">

                        <div class="flex justify-between items-center mb-5">
                            <div>
                                <h2 class="text-2xl font-bold text-indigo-600">
                                    Detail Pengajuan Lembur
                                </h2>
                                <p class="text-sm text-gray-500 mt-1">
                                    Informasi lengkap pengajuan lembur karyawan
                                </p>
                            </div>
                            <button @click="showDetail = false" class="text-3xl text-gray-400 hover:text-gray-700">
                                ×
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Nama</p>
                                <p class="font-semibold">{{ $item->user->name }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Departemen</p>
                                <p class="font-semibold">{{ strtoupper(str_replace('_', ' ', $item->department)) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Tanggal Lembur</p>
                                <p class="font-semibold">{{ \Carbon\Carbon::parse($item->overtime_date)->format('d M Y') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Jenis Hari</p>
                                <p class="font-semibold">{{ $item->day_type == 'hari_libur' ? 'Hari Libur' : 'Hari Kerja' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Jam Mulai</p>
                                <p class="font-semibold">{{ $item->start_time }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Jam Selesai</p>
                                <p class="font-semibold">{{ $item->end_time }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Total Jam</p>
                                <p class="font-semibold">{{ $item->total_hours }} Jam</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Disetujui PJ Oleh</p>
                                <p class="font-semibold text-green-600">{{ $item->pjApprover->name ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 bg-slate-50 border rounded-2xl p-4">
                            <p class="text-sm text-gray-500 mb-2">Uraian Tugas</p>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $item->reason }}</p>
                        </div>
                    </div>
                </div>

                <div class="border rounded-2xl p-5 bg-white shadow-sm">
                    <div class="flex flex-col lg:flex-row justify-between gap-4">

                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-xl">
                                    ⏰
                                </div>
                                <div>
                                    <h2 class="font-bold text-lg">{{ $item->user->name }}</h2>
                                    <p class="text-sm text-gray-500">{{ strtoupper(str_replace('_', ' ', $item->department)) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <p class="text-sm">
                                    <span class="font-semibold">Tanggal:</span>
                                    {{ \Carbon\Carbon::parse($item->overtime_date)->format('d M Y') }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-semibold">Jam:</span>
                                    {{ $item->start_time }} - {{ $item->end_time }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-semibold">Total:</span>
                                    {{ $item->total_hours }} Jam
                                </p>
                                <p class="text-sm">
                                    <span class="font-semibold">Jenis:</span>
                                    {{ $item->day_type == 'hari_libur' ? 'Hari Libur' : 'Hari Kerja' }}
                                </p>
                            </div>

                            <div class="mt-4 bg-slate-50 border rounded-2xl px-6 py-4">
                                <p class="text-xs text-gray-500 mb-2">Uraian Tugas</p>
                                <p class="text-sm text-gray-700">{{ $item->reason }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 lg:w-[180px]">

                            <button @click="showDetail = true" type="button"
                                class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2 rounded-xl font-medium transition">
                                Detail
                            </button>

                            @if($item->pdf_file)
                            <a href="{{ asset('storage/' . $item->pdf_file) }}" target="_blank"
                                class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2 rounded-xl text-center font-medium transition">
                                Lihat PDF
                            </a>
                            @endif

                            <button type="button" @click="approveModal = true; initPad({{ $item->id }});"
                                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-xl font-medium transition">
                                Approve
                            </button>

                            <button @click="rejectModal = true" type="button"
                                class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl font-medium transition">
                                Reject
                            </button>

                        </div>
                    </div>
                </div>

                <div x-show="approveModal"
                    x-transition
                    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 p-4"
                    style="display:none;">

                    <div @click.away="approveModal = false"
                        class="bg-white rounded-3xl p-6 w-full max-w-md shadow-xl">

                        <h2 class="text-xl font-bold text-green-600 mb-4">
                            Tanda Tangan Approval HRD
                        </h2>

                        <form id="approve-form-{{ $item->id }}" method="POST" action="{{ route('hrd.lembur.approve', $item->id) }}">
                            @csrf
                            <canvas id="signature-pad-{{ $item->id }}" width="400" height="200"
                                class="border rounded-2xl w-full bg-white">
                            </canvas>

                            <input type="hidden" name="signature" id="signature-input-{{ $item->id }}">

                            <div class="grid grid-cols-3 gap-2 mt-4">
                                <button type="button" onclick="clearPad({{ $item->id }})" class="bg-gray-200 py-3 rounded-xl text-sm font-semibold">
                                    Clear
                                </button>
                                <button type="button" @click="approveModal = false" class="bg-red-500 text-white py-3 rounded-xl text-sm font-semibold">
                                    Batal
                                </button>
                                <button type="button" onclick="submitApprove({{ $item->id }})" class="bg-green-600 text-white py-3 rounded-xl text-sm font-semibold">
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="rejectModal"
                    x-transition
                    class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50 p-4"
                    style="display:none;">

                    <div @click.away="rejectModal = false" class="bg-white rounded-3xl p-6 w-full max-w-md shadow-xl">
                        <h2 class="text-xl font-bold text-red-500 mb-4">
                            Catatan Penolakan HRD
                        </h2>

                        <form method="POST" action="{{ route('hrd.lembur.reject', $item->id) }}">
                            @csrf
                            <textarea name="note" rows="4" required class="w-full border rounded-2xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                placeholder="Masukkan alasan penolakan HRD..."></textarea>

                            <div class="grid grid-cols-2 gap-3 mt-4">
                                <button type="button" @click="rejectModal = false" class="bg-gray-200 py-3 rounded-xl text-sm font-semibold">
                                    Batal
                                </button>
                                <button type="submit" class="bg-red-500 text-white py-3 rounded-xl text-sm font-semibold">
                                    Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            @empty

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center">
                <div class="text-6xl mb-4">📄</div>
                <h2 class="text-xl font-bold text-gray-700">Tidak Ada Approval</h2>
                <p class="text-gray-500 mt-2">Belum ada pengajuan lembur yang menunggu approval HRD</p>
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
                const canvas = document.getElementById('signature-pad-' + id);
                if (!canvas) return;

                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = 200 * ratio;
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
                alert('Tanda tangan wajib diisi');
                return;
            }

            const signature = pad.toDataURL('image/png');
            document.getElementById('signature-input-' + id).value = signature;
            document.getElementById('approve-form-' + id).submit();
        }
    </script>

</x-app-layout>