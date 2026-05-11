<x-app-layout>

    <div class="min-h-screen bg-[#f8f9ff] px-3 sm:px-5 py-6 pb-28">

        <div class="w-full max-w-5xl mx-auto">

            <!-- NAV -->
            <div class="flex justify-between items-center mb-6">

                <a href="{{ route('izin') }}"
                    class="text-[#1E40AF] font-semibold text-sm">

                    ← Kembali

                </a>

            </div>

            <!-- CARD -->
            <div class="bg-white rounded-3xl shadow-sm border border-blue-50 overflow-hidden">

                <!-- HEADER -->
                <div class="p-5 sm:p-6 border-b border-blue-50">

                    <h2 class="text-xl sm:text-2xl font-extrabold text-[#1E40AF]">

                        Riwayat Pengajuan Izin

                    </h2>

                    <p class="text-sm text-gray-500 mt-1">

                        Data seluruh pengajuan izin Anda

                    </p>

                </div>

                @if($permissions->count() > 0)

                <!-- TABLE -->
                <div class="overflow-x-auto">

                    <table class="w-full text-sm whitespace-nowrap">

                        <thead class="bg-blue-50">

                            <tr class="text-gray-700">

                                <th class="text-left px-5 py-4 font-semibold">
                                    Tanggal
                                </th>

                                <th class="text-left px-5 py-4 font-semibold">
                                    Jenis
                                </th>

                                <th class="text-left px-5 py-4 font-semibold">
                                    Jam
                                </th>

                                <th class="text-left px-5 py-4 font-semibold">
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($permissions as $item)

                            <tr class="border-t hover:bg-blue-50/40 transition">

                                <!-- TANGGAL -->
                                <td class="px-5 py-4 text-gray-700">

                                    {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}

                                </td>

                                <!-- JENIS -->
                                <td class="px-5 py-4 font-medium text-gray-800 capitalize">

                                    {{ $item->jenis }}

                                </td>

                                <!-- JAM -->
                                <td class="px-5 py-4 text-gray-600">

                                    {{ $item->jam_mulai ?? '-' }}
                                    -
                                    {{ $item->jam_selesai ?? '-' }}

                                </td>

                                <!-- STATUS -->
                                <td class="px-5 py-4">

                                    @if($item->status == 'pending')

                                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-medium">

                                        Pending

                                    </span>

                                    @elseif($item->status == 'approved')

                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium">

                                        Approved

                                    </span>

                                    @elseif($item->status == 'rejected')

                                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-medium">

                                        Rejected

                                    </span>

                                    @endif

                                </td>

                            </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                @else

                <!-- EMPTY -->
                <div class="text-center py-14">

                    <div class="text-5xl mb-4">
                        📝
                    </div>

                    <p class="text-gray-500 text-sm">

                        Belum ada riwayat izin

                    </p>

                    <a href="{{ route('izin') }}"
                        class="inline-block mt-5 bg-[#1E40AF] hover:bg-[#1e3a8a]
                        text-white px-5 py-2 rounded-xl text-sm font-medium transition">

                        Buat Pengajuan

                    </a>

                </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>