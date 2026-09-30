@props(['state' => null])

{{--
|--------------------------------------------------------------------------
| KARTU ABSEN LEMBUR REALTIME
|--------------------------------------------------------------------------
| Karyawan menekan "Mulai Lembur" / "Selesai Lembur" dengan bukti GPS dan
| foto selfie. Jam yang tercatat adalah jam SERVER, jadi mengubah waktu
| perangkat tidak berpengaruh pada volume jam lembur.
|
| Data awal dikirim controller lewat App\Services\OvertimePunchService::state()
| dan disegarkan berkala lewat route lembur.punch.active.
--}}

@if(!empty($state['has_submission']))
<div id="otPunchCard"
     class="mt-4 bg-white rounded-2xl shadow-sm border border-emerald-200 overflow-hidden"
     data-active-url="{{ route('lembur.punch.active') }}"
     data-start-url="{{ route('lembur.punch.start', ['id' => $state['overtime_id']]) }}"
     data-finish-url="{{ route('lembur.punch.finish', ['id' => $state['overtime_id']]) }}"
     data-token="{{ csrf_token() }}">

    <div class="border-b border-emerald-100 bg-emerald-50/60 px-5 py-4 flex items-center justify-between gap-2">
        <div class="min-w-0">
            <h2 class="text-sm font-bold text-emerald-800 tracking-wide">Absen Lembur Realtime</h2>
            <p class="text-[11px] text-emerald-600 mt-0.5">
                Jam server <span id="otPunchServerTime" class="font-bold">--:--:--</span>
                &middot; bukti GPS + selfie
            </p>
        </div>
        <span id="otPunchBadge"
              class="shrink-0 text-[10px] font-bold border px-2 py-1 rounded-full {{ $state['running'] ? 'text-emerald-700 bg-emerald-100 border-emerald-200' : 'text-amber-700 bg-amber-50 border-amber-200' }}">
            {{ $state['finished'] ? 'Selesai diabsen' : ($state['running'] ? 'Sedang berjalan' : 'Menunggu absen mulai') }}
        </span>
    </div>

    <div class="p-5 space-y-4">

        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-center">
            <div class="bg-slate-50 border border-slate-100 rounded-xl px-2 py-2.5">
                <p class="text-[10px] text-slate-400 font-medium">Rencana (SPL)</p>
                <p class="text-xs font-bold text-slate-700 mt-0.5">{{ $state['planned'] }}</p>
            </div>
            <div class="bg-slate-50 border border-slate-100 rounded-xl px-2 py-2.5">
                <p class="text-[10px] text-slate-400 font-medium">Mulai Nyata</p>
                <p id="otPunchActualStart" class="text-xs font-bold text-emerald-700 mt-0.5">
                    {{ $state['actual_start_at'] ? \Carbon\Carbon::parse($state['actual_start_at'])->format('H:i:s') : '--:--:--' }}
                </p>
            </div>
            <div class="bg-slate-50 border border-slate-100 rounded-xl px-2 py-2.5">
                <p class="text-[10px] text-slate-400 font-medium">Selesai Nyata</p>
                <p id="otPunchActualEnd" class="text-xs font-bold text-emerald-700 mt-0.5">
                    {{ $state['actual_end_at'] ? \Carbon\Carbon::parse($state['actual_end_at'])->format('H:i:s') : '--:--:--' }}
                </p>
            </div>
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-2 py-2.5">
                <p class="text-[10px] text-emerald-500 font-medium">Berjalan</p>
                <p id="otPunchElapsed" class="text-sm font-black text-emerald-700 mt-0.5">00:00:00</p>
            </div>
        </div>

        <p class="text-[11px] text-slate-500 bg-slate-50 border border-slate-100 rounded-xl px-3 py-2">
            Jendela absen {{ \Carbon\Carbon::parse($state['window_open_at'])->format('d-m-Y H:i') }}
            s/d {{ \Carbon\Carbon::parse($state['window_close_at'])->format('d-m-Y H:i') }}
            &middot; volume jam dihitung dari jam nyata (dibulatkan ke bawah per jam).
        </p>

        @if(!empty($state['eligibility_reason']))
            <p class="text-[10px] text-slate-400 leading-tight">
                {{ $state['eligibility_reason'] }}
            </p>
        @endif

        <div class="flex items-center justify-between gap-2">
            <p id="otPunchGps" class="text-[11px] text-slate-500">
                📍 Mengambil lokasi...
            </p>
            <p id="otPunchPhoto" class="text-[11px] text-slate-400">
                Selfie belum diambil
            </p>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button type="button" id="otPunchStartBtn" onclick="overtimePunch.begin('start')"
                    class="py-3 rounded-xl text-xs font-bold text-white shadow-xs transition tracking-wide {{ $state['can_start'] ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-200 text-slate-400 cursor-not-allowed' }}"
                    @disabled(!$state['can_start'])>
                Mulai Lembur
            </button>
            <button type="button" id="otPunchFinishBtn" onclick="overtimePunch.begin('finish')"
                    class="py-3 rounded-xl text-xs font-bold text-white shadow-xs transition tracking-wide {{ $state['can_finish'] ? 'bg-[#1E40AF] hover:bg-blue-800' : 'bg-slate-200 text-slate-400 cursor-not-allowed' }}"
                    @disabled(!$state['can_finish'])>
                Selesai Lembur
            </button>
        </div>

        <p id="otPunchMessage" class="hidden text-[11px] font-semibold rounded-xl px-3 py-2"></p>
    </div>

    {{--AMBIL FOTO SELFIE SEBAGAI BUKTI--}}
    <div id="otPunchModal" class="hidden fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl overflow-hidden w-full max-w-sm">
            <div class="px-4 py-3 border-b">
                <p class="text-xs font-bold text-slate-800">Foto Bukti Absen Lembur</p>
                <p class="text-[11px] text-slate-400">Pastikan wajah terlihat jelas lalu ambil foto.</p>
            </div>
            <div class="relative bg-black">
                <video id="otPunchVideo" autoplay playsinline muted class="w-full max-h-[320px] object-cover"></video>
                <img id="otPunchShot" class="hidden w-full max-h-[320px] object-cover" alt="Pratinjau foto">
            </div>
            <div class="p-4 grid grid-cols-2 gap-2">
                <button type="button" id="otPunchCaptureBtn" onclick="overtimePunch.capture()"
                        class="py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold">
                    Ambil Foto
                </button>
                <button type="button" id="otPunchSubmitBtn" onclick="overtimePunch.send()"
                        class="py-2.5 rounded-xl bg-[#1E40AF] hover:bg-blue-800 text-white text-xs font-bold hidden">
                    Absen Sekarang
                </button>
                <button type="button" onclick="overtimePunch.cancel()"
                        class="col-span-2 py-2.5 rounded-xl border border-slate-200 text-slate-500 text-xs font-bold">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    /*
    |--------------------------------------------------------------------------
    | TIMER + GPS + SELFIE UNTUK ABSEN LEMBUR
    |--------------------------------------------------------------------------
    | Timer klien hanya untuk tampilan; jam yang disimpan server dihitung ulang
    | di App\Services\OvertimePunchService, jadi tidak bisa dicurangi dengan
    | mengubah jam perangkat.
    */
    const overtimePunch = (() => {
        const card = document.getElementById('otPunchCard');

        if (!card) {
            return { begin() {}, capture() {}, send() {}, cancel() {} };
        }

        const token = card.dataset.token;

        const urls = {
            active: card.dataset.activeUrl,
            start: card.dataset.startUrl,
            finish: card.dataset.finishUrl,
        };

        let state = @json($state);

        let geo = { latitude: null, longitude: null, accuracy: null };

        let serverOffset = 0; // jam server - jam perangkat (milidetik)

        let shot = null;
        let mode = null;
        let stream = null;

        const el = (id) => document.getElementById(id);

        const parse = (value) => (value ? new Date(value.replace(' ', 'T')) : null);

        const pad = (value) => String(value).padStart(2, '0');

        function syncOffset() {
            const server = parse(state.server_time);

            serverOffset = server ? (server.getTime() - Date.now()) : 0;
        }

        function serverNow() {
            return new Date(Date.now() + serverOffset);
        }

        function elapsedSeconds() {
            const start = parse(state.actual_start_at);

            if (!start) {
                return 0;
            }

            const until = state.finished ? parse(state.actual_end_at) : serverNow();

            return Math.max(0, Math.floor((until - start) / 1000));
        }

        function renderClock() {
            const now = serverNow();

            el('otPunchServerTime').innerText = pad(now.getHours())
                + ':' + pad(now.getMinutes())
                + ':' + pad(now.getSeconds());

            const total = elapsedSeconds();

            el('otPunchElapsed').innerText = pad(Math.floor(total / 3600))
                + ':' + pad(Math.floor(total / 60) % 60)
                + ':' + pad(total % 60);
        }

        function notify(message, isError) {
            const box = el('otPunchMessage');

            box.classList.remove('hidden');
            box.innerText = message;
            box.className = 'text-[11px] font-semibold rounded-xl px-3 py-2 '
                + (isError
                    ? 'text-red-700 bg-red-50 border border-red-200'
                    : 'text-emerald-700 bg-emerald-50 border border-emerald-200');

            if (isError && typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Absen lembur', text: message, confirmButtonColor: '#1E40AF' });
            }
        }

        function applyState(next) {
            if (!next || !next.has_submission) {
                return;
            }

            state = next;

            syncOffset();

            el('otPunchActualStart').innerText = state.actual_start_at
                ? parse(state.actual_start_at).toLocaleTimeString('id-ID')
                : '--:--:--';

            el('otPunchActualEnd').innerText = state.actual_end_at
                ? parse(state.actual_end_at).toLocaleTimeString('id-ID')
                : '--:--:--';

            const badge = el('otPunchBadge');

            badge.innerText = state.finished
                ? 'Selesai diabsen'
                : (state.running ? 'Sedang berjalan' : 'Menunggu absen mulai');

            el('otPunchStartBtn').disabled = !state.can_start;
            el('otPunchFinishBtn').disabled = !state.can_finish;

            renderClock();
        }

        async function refresh() {
            try {
                const res = await fetch(urls.active, {
                    headers: { 'Accept': 'application/json' },
                });

                if (res.ok) {
                    applyState(await res.json());
                }
            } catch (error) {
                /* Penyegaran gagal; timer klien tetap berjalan. */
            }
        }

        /*
        |----------------------------------------------------------------------
        | LOKASI (GPS)
        |----------------------------------------------------------------------
        */
        function watchGps() {
            if (!navigator.geolocation) {
                el('otPunchGps').innerText = '📍 Perangkat tidak mendukung GPS';
                return;
            }

            navigator.geolocation.watchPosition(
                (pos) => {
                    geo = {
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: Math.round(pos.coords.accuracy),
                    };

                    el('otPunchGps').innerText = '📍 Akurasi ' + geo.accuracy + ' meter';
                },
                () => {
                    el('otPunchGps').innerText = '📍 GPS gagal: izinkan akses lokasi lalu muat ulang';
                },
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 }
            );
        }

        /*
        |----------------------------------------------------------------------
        | KAMERA (SELFIE)
        |----------------------------------------------------------------------
        */
        async function begin(nextMode) {
            mode = nextMode;
            shot = null;

            if (!geo.latitude) {
                notify('Lokasi belum terbaca, tunggu sampai koordinat GPS muncul.', true);
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                notify('Peramban tidak mendukung kamera. Absen lembur butuh foto selfie, atau minta koreksi ke PJ.', true);
                return;
            }

            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false,
                });
            } catch (error) {
                notify('Kamera tidak bisa dibuka: ' + error.message, true);
                return;
            }

            const video = el('otPunchVideo');

            video.srcObject = stream;
            video.classList.remove('hidden');
            el('otPunchShot').classList.add('hidden');
            el('otPunchCaptureBtn').classList.remove('hidden');
            el('otPunchSubmitBtn').classList.add('hidden');
            el('otPunchModal').classList.remove('hidden');
        }

        function capture() {
            const video = el('otPunchVideo');
            const canvas = document.createElement('canvas');

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            shot = canvas.toDataURL('image/jpeg', 0.8);

            const preview = el('otPunchShot');

            preview.src = shot;
            preview.classList.remove('hidden');
            video.classList.add('hidden');

            el('otPunchCaptureBtn').classList.add('hidden');
            el('otPunchSubmitBtn').classList.remove('hidden');
            el('otPunchPhoto').innerText = 'Selfie siap dikirim';
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach((track) => track.stop());
                stream = null;
            }

            el('otPunchModal').classList.add('hidden');
        }

        function cancel() {
            stopCamera();
        }

        /*
        |----------------------------------------------------------------------
        | KIRIM ABSEN
        |----------------------------------------------------------------------
        */
        async function send() {
            if (!shot) {
                notify('Ambil foto selfie terlebih dahulu.', true);
                return;
            }

            const body = Object.assign({}, geo, { image: shot, source: 'web' });

            let response;

            try {
                response = await fetch(mode === 'start' ? urls.start : urls.finish, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify(body),
                });
            } catch (error) {
                notify('Jaringan bermasalah, absen tidak terkirim.', true);
                return;
            }

            const data = await response.json().catch(() => ({ message: 'Absen gagal disimpan.' }));

            stopCamera();

            if (!response.ok || data.success === false) {
                notify(data.message || 'Absen gagal disimpan.', true);
                return;
            }

            notify(data.message, false);

            if (data.state) {
                applyState(data.state);
            }

            /* Setelah absen selesai, muat ulang agar surat & daftar riwayat ikut terbarui. */
            if (mode === 'finish') {
                setTimeout(() => location.reload(), 2200);
            }
        }

        syncOffset();
        renderClock();
        watchGps();

        setInterval(renderClock, 1000);
        setInterval(refresh, 60000);

        return { begin, capture, send, cancel };
    })();
</script>
@endif

{{--
|--------------------------------------------------------------------------
| KARTU PENJELAS (ABSEN REALTIME TIDAK DIPAKAI)
|--------------------------------------------------------------------------
| Muncul bila ada SPL dalam jendela absen tetapi jamnya masih bersinggungan
| dengan jam kerja reguler atau berada setelah shift selesai: kehadiran sudah
| dibuktikan absen harian, jadi volume tetap mengikuti SPL / koreksi PJ-HRD.
|--}}
@if(empty($state['has_submission']) && !empty($state['blocked']))
<div class="mt-4 bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
    <div class="flex items-start gap-3">
        <i class="fas fa-circle-info text-slate-400 mt-0.5"></i>
        <div class="text-[11px] leading-relaxed">
            <p class="text-xs font-bold text-slate-700">Absen Lembur Realtime tidak digunakan untuk pengajuan ini</p>
            <p class="text-slate-500 mt-0.5">{{ $state['blocked']['reason'] }}</p>
            <p class="text-slate-400 mt-1">
                Rencana lembur {{ $state['blocked']['overtime_date'] }}
                {{ $state['blocked']['planned'] }}
                @if(!empty($state['blocked']['schedule']))
                    &middot; jadwal reguler: {{ $state['blocked']['schedule'] }}
                @endif
            </p>
            <p class="text-slate-400">
                Volume jam mengikuti SPL; PJ/HRD dapat mengoreksi lewat halaman Rekap Lembur
                bila jam sebenarnya berbeda.
            </p>
        </div>
    </div>
</div>
@endif
{{--
|--------------------------------------------------------------------------
| KARTU JADWAL ABSEN (PENGAJUAN YANG AKAN DATANG)
|--------------------------------------------------------------------------
| Kartu absen sengaja baru muncul saat jendela absennya tiba (60 menit sebelum
| jam mulai rencana). Supaya tidak terlihat seperti fiturnya hilang, tampilkan
| kapan absen dibuka untuk pengajuan lembur berikutnya.
|--}}
@if(empty($state['has_submission']) && !empty($state['upcoming']))
<div class="mt-4 bg-white rounded-2xl shadow-sm border border-blue-200 p-4">
    <div class="flex items-start gap-3">
        <i class="fas fa-clock text-[#1E40AF] mt-0.5"></i>
        <div class="text-[11px] leading-relaxed">
            <p class="text-xs font-bold text-slate-700">Absen Lembur Realtime belum dibuka</p>
            <p class="text-slate-500 mt-0.5">
                Absen untuk lembur {{ $state['upcoming']['day_label'] }}
                {{ $state['upcoming']['planned'] }} baru bisa dimulai
                <span class="font-bold text-[#1E40AF]">{{ $state['upcoming']['opens_label'] }}</span>
                ({{ $state['upcoming']['opens_in'] }}).
            </p>
            <p class="text-slate-400 mt-1">
                Buka halaman ini pada jam tersebut untuk absen mulai (GPS + selfie).
                Batas absen: {{ $state['upcoming']['closes_label'] }}.
            </p>
        </div>
    </div>
</div>
@endif



