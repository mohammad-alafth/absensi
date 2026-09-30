@props([])

{{--
|--------------------------------------------------------------------------
| BUKTI ABSEN SAAT PENGIRIMAN SPL (LEMBUR "SAMPAI SELESAI")
|--------------------------------------------------------------------------
| Pada lembur hari libur, menekan "Kirim Formulir" sekaligus mencatat absen
| mulai. Karena itu GPS + selfie harus sah SEBELUM kirim: pengajuan ditolak
| server bila bukti tidak lengkap (OvertimePunchService::assertEvidence()).
|
| Komponen ini diletakkan DI DALAM <form> pengajuan supaya input tersembunyi
| latitude/longitude/accuracy/image ikut terkirim bersama form.
--}}

<div id="otCapturePanel" class="hidden mt-3 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 space-y-3">

    <input type="hidden" name="latitude" id="otCaptureLatitude">
    <input type="hidden" name="longitude" id="otCaptureLongitude">
    <input type="hidden" name="accuracy" id="otCaptureAccuracy">
    <input type="hidden" name="image" id="otCaptureImage">

    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-bold text-emerald-800">Bukti Absen Mulai (GPS + Selfie)</p>
            <p class="text-[11px] text-emerald-700 mt-0.5 leading-relaxed">
                Absen mulai tercatat memakai jam server saat pengajuan dikirim, jadi lokasi dan
                selfie wajib lengkap lebih dahulu.
            </p>
        </div>
        <span id="otCaptureBadge"
              class="shrink-0 text-[10px] font-bold border px-2 py-1 rounded-full text-amber-700 bg-amber-50 border-amber-200">
            Belum lengkap
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <p id="otCaptureGps" class="text-[11px] text-emerald-700 bg-white border border-emerald-100 rounded-xl px-3 py-2">
            📍 Mengambil lokasi...
        </p>
        <p id="otCapturePhoto" class="text-[11px] text-slate-500 bg-white border border-slate-200 rounded-xl px-3 py-2">
            Selfie belum diambil
        </p>
    </div>

    <div class="flex items-center gap-3">
        <img id="otCapturePreview" src="" alt="Pratinjau selfie"
             class="hidden w-16 h-16 object-cover rounded-xl border border-emerald-200">
        <button type="button" onclick="overtimeCapture.open()"
                class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition">
            📸 Ambil Selfie & Kunci Lokasi
        </button>
    </div>

    <p id="otCaptureMessage" class="hidden text-[11px] font-semibold rounded-xl px-3 py-2"></p>
</div>

{{--AMBIL FOTO SELFIE--}}
<div id="otCaptureModal" class="hidden fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl overflow-hidden w-full max-w-sm">
        <div class="px-4 py-3 border-b">
            <p class="text-xs font-bold text-slate-800">Foto Bukti Absen Mulai</p>
            <p class="text-[11px] text-slate-400">Pastikan wajah terlihat jelas lalu ambil foto.</p>
        </div>
        <div class="relative bg-black">
            <video id="otCaptureVideo" autoplay playsinline muted class="w-full max-h-[320px] object-cover"></video>
            <img id="otCaptureShot" class="hidden w-full max-h-[320px] object-cover" alt="Pratinjau foto">
        </div>
        <div class="p-4 grid grid-cols-2 gap-2">
            <button type="button" id="otCaptureShootBtn" onclick="overtimeCapture.capture()"
                    class="py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold">
                Ambil Foto
            </button>
            <button type="button" id="otCaptureSaveBtn" onclick="overtimeCapture.confirm()"
                    class="py-2.5 rounded-xl bg-[#1E40AF] hover:bg-blue-800 text-white text-xs font-bold hidden">
                Pakai Foto Ini
            </button>
            <button type="button" onclick="overtimeCapture.cancel()"
                    class="col-span-2 py-2.5 rounded-xl border border-slate-200 text-slate-500 text-xs font-bold">
                Batal
            </button>
        </div>
    </div>
</div>

