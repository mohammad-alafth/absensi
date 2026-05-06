<x-app-layout>

    <div class="min-h-screen bg-gray-100 pb-20">

        <!-- Header -->
        <div class="bg-indigo-500 rounded-b-[35px] px-4 py-5 text-white">

            <div class="max-w-6xl mx-auto">

                <h2 class="text-xl font-bold">
                    Riwayat Izin
                </h2>

                <p class="text-sm opacity-90 mt-1">
                    Data pengajuan izin Anda
                </p>

            </div>

        </div>

        <div class="px-3 sm:px-5 -mt-4">

            <div class="bg-white rounded-2xl shadow overflow-hidden">

                @if($permissions->count() > 0)

                <div class="overflow-x-auto">

                    <table class="w-full text-sm whitespace-nowrap">

                        <thead class="bg-gray-50 border-b">

                            <tr class="text-gray-600">

                                <th class="text-left p-4">
                                    Tanggal
                                </th>

                                <th class="text-left p-4">
                                    Jenis
                                </th>

                                <th class="text-left p-4">
                                    Jam
                                </th>

                                <th class="text-left p-4">
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($permissions as $item)

                            <tr class="border-b hover:bg-gray-50">

                                <!-- Tanggal -->
                                <td class="p-4">
                                    {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}
                                </td>

                                <!-- Jenis -->
                                <td class="p-4 font-medium">
                                    {{ $item->jenis }}
                                </td>

                                <!-- Jam -->
                                <td class="p-4">
                                    {{ $item->jam_mulai ?? '-' }}
                                    -
                                    {{ $item->jam_selesai ?? '-' }}
                                </td>

                                <!-- Status -->
                                <td class="p-4">

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

                <!-- Empty State -->
                <div class="text-center py-12">

                    <div class="text-5xl mb-3">
                        📝
                    </div>

                    <p class="text-gray-500">
                        Belum ada riwayat izin
                    </p>

                </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>