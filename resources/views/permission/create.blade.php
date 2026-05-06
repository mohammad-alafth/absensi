<x-app-layout>

    <div class="max-w-2xl mx-auto p-4">



        <div class="bg-white rounded-2xl shadow p-6">
            <div class="mb-4">
                <a href="{{ route('izin.history') }}"
                    class="bg-gray-700 text-white px-4 py-2 rounded-xl text-sm">

                    Lihat Riwayat Izin

                </a>
            </div>

            <h2 class="text-xl font-bold mb-5">
                Form Pengajuan Izin
            </h2>

            @if(session('success'))
            <div class="mb-4 text-green-600">
                {{ session('success') }}
            </div>
            @endif

            <form
                action="/izin"
                method="POST"
                enctype="multipart/form-data">

                @csrf

                <div class="mb-4">
                    <label>Tanggal</label>
                    <input
                        type="date"
                        name="tanggal"
                        class="w-full border rounded-lg p-2">
                </div>

                <div class="mb-4">
                    <label>Jenis Izin</label>

                    <select
                        name="jenis"
                        class="w-full border rounded-lg p-2">

                        <option value="">Pilih</option>
                        <option value="izin pribadi">Izin Pribadi</option>
                        <option value="sakit">Sakit</option>
                        <option value="keperluan keluarga">Keperluan Keluarga</option>
                        <option value="terlambat masuk">Terlambat Masuk</option>
                        <option value="pulang lebih awal">Pulang Lebih Awal</option>
                        <option value="dinas luar">Dinas Luar</option>

                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">

                    <div>
                        <label>Jam Mulai</label>
                        <input
                            type="time"
                            name="jam_mulai"
                            class="w-full border rounded-lg p-2">
                    </div>

                    <div>
                        <label>Jam Selesai</label>
                        <input
                            type="time"
                            name="jam_selesai"
                            class="w-full border rounded-lg p-2">
                    </div>

                </div>

                <div class="mb-4">
                    <label>Alasan</label>

                    <textarea
                        name="alasan"
                        rows="4"
                        class="w-full border rounded-lg p-2"></textarea>
                </div>

                <div class="mb-4">
                    <label>Lampiran</label>

                    <input
                        type="file"
                        name="lampiran"
                        class="w-full">
                </div>

                <button
                    type="submit"
                    class="w-full bg-indigo-500 text-white py-3 rounded-xl">

                    Kirim Pengajuan

                </button>

            </form>

        </div>

    </div>

</x-app-layout>