<script>
    /*
    |--------------------------------------------------------------------------
    | GPS + SELFIE UNTUK PENGIRIMAN SPL
    |--------------------------------------------------------------------------
    | Data lokasi dan foto hanya disalin ke input tersembunyi setelah karyawan
    | menyetujui foto, sehingga server selalu menerima bukti yang utuh.
    */
    const overtimeCapture = (() => {
        const el = (id) => document.getElementById(id);

        const panel = el('otCapturePanel');

        if (!panel) {
            return {
                open() {}, capture() {}, confirm() {}, cancel() {},
                reset() {}, ready: () => false, show() {}, hide() {},
            };
        }

        let geo = { latitude: null, longitude: null, accuracy: null };
        let shot = null;
        let stream = null;

        function notify(message, isError) {
            const box = el('otCaptureMessage');

            box.classList.remove('hidden');
            box.innerText = message;
            box.className = 'text-[11px] font-semibold rounded-xl px-3 py-2 '
                + (isError
                    ? 'text-red-700 bg-red-50 border border-red-200'
                    : 'text-emerald-700 bg-emerald-50 border border-emerald-200');
        }

        function setBadge(complete) {
            const badge = el('otCaptureBadge');

            badge.innerText = complete ? 'Siap dikirim' : 'Belum lengkap';
            badge.className = 'shrink-0 text-[10px] font-bold border px-2 py-1 rounded-full '
                + (complete
                    ? 'text-emerald-700 bg-emerald-100 border-emerald-200'
                    : 'text-amber-700 bg-amber-50 border-amber-200');
        }

        function watchGps() {
            if (!navigator.geolocation) {
                el('otCaptureGps').innerText = '📍 Perangkat tidak mendukung GPS';
                return;
            }

            navigator.geolocation.watchPosition(
                (pos) => {
                    geo = {
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: Math.round(pos.coords.accuracy),
                    };

                    el('otCaptureGps').innerText = '📍 Akurasi ' + geo.accuracy + ' meter';
                },
                () => {
                    el('otCaptureGps').innerText = '📍 GPS gagal: izinkan akses lokasi lalu muat ulang';
                },
                { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 }
            );
        }

        async function open() {
            shot = null;

            if (!geo.latitude) {
                notify('Lokasi belum terbaca, tunggu sampai koordinat GPS muncul.', true);
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                notify('Peramban tidak mendukung kamera. Pengajuan hari libur wajib memakai selfie.', true);
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

            const video = el('otCaptureVideo');

            video.srcObject = stream;
            video.classList.remove('hidden');
            el('otCaptureShot').classList.add('hidden');
            el('otCaptureShootBtn').classList.remove('hidden');
            el('otCaptureSaveBtn').classList.add('hidden');
            el('otCaptureModal').classList.remove('hidden');
        }

        function capture() {
            const video = el('otCaptureVideo');
            const canvas = document.createElement('canvas');

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            shot = canvas.toDataURL('image/jpeg', 0.8);

            const preview = el('otCaptureShot');

            preview.src = shot;
            preview.classList.remove('hidden');
            video.classList.add('hidden');

            el('otCaptureShootBtn').classList.add('hidden');
            el('otCaptureSaveBtn').classList.remove('hidden');
        }


        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach((track) => track.stop());
                stream = null;
            }

            el('otCaptureModal').classList.add('hidden');
        }

        function cancel() {
            stopCamera();
        }

        function confirm() {
            if (!shot) {
                notify('Ambil foto selfie terlebih dahulu.', true);
                return;
            }

            el('otCaptureLatitude').value = geo.latitude;
            el('otCaptureLongitude').value = geo.longitude;
            el('otCaptureAccuracy').value = geo.accuracy ?? '';
            el('otCaptureImage').value = shot;

            const preview = el('otCapturePreview');

            preview.src = shot;
            preview.classList.remove('hidden');

            el('otCapturePhoto').innerText = '✅ Selfie siap (akurasi GPS ' + (geo.accuracy ?? '-') + ' meter)';
            setBadge(true);
            stopCamera();
            notify('Bukti lengkap. Tekan tombol kirim di bawah untuk mencatat absen awal Anda.', false);
        }

        function reset() {
            shot = null;

            el('otCaptureLatitude').value = '';
            el('otCaptureLongitude').value = '';
            el('otCaptureAccuracy').value = '';
            el('otCaptureImage').value = '';

            el('otCapturePreview').classList.add('hidden');
            el('otCapturePhoto').innerText = 'Selfie belum diambil';
            el('otCaptureMessage').classList.add('hidden');
            setBadge(false);
        }

        function ready() {
            return !!geo.latitude && !!shot && !!el('otCaptureImage').value;
        }

        function show() {
            panel.classList.remove('hidden');
        }

        function hide() {
            panel.classList.add('hidden');
            stopCamera();
        }

        watchGps();

        return { open, capture, confirm, cancel, reset, ready, show, hide };
    })();
</script>
