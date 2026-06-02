<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-4 py-8 pb-28">
        <div x-data="{ showModal: false, selectedUser: null, actionUrl: '' }" class="max-w-6xl mx-auto">

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-8 border-b border-slate-100 mb-8">
                    <div>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline transition mb-2">
                            ← Kembali ke Dashboard
                        </a>
                        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
                            <span>⚙️</span> Manajemen Akun
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Persetujuan akses pengguna dan reset password sistem RS</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                    <div class="bg-slate-50 rounded-3xl p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="font-bold text-lg text-gray-800">Approval Akun Baru</h2>
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-black rounded-full uppercase tracking-wider">
                                {{ $pendingUsers->count() }} Menunggu
                            </span>
                        </div>
                        <div class="space-y-3">
                            @forelse($pendingUsers as $user)
                            <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <div>
                                    <p class="font-bold text-sm text-gray-800">{{ $user->name }}</p>
                                    <p class="text-[11px] text-gray-500">{{ $user->email }}</p>
                                </div>
                                <button @click="showModal = true; selectedUser = {{ $user }}; actionUrl = '{{ route('hrd.users.approve', $user->id) }}'"
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-indigo-700 transition">
                                    Proses
                                </button>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 italic text-center py-4">Tidak ada akun baru.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-3xl p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="font-bold text-lg text-gray-800">Request Reset Password</h2>
                            <span class="px-3 py-1 bg-red-50 text-red-700 text-[10px] font-black rounded-full uppercase tracking-wider">
                                {{ $resetRequests->count() }} Pending
                            </span>
                        </div>
                        <div class="space-y-3">
                            @forelse($resetRequests as $user)
                            <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-100 shadow-sm">
                                <p class="font-bold text-sm text-gray-800">{{ $user->name }}</p>
                                <form action="{{ route('hrd.users.reset-password', $user->id) }}" method="POST" class="flex gap-2">
                                    @csrf
                                    <input type="text" name="new_password" placeholder="Pass Baru" class="rounded-xl border-gray-200 text-xs p-2 w-20">
                                    <button class="bg-red-600 text-white px-3 py-2 rounded-xl text-xs font-bold hover:bg-red-700 transition">Reset</button>
                                </form>
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 italic text-center py-4">Tidak ada permintaan reset.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/30 backdrop-blur-sm">
                <div @click.away="showModal = false" class="bg-white rounded-[2rem] p-8 w-full max-w-sm shadow-2xl border border-gray-100">
                    <div class="mb-6 text-center">
                        <h3 class="font-extrabold text-xl text-gray-900 mb-1">Setujui User</h3>
                        <p class="text-sm text-gray-500">Memberikan hak akses untuk <span class="font-bold text-indigo-600" x-text="selectedUser?.name"></span></p>
                    </div>

                    <form :action="actionUrl" method="POST" class="space-y-5">
                        @csrf
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-wider font-bold text-gray-400">Assign Role</label>
                            <select name="role" class="w-full rounded-2xl text-sm border-gray-200 bg-gray-50 focus:ring-2 focus:ring-indigo-500">
                                @foreach(['head_pegawai', 'director', 'hrd', 'it', 'marketing', 'creator', 'medical_service', 'pipp', 'pj_security', 'pj_marketing', 'pj_ipsrs', 'pj_casemix', 'nurse', 'finance'] as $role)
                                <option value="{{ $role }}">{{ strtoupper($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-wider font-bold text-gray-400">Tipe Kerja</label>
                            <select name="work_type" class="w-full rounded-2xl text-sm border-gray-200 bg-gray-50 focus:ring-2 focus:ring-indigo-500">
                                <option value="office_5">Office (5 Hari)</option>
                                <option value="office_6">Office (6 Hari)</option>
                                <option value="shift">Shift</option>
                            </select>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button type="button" @click="showModal = false" class="flex-1 py-4 rounded-2xl bg-gray-100 text-gray-600 text-xs font-bold hover:bg-gray-200 transition">Batal</button>
                            <button type="submit" class="flex-1 py-4 rounded-2xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition">Simpan Akses</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>