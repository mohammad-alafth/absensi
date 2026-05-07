<x-app-layout>

    <div class="min-h-screen bg-gray-100 pb-28">

        <!-- Header -->
        <div class="bg-indigo-500 rounded-b-[35px] px-4 py-5 text-white">

            <div class="max-w-6xl mx-auto">

                <h2 class="text-xl font-bold">
                    Riwayat Cuti
                </h2>

                <p class="text-sm opacity-90 mt-1">
                    Data pengajuan cuti Anda
                </p>

            </div>

        </div>

        <div class="px-3 sm:px-5 -mt-4">

            <div class="bg-white rounded-2xl shadow overflow-hidden">

                @if($leaves->count() > 0)

                <div class="overflow-x-auto">

                    <table class="w-full table-fixed">

                        <!-- Header -->
                        <thead class="bg-gray-50 border-b">

                            <tr>

                                <th class="w-1/4 px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Periode
                                </th>

                                <th class="w-1/4 px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Durasi
                                </th>

                                <th class="w-1/4 px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Jenis
                                </th>

                                <th class="w-1/4 px-4 py-4 text-left text-sm font-semibold text-gray-700">
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <!-- Body -->
                        <tbody>

                            @foreach($leaves as $item)

                            <tr class="border-b">

                                <!-- Periode -->
                                <td class="px-4 py-4 align-top">

                                    <div class="font-medium">
                                        {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}
                                    </div>

                                    <div class="text-sm text-gray-500 mt-1">
                                        s/d {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                                    </div>

                                </td>

                                <!-- Durasi -->
                                <td class="px-4 py-4 align-top text-sm">

                                    {{ $item->total_days }} Hari

                                </td>

                                <!-- Jenis -->
                                <td class="px-4 py-4 align-top text-sm">

                                    {{ $item->leave_type }}

                                </td>

                                <!-- Status -->
                                <td class="px-4 py-4 align-top">

                                    @if($item->status == 'pending')

                                    <span class="inline-block bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">
                                        Pending
                                    </span>

                                    @elseif($item->status == 'approved')

                                    <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">
                                        Approved
                                    </span>

                                    @else

                                    <span class="inline-block bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs">
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
                        📅
                    </div>

                    <p class="text-gray-500">
                        Belum ada riwayat cuti
                    </p>

                </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>