<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-5xl mx-auto">

            <!-- HEADER BAR -->
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
                    {{ $shiftChangeRequests->count() }} Pengajuan Pending
                </div>

            </div>

            <!-- TITLE CARD -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-5">
                <h1 class="text-2xl font-bold text-gray-800">
                    Approval Perubahan Shift PJ
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Otorisasi permohonan perubahan shift karyawan divisi
                    <span class="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-lg border border-blue-100 uppercase">
                        {{ $divisionRole }}
                    </span>
                </p>
            </div>

            @if(session('success'))
            <div class="mb-5 bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium">
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="mb-5 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium">
                {{ session('error') }}
            </div>
            @endif

            <!-- LIST REQUESTS -->
            <div class="space-y-4">

                @forelse($shiftChangeRequests as $item)
                <div x-data="{ showDetail: false, rejectModal: false, approveModal: false }"
                    x-effect="document.body.classList.toggle('overflow-hidden', showDetail)"
                    class="mb-4">

                    <!-- DETAIL MODAL -->
                    <div x-show="showDetail"
                        x-transition
                        class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm p-4"
                        style="display:none;">

                        <div @click.away="showDetail = false"
                            class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">

                            <div class="flex justify-between items-center mb-5">
                                <div>
                                    <h2 class="text-2xl font-bold text-indigo-600">
                                        Detail Perubahan Shift
                                    </h2>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Informasi pengajuan perubahan shift karyawan
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
                                    <p class="text-gray-500">Tanggal Shift</p>
                                    <p class="font-semibold text-indigo-700">{{ \Carbon\Carbon::parse($item->shift_date)->translatedFormat('l, d F Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Shift Saat Ini</p>
                                    <p class="font-semibold text-gray-800">{{ $item->currentShift->name ?? 'Tidak Ada Shift / Off' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Shift Yang Diminta</p>
                                    <p class="font-semibold text-indigo-700">
                                        {{ $item->requestedShift->name ?? '-' }}
                                        <span class="text-xs font-normal">({{ \Carbon\Carbon::parse($item->requestedShift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($item->requestedShift->end_time)->format('H:i') }})</span>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 bg-slate-50 border rounded-2xl p-4">
                                <p class="text-sm text-gray-500 mb-2">Alasan Pengajuan</p>
                                <p class="text-sm text-gray-700 leading-relaxed">{{ $item->reason }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD ITEM -->
                    <div class="border rounded-2xl p-5 bg-white shadow-sm hover:shadow-md transition">
                        <div class="flex flex-col lg:flex-row justify-between gap-4">

                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-xl font-bold">
                                        🔄
                                    </div>
                                    <div>
                                        <h2 class="font-bold text-lg text-gray-800">{{ $item->user->name }}</h2>
                                        <p class="text-sm text-gray-500">
                                            Tanggal Shift: <span class="font-semibold text-indigo-600">{{ \Carbon\Carbon::parse($item->shift_date)->translatedFormat('l, d F Y') }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div class="bg-gray-50 border rounded-xl p-3">
                                        <p class="text-xs text-gray-400 font-medium">Shift Asal:</p>
                                        <p class="text-sm font-bold text-gray-700">
                                            {{ $item->currentShift->name ?? 'Tidak Ada Shift / Off' }}
                                        </p>
                                    </div>

                                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-xl p-3">
                                        <p class="text-xs text-indigo-400 font-medium">Shift Yang Diminta:</p>
                                        <p class="text-sm font-bold text-indigo-800">
                                            {{ $item->requestedShift->name ?? '-' }}
                                            <span class="text-xs font-normal text-indigo-600">
                                                ({{ \Carbon\Carbon::parse($item->requestedShift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($item->requestedShift->end_time)->format('H:i') }})
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-3 bg-slate-50 border rounded-2xl px-5 py-3">
                                    <p class="text-xs text-gray-400 mb-1">Alasan Pengajuan</p>
                                    <p class="text-sm text-gray-700 font-medium">{{ $item->reason }}</p>
                                </div>
                            </div>

                            <!-- ACTION BUTTONS -->
                            <div class="flex flex-col gap-3 lg:w-[180px] justify-center">

                                <button @click="showDetail = true" type="button"
                                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-medium transition text-sm shadow-sm">
                                    Detail
                                </button>

                                <button @click="approveModal = true" type="button"
                                    class="bg-green-500 hover:bg-green-600 text-white px-5 py-2.5 rounded-xl font-medium transition text-sm shadow-sm">
                                    Approve
                                </button>

                                <button @click="rejectModal = true" type="button"
                                    class="bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-xl font-medium transition text-sm shadow-sm">
                                    Reject
                                </button>

                            </div>
                        </div>
                    </div>

                    <!-- APPROVE MODAL -->
                    <div x-show="approveModal"
                        x-transition
                        class="fixed inset-0 z-[999] overflow-y-auto bg-black/60 backdrop-blur-sm p-4"
                        style="display:none;">

                        <div @click.away="approveModal = false" class="bg-white rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl border border-gray-100 mx-auto my-8">
                            <h2 class="text-xl font-bold text-green-600 mb-2 flex items-center gap-2">
                                <span>✅</span> Konfirmasi Approval Shift
                            </h2>
                            <p class="text-xs text-gray-500 mb-4">
                                Anda menyetujui perubahan shift untuk <b>{{ $item->user->name }}</b> pada tanggal <b>{{ \Carbon\Carbon::parse($item->shift_date)->translatedFormat('d F Y') }}</b>.
                            </p>

                            <form method="POST" action="{{ route('pj.shift-change.approve', $item->id) }}">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Setujui / Ubah Ke Shift:</label>
                                    <select name="requested_shift_id" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-xs focus:ring-4 focus:ring-green-500/10 focus:border-green-500 outline-none">
                                        @foreach($shifts as $sf)
                                        <option value="{{ $sf->id }}" {{ $sf->id == $item->requested_shift_id ? 'selected' : '' }}>
                                            {{ $sf->name }} ({{ \Carbon\Carbon::parse($sf->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sf->end_time)->format('H:i') }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mt-5">
                                    <button type="button" @click="approveModal = false" class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl text-xs font-bold transition">
                                        ← Batal
                                    </button>
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl text-xs font-bold shadow-md shadow-green-200 transition">
                                        ✓ Ya, Setujui & Update
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- REJECT MODAL -->
                    <div x-show="rejectModal"
                        x-transition
                        class="fixed inset-0 z-[999] overflow-y-auto bg-black/50 p-4"
                        style="display:none;">

                        <div @click.away="rejectModal = false" class="bg-white rounded-3xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto shadow-xl mx-auto my-8">
                            <h2 class="text-xl font-bold text-red-500 mb-4">
                                Catatan Penolakan Perubahan Shift
                            </h2>

                            <form method="POST" action="{{ route('pj.shift-change.reject', $item->id) }}">
                                @csrf
                                <textarea name="note" rows="4" required class="w-full border rounded-2xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                    placeholder="Masukkan alasan penolakan PJ..."></textarea>

                                <div class="grid grid-cols-2 gap-3 mt-4">
                                    <button type="button" @click="rejectModal = false" class="bg-gray-200 py-3 rounded-xl text-sm font-semibold">
                                        Batal
                                    </button>
                                    <button type="submit" class="bg-red-500 text-white py-3 rounded-xl text-sm font-semibold">
                                        Submit Penolakan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                @empty

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-10 text-center">
                    <div class="text-6xl mb-4">📄</div>
                    <h2 class="text-xl font-bold text-gray-700">Tidak Ada Approval Pending</h2>
                    <p class="text-gray-500 mt-2 text-sm">Belum ada pengajuan perubahan shift yang menunggu approval dari divisi Anda.</p>
                </div>

                @endforelse
            </div>

        </div>
    </div>

</x-app-layout>
