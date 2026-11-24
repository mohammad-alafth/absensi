{{-- Scan wajah: selfie + GPS, verifikasi server, retry sampai cocok. --}}
<x-app-layout>
    <script src="{{ asset('js/camera-permission.js') }}"></script>

    <style>
        @keyframes radarPulse {
            0%   { transform: scale(0.98); opacity: 0.9; }
            50%  { transform: scale(1.02); opacity: 0.65; }
            100% { transform: scale(0.98); opacity: 0.9; }
        }
        .animate-oval-breathe {
            animation: radarPulse 3s ease-in-out infinite;
        }
        @keyframes scanLine {
            0%   { top: 12%; opacity: 0.2; }
            50%  { opacity: 0.85; }
            100% { top: 88%; opacity: 0.2; }
        }
        .animate-scan {
            animation: scanLine 2.6s ease-in-out infinite alternate;
        }
        @keyframes spinnerRing {
            to { transform: rotate(360deg); }
        }
        .animate-spin-ring {
            animation: spinnerRing 0.9s linear infinite;
        }
    </style>

    <div class="min-h-screen bg-[#f8f9ff] px-4 py-6 sm:py-8 pb-28">
        <div class="max-w-xl mx-auto space-y-5">

            {{-- ── Header Card ── --}}
            <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200">
                <div class="flex items-center justify-between">
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline">
                        <span>&larr;</span> Kembali ke Dashboard
                    </a>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Biometrik PBEC</span>
                    </div>
                </div>

                <div class="mt-3">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Presensi Wajah &amp; GPS</h1>
                    <p class="text-xs text-slate-500 mt-0.5">RS Mata PBEC &bull; Sentra Operasional</p>
                </div>

                {{-- Role &amp; enrollment status banners --}}
                <div class="mt-4 space-y-2">
                    @if($faceRequired)
                        <div class="rounded-xl bg-blue-50/80 border border-blue-200 text-blue-900 px-4 py-2.5 text-xs sm:text-sm font-semibold flex items-center gap-2">
                            <span>👤</span>
                            <span>Verifikasi wajah wajib untuk role Anda.</span>
                        </div>
                        @unless($enrolled)
                            <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-2.5 text-xs font-semibold flex items-center gap-2">
                                <span>⚠️</span>
                                <span>Wajah Anda belum terdaftar. Hubungi HRD untuk registrasi.</span>
                            </div>
                        @endunless
                    @else
                        <div class="rounded-xl bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 text-xs flex items-center gap-2">
                            <span>ℹ️</span>
                            <span>Role Anda bebas verifikasi wajah.</span>
                        </div>
                    @endif

                    <p class="text-[11px] text-slate-400 pt-1">
                        Maks. {{ $maxAttempts }}x percobaan &middot; Ambang similarity {{ number_format($matchThreshold, 2) }} &middot; Threshold 0.62
                    </p>
                </div>
            </div>

            {{-- ── Camera + Status Card ── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden"
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
             this.stream = await window.openAbsensiCamera({
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
            this.showRetryPopup(this.message);
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
        canvas.width  = video.videoWidth  || 1280;
        canvas.height = video.videoHeight || 720;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        const body = new FormData();
        body.append('latitude',  coords.latitude);
        body.append('longitude', coords.longitude);
        body.append('accuracy',  coords.accuracy ?? '');
        body.append('image',     canvas.toDataURL('image/jpeg', 0.9));
        body.append('attempt',   this.attempt);

        try {
            const response = await fetch('{{ route('face.scan.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Device-Id': (() => {
                        try { return localStorage.getItem('absensi.device_uuid') || ''; } catch (error) { return ''; }
                    })(),
                },
                body: body,
            });
            const json = await response.json();

            this.score     = json.face?.score ?? null;
            this.remaining = json.attempt_remaining ?? 0;

            if (json.success) {
                this.done     = true;
                this.autoScan = false;
                this.stopCamera();
                this.message  = json.message;

                // Fail-open: absen tetap dicatat, TETAPI wajah tidak
                // diverifikasi karena layanannya mati. Ini wajib
                // ditampilkan mencolok supaya tidak bisa disalahgunakan.
                this.degraded = !!json.degraded;
                this.warning  = json.warning || '';

                if (json.device_unverified && json.warning) {
                    this.message = json.warning;
                }

                this.showSuccessPopup();
                return;
            }

            this.message = json.message || 'Wajah belum dikenali.';

            if (json.redirect) {
                this.rejected   = true;
                this.autoScan   = false;
                this.redirectTo = json.redirect;
                this.stopCamera();
                this.showRetryPopup(this.message);
                return;
            }

            this.attempt = (json.attempt ?? this.attempt) + 1;
            this.autoScan = false;
            this.stopCamera();
            this.showRetryPopup(this.message);
        } catch (err) {
            this.message = 'Gagal menghubungi server absensi.';
            this.autoScan = false;
            this.stopCamera();
            this.showRetryPopup(this.message);
            return;
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
        this.autoScan  = true;
        this.attempt   = 1;
        this.remaining = {{ $maxAttempts }};
        this.rejected  = false;
        this.degraded  = false;
        this.warning   = '';
        this.ready     = false;
        this.openCamera();
    },

    showSuccessPopup() {
        const title = this.degraded
            ? 'Absen Telah Tercatat'
            : 'Scan Telah Tercatat';
        const text = this.degraded
            ? (this.warning || this.message)
            : (this.message || 'Presensi berhasil dicatat.');

        Promise.resolve(window.Swal?.fire({
            icon: this.degraded ? 'warning' : 'success',
            title,
            text,
            confirmButtonText: 'Ke Dashboard',
            timer: 2500,
            timerProgressBar: true,
            allowOutsideClick: false,
        })).then(() => {
            window.location.href = '{{ route('dashboard') }}';
        });
    },

    showRetryPopup(reason) {
        this.busy = false;
        Promise.resolve(window.Swal?.fire({
            icon: 'error',
            title: 'Absen Gagal',
            text: reason || 'Wajah tidak dikenali. Silakan lakukan absen ulang.',
            confirmButtonText: 'Absen Ulang',
            showCancelButton: true,
            cancelButtonText: 'Kembali ke Dashboard',
            allowOutsideClick: false,
        })).then((result) => {
            if (result && result.isConfirmed) {
                this.retryNow();
                return;
            }
            window.location.href = '{{ route('dashboard') }}';
        });
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
                 @keydown.window.escape="/* noop */">

                {{-- ── Step indicator chips ── --}}
                <div class="px-5 pt-5 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> GPS Siap
                        </div>
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-[#1E40AF] text-[11px] font-bold">
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <span x-text="busy ? 'Memproses...' : (done ? 'Selesai' : 'Pemindaian Wajah')">Pemindaian Wajah</span>
                        </div>
                        <div x-show="remaining > 0 && !done"
                             class="ml-auto text-[11px] font-semibold text-slate-400">
                            Percobaan <span x-text="attempt"></span>/<span>{{ $maxAttempts }}</span>
                        </div>
                    </div>
                </div>

                {{-- ── Camera viewport with oval guide ── --}}
                <div class="relative w-full bg-slate-950 overflow-hidden" style="aspect-ratio: 4/5;">

                    {{-- Live video --}}
                    <video x-ref="video" autoplay playsinline muted
                           class="absolute inset-0 w-full h-full object-cover"
                           :class="{ 'opacity-30': done }"
                           style="transform: scaleX(-1);"></video>

                    {{-- Dark gradient overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-slate-950/40 pointer-events-none"></div>

                    {{-- Oval scanning guide --}}
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="relative w-[62%] h-[76%] rounded-[50%] border-2 border-blue-300/80 shadow-[0_0_24px_rgba(29,78,216,0.4)] animate-oval-breathe flex items-center justify-center">
                            {{-- Corner guides --}}
                            <div class="absolute -top-1 -left-1 w-5 h-5 border-t-2 border-l-2 border-blue-300"></div>
                            <div class="absolute -top-1 -right-1 w-5 h-5 border-t-2 border-r-2 border-blue-300"></div>
                            <div class="absolute -bottom-1 -left-1 w-5 h-5 border-b-2 border-l-2 border-blue-300"></div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 border-b-2 border-r-2 border-blue-300"></div>
                            {{-- Laser scan line --}}
                            <div class="absolute w-[86%] h-0.5 bg-gradient-to-r from-transparent via-sky-300 to-transparent animate-scan pointer-events-none shadow-[0_0_10px_rgba(56,189,248,0.8)]"></div>
                            {{-- Centre reticle --}}
                            <div class="w-8 h-8 rounded-full border border-blue-300/40 flex items-center justify-center opacity-50">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-300"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Bottom live-feed pill --}}
                    <div class="absolute bottom-3 left-3 right-3 z-10 flex items-center justify-between px-3 py-1.5 rounded-lg bg-slate-900/80 backdrop-blur-sm border border-white/10 text-white">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span class="text-[11px] font-medium tracking-wide" x-text="message">Menyiapkan kamera...</span>
                        </div>
                        <template x-if="busy">
                            <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin-ring"></div>
                        </template>
                    </div>

                    {{-- Score overlay (when available) --}}
                    <template x-if="score !== null && !done">
                        <div class="absolute top-3 right-3 px-2 py-0.5 rounded-full bg-slate-900/80 backdrop-blur-sm text-[11px] text-slate-200 font-mono">
                            Score: <span x-text="parseFloat(score).toFixed(2)"></span>
                        </div>
                    </template>
                </div>

                {{-- ── Instruction note ── --}}
                <div class="px-5 py-3 border-b border-slate-100 text-[11px] text-slate-500 text-center">
                    Posisikan wajah tepat di dalam bingkai oval &bull; Buka kacamata &amp; masker &bull; Pastikan pencahayaan cukup
                </div>

                {{-- ── Action area ── --}}
                <div class="px-5 py-5 space-y-3">

                    {{-- WAJAH DITOLAK: semua percobaan habis --}}
                    <template x-if="rejected">
                        <div class="w-full rounded-2xl bg-rose-50 border-2 border-rose-300 p-4 text-center">
                            <p class="text-sm font-black text-rose-900">✗ Verifikasi Gagal</p>
                            <p class="text-xs font-bold text-rose-800 mt-2 leading-relaxed">
                                Wajah Anda tidak dapat dikenali setelah {{ $maxAttempts }} percobaan.
                                <b>Tidak ada data absen yang tercatat.</b>
                            </p>
                            <div class="mt-3 flex flex-col sm:flex-row gap-2">
                                <a :href="redirectTo"
                                   class="w-full sm:flex-1 px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700 transition shadow-sm">
                                    Lanjut ke Absen Biasa
                                </a>
                                <button type="button" @click="window.location.reload()"
                                        class="w-full sm:flex-1 px-4 py-2.5 rounded-xl text-xs font-bold text-rose-800 border border-rose-300 hover:bg-rose-100 transition">
                                    Coba Pindai Lagi
                                </button>
                            </div>
                             <p class="text-[10px] text-rose-500 mt-2">
                                 Pilih Absen Ulang pada popup untuk mencoba kembali.
                             </p>
                        </div>
                    </template>

                    {{-- PERINGATAN: absen tercatat TANPA verifikasi wajah (fail-open / degraded).
                         Wajib ditampilkan mencolok — kalau tidak, absen wajah
                         orang lain terlihat sama sahnya dengan absen asli. --}}
                    <template x-if="degraded">
                        <div class="w-full rounded-2xl bg-amber-50 border-2 border-amber-400 p-4 text-center">
                            <p class="text-sm font-black text-amber-900">⚠ TIDAK TERVERIFIKASI ⚠</p>
                            <p class="text-xs font-bold text-amber-800 mt-2 leading-relaxed" x-text="warning"></p>
                            <p class="text-[11px] text-amber-700 mt-2">
                                Absen tercatat, tetapi WAJAH TIDAK DIBANDINGKAN dengan data Anda.
                            </p>
                            <div class="mt-3 flex flex-col sm:flex-row gap-2">
                                <a href="/face"
                                   class="w-full sm:flex-1 px-4 py-2.5 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-700 transition shadow-sm">
                                    Lanjut ke Halaman Absen
                                </a>
                                <a href="{{ route('dashboard') }}"
                                   class="w-full sm:flex-1 px-4 py-2.5 rounded-xl text-xs font-bold text-amber-800 border border-amber-300 hover:bg-amber-100 transition">
                                    Kembali ke Dashboard
                                </a>
                            </div>
                             <p class="text-[10px] text-amber-500 mt-2">
                                 Status tercatat dan akan kembali ke dashboard.
                             </p>
                        </div>
                    </template>

                    {{-- SUKSES: absen tercatat & wajah terverifikasi --}}
                    <template x-if="done && !degraded && !rejected">
                        <div class="w-full rounded-2xl bg-emerald-50 border border-emerald-200 p-5 text-center shadow-sm">
                            <p class="text-base font-extrabold text-emerald-800">✓ Absen Tercatat &amp; Wajah Terverifikasi</p>
                            <p class="text-xs font-medium text-emerald-700 mt-1" x-text="message"></p>
                            <a href="{{ route('dashboard') }}"
                               class="mt-3 inline-block px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm">
                                Kembali ke Dashboard
                            </a>
                        </div>
                    </template>

                    {{-- Manual: Aktifkan Kamera --}}
                    <template x-if="!done && !busy && !ready">
                        <button type="button" @click="openCamera()"
                                class="w-full px-5 py-3 rounded-xl text-sm font-bold bg-[#1D4ED8] hover:bg-[#1E40AF] text-white transition shadow-md">
                            Aktifkan Kamera
                        </button>
                    </template>

                    {{-- Manual: Scan Ulang --}}
                    <template x-if="!done && !busy && ready && !autoScan">
                        <button type="button" @click="retryNow()"
                                class="w-full px-5 py-3 rounded-xl text-sm font-bold bg-[#1D4ED8] hover:bg-[#1E40AF] text-white transition shadow-md">
                            Pindai Ulang Wajah
                        </button>
                    </template>

                    {{-- Fail-open policy note --}}
                    <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3.5 flex items-start gap-2.5 text-[11px] text-slate-600 leading-relaxed">
                        <span class="text-[#1E40AF] mt-0.5 shrink-0 text-base">🛡</span>
                        <div>
                            <p class="font-semibold text-slate-700 mb-0.5">Kebijakan Fail-Open Transparan</p>
                            Jika verifikasi wajah terganggu setelah {{ $maxAttempts }} percobaan, kehadiran tetap tercatat sah dan masuk antrean review HRD — tanpa sanksi keterlambatan.
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400 text-center leading-relaxed">
                        Kamera aktif otomatis &bull; Wajah dipindai tanpa tombol &bull; Data biometrik dienkripsi end-to-end sesuai standar RS Mata PBEC
                    </p>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
