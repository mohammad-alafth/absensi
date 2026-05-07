<x-app-layout>

<div class="min-h-screen bg-gray-100 p-4 pb-24">

    <div class="max-w-5xl mx-auto">

        <div class="bg-white rounded-3xl shadow p-5">

            <h1 class="text-2xl font-bold mb-5">
                Approval Cuti
            </h1>

            @foreach($leaves as $leave)

            <div class="border rounded-2xl p-4 mb-4">

                <div class="flex justify-between">

                    <div>
                        <h2 class="font-bold">
                            {{ $leave->user->name }}
                        </h2>

                        <p class="text-sm text-gray-500">
                            {{ $leave->leave_type }}
                        </p>

                        <p class="text-sm mt-2">
                            {{ $leave->start_date }}
                            -
                            {{ $leave->end_date }}
                        </p>
                    </div>

                    <div class="flex gap-2">

                        <form method="POST"
                            action="{{ route('pj.cuti.approve', $leave->id) }}">

                            @csrf

                            <button class="bg-green-500 text-white px-4 py-2 rounded-xl">
                                Approve
                            </button>

                        </form>

                        <form method="POST"
                            action="{{ route('pj.cuti.reject', $leave->id) }}">

                            @csrf

                            <button class="bg-red-500 text-white px-4 py-2 rounded-xl">
                                Reject
                            </button>

                        </form>

                    </div>

                </div>

            </div>

            @endforeach

        </div>

    </div>

</div>

</x-app-layout>