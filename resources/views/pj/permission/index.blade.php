<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-5xl mx-auto">

            <div class="bg-white rounded-3xl shadow p-5">

                <h1 class="text-2xl font-bold mb-5">
                    Approval Izin
                </h1>

                @forelse($permissions as $item)

                <div
                    x-data="{ showDetail: false }"
                    class="border rounded-2xl p-5 mb-4">

                    <div class="flex justify-between">

                        <!-- LEFT -->
                        <div>

                            <h2 class="font-bold">
                                {{ $item->user->name }}
                            </h2>

                            <p class="text-sm text-gray-500 mt-1">
                                {{ $item->jenis }}
                            </p>

                            <p class="text-sm mt-2">
                                {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}
                            </p>

                        </div>


                        <!-- RIGHT -->
                        <div class="flex flex-col gap-2">

                            <!-- DETAIL -->
                            <button
                                @click="showDetail = true"
                                type="button"
                                class="bg-indigo-500 text-white px-4 py-2 rounded-xl">

                                Detail

                            </button>

                            <!-- APPROVE -->
                            <form
                                method="POST"
                                action="{{ route('pj.izin.approve', $item->id) }}">

                                @csrf

                                <button
                                    class="bg-green-500 text-white px-4 py-2 rounded-xl">

                                    Approve

                                </button>

                            </form>

                            <!-- REJECT -->
                            <form
                                method="POST"
                                action="{{ route('pj.izin.reject', $item->id) }}">

                                @csrf

                                <button
                                    class="bg-red-500 text-white px-4 py-2 rounded-xl">

                                    Reject

                                </button>

                            </form>

                        </div>

                    </div>


                    <!-- MODAL -->
                    <div
                        x-show="showDetail"
                        x-transition
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                        style="display:none;">

                        <div
                            @click.away="showDetail = false"
                            class="bg-white rounded-3xl shadow-xl w-full max-w-2xl p-6">

                            <div class="flex justify-between mb-5">

                                <h2 class="font-bold text-xl">
                                    Detail Izin
                                </h2>

                                <button
                                    @click="showDetail = false"
                                    class="text-xl">

                                    ×

                                </button>

                            </div>


                            <div class="space-y-4 text-sm">

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
                                        Jenis Izin
                                    </p>

                                    <p class="font-semibold">
                                        {{ $item->jenis }}
                                    </p>

                                </div>

                                <div>

                                    <p class="text-gray-500">
                                        Tanggal
                                    </p>

                                    <p class="font-semibold">
                                        {{ $item->tanggal }}
                                    </p>

                                </div>

                                <div>

                                    <p class="text-gray-500">
                                        Jam
                                    </p>

                                    <p class="font-semibold">
                                        {{ $item->jam_mulai ?? '-' }}
                                        -
                                        {{ $item->jam_selesai ?? '-' }}
                                    </p>

                                </div>

                                <div>

                                    <p class="text-gray-500">
                                        Alasan
                                    </p>

                                    <p class="font-semibold">
                                        {{ $item->alasan }}
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                @empty

                <div class="text-center py-10 text-gray-500">

                    Tidak ada pengajuan izin

                </div>

                @endforelse

            </div>

        </div>

    </div>

</x-app-layout>