<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-5xl mx-auto">

            <div class="flex items-center justify-between mb-5">

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

                <div class="bg-gradient-to-r from-[#1E40AF] to-blue-500
                    text-white px-5 py-2 rounded-2xl
                    shadow-lg text-sm font-semibold">
                    {{ $permissions->count() }} Pengajuan
                </div>

            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-5">
                <h1 class="text-2xl font-bold text-gray-800">
                    Approval Izin PJ
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Pengajuan izin karyawan berdasarkan divisi penanggung jawab Anda
                </p>
            </div>

            @if(session('success'))
            <div class="mb-5 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-2xl">
                {{ session('success') }}
            </div>
            @endif

            <div class="space-y-4">

                @forelse($permissions as $item)
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
                                        Detail Pengajuan Izin
                                    </h2>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Informasi lengkap berkas pengajuan izin karyawan
                                    </p>
                                </div>
                                <button @click="showDetail = false" class="text-3xl text-gray-400 hover:text-gray-700">
                                    ×
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-500">Nama Karyawan</p>
                                    <p class="font-semibold text-gray-800">{{ $item->user->name }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Jenis Izin</p>
                                    <p class="font-semibold text-indigo-700">{{ $item->jenis }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Tanggal Diajukan</p>
                                    <p class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}</p>
                                </div>
                                @if($item->jam_mulai)
                                <div>
                                    <p class="text-gray-500">Jam Mulai</p>
                                    <p class="font-semibold text-gray-800">{{ $item->jam_mulai }}</p>
                                </div>
                                @endif
                                @if($item->jam_selesai)
                                <div>
                                    <p class="text-gray-500">Jam Selesai</p>
                                    <p class="font-semibold text-gray-800">{{ $item->jam_selesai }}</p>
                                </div>
                                @endif
                            </div>

                            <div class="mt-5 bg-slate-50 border rounded-2xl p-4">
                                <p class="text-sm text-gray-500 mb-2">Alasan Pengajuan / Keterangan</p>
                                <p class="text-sm text-gray-700 leading-relaxed">{{ $item->alasan }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-2xl p-5 bg-white shadow-sm">
                        <div class="flex flex-col lg:flex-row justify-between gap-4">

                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-xl">
                                        📄
                                    </div>
                                    <div>
                                        <h2 class="font-bold text-lg text-gray-800">{{ $item->user->name }}</h2>
                                        <p class="text-sm text-gray-500">Kategori: <span class="font-semibold text-indigo-600">{{ $item->jenis }}</span></p>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-2">
                                    <p class="text-sm text-gray-700">
                                        <span class="font-semibold text-gray-500">Tanggal:</span>
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                                    </p>
                                    @if($item->jam_mulai || $item->jam_selesai)
                                    <p class="text-sm text-gray-700">
                                        <span class="font-semibold text-gray-500">Waktu / Jam:</span>
                                        {{ $item->jam_mulai ?? '00:00' }} s/d {{ $item->jam_selesai ?? 'Selesai' }}
                                    </p>
                                    @endif
                                </div>

                                <div class="mt-4 bg-slate-50 border rounded-2xl px-6 py-4">
                                    <p class="text-xs text-gray-500 mb-2">Alasan</p>
                                    <p class="text-sm text-gray-700">{{ $item->alasan }}</p>
                                </div>

                                @if($item->lampiran)
                                <div class="mt-3">
                                    <a href="{{ asset('storage/' . $item->lampiran) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:underline">
                                        📎 Lihat Bukti / Lampiran Dokumen
                                    </a>
                                </div>
                                @endif
                            </div>

                            <div class="flex flex-col gap-3 lg:w-[180px]">

                                <button @click="showDetail = true" type="button"
                                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2 rounded-xl font-medium transition text-sm shadow-sm">
                                    Detail
                                </button>

                                @if($item->pdf_file)
                                <a
                                    href="{{ asset('storage/' . $item->pdf_file) }}"
                                    target="_blank"
                                    class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl text-center">

                                    Lihat PDF

                                </a>
                                @endif

                                <button type="button" @click="approveModal = true; initPad({{ $item->id }});"
                                    class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-xl font-medium transition text-sm shadow-sm">
                                    Approve
                                </button>

                                <button @click="rejectModal = true" type="button"
                                    class="bg-red-500 hover:bg-red-600 text-white px-5 py-2 rounded-xl font-medium transition text-sm shadow-sm">
                                    Reject
                                </button>

                            </div>
                        </div>
                    </div>

                    <div x-show="approveModal"
                        x-transition
                        class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                        style="display:none;">

                        <div @click.away="approveModal = false"
                            class="bg-white rounded-3xl p-6 w-full max-w-md shadow-2xl border border-gray-100">

                            <div class="flex justify-between items-center mb-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="approveModal = false" class="text-xs font-bold text-gray-500 hover:text-indigo-600 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                        <span>←</span> Kembali
                                    </button>
                                    <h2 class="text-lg font-bold text-gray-800">
                                        ✍️ Tanda Tangan Approval PJ
                                    </h2>
                                </div>
                                <button @click="approveModal = false" class="text-2xl text-gray-400 hover:text-gray-700">
                                    ×
                                </button>
                            </div>

                            <form id="approve-form-{{ $item->id }}" method="POST" action="{{ route('pj.izin.approve', $item->id) }}">
                                @csrf
                                <div class="relative border-2 border-dashed border-gray-300 rounded-2xl overflow-hidden bg-gray-50/50 shadow-inner">
                                    <canvas id="signature-pad-{{ $item->id }}"
                                        class="w-full h-48 bg-white cursor-crosshair touch-none"
                                        style="touch-action: none;">
                                    </canvas>
                                    <div class="absolute bottom-2 right-3 pointer-events-none text-[11px] text-gray-400 font-medium">
                                        Goreskan tanda tangan Anda
                                    </div>
                                </div>

                                <input type="hidden" name="signature" id="signature-input-{{ $item->id }}">

                                <div class="grid grid-cols-3 gap-2 mt-5">
                                    <button type="button" onclick="clearPad({{ $item->id }})" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-2xl text-xs font-bold transition active:scale-95">
                                        🔄 Bersihkan
                                    </button>
                                    <button type="button" @click="approveModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-2xl text-xs font-bold transition active:scale-95">
                                        ← Kembali
                                    </button>
                                    <button type="button" onclick="submitApprove({{ $item->id }})" class="bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-2xl text-xs font-bold shadow-md shadow-emerald-200 transition active:scale-95">
                                        ✓ Simpan
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
                                Catatan Penolakan PJ
                            </h2>

                            <form method="POST" action="{{ route('pj.izin.reject', $item->id) }}">
                                @csrf
                                <textarea name="note" rows="4" required class="w-full border rounded-2xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                    placeholder="Masukkan alasan penolakan PJ..."></textarea>

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
                    <p class="text-gray-500 mt-2">Belum ada pengajuan izin yang menunggu approval PJ divisi Anda</p>
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
                alert('Tanda tangan wajib diisi');
                return;
            }

            const signature = pad.toDataURL('image/png');
            document.getElementById('signature-input-' + id).value = signature;
            document.getElementById('approve-form-' + id).submit();
        }
    </script>

</x-app-layout>