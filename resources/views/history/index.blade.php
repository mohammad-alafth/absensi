<x-app-layout>

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-6xl mx-auto">

            <!-- HEADER -->
            <div class="flex justify-between items-center mb-6">

                <a href="{{ route('dashboard') }}"
                    class="text-[#1E40AF] font-semibold text-sm">

                    ← Kembali

                </a>

                <div class="bg-[#1E40AF] text-white px-4 py-2 rounded-xl text-sm shadow">

                    Riwayat Pengajuan

                </div>

            </div>

            @forelse($years as $year)

            <!-- TAHUN -->
            <div class="mb-5">

                <h2 class="text-2xl font-extrabold text-[#1E40AF]">

                    Tahun {{ $year }}

                </h2>

                <p class="text-sm text-gray-500 mt-1">

                    Riwayat cuti, izin, dan lembur

                </p>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-10">

                <!-- ================= CUTI ================= -->
                <div class="bg-white rounded-3xl shadow-sm border border-blue-50 overflow-hidden">

                    <!-- HEADER -->
                    <div class="bg-[#1E40AF] text-white px-5 py-4">

                        <h3 class="font-bold text-lg">
                            📅 Riwayat Cuti
                        </h3>

                    </div>

                    <!-- CONTENT -->
                    <div class="p-4 space-y-4">

                        @forelse($leaves[$year] ?? [] as $leave)

                        <div class="border border-blue-50 rounded-2xl p-4 bg-[#f8f9ff]">

                            <div class="flex justify-between items-start mb-3">

                                <div>

                                    <p class="font-bold text-gray-800">
                                        {{ $leave->leave_type }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                                        s/d
                                        {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                                    </p>

                                </div>

                                @if($leave->status == 'approved')

                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Approved

                                </span>

                                @elseif($leave->status == 'rejected')

                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Rejected

                                </span>

                                @else

                                <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Pending

                                </span>

                                @endif

                            </div>

                            <div class="text-sm text-gray-600 mb-4">

                                Total:
                                <span class="font-semibold">
                                    {{ $leave->total_days }} Hari
                                </span>

                            </div>

                            <button
                                onclick="openModal('leave-{{ $leave->id }}')"
                                class="w-full bg-[#1E40AF] hover:bg-[#1e3a8a]
                                text-white py-2 rounded-xl text-sm font-medium transition">

                                Lihat Detail

                            </button>

                        </div>

                        <!-- MODAL CUTI -->
                        <div
                            id="leave-{{ $leave->id }}"
                            class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">

                            <div class="bg-white rounded-3xl w-full max-w-lg p-6 relative">

                                <button
                                    onclick="closeModal('leave-{{ $leave->id }}')"
                                    class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-xl">

                                    ✕

                                </button>

                                <h2 class="text-2xl font-bold text-[#1E40AF] mb-5">

                                    Detail Cuti

                                </h2>

                                <div class="space-y-4 text-sm">

                                    <div>
                                        <p class="text-gray-500">Jenis Cuti</p>
                                        <p class="font-semibold">
                                            {{ $leave->leave_type }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Periode</p>
                                        <p class="font-semibold">
                                            {{ $leave->start_date }} - {{ $leave->end_date }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Tanggal Masuk</p>
                                        <p class="font-semibold">
                                            {{ $leave->return_date }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Total Hari</p>
                                        <p class="font-semibold">
                                            {{ $leave->total_days }} Hari
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Alasan</p>
                                        <p class="font-semibold">
                                            {{ $leave->reason }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Delegasi</p>
                                        <p class="font-semibold">
                                            {{ $leave->delegate_name }}
                                            ({{ $leave->delegate_nik }})
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Kontak Darurat</p>
                                        <p class="font-semibold">
                                            {{ $leave->emergency_contact }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Status</p>
                                        <p class="font-semibold capitalize">
                                            {{ $leave->status }}
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </div>

                        @empty

                        <div class="text-center py-10">

                            <div class="text-4xl mb-3">
                                📅
                            </div>

                            <p class="text-sm text-gray-400">

                                Tidak ada data cuti

                            </p>

                        </div>

                        @endforelse

                    </div>

                </div>

                <!-- ================= IZIN ================= -->
                <div class="bg-white rounded-3xl shadow-sm border border-blue-50 overflow-hidden">

                    <!-- HEADER -->
                    <div class="bg-indigo-600 text-white px-5 py-4">

                        <h3 class="font-bold text-lg">
                            📝 Riwayat Izin
                        </h3>

                    </div>

                    <!-- CONTENT -->
                    <div class="p-4 space-y-4">

                        @forelse($permissions[$year] ?? [] as $permission)

                        <div class="border border-blue-50 rounded-2xl p-4 bg-[#f8f9ff]">

                            <div class="flex justify-between items-start mb-3">

                                <div>

                                    <p class="font-bold text-gray-800 capitalize">
                                        {{ $permission->jenis }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ \Carbon\Carbon::parse($permission->tanggal)->format('d M Y') }}
                                    </p>

                                </div>

                                @if($permission->status == 'approved')

                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Approved

                                </span>

                                @elseif($permission->status == 'rejected')

                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Rejected

                                </span>

                                @else

                                <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Pending

                                </span>

                                @endif

                            </div>

                            <div class="text-sm text-gray-600 mb-4">

                                Jam:
                                <span class="font-semibold">

                                    {{ $permission->jam_mulai }}
                                    -
                                    {{ $permission->jam_selesai }}

                                </span>

                            </div>

                            <button
                                onclick="openModal('permission-{{ $permission->id }}')"
                                class="w-full bg-indigo-600 hover:bg-indigo-700
                                text-white py-2 rounded-xl text-sm font-medium transition">

                                Lihat Detail

                            </button>

                        </div>

                        <!-- MODAL IZIN -->
                        <div
                            id="permission-{{ $permission->id }}"
                            class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">

                            <div class="bg-white rounded-3xl w-full max-w-lg p-6 relative">

                                <button
                                    onclick="closeModal('permission-{{ $permission->id }}')"
                                    class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-xl">

                                    ✕

                                </button>

                                <h2 class="text-2xl font-bold text-indigo-600 mb-5">

                                    Detail Izin

                                </h2>

                                <div class="space-y-4 text-sm">

                                    <div>
                                        <p class="text-gray-500">Tanggal</p>
                                        <p class="font-semibold">
                                            {{ $permission->tanggal }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Jenis</p>
                                        <p class="font-semibold capitalize">
                                            {{ $permission->jenis }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Jam</p>
                                        <p class="font-semibold">
                                            {{ $permission->jam_mulai }} - {{ $permission->jam_selesai }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Alasan</p>
                                        <p class="font-semibold">
                                            {{ $permission->alasan }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Lampiran</p>

                                        @if($permission->lampiran)

                                        <a href="{{ asset('storage/'.$permission->lampiran) }}"
                                            target="_blank"
                                            class="text-indigo-600 underline">

                                            Lihat Lampiran

                                        </a>

                                        @else

                                        <p class="font-semibold">-</p>

                                        @endif
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Status</p>
                                        <p class="font-semibold capitalize">
                                            {{ $permission->status }}
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </div>

                        @empty

                        <div class="text-center py-10">

                            <div class="text-4xl mb-3">
                                📝
                            </div>

                            <p class="text-sm text-gray-400">

                                Tidak ada data izin

                            </p>

                        </div>

                        @endforelse

                    </div>

                </div>

                <!-- ================= LEMBUR ================= -->
                <div class="bg-white rounded-3xl shadow-sm border border-blue-50 overflow-hidden">

                    <!-- HEADER -->
                    <div class="bg-cyan-600 text-white px-5 py-4">

                        <h3 class="font-bold text-lg">
                            ⏰ Riwayat Lembur
                        </h3>

                    </div>

                    <!-- CONTENT -->
                    <div class="p-4 space-y-4">

                        @forelse($overtimes[$year] ?? [] as $overtime)

                        <div class="border border-blue-50 rounded-2xl p-4 bg-[#f8f9ff]">

                            <div class="flex justify-between items-start mb-3">

                                <div>

                                    <p class="font-bold text-gray-800">
                                        {{ \Carbon\Carbon::parse($overtime->overtime_date)->format('d M Y') }}
                                    </p>

                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $overtime->start_time }}
                                        -
                                        {{ $overtime->end_time }}
                                    </p>

                                </div>

                                @if($overtime->status == 'approved')

                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Approved

                                </span>

                                @elseif($overtime->status == 'rejected')

                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Rejected

                                </span>

                                @else

                                <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-medium">

                                    Pending

                                </span>

                                @endif

                            </div>

                            <div class="text-sm text-gray-600 mb-4 line-clamp-2">

                                {{ $overtime->reason }}

                            </div>

                            <button
                                onclick="openModal('overtime-{{ $overtime->id }}')"
                                class="w-full bg-cyan-600 hover:bg-cyan-700
                                text-white py-2 rounded-xl text-sm font-medium transition">

                                Lihat Detail

                            </button>

                        </div>

                        <!-- MODAL LEMBUR -->
                        <div
                            id="overtime-{{ $overtime->id }}"
                            class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">

                            <div class="bg-white rounded-3xl w-full max-w-lg p-6 relative">

                                <button
                                    onclick="closeModal('overtime-{{ $overtime->id }}')"
                                    class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-xl">

                                    ✕

                                </button>

                                <h2 class="text-2xl font-bold text-cyan-600 mb-5">

                                    Detail Lembur

                                </h2>

                                <div class="space-y-4 text-sm">

                                    <div>
                                        <p class="text-gray-500">Tanggal</p>
                                        <p class="font-semibold">
                                            {{ $overtime->overtime_date }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Jam</p>
                                        <p class="font-semibold">
                                            {{ $overtime->start_time }} - {{ $overtime->end_time }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Total Jam</p>
                                        <p class="font-semibold">
                                            {{ $overtime->total_hours }} Jam
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Uraian</p>
                                        <p class="font-semibold">
                                            {{ $overtime->reason }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-gray-500">Status</p>
                                        <p class="font-semibold capitalize">
                                            {{ $overtime->status }}
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </div>

                        @empty

                        <div class="text-center py-10">

                            <div class="text-4xl mb-3">
                                ⏰
                            </div>

                            <p class="text-sm text-gray-400">

                                Tidak ada data lembur

                            </p>

                        </div>

                        @endforelse

                    </div>

                </div>

            </div>

            @empty

            <!-- EMPTY -->
            <div class="bg-white rounded-3xl shadow-sm border border-blue-50 p-10 text-center">

                <div class="text-6xl mb-4">
                    📂
                </div>

                <p class="text-gray-500">

                    Belum ada riwayat pengajuan

                </p>

            </div>

            @endforelse

        </div>

    </div>

    <!-- SCRIPT MODAL -->
    <script>
        function openModal(id) {

            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');

        }

        function closeModal(id) {

            document.getElementById(id).classList.remove('flex');
            document.getElementById(id).classList.add('hidden');

        }

        window.onclick = function(event) {

            document.querySelectorAll('[id^="leave-"], [id^="permission-"], [id^="overtime-"]').forEach(modal => {

                if (event.target === modal) {

                    modal.classList.remove('flex');
                    modal.classList.add('hidden');

                }

            });

        }
    </script>

</x-app-layout>