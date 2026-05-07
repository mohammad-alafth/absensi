<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-6xl mx-auto">

            <!-- HEADER -->
            <div class="mb-6">

                <h1 class="text-2xl font-bold text-gray-800">
                    Dashboard PJ
                </h1>

                <p class="text-sm text-gray-500">
                    Divisi:
                    {{ strtoupper($divisionRole) }}
                </p>

            </div>

            <!-- STATS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                <div class="bg-yellow-400 text-white rounded-3xl p-5 shadow">
                    <p class="text-sm opacity-80">
                        Pending Cuti
                    </p>

                    <h2 class="text-3xl font-bold mt-2">
                        {{ $pendingLeave }}
                    </h2>
                </div>

                <div class="bg-green-500 text-white rounded-3xl p-5 shadow">
                    <p class="text-sm opacity-80">
                        Approved
                    </p>

                    <h2 class="text-3xl font-bold mt-2">
                        {{ $approvedLeave }}
                    </h2>
                </div>

                <div class="bg-red-500 text-white rounded-3xl p-5 shadow">
                    <p class="text-sm opacity-80">
                        Rejected
                    </p>

                    <h2 class="text-3xl font-bold mt-2">
                        {{ $rejectedLeave }}
                    </h2>
                </div>

                <div class="bg-blue-500 text-white rounded-3xl p-5 shadow">
                    <p class="text-sm opacity-80">
                        Pending Izin
                    </p>

                    <h2 class="text-3xl font-bold mt-2">
                        {{ $pendingPermission }}
                    </h2>
                </div>

            </div>

            <!-- MENU -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

                <a href="{{ route('pj.cuti') }}"
                    class="bg-white rounded-3xl shadow p-5 text-center hover:shadow-xl transition">

                    <div class="text-4xl">
                        📅
                    </div>

                    <p class="mt-2 font-semibold">
                        Approval Cuti
                    </p>

                </a>

                <a href="{{ route('pj.izin') }}"
                    class="bg-white rounded-3xl shadow p-5 text-center hover:shadow-xl transition">

                    <div class="text-4xl">
                        📝
                    </div>

                    <p class="mt-2 font-semibold">
                        Approval Izin
                    </p>

                </a>

                <a href="{{ route('pj.lembur') }}"
                    class="bg-white rounded-3xl shadow p-5 text-center hover:shadow-xl transition">

                    <div class="text-4xl">
                        ⏰
                    </div>

                    <p class="mt-2 font-semibold">
                        Approval Lembur
                    </p>

                </a>

            </div>

            <!-- RECENT -->
            <div class="bg-white rounded-3xl shadow p-5">

                <h2 class="text-lg font-bold mb-4">
                    Pengajuan Terbaru
                </h2>

                <div class="space-y-3">

                    @forelse($recentLeaves as $leave)

                    <div class="border rounded-2xl p-4 flex justify-between items-center">

                        <div>

                            <p class="font-semibold">
                                {{ $leave->user->name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ $leave->leave_type }}
                            </p>

                        </div>

                        <div>

                            @if($leave->status == 'pending')
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm">
                                Pending
                            </span>
                            @elseif($leave->status == 'approved')
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                                Approved
                            </span>
                            @else
                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm">
                                Rejected
                            </span>
                            @endif

                        </div>

                    </div>

                    @empty

                    <div class="text-center text-gray-500 py-10">
                        Belum ada pengajuan
                    </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

</x-app-layout>