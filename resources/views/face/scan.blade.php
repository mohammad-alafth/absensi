{{-- Scan wajah: selfie + GPS, verifikasi server, retry sampai cocok. --}}
<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-4 py-8 pb-28">
        <div class="max-w-2xl mx-auto space-y-6">

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline mb-2">
                    &larr; Kembali ke Dashboard
                </a>

                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Scan Wajah</h1>

                <div class="mt-4 space-y-2 text-sm">
                    @if($faceRequired)
                        <p class="rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-3 font-semibold">
                            Wajah wajib untuk role Anda.
                        </p>
                        @unless($enrolled)
                            <p class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 font-semibold">
                                Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi.
                            </p>
                        @endunless
                    @else
                        <p class="rounded-xl bg-slate-50 border border-slate-200 text-slate-700 px-4 py-3">
                            Role Anda bebas verifikasi wajah.
                        </p>
                    @endif

                    <p class="text-[11px] text-gray-500">
                        Maksimum percobaan {{ $maxAttempts }}x &middot; ambang similarity {{ number_format($matchThreshold, 2) }}
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white"
                 x-data="{
    stream: null,
    video: null,
    busy: false,
    ready: false,
    done: false,
    degraded: false,
    rejected: false,
    redirectTo: '',
    warning: '',
    autoScan: true,
    attempt: 1,
    message: 'Menyiapkan kamera...',
    score: null,
    remaining: {{ $maxAttempts }},
    enrolled: {{ $enrolled ? 'true' : 'false' }},
    retryTimer: null,
    redirectTimer: null,

    /* ---- Kamera: langsung dibuka begitu halaman dimuat ---- */
    async openCamera() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
        } catch (err) {
            this.message = 'Kamera tidak dapat diakses. Izinkan akses kamera lalu muat ulang halaman.';
            this.autoScan = false;
            return;
        }

        this.video = this.$refs.video;
        this.video.srcObject = this.stream;
        this.video.play().catch(() => {});

        await this.waitVideoReady();

        this.ready = true;
        this.message = 'Kamera aktif. Memindai wajah...';

        // Scan langsung, tanpa perlu tekan tombol.
        this.scan();
    },

    /* Tunggu video benar-benar punya dimensi, bukan asal setTimeout. */
    waitVideoReady() {
        return new Promise((resolve) => {
            const video = this.video;
            if (video.videoWidth > 0 && video.videoHeight > 0) { return resolve(); }
            const onReady = () => {
                if (video.videoWidth > 0 && video.videoHeight > 0) {
                    video.removeEventListener('loadedmetadata', onReady);
                    resolve();
                }
            };
            video.addEventListener('loadedmetadata', onReady);
            setTimeout(resolve, 2500);
        });
    },

    /* ---- Alur scan otomatis ---- */
    async scan() {
        if (this.busy || this.done || !this.ready) return;
        if (!this.enrolled) {
            this.message = 'Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi.';
            this.autoScan = false;
            return;
        }

        this.busy = true;
        this.message = 'Memproses wajah...';

        const point = await this.locate();
        if (!point) {
            this.busy = false;
            this.message = 'Lokasi tidak terbaca. Izinkan akses lokasi lalu coba lagi.';
            this.autoScan = false;
            return;
        }

        await this.shoot(point);
    },

    locate() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) { return resolve(null); }
            navigator.geolocation.getCurrentPosition(
                (pos) => resolve(pos.coords),
                () => resolve(null),
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
            );
        });
    },

    async shoot(coords) {
        const video = this.video;
        const canvas = document.createElement('canvas');

        // Resolusi NATIVE dari kamera, bukan 480x360 hardcode.
        // Microservice InsightFace (SCRFD) butuh wajah cukup besar;
        // foto 480x360 membuat wajah kecil dan embedding tidak stabil,
        // sehingga skor match ikut turun.
        canvas.width = video.videoWidth || 1280;
        canvas.height = video.videoHeight || 720;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        const body = new FormData();
        body.append('latitude', coords.latitude);
        body.append('longitude', coords.longitude);
        body.append('accuracy', coords.accuracy ?? '');
        body.append('image', canvas.toDataURL('image/jpeg', 0.9));
        body.append('attempt', this.attempt);

        try {
            const response = await fetch('{{ route('face.scan.store') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: body,
            });
            const json = await response.json();

            this.score = json.face?.score ?? null;
            this.remaining = json.attempt_remaining ?? 0;

            if (json.success) {
                this.done = true;
                this.autoScan = false;
                this.stopCamera();
                this.message = json.message;

                // Fail-open: absen tetap dicatat, TETAPI wajah tidak
                // diverifikasi karena layanannya mati. Ini wajib
                // ditampilkan mencolok - kalau tidak, absen wajah
                // orang lain terlihat sama sahnya dengan absen asli.
                this.degraded = !!json.degraded;
                this.warning = json.warning || '';

                if (this.degraded) {
                    // Arahkan ke /face (alur absen utama) setelah
                    // pengguna sempat membaca peringatan.
                    this.redirectTimer = setTimeout(() => {
                        window.location.href = '/face';
                    }, 6000);
                }

                return;
            }

            this.message = json.message || 'Wajah belum dikenali.';

            // Server TIDAK menulis data absen saat wajah ditolak; dia
            // mengirim `redirect` ke /face (alur absen lama). Hentikan
            // retry di sini - retry lagi tidak akan pernah berhasil dan
            // hanya membuang waktu pengguna.
            if (json.redirect) {
                this.rejected = true;
                this.autoScan = false;
                this.redirectTo = json.redirect;
                this.stopCamera();
                this.redirectTimer = setTimeout(() => {
                    window.location.href = this.redirectTo;
                }, 6000);
                return;
            }

            this.attempt = (json.attempt ?? this.attempt) + 1;
        } catch (err) {
            this.message = 'Gagal menghubungi server absensi.';
        }

        this.busy = false;

        // Auto-coba lagi selama percobaan masih tersedia.
        if (this.autoScan && this.remaining > 0) {
            this.retryTimer = setTimeout(() => this.scan(), 2000);
        } else if (this.autoScan) {
            this.autoScan = false;
            this.stopCamera();
        }
    },

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach((t) => t.stop());
            this.stream = null;
        }
    },

    retryNow() {
        if (this.busy || this.done) return;
        clearTimeout(this.retryTimer);
        this.autoScan = true;
        this.attempt = 1;
        this.remaining = {{ $maxAttempts }};
        this.scan();
    },

    init() {
        window.addEventListener('beforeunload', () => {
            clearTimeout(this.retryTimer);
            clearTimeout(this.redirectTimer);
            this.stopCamera();
        });
        this.openCamera();
    }
}"

                <div class="flex flex-col items-center gap-4">
                    <div class="relative w-full aspect-video rounded-3xl bg-slate-900 overflow-hidden border border-slate-200">

                        {{-- Live preview; kamera dibuka otomatis oleh init() --}}
                        <video x-ref="video" autoplay playsinline muted
                            class="absolute inset-0 w-full h-full object-cover"
                            style="transform: scaleX(-1);"></video>

                        {{-- Bingkai oval + status, menimpa video --}}
                        <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 pointer-events-none">
                            <div class="w-36 h-48 rounded-full border-2 border-white/70 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>

                            <template x-if="!ready">
                                <p class="text-xs font-bold text-white bg-slate-900/70 px-3 py-1 rounded-full">
                                    Menyiapkan kamera...
                                </p>
                            </template>

                            <template x-if="ready && busy">
                                <div class="flex items-center gap-2 bg-slate-900/70 px-3 py-1 rounded-full">
                                    <div class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                    <p class="text-xs font-bold text-white" x-text="message"></p>
                                </div>
                            </template>

                            <template x-if="ready && !busy && !done">
                                <p class="text-xs font-bold text-white bg-slate-900/70 px-3 py-1 rounded-full text-center max-w-[90%]" x-text="message"></p>
                            </template>
                        </div>
                    </div>

                    <p x-show="score !== null" class="text-xs text-gray-500 text-center">
                        Skor similarity: <span x-text="score"></span>
                    </p>

                    <p x-show="!done && remaining > 0" class="text-xs font-bold text-amber-700 text-center">
                        Percobaan tersisa: <span x-text="remaining"></span>
                    </p>

                    {{-- WAJAH DITOLAK: tidak ada absen yang tercatat. Arahkan ke /face. --}}
                    <template x-if="rejected">
                        <div class="w-full rounded-2xl bg-rose-50 border-2 border-rose-400 px-4 py-4 text-center">
                            <p class="text-sm font-black text-rose-900">✕ Wajah Tidak Dikenali</p>
                            <p class="text-xs font-bold text-rose-800 mt-2 leading-relaxed" x-text="message"></p>
                            <p class="text-[11px] text-rose-700 mt-2">
                                <b>Tidak ada data absen yang tercatat.</b>
                            </p>
                            <div class="mt-3 flex flex-col gap-2">
                                <a :href="redirectTo"
                                    class="w-full px-4 py-2.5 rounded-xl text-xs font-black bg-rose-600 text-white hover:bg-rose-700 transition">
                                    Lanjut ke Absen Biasa
                                </a>
                                <button type="button" @click="window.location.reload()"
                                    class="w-full px-4 py-2 rounded-xl text-[11px] font-bold text-rose-800 border border-rose-300 hover:bg-rose-100 transition">
                                    Coba Pindai Lagi
                                </button>
                            </div>
                            <p class="text-[10px] text-rose-600 mt-2">
                                Dialihkan otomatis ke halaman absen biasa dalam 6 detik.
                            </p>
                        </div>
                    </template>

                    {{-- PERINGATAN KERASAN: absen tercatat TANPA verifikasi wajah.

                         Fail-open disengaja supaya karyawan tidak terkunci
                         absen saat layanan mati. Tapi kalau tidak diberi
                         tanda, absen wajah orang lain terlihat sama sahnya
                         dengan absen asli - itu lubang keamanan. --}}
                    <template x-if="degraded">
                        <div class="w-full rounded-2xl bg-amber-50 border-2 border-amber-400 px-4 py-4 text-center">
                            <p class="text-sm font-black text-amber-900">⚠ TIDAK TERVERIFIKASI ⚠</p>
                            <p class="text-xs font-bold text-amber-800 mt-2 leading-relaxed" x-text="warning"></p>
                            <p class="text-[11px] text-amber-700 mt-2">
                                Absen tercatat, tetapi WAJAH TIDAK DICOMPARASIKAN dengan data Anda.
                            </p>
                            <div class="mt-3 flex flex-col gap-2">
                                <a href="/face"
                                    class="w-full px-4 py-2.5 rounded-xl text-xs font-black bg-amber-600 text-white hover:bg-amber-700 transition">
                                    Lanjut ke Halaman Absen
                                </a>
                                <a href="{{ route('dashboard') }}"
                                    class="w-full px-4 py-2 rounded-xl text-[11px] font-bold text-amber-800 border border-amber-300 hover:bg-amber-100 transition">
                                    Kembali ke Dashboard
                                </a>
                            </div>
                            <p class="text-[10px] text-amber-600 mt-2">
                                Dialihkan otomatis ke /face dalam 6 detik.
                            </p>
                        </div>
                    </template>

                    <template x-if="done && !degraded">
                        <div class="w-full rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-center">
                            <p class="text-sm font-black text-emerald-800">✓ Absen tercatat &amp; wajah terverifikasi</p>
                            <p class="text-xs text-emerald-700 mt-1" x-text="message"></p>
                            <a href="{{ route('dashboard') }}"
                                class="mt-2 inline-block px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                Kembali ke Dashboard
                            </a>
                        </div>
                    </template>

                    <template x-if="!done && !busy && !ready">
                        <button type="button" @click="openCamera()"
                            class="w-full px-5 py-3.5 rounded-2xl text-sm font-black bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            Aktifkan Kamera
                        </button>
                    </template>

                    <template x-if="!done && !busy && ready && !autoScan">
                        <button type="button" @click="retryNow()"
                            class="w-full px-5 py-3.5 rounded-2xl text-sm font-black bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            Scan Lagi
                        </button>
                    </template>

                    <p class="text-[11px] text-gray-500 text-center">
                        Kamera langsung aktif dan wajah dipindai otomatis. Jika belum dikenali,
                        sistem mencoba lagi sampai batas {{ $maxAttempts }} kali, lalu absen tetap
                        dicatat untuk diverifikasi HRD.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
