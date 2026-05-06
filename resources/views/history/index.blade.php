<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4">

        @forelse($years as $year)

        <!-- HEADER TAHUN -->
        <div class="mb-4 mt-6">

            <h2 class="text-2xl font-bold text-[#1E40AF]">

                Tahun {{ $year }}

            </h2>

        </div>


        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-8">

            <!-- ================= CUTI ================= -->
            <div class="bg-white rounded-2xl shadow overflow-hidden">

                <div class="bg-[#1E40AF] text-white p-4 font-bold">

                    📅 Riwayat Cuti

                </div>

                <div class="overflow-x-auto">

                    <table class="w-full text-sm">

                        <thead class="bg-gray-50">

                            <tr>

                                <th class="p-3 text-left">
                                    Periode
                                </th>

                                <th class="p-3 text-left">
                                    Jenis
                                </th>

                                <th class="p-3 text-left">
                                    Hari
                                </th>

                                <th class="p-3 text-left">
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($leaves[$year] ?? [] as $leave)

                            <tr class="border-b">

                                <td class="p-3">

                                    {{ $leave->start_date }}
                                    <br>
                                    <span class="text-gray-400 text-xs">
                                        s/d {{ $leave->end_date }}
                                    </span>

                                </td>

                                <td class="p-3">

                                    {{ $leave->leave_type }}

                                </td>

                                <td class="p-3">

                                    {{ $leave->total_days }} hari

                                </td>

                                <td class="p-3">

                                    @if($leave->status == 'approved')

                                    <span class="text-green-600 font-medium">
                                        Approved
                                    </span>

                                    @elseif($leave->status == 'rejected')

                                    <span class="text-red-600 font-medium">
                                        Rejected
                                    </span>

                                    @else

                                    <span class="text-yellow-600 font-medium">
                                        Pending
                                    </span>

                                    @endif

                                </td>

                            </tr>

                            @empty

                            <tr>

                                <td colspan="4"
                                    class="p-4 text-center text-gray-400">

                                    Tidak ada data cuti

                                </td>

                            </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- ================= IZIN ================= -->
            <div class="bg-white rounded-2xl shadow overflow-hidden">

                <div class="bg-indigo-600 text-white p-4 font-bold">

                    📝 Riwayat Izin

                </div>

                <div class="overflow-x-auto">

                    <table class="w-full text-sm">

                        <thead class="bg-gray-50">

                            <tr>

                                <th class="p-3 text-left">
                                    Tanggal
                                </th>

                                <th class="p-3 text-left">
                                    Jenis
                                </th>

                                <th class="p-3 text-left">
                                    Jam
                                </th>

                                <th class="p-3 text-left">
                                    Status
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($permissions[$year] ?? [] as $permission)

                            <tr class="border-b">

                                <td class="p-3">

                                    {{ $permission->tanggal }}

                                </td>

                                <td class="p-3">

                                    {{ $permission->jenis }}

                                </td>

                                <td class="p-3">

                                    {{ $permission->jam_mulai }}
                                    -
                                    {{ $permission->jam_selesai }}

                                </td>

                                <td class="p-3">

                                    @if($permission->status == 'approved')

                                    <span class="text-green-600 font-medium">
                                        Approved
                                    </span>

                                    @elseif($permission->status == 'rejected')

                                    <span class="text-red-600 font-medium">
                                        Rejected
                                    </span>

                                    @else

                                    <span class="text-yellow-600 font-medium">
                                        Pending
                                    </span>

                                    @endif

                                </td>

                            </tr>

                            @empty

                            <tr>

                                <td colspan="4"
                                    class="p-4 text-center text-gray-400">

                                    Tidak ada data izin

                                </td>

                            </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        @empty

        <div class="bg-white rounded-2xl shadow p-8 text-center">

            <p class="text-gray-500">

                Belum ada riwayat cuti atau izin

            </p>

        </div>

        @endforelse

    </div>

</x-app-layout>