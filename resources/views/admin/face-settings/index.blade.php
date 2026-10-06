{{-- Pengaturan face recognition (admin): semua nilai disimpan di database. --}}
<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-4 py-8 pb-28">
        <div class="max-w-4xl mx-auto space-y-6">

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline mb-2">
                    &larr; Kembali ke Dashboard
                </a>

                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Pengaturan Face Recognition</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Nilai disimpan di database, jadi dapat diubah tanpa mengubah kode.
                    Setelah mengubah isian, tekan tombol <span class="font-bold text-gray-700">Simpan Pengaturan</span> di bagian bawah form.
                </p>

                @if(session('success'))
                    <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold rounded-xl px-4 py-3">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mt-4 bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold rounded-xl px-4 py-3">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="mt-4 bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-xl px-4 py-3">
                        <p class="font-bold mb-1">Pengaturan BELUM tersimpan karena ada isian tidak valid:</p>
                        <ul class="list-disc ml-5 space-y-0.5">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.face-settings.update') }}" class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @csrf

                    <label class="flex items-center gap-3 sm:col-span-2 rounded-2xl border border-slate-200 px-4 py-3">
                        <input type="checkbox" name="settings[face.enabled]" value="1" @checked($settings['face.enabled']) class="w-5 h-5">
                        <span class="text-sm font-bold text-gray-700">Aktifkan verifikasi wajah</span>
                    </label>

                    <label class="block sm:col-span-2 rounded-2xl border border-slate-200 px-4 py-3">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="settings[face.spoof_enabled]" value="1" @checked($settings['face.spoof_enabled']) class="w-5 h-5">
                            <span class="text-sm font-bold text-gray-700">Periksa anti-spoof sebelum pencocokan wajah</span>
                        </label>
                        <p class="text-[11px] text-amber-700 mt-1">
                            Nonaktif secara bawaan. Microservice bawaan BELUM punya endpoint /detect, sehingga saat
                            dinyalakan pemeriksaan akan dilewati (fail-open) dan hanya tercatat di log.
                            Nyalakan hanya setelah endpoint /detect terpasang DAN lisensi kode serta bobot model anti-spoof
                            sudah diperiksa.
                        </p>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Ambang match (skor minimum)</span>
                        <input type="number" step="0.01" min="0" max="1" name="settings[face.match_threshold]" value="{{ $settings['face.match_threshold'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <span class="text-[10px] text-gray-400">Bawaan {{ $defaults['face.match_threshold'] }}</span>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Ambang abu-abu</span>
                        <input type="number" step="0.01" min="0" max="1" name="settings[face.gray_threshold]" value="{{ $settings['face.gray_threshold'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <span class="text-[10px] text-gray-400">Harus lebih kecil dari ambang match</span>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Maksimum percobaan scan</span>
                        <input type="number" min="1" max="10" name="settings[face.max_attempts]" value="{{ $settings['face.max_attempts'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Minimal sampel registrasi</span>
                        <input type="number" min="1" max="20" name="settings[face.min_samples]" value="{{ $settings['face.min_samples'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Minimal kualitas sampel</span>
                        <input type="number" step="0.01" min="0" max="1" name="settings[face.min_quality]" value="{{ $settings['face.min_quality'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Timeout layanan (ms)</span>
                        <input type="number" min="500" max="30000" name="settings[face.attempt_timeout_ms]" value="{{ $settings['face.attempt_timeout_ms'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Retensi foto (hari)</span>
                        <input type="number" min="1" max="3650" name="settings[face.retention_days]" value="{{ $settings['face.retention_days'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <span class="text-[10px] text-gray-400">Bawaan 60 hari (2 bulan)</span>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">URL microservice</span>
                        <input name="settings[face.service_url]" value="{{ $settings['face.service_url'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Token API</span>
                        <input name="settings[face.service_token]" value="{{ $settings['face.service_token'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Retensi log audit (hari)</span>
                        <input type="number" min="1" max="3650" name="settings[face.audit_retention_days]" value="{{ $settings['face.audit_retention_days'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <span class="text-[10px] text-gray-400">Bawaan 60 hari</span>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Tolak oleh HRD berarti</span>
                        <select name="settings[face.reject_action]" class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                            <option value="keep" @selected($settings['face.reject_action'] === 'keep')>Tetap dihitung hadir (hanya ditandai)</option>
                            <option value="void" @selected($settings['face.reject_action'] === 'void')>Dihitung tidak hadir (batalkan absen)</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Versi informasi persetujuan</span>
                        <input name="settings[face.consent_notice_version]" value="{{ $settings['face.consent_notice_version'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <span class="text-[10px] text-gray-400">Naikkan versi bila isi informasi persetujuan berubah</span>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Token bot Telegram</span>
                        <input name="settings[telegram.bot_token]" value="{{ $settings['telegram.bot_token'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Chat ID Telegram</span>
                        <input name="settings[telegram.chat_id]" value="{{ $settings['telegram.chat_id'] }}" placeholder="contoh: 5123456789"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <span class="text-[10px] text-gray-400">Nomor HP bukan chat id. Chat id didapat dari @userinfobot atau dengan mengetik /start ke bot.</span>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Ambang alert antrean review</span>
                        <input type="number" min="1" name="settings[telegram.alert_mismatch_threshold]" value="{{ $settings['telegram.alert_mismatch_threshold'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <span class="text-[10px] text-gray-400">Kirim alert bila antrean review melewati jumlah ini</span>
                    </label>

                    <label class="block sm:col-span-2">
                        <span class="text-xs font-bold text-gray-600">Role yang bebas verifikasi wajah</span>
                        <input name="settings[face.exempt_roles]" value="{{ $settings['face.exempt_roles'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <span class="text-[10px] text-gray-400">Pisahkan dengan koma. Bawaan: {{ $defaults['face.exempt_roles'] }}</span>
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Radius kantor (meter)</span>
                        <input type="number" min="0" name="settings[office.radius_meters]" value="{{ $settings['office.radius_meters'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Latitude kantor</span>
                        <input type="number" step="0.0000001" name="settings[office.latitude]" value="{{ $settings['office.latitude'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-xs font-bold text-gray-600">Longitude kantor</span>
                        <input type="number" step="0.0000001" name="settings[office.longitude]" value="{{ $settings['office.longitude'] }}"
                            class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </label>

                    <div class="sm:col-span-2 flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="px-6 py-3 rounded-xl text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            {{-- Kendali mikroservice face recognition.

                 PENTING: kalau layanan ini MATI, verifikasi wajah tidak
                 dijalankan sama sekali dan siapa pun bisa absen tanpa
                 pemeriksaan wajah. Jadi statusnya harus terlihat jelas,
                 bukan jadi urusan PowerShell manual. --}}
            @php $svc = $serviceProcess->status(); @endphp
            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-gray-800 mb-1">Mikroservice Face Recognition</h2>
                        <p class="text-xs text-gray-500">
                            Layanan pengenalan wajah (InsightFace) pada port {{ $svc['port'] }}.
                        </p>
                    </div>
                    <span class="text-[11px] font-black uppercase px-3 py-1.5 rounded-lg
                        @if($svc['bool'])
                            bg-emerald-50 text-emerald-700 border border-emerald-200
                        @elseif($svc['state'] === 'starting')
                            bg-amber-50 text-amber-700 border border-amber-200
                        @else
                            bg-rose-50 text-rose-700 border border-rose-300
                        @endif">
                        {{ $svc['label'] }}
                    </span>
                </div>

                <div class="mt-3 rounded-xl px-4 py-3 text-xs
                    @if($svc['bool'])
                        bg-emerald-50 text-emerald-800
                    @elseif($svc['state'] === 'starting')
                        bg-amber-50 text-amber-800
                    @else
                        bg-rose-50 text-rose-800
                    @endif">
                    {{ $svc['message'] }}
                </div>

                <div class="mt-4 flex flex-wrap gap-3 items-center">
                    <form method="POST" action="{{ route('admin.face-settings.service-control', ['action' => 'start']) }}"
                        onsubmit="return confirm('Nyalakan mikroservice face recognition?')">
                        @csrf
                        <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            Nyalakan
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.face-settings.service-control', ['action' => 'restart']) }}">
                        @csrf
                        <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-500 text-white hover:bg-amber-600 transition">
                            Restart
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.face-settings.service-control', ['action' => 'stop']) }}"
                        onsubmit="return confirm('Matikan mikroservice? Akibatnya siapa pun bisa absen TANPA pemeriksaan wajah.')">
                        @csrf
                        <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition">
                            Matikan
                        </button>
                    </form>
                </div>

                <p class="mt-4 text-[11px] text-gray-500 leading-relaxed">
                    @if(PHP_OS_FAMILY === 'Windows')
                        Mikroservice juga menyala otomatis saat Windows dinyalakan. Bila tombol
                        &ldquo;Nyalakan&rdquo; tidak berhasil, jalankan manual di server:
                        <code class="bg-slate-100 px-1 py-0.5 rounded">face-service\start.ps1</code>
                    @else
                        Bila tombol &ldquo;Nyalakan&rdquo; tidak berhasil, jalankan sekali di server
                        (SSH / Terminal Plesk):
                        <code class="bg-slate-100 px-1 py-0.5 rounded">cd face-service &amp;&amp; sh setup.sh</code>
                        untuk membuat <code class="bg-slate-100 px-1 py-0.5 rounded">.venv</code>,
                        lalu
                        <code class="bg-slate-100 px-1 py-0.5 rounded">sh start.sh</code>.
                        Agar hidup lagi setelah reboot, pasang
                        <code class="bg-slate-100 px-1 py-0.5 rounded">face-service.service.example</code>
                        sebagai unit systemd.
                    @endif
                </p>

                
            </div>

            {{-- Aksi berikut memakai form TERPISAH agar tidak pernah ikut mengirim
                 atau membatalkan isian pengaturan di atas. --}}
            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <h2 class="font-bold text-gray-800 mb-1">Alat bantu</h2>
                <p class="text-xs text-gray-500 mb-4">Form terpisah, tidak mengubah nilai pengaturan di atas.</p>

                <div class="flex flex-wrap gap-3 items-center">
                    <form method="POST" action="{{ route('admin.face-settings.test-service') }}">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                            Uji Layanan Wajah
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.face-settings.telegram-test') }}">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-sky-50 text-sky-700 hover:bg-sky-100 transition">
                            Uji Telegram
                        </button>
                    </form>

                    <a href="{{ route('admin.face-settings.audit') }}"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Log Akses Wajah
                    </a>

                    <form method="POST" action="{{ route('admin.face-settings.reset') }}"
                        onsubmit="return confirm('Kembalikan semua pengaturan face recognition ke nilai bawaan?')">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition">
                            Kembalikan Default
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <h2 class="font-bold text-gray-800 mb-2">Status saat ini</h2>
                <p class="text-sm {{ $policyEnabled ? 'text-emerald-700 font-bold' : 'text-amber-700 font-bold' }}">
                    {{ $policyEnabled ? 'Verifikasi wajah AKTIF' : 'Verifikasi wajah NONAKTIF (absen berjalan seperti biasa)' }}
                </p>
                <ul class="mt-3 text-xs text-gray-600 space-y-1">
                    <li>Radius kantor: <span class="font-bold">{{ $settings['office.radius_meters'] }}</span> meter</li>
                    <li>Anti-spoof: <span class="font-bold">{{ $settings['face.spoof_enabled'] ? 'aktif' : 'nonaktif' }}</span></li>
                    <li>Telegram: <span class="font-bold">{{ $settings['telegram.bot_token'] === '' || $settings['telegram.chat_id'] === '' ? 'belum dikonfigurasi' : 'terkonfigurasi' }}</span></li>
                    <li>Role bebas: {{ $exemptRoles === [] ? '(tidak ada)' : implode(', ', $exemptRoles) }}</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>