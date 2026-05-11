<x-app-layout>

    <div class="min-h-screen bg-gray-100 p-4 pb-24">

        <div class="max-w-5xl mx-auto">

            <h1 class="text-2xl font-bold mb-6">
                Approval Lembur
            </h1>

            <div class="space-y-4">

                @forelse($overtimes as $item)

                <div class="bg-white rounded-3xl p-5 shadow">

                    <div class="flex justify-between">

                        <div>

                            <h2 class="font-bold text-lg">
                                {{ $item->user->name }}
                            </h2>

                            <p class="text-sm text-gray-500">
                                {{ $item->overtime_date }}
                            </p>

                            <p class="mt-2">
                                {{ $item->start_time }}
                                -
                                {{ $item->end_time }}
                            </p>

                            <p class="mt-2 text-sm">
                                {{ $item->reason }}
                            </p>

                        </div>

                    </div>

                    <div class="flex gap-3 mt-5">

                        <form
                            method="POST"
                            action="{{ route('pj.lembur.approve', $item->id) }}">

                            @csrf

                            <button
                                class="bg-green-500 text-white px-4 py-2 rounded-xl">

                                Approve

                            </button>

                        </form>

                        <form
                            method="POST"
                            action="{{ route('pj.lembur.reject', $item->id) }}">

                            @csrf

                            <button
                                class="bg-red-500 text-white px-4 py-2 rounded-xl">

                                Reject

                            </button>

                        </form>

                    </div>

                </div>

                @empty

                <div class="bg-white rounded-3xl p-10 text-center text-gray-500">

                    Tidak ada pengajuan lembur

                </div>

                @endforelse

            </div>

        </div>

    </div>

</x-app-layout>