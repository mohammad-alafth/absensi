{{-- Registrasi wajah: ambil beberapa sampel, server menghitung vektor wajah. --}}
<x-app-layout>
    <div class="min-h-screen bg-[#f0f2f9] px-4 py-8 pb-28">
        <div class="max-w-3xl mx-auto space-y-6">

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[#1E40AF] font-bold text-xs hover:underline mb-2">
                    &larr; Kembali ke Dashboard
                </a>

                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Registrasi Wajah</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Ambil {{ $minSamples }} foto wajah. Hasilnya disimpan sebagai vektor wajah (bukan foto) di server organisasi.
                </p>

                @if(session('success'))
                    <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold rounded-xl px-4 py-3">
                        {{ session('success') }}
                    </div>
                @endif

                @if($enrolled)
                    <div class="mt-4 bg-indigo-50 border border-indigo-200 text-indigo-800 text-sm rounded-xl px-4 py-3">
                        Wajah Anda sudah terdaftar ({{ $enrolledSamples }} sampel). Daftar ulang bila ingin memperbarui.
                    </div>
                @endif

                @unless($faceRequired)
                    <div class="mt-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
                        Role Anda tidak wajib absen memakai wajah, tetapi registrasi tetap bisa dilakukan bila ingin.
                    </div>
                @endunless
            </div>

            <div class="bg-white rounded-[2rem] p-6 sm:p-10 shadow-xl shadow-slate-200/50 border border-white"
                 x-data="{
                     samples: [],
                     busy: false,
                     done: false,
                     message: '',
                     stream: null,
                     starting: false,
                     ready: false,
                     cameraError: '',
                     coach: null,
                     liveness: null,
                     step: 'scan',
                     insideOval: false,
                     scanReady: false,
                     lastLandmarks: null,
                     liveState: 'idle',
                     liveIndex: 0,
                     liveTotal: 0,
                     liveProgress: 0,
                     liveChallenge: null,
                     liveDetail: '',
                     liveEar: null,
                     scanHold: 0,
                     scanHoldAt: 0,
                     // Jeda antar sampel otomatis (ms). Tanpa ini kelima sampel
                     // terpotret nyaris bersamaan karena coach hanya butuh
                     // ~100 ms untuk mencapai requiredFrames.
                     lastCaptureAt: 0,
                     captureIntervalMs: 1500,
                     detectorOk: true,
                     nextHint: '',
                     // Auto-capture. PENTING: checkbox Ambil otomatis memakai
                     // x-model dengan nama autoCapture, jadi properti ini
                     // WAJIB boolean. Sebelumnya namanya sama dengan method
                     // autoCapture(), sehingga Alpine menimpanya dengan
                     // true/false dan pemanggilan self.autoCapture() melempar
                     // is not a function.
                     // Catatan: jangan memakai karakter kutip dobel di dalam
                     // komentar ini -- Blade mengakhiri atribut HTML di kutip
                     // pertama, sehingga x-data ikut terpotong.
                     autoCapture: true,
                     debugMode: false,
                     diag: null,
                     async startCamera() {
                         if (this.ready || this.starting) return;
                         this.starting = true;
                         this.cameraError = '';
                         try {
                             const stream = await navigator.mediaDevices.getUserMedia({
                                 video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
                                 audio: false,
                             });
                             this.stream = stream;
                             this.$nextTick(() => {
                                 const video = this.$refs.preview;
                                 if (video) {
                                     video.srcObject = stream;
                                     video.play();
                                 }
                                 this.startCoach();
                             });
                             this.ready = true;
                         } catch (e) {
                             this.cameraError = 'Kamera tidak dapat diakses. Izinkan akses kamera pada browser lalu muat ulang halaman ini.';
                         } finally {
                             this.starting = false;
                         }
                     },
                     startCoach() {
                         if (this.coach || typeof window.FaceCoach === 'undefined') return;
                         const self = this;
                         this.coach = new window.FaceCoach({
                             video: this.$refs.preview,
                             onUpdate: (update) => self.applyCoach(update),
                             onReady: () => self.maybeAutoCapture(),
                             requiredFrames: 6,
                         });
                         this.coach.start();
                         this.coachLabel = 'Menyiapkan coach wajah...';
                         this.startLiveness();
                     },
                     startLiveness() {
                         if (this.liveness || typeof window.FaceLiveness === 'undefined') return;
                         const self = this;
                         this.liveness = new window.FaceLiveness({
                             count: 3,
                             onUpdate: (u) => self.applyLiveness(u),
                             onPass: () => { self.step = 'capture'; self.liveState = 'passed'; },
                             onFail: (reason) => { self.liveState = 'failed'; self.liveDetail = reason; },
                         });
                     },
                     beginLiveness() {
                         if (!this.liveness) this.startLiveness();
                         if (!this.liveness) return;
                         this.step = 'liveness';
                         this.liveState = 'running';
                         this.liveDetail = '';
                         this.liveness.start();
                     },
                     // Resolusi sampel mengikuti native kamera (rasio terjaga,
                     // lebar dibatasi 1280). Microservice menolak wajah dengan sisi
                     // terpendek < 120px, sehingga thumbnail 480x360 membuat
                     // seluruh sampel ditolak dan muncul 0/5 sampel terbaca.
                     nativeSize() {
                         const v = this.$refs.preview;
                         const vw = (v && v.videoWidth) || 1280;
                         const vh = (v && v.videoHeight) || 720;
                         const w = Math.min(1280, vw);
                         return { w: w, h: Math.round(w * (vh / vw)) };
                     },
                     applyLiveness(u) {
                         this.liveState = u.state;
                         this.liveIndex = u.index;
                         this.liveTotal = u.total;
                         this.liveProgress = u.progress;
                         this.liveChallenge = u.challenge;
                         this.liveDetail = u.detail;

                         // Angka EAR untuk panel diagnostik. Berguna saat kedip
                         // tidak terbaca: baseline yang rendah berarti mata memang
                         // sipit, dan ambang relatif akan menyesuaikan.
                         this.liveEar = u.ear || null;

                         // JANGAN memanggil liveness.update() di sini.
                         //
                         // applyLiveness() adalah callback onUpdate dari
                         // Liveness.emit(). Kalau emit() dipanggil lagi dari
                         // sini, alurnya jadi:
                         //   applyCoach -> liveness.update() -> emit()
                         //              -> applyLiveness() -> liveness.update()
                         //              -> emit() -> ... (tak berujung)
                         // dan berakhir dengan
                         // Maximum call stack size exceeded pada tahap
                         // update, persis seperti yang dilaporkan user.
                         //
                         // applyCoach() sudah memanggil liveness.update()
                         // satu kali per frame, jadi cukup di sana saja.
                     },
                     stopCoach() {
                         if (this.coach) {
                             this.coach.stop();
                             this.coach = null;
                         }
                         this.coachState = null;
                         this.coachLabel = '';
                         this.coachDetail = '';
                         this.coachOk = false;
                         this.faceBox = null;
                         this.guideBox = null;
                         this.progress = 0;
                     },
                     applyCoach(update) {
                         const box = update.display;
                         this.coachState = update.state;
                         this.coachLabel = update.state.instruction;
                         this.coachDetail = update.state.detail;
                         this.coachOk = update.state.ok;
                         this.progress = update.state.ok
                             ? Math.min(100, Math.round(((this.coach ? this.coach.stableFrames : 0) / 6) * 100))
                             : 0;
                         this.faceBox = box;
                         this.guideBox = update.displayGuide || null;
                         this.diag = update.diagnostics || null;

                         // Mode pemindaian: hanya boleh lanjut kalau wajah benar-benar
                         // berada DI DALAM oval, bukan sekadar di dekat.
                         if (this.step === 'scan') {
                             this.insideOval = !!update.insideOval;
                             this.scanReady = this.insideOval && this.coachOk;
                             this.detectorOk = update.detectorState !== 'unavailable' && !update.detectError;

                             // Auto-lanjut setelah syarat Scan terpenuhi terus
                             // selama ~1 detik, supaya pengguna tidak perlu
                             // mencari-cari tombol.
                             // ScanHold dihitung dalam MILIDETIK, bukan frame.
                             // Sebelumnya 15 frame = 250 ms di monitor 60 Hz, jadi
                             // Tahap lanjut terjadi hampir instan dan terasa
                             // loading cepat banget. 900 ms cukup untuk user
                             // membaca arahan tanpa membuat alenya bertele-tele.
                             if (this.scanReady) {
                                 if (!this.scanHoldAt) this.scanHoldAt = Date.now();

                                 if (Date.now() - this.scanHoldAt > 900) {
                                     this.scanDone();
                                 }
                             } else {
                                 this.scanHold = 0;
                                 this.scanHoldAt = 0;
                             }

                             // Selalu jelaskan APA yang sedang ditunggu.
                             this.nextHint = !this.detectorOk
                                 ? 'Deteksi wajah bermasalah: ' + (update.detectError || 'model gagal dimuat') + '. Muat ulang halaman (Ctrl+F5).'
                                 : (!this.insideOval
                                     ? 'Posisikan wajah di DALAM oval.'
                                     : (this.coachLabel || 'Tahan posisi sebentar.'));
                         }

                         // Landmark untuk liveness (dari frame video, bukan layar).
                         if (update.landmarks) {
                             this.lastLandmarks = {
                                 ok: true,
                                 ear: update.landmarks.ear,
                                 yaw: update.landmarks.yaw,
                                 mouth: update.landmarks.mouth,
                                 faceWidth: update.landmarks.faceWidth,
                                 faceHeight: update.landmarks.faceHeight,
                             };

                             if (this.liveness && this.liveState === 'running' && this.step === 'liveness') {
                                 this.liveness.update(this.lastLandmarks);
                             }
                         } else {
                             this.lastLandmarks = null;
                         }
                     },
                     scanDone() {
                         if (!this.scanReady) return;
                         this.scanHold = 0;
                         this.scanHoldAt = 0;
                         this.step = 'liveness';
                         this.beginLiveness();
                     },
                     maybeAutoCapture() {
                         if (this.busy || !this.ready || !this.coach) return;
                         if (!this.autoCapture) return;

                         // Sampel HANYA boleh diambil pada tahap 3, yaitu
                         // setelah liveness memastikan ini manusia asli.
                         if (this.step !== 'capture') return;
                         if (this.liveState !== 'passed') return;
                         if (this.samples.length >= {{ $minSamples }}) return;

                         // JEDA WAJIB antar sampel.
                         //
                         // Tanpa jeda, coach mencapai requiredFrames (6 frame)
                         // dalam ~100 ms, sehingga kelima sampel terpotret
                         // nyaris bersamaan: pose sama semua, tidak ada variasi,
                         // dan user merasa prosesnya terlalu cepat. Jeda ini
                         // juga memberi waktu untuk bergeser ke pose berikutnya.
                         const sinceLast = Date.now() - this.lastCaptureAt;

                         if (sinceLast < this.captureIntervalMs) {
                             this.nextHint = 'Tunggu sebentar, preparing sampel berikutnya...';
                             return;
                         }

                         this.capture();
                     },
                     stopCamera() {
                         this.stopCoach();
                         if (this.stream) {
                             this.stream.getTracks().forEach((t) => t.stop());
                             this.stream = null;
                         }
                         const video = this.$refs.preview;
                         if (video) video.srcObject = null;
                         this.ready = false;
                     },
                     capture() {
                         if (this.busy || !this.ready) return;

                         if (this.coach) {
                             this.busy = true;
                             this.message = '';

                             // Watchdog: kalau callback tidak pernah terpanggil
                             // (mis. requestVideoFrameCallback tidak Supported),
                             // flag busy harus dilepas supaya alur BERJALAN lagi.
                             if (this.busyTimer) clearTimeout(this.busyTimer);
                             this.busyTimer = setTimeout(() => {
                                 if (this.busy) {
                                     this.busy = false;
                                     this.progress = 0;
                                     this.savedFlash = 'Kamera lambat, sampel dilewati.';
                                     if (this.coach) this.coach.reset();
                                     setTimeout(() => { this.savedFlash = ''; }, 1200);
                                 }
                             }, 1500);

                             // Coach menunggu frame yang benar-benar tampil,
                             // lalu memberi tahu hasilnya lewat callback.
                             this.coach.capture(null, null, (dataUrl) => {
                                 if (this.busyTimer) { clearTimeout(this.busyTimer); this.busyTimer = null; }

                                 if (!dataUrl) {
                                     this.busy = false;
                                     this.savedFlash = 'Kamera belum siap, sampel dibatalkan.';
                                     if (this.coach) this.coach.reset();
                                     setTimeout(() => { this.savedFlash = ''; }, 1200);
                                     return;
                                 }

                                 this.samples.push(dataUrl);
                                 this.lastCaptureAt = Date.now();
                                 this.savedFlash = this.samples.length + ' / ' + {{ $minSamples }};
                                 this.busy = false;
                                 this.progress = 0;

                                 // Geser target pose untuk sampel berikutnya supaya
                                 // setiap sampel punya posisi wajah yang berbeda.
                                 if (this.coach) this.coach.setPose(this.samples.length);

                                 if (this.flashTimer) clearTimeout(this.flashTimer);
                                 this.flashTimer = setTimeout(() => {
                                     this.savedFlash = '';
                                 }, 1200);
                             });

                             return;
                         }

                         const video = this.$refs.preview;
                         if (!video || !video.videoWidth) {
                             this.message = 'Kamera belum siap. Tunggu beberapa detik lalu coba lagi.';
                             return;
                         }
                         this.busy = true;
                         this.message = '';
                         const size = this.nativeSize();
                         const canvas = document.createElement('canvas');
                         canvas.width = size.w;
                         canvas.height = size.h;
                         const ctx = canvas.getContext('2d');
                         // Cermin agar hasilnya sama dengan yang dilihat pengguna di preview.
                         ctx.translate(size.w, 0);
                         ctx.scale(-1, 1);
                         ctx.drawImage(video, 0, 0, size.w, size.h);
                         this.samples.push(canvas.toDataURL('image/jpeg', 0.9));
                         this.lastCaptureAt = Date.now();
                         this.busy = false;
                     },
                     countdown() {
                         if (this.busy || !this.ready) return;
                         this.busy = true;
                         this.message = 'Bersiap 3...';
                         let n = 3;
                         const timer = setInterval(() => {
                             n -= 1;
                             if (n > 0) {
                                 this.message = 'Bersiap ' + n + '...';
                                 return;
                             }
                             clearInterval(timer);
                             const video = this.$refs.preview;
                             if (video && video.videoWidth) {
                                 const size = this.nativeSize();
                                 const canvas = document.createElement('canvas');
                                 canvas.width = size.w;
                                 canvas.height = size.h;
                                 const ctx = canvas.getContext('2d');
                                 ctx.translate(size.w, 0);
                                 ctx.scale(-1, 1);
                                 ctx.drawImage(video, 0, 0, size.w, size.h);
                                 this.samples.push(canvas.toDataURL('image/jpeg', 0.9));
                                 this.lastCaptureAt = Date.now();
                                 this.message = '';
                             } else {
                                 this.message = 'Kamera belum siap. Tunggu beberapa detik lalu coba lagi.';
                             }
                             this.busy = false;
                         }, 1000);
                     },
                     async submit() {
                         this.busy = true;
                         const body = new FormData();
                         this.samples.forEach((s, i) => body.append('samples[' + i + ']', s));
                         const response = await fetch('{{ route('face.register.store') }}', {
                             method: 'POST',
                             headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                             body,
                         });
                         const json = await response.json();
                         this.busy = false;
                         this.message = json.message || (json.success ? 'Wajah terdaftar.' : 'Gagal mendaftarkan wajah.');
                         this.done = !!json.success;
                     },
                     reset() {
                         this.samples = [];
                         this.done = false;
                         this.message = '';
                     }
                 }"
                 x-init="
                    window.addEventListener('beforeunload', () => {
                        if (this.stream) this.stream.getTracks().forEach((t) => t.stop());
                    });
                    // Alur seperti aplikasi e-wallet: kamera langsung dinyalakan,
                    // pengguna tinggal mengikuti arahan. Izin kamera tetap diminta browser.
                    this.$nextTick(() => {
                        setTimeout(() => {
                            if (!this.ready && !this.starting) this.startCamera();
                        }, 600);
                    });
                 ">

                {{-- B4: informasi wajib dibaca, lalu persetujuan dicatat sebagai
                     bukti (versi + hash dokumen, waktu, tanda tangan). Bukan
                     sekadar checkbox agree = 1, dan TIDAK menyatakan sistem
                     otomatis patuh UU PDP. --}}
                <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 mb-6">
                    <h2 class="font-bold text-sm text-gray-800 mb-2">Informasi Pemrosesan Data Biometrik Wajah</h2>

                    @php($notice = \App\Services\FaceConsentService::noticeText())
                    <pre class="text-[11px] leading-relaxed text-gray-700 whitespace-pre-wrap bg-white rounded-xl border border-slate-100 p-4">{{ $notice }}</pre>

                    <p class="text-[10px] text-gray-400 mt-2">
                        Versi informasi: {{ \App\Services\FaceConsentService::noticeVersion() }}
                        &middot; hash dokumen: {{ substr(\App\Services\FaceConsentService::noticeHash(), 0, 16) }}
                    </p>

                    @if($consented)
                        <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl px-4 py-3">
                            Persetujuan Anda sudah tersimpan pada {{ $consentedAt }}.
                            Mohon hubungi HRD bila ingin menariknya.
                        </div>
                        <form method="POST" action="{{ route('face.consent.revoke') }}" class="mt-3"
                            onsubmit="return confirm('Tarik persetujuan dan hapus data wajah?')">
                            @csrf
                            <button class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 transition">
                                Tarik Persetujuan
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('face.consent') }}" class="mt-4 space-y-3">
                            @csrf
                            <label class="flex items-start gap-2 text-xs text-gray-700">
                                <input type="checkbox" name="confirmed" value="1" required class="mt-0.5">
                                <span>Saya sudah membaca informasi di atas dan memberikan persetujuan atas pemrosesan data biometrik wajah saya untuk keperluan absensi.</span>
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-gray-600">Ketik nama Anda sebagai tanda tangan ({{ $userName }})</span>
                                <input name="typed_name" required placeholder="{{ $userName }}"
                                    class="mt-1 w-full rounded-xl border-slate-200 bg-white text-sm">
                            </label>

                            <p class="text-[10px] text-gray-500">
                                Bukti persetujuan yang tersimpan: versi informasi, hash dokumen, waktu, IP, dan peramban.
                                Persetujuan ini adalah salah satu dasar pemrosesan yang diakui UU PDP; pemilihan dasar
                                pemrosesan yang dipakai tetap keputusan legal/compliance organisasi.
                            </p>

                            <button class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                Simpan Persetujuan
                            </button>
                        </form>
                    @endif
                </div>
                <h2 class="font-bold text-gray-800 mb-4">Ambil Sampel</h2>

                {{-- Preview kamera tampil lebih dulu supaya pengguna bisa
                    mengatur posisi wajah sebelum menekan tombol ambil sampel. --}}
                <div class="rounded-2xl border border-slate-200 overflow-hidden mb-5">
                    <div class="relative bg-slate-900" style="aspect-ratio: 16 / 9;">
                        {{-- Video TIDAK memakai x-show. Element yang display:none
                             bisa berhenti men-decode frame di beberapa browser,
                             sehingga capture menghasilkan gambar hitam. --}}
                        <video
                            x-ref="preview"
                            playsinline
                            autoplay
                            muted
                            class="w-full h-full object-cover"
                            style="transform: scaleX(-1);">
                        </video>

                        {{-- Placeholder hanya saat kamera BELUM hidup --}}
                        <div x-show="!ready" x-cloak
                             class="absolute inset-0 flex flex-col items-center justify-center text-center px-6 bg-slate-900">
                            <div class="text-4xl mb-2">&#128247;</div>
                            <p class="text-sm font-bold text-white">Menyiapkan kamera...</p>
                            <p class="text-[11px] text-slate-300 mt-1" x-text="cameraError || 'Izinkan akses kamera pada browser.'"></p>
                        </div>

                        {{-- Guide: digambar memakai pemetaan yang sama dengan kotak wajah --}}
                        <div x-show="ready && guideBox" x-cloak
                            class="pointer-events-none absolute"
                            :style="`left:${guideBox.left}%; top:${guideBox.top}%; width:${guideBox.width}%; height:${guideBox.height}%;`">
                            <div class="w-full h-full border-4 border-dashed border-white/60"
                                 style="border-radius: 50%; box-sizing: border-box;"></div>
                        </div>

                        {{-- Fallback: dipakai hanya sebelum coach melaporkan guide.
                             Rasio dibuatPORTRAIT (0.75) yang sama dengan
                             guideRect() di face-coach.js, supaya bentuk di
                             layar tidak berbeda dari penilaian sistem.
                             Catatan: memakai inline style, BUKAN kelas
                             Tailwind `rounded-[50%]`/`aspect-*`, karena kelas
                             arbitrary tersebut bisa hilang bila hasil build
                             CSS belum di-regenerate -- dan oval akan tampil
                             sebagai persegi panjang. --}}
                        <div x-show="ready && !guideBox" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="border-4 border-dashed border-white/40"
                                 style="width: 34%; height: 62%; border-radius: 50%; box-sizing: border-box;"></div>
                        </div>

                        {{-- Kotak wajah hasil tracking face-api (menempel di wajah) --}}
                        <div x-show="ready && faceBox" x-cloak
                            class="pointer-events-none absolute"
                            :style="`left:${faceBox.left}%; top:${faceBox.top}%; width:${faceBox.width}%; height:${faceBox.height}%;`">
                            <div class="w-full h-full border-4 transition-colors"
                                style="border-radius: 50%; box-sizing: border-box;"
                                :class="coachOk ? 'border-emerald-400' : 'border-amber-300'"></div>
                        </div>

                        {{-- Panel arahan: mengikuti tahap saat ini --}}
                        <div x-show="ready && (step === 'scan' || step === 'capture')" x-cloak
                            class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-slate-900/90 to-transparent">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-lg leading-none"
                                    :class="coachState && coachState.level === 'ok' ? 'text-emerald-400'
                                        : (coachState && coachState.level === 'error' ? 'text-rose-400' : 'text-amber-300')">
                                    <span x-show="coachState && coachState.level === 'ok'">&#10003;</span>
                                    <span x-show="!coachState || coachState.level !== 'ok'">&#9888;</span>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-white leading-tight" x-text="coachLabel"></p>
                                    <p class="text-[11px] text-slate-300 leading-tight mt-0.5" x-text="coachDetail"></p>
                                </div>
                            </div>

                            <div x-show="progress > 0" x-cloak class="mt-2 h-1.5 bg-white/20 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-400 rounded-full transition-all duration-150"
                                    :style="`width:${progress}%`"></div>
                            </div>
                        </div>

                        {{-- TAHAP 1: pemindaian wajah di dalam oval --}}
                        <div x-show="ready && step === 'scan'" x-cloak
                             class="absolute inset-x-0 top-0 p-3 bg-gradient-to-b from-slate-900/90 to-transparent">
                            <div class="flex items-center justify-between text-white">
                                <span class="text-[11px] font-black tracking-wide">TAHAP 1 / 3 - PEMINDAIAN WAJAH</span>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                                    :class="insideOval ? 'bg-emerald-500' : 'bg-slate-700'"
                                    x-text="insideOval ? 'DI DALAM AREA' : 'LUAR AREA'"></span>
                            </div>
                            <p class="text-[11px] mt-1 font-bold"
                                :class="detectorOk ? 'text-amber-300' : 'text-rose-300'"
                                x-text="nextHint"></p>
                            <div class="mt-2 h-1.5 bg-white/20 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-400 rounded-full transition-all duration-150"
                                    :style="`width:${scanReady ? 100 : 0}%`"></div>
                            </div>
                        </div>

                        {{-- TAHAP 2: liveness detection (manusia asli) --}}
                        <div x-show="ready && step === 'liveness'" x-cloak
                             class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/85 px-6 text-center">
                            <div class="text-[11px] font-black tracking-wide text-emerald-300 mb-2">
                                TAHAP 2 / 3 - PEMERIKSAAN KEASLIAN WAJAH
                            </div>

                            <template x-if="liveState === 'running'">
                                <div>
                                    <p class="text-xl font-black text-white mb-1"
                                        x-text="liveChallenge ? liveChallenge.text.label : 'Menyiapkan...'"></p>
                                    <p class="text-sm text-slate-300" x-text="liveDetail"></p>
                                    <p class="text-[11px] text-slate-400 mt-1"
                                        x-text="`Langkah ${liveIndex + 1} dari ${liveTotal}`"></p>
                                    <div class="mt-3 h-2 w-56 bg-white/20 rounded-full overflow-hidden mx-auto">
                                        <div class="h-full bg-emerald-400 rounded-full transition-all duration-200"
                                            :style="`width:${liveProgress * 100}%`"></div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="liveState === 'passed'">
                                <div>
                                    <div class="text-6xl mb-1">&#10003;</div>
                                    <p class="text-xl font-black text-emerald-300">Wajah asli terverifikasi</p>
                                    <p class="text-sm text-slate-300">Lanjut mengambil sampel...</p>
                                </div>
                            </template>

                            <template x-if="liveState === 'failed'">
                                <div>
                                    <div class="text-5xl mb-1">&#10006;</div>
                                    <p class="text-xl font-black text-rose-300">Wajah tidak dikenali sebagai manusia</p>
                                    <p class="text-sm text-slate-300 mt-1" x-text="liveDetail"></p>
                                    <button type="button" @click="beginLiveness()"
                                        class="mt-4 px-5 py-2.5 rounded-xl text-xs font-bold bg-white text-slate-800">
                                        Coba Lagi
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Konfirmasi sampel tersimpan (mirip notifikasi e-wallet) --}}
                        <div x-show="savedFlash" x-cloak
                             x-transition
                             class="absolute inset-0 flex items-center justify-center bg-emerald-500/85 pointer-events-none">
                            <div class="text-center">
                                <div class="text-6xl mb-2">&#10003;</div>
                                <p class="text-2xl font-black text-white">Sampel tersimpan</p>
                                <p class="text-lg font-bold text-emerald-50" x-text="savedFlash"></p>
                            </div>
                        </div>

                        {{-- Indikator jumlah sampel pada preview --}}
                        <div x-show="ready" class="absolute top-3 left-3 bg-slate-900/70 text-white text-[11px] font-bold rounded-lg px-2.5 py-1">
                            Sampel terkumpul: <span x-text="samples.length"></span> / {{ $minSamples }}
                        </div>

                        <div x-show="starting" class="absolute inset-0 flex items-center justify-center bg-slate-900/60">
                            <span class="text-sm font-bold text-white">Menyiapkan kamera...</span>
                        </div>
                    </div>

                    <div class="p-4 bg-white border-t border-slate-100">
                    {{-- Panel status: selalu menjelaskan posisi & alasan tertahan --}}
                    <div class="mb-3 rounded-xl border px-4 py-2.5"
                         :class="step === 'capture'
                             ? 'bg-emerald-50 border-emerald-200'
                             : 'bg-amber-50 border-amber-200'">
                        <p class="text-[11px] font-black tracking-wide text-gray-700"
                           x-text="step === 'scan' ? 'LANGKAH 1 DARI 3 - MENEMPATKAN WAJAH'
                                 : (step === 'liveness' ? 'LANGKAH 2 DARI 3 - MEMBUKTIKAN WAJAH ASLI'
                                 : 'LANGKAH 3 DARI 3 - MENGAMBIL SAMPEL')"></p>
                        <p class="text-xs font-bold text-gray-800 mt-0.5" x-text="
                            step === 'scan' ? (nextHint || 'Posisikan wajah di dalam oval.')
                            : (step === 'liveness'
                                ? (liveState === 'failed' ? (liveDetail || 'Wajah tidak merespons perintah.')
                                    : (liveChallenge ? liveChallenge.text.label : 'Tunggu perintah berikutnya.'))
                                : (samples.length >= {{ $minSamples }}
                                    ? 'Semua sampel lengkap. Tekan Daftarkan.'
                                    : 'Tahan posisi sesuai arahan. Sampel diambil otomatis.'))"></p>
                    </div>

                    {{-- Progress bar jumlah sampel (gaya e-wallet) --}}
                    <div class="mb-3">
                        <div class="flex items-center justify-between text-[11px] font-bold text-gray-700 mb-1.5">
                            <span>Progres sampel</span>
                            <span x-text="samples.length + ' / ' + {{ $minSamples }}"></span>
                        </div>
                        <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full transition-all duration-300"
                                :style="`width:${Math.min(100, (samples.length / {{ $minSamples }}) * 100)}%`"></div>
                        </div>
                    </div>

                    {{-- Diagnostik: tampilkan angka mentah face-api agar masalah
                         "tidak terdeteksi" bisa terlihat langsung, bukan ditebak.
                         Hanya muncul saat mode diagnostik diaktifkan. --}}
                    <div class="mb-3">
                        <label class="inline-flex items-center gap-2 text-[11px] font-bold text-slate-600 cursor-pointer">
                            <input type="checkbox" x-model="debugMode" class="rounded">
                            <span>Mode diagnostik deteksi wajah</span>
                        </label>

                        <div x-show="debugMode" x-cloak
                             class="mt-2 bg-slate-900 text-slate-100 rounded-xl p-3 font-mono text-[10px] leading-relaxed">
                            <div>face-api loaded : <span x-text="diag ? (diag.detectorState || '-') : '-'"></span></div>
                            <div>wajah ditemukan  : <span x-text="diag ? diag.count : '-'"></span></div>
                            <div>score           : <span x-text="diag && diag.score !== null && diag.score !== undefined ? Number(diag.score).toFixed(3) : '-'"></span></div>
                            <div>resolusi video  : <span x-text="diag ? diag.video : '-'"></span></div>
                            <div>kanvas kerja    : <span x-text="diag ? diag.work : '-'"></span></div>
                            <div>siklus deteksi  : <span x-text="diag ? diag.frames : '-'"></span></div>
                            <div>percobaan        : <span x-text="diag ? diag.attempts : '-'"></span></div>
                            <div>readyState video : <span x-text="diag ? diag.readyState : '-'"></span></div>
                            <div>blokir terakhir  : <span x-text="diag ? (diag.skip || '-') : '-'"></span></div>
                            <div>tick coach        : <span x-text="diag ? diag.ticks : '-'"></span></div>
                            <div class="text-rose-300" x-show="diag && diag.tickError">
                                error tick: <span x-text="diag.tickError"></span>
                            </div>
                            <div>detecting       : <span x-text="diag ? (diag.detecting ? 'ya' : 'tidak') : '-'"></span></div>
                            <div>stale           : <span x-text="diag ? (diag.stale ? 'ya' : 'tidak') : '-'"></span></div>
                            <div>landmark aktif  : <span x-text="diag ? (diag.landmarked ? 'ya' : 'tidak') : '-'"></span></div>
                            <div>di dalam oval   : <span x-text="insideOval ? 'ya' : 'tidak'"></span></div>
                            <div class="mt-2 border-t border-slate-700 pt-2">
                                <div>EAR baseline  : <span x-text="liveEar && liveEar.baseline ? Number(liveEar.baseline).toFixed(3) : '-'"></span></div>
                                <div>EAR sekarang  : <span x-text="liveEar && liveEar.current ? Number(liveEar.current).toFixed(3) : '-'"></span></div>
                                <div>EAR terendah  : <span x-text="liveEar && liveEar.min !== undefined ? Number(liveEar.min).toFixed(3) : '-'"></span></div>
                                <div>kedip terhitung: <span x-text="liveEar ? liveEar.blinks : '-'"></span></div>
                            </div>
                            <div class="text-amber-300" x-show="diag && diag.detectError">
                                error: <span x-text="diag.detectError"></span>
                            </div>
                        </div>
                    </div>

                    <p x-show="samples.length >= {{ $minSamples }}" x-cloak
                        class="mb-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl px-4 py-3">
                        Semua sampel sudah lengkap. Tekan tombol Daftarkan untuk menyimpan.
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <button type="button" @click="ready ? stopCamera() : startCamera()" :disabled="starting || busy"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold disabled:opacity-50 transition"
                            :class="ready
                                ? 'bg-rose-50 text-rose-700 hover:bg-rose-100'
                                : 'bg-indigo-600 text-white hover:bg-indigo-700'">
                            <span x-text="ready ? 'Matikan Kamera' : 'Nyalakan Kamera'"></span>
                        </button>

                        {{-- Tahap 1 -> 2: lanjut ke pemeriksaan keaslian --}}
                        <button type="button" @click="scanDone()" x-show="step === 'scan'" x-cloak
                            :disabled="!scanReady"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white disabled:opacity-40 hover:bg-emerald-700 transition">
                            Lanjut: Periksa Wajah Asli
                        </button>

                        <button type="button" @click="capture()" :disabled="busy || !ready || step !== 'capture'"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 text-white disabled:opacity-40 hover:bg-indigo-700 transition">
                            Ambil Sampel Sekarang
                        </button>

                        <button type="button" @click="countdown()" :disabled="busy || !ready"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-white text-indigo-700 border border-indigo-200 disabled:opacity-40 hover:bg-indigo-50 transition">
                            Ambil dengan Hitung Mundur
                        </button>

                        <label class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 cursor-pointer">
                            <input type="checkbox" x-model="autoCapture" class="w-4 h-4">
                            <span class="text-[11px] font-bold text-emerald-800">Ambil otomatis</span>
                        </label>
                    </div>
                </div>

                <p x-show="cameraError" x-cloak class="mb-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-xl px-4 py-3"
                    x-text="cameraError"></p>

                <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                    <template x-for="(sample, i) in samples" :key="i">
                        <div class="relative">
                            <img :src="sample" class="rounded-2xl border border-slate-200 aspect-square object-cover" alt="Sampel wajah">
                            <span class="absolute bottom-1 right-1 text-[10px] font-black bg-white/90 rounded px-1.5 py-0.5" x-text="i + 1"></span>
                        </div>
                    </template>
                </div>

                <p x-show="message" x-cloak class="mt-4 text-sm font-semibold text-gray-700" x-text="message"></p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="button" @click="submit()" :disabled="busy || samples.length < {{ $minSamples }}"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white disabled:opacity-50 hover:bg-emerald-700 transition">
                        Daftarkan ({{ $minSamples }} sampel)
                    </button>

                    <button type="button" @click="reset()" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Ulangi
                    </button>
                </div>

                <p class="mt-4 text-[11px] text-gray-500">
                    Tips: nyalakan kamera, letakkan wajah di dalam guide oval, lalu ambil sampel. Wajah menghadap depan,
                    pencahayaan merata, tanpa masker atau kacamata, dan hanya satu wajah dalam frame.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>

{{-- Coach wajah: face-api.js (dari server lokal, bukan CDN) untuk tracking
     wajah real-time + public/js/face-coach.js untuk metrik cahaya/ketajaman.
     Keduanya dimuat LAZY: hanya saat halaman ini dibuka, tidak menambah beban
     halaman lain. Coach boleh gagal total; halaman tetap berfungsi. --}}
<script src="{{ asset('vendor/face-api/face-api.min.js') }}" defer></script>
<script src="{{ asset('js/face-coach.js') }}" defer></script>
<script src="{{ asset('js/face-liveness.js') }}" defer></script>
