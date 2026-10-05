/**
 * Face Coach: pemeriksaan kualitas wajah REAL-TIME di browser.
 *
 * Berjalan penuh di perangkat (tidak memanggil server), jadi umpan balik
 * muncul seketika tanpa menambah beban microservice.
 *
 * Yang diukur setiap frame:
 *   - Face detection : face-api.js TinyFaceDetector (model lokal /models)
 *   - Cahaya        : rata-rata luminans -> deteksi kurang cahaya
 *   - Ketajaman     : variance Laplacian -> deteksi blur / gemetar
 *   - Ukuran wajah  : area bbox -> terlalu jauh / terlalu dekat
 *   - Posisi        : jarak bbox ke oval -> arahan kiri/kanan/atas/bawah
 *   - Stabilitas    : pergerakan antar frame -> arahan tahan diam
 *
 * Hanya bila SEMUA lolos beberapa frame berturut-turut, sampel diambil
 * otomatis. Contohnya selfie tidak lagi terpejam mata atau buram.
 */
(function (global) {
    'use strict';

    /**
     * Guide oval dalam koordinat VIDEO.
     *
     * `aspect` (lebar / tinggi) dipakai supaya guide selalu BERDIRI seperti
     * bentuk kepala._guide yang landscape (0.42 x 0.62 dari frame 16:9 =
     * 537 x 446 px) membuat wajah portrait tidak akan pernah lolos uji elips,
     * sehingga UI terus menampilkan "LUAR AREA" padahal wajah di tengah.
     */
    var GUIDE = {
        height: 0.62,   // tinggi guide = 62% tinggi frame
        aspect: 0.75,   // lebar / tinggi -> oval kepala, bukan landscape
        maxWidth: 0.42, // batas aman untuk kamera yang sangat lebar
    };

    /**
     * Ukuran foto sampel saat registrasi.
     *
     * PENTING: ini BUKAN angka bebas. Microservice menolak wajah yang sisi
     * terpendeknya di bawah 120 px (env FACE_MIN_PX di face-service/app.py).
     * Wajah yang mengisi ~40% tinggi frame 1280x720 hanya berukuran sekitar
     * 110x150 px. Begitu foto dikecilkan ke 480x360, wajah itu tinggal
     * 82x113 px, di bawah ambang, sehingga SETIAP sampel ditolak dan
     * pendaftaran berakhir dengan "0/5 sampel terbaca" padahal fotonya
     * jelas-jelas bagus.
     *
     * Karena itu sampel diambil mendekati resolusi native kamera (16:9),
     * bukan thumbnail. Ambang 120 px dilewati dengan nyaman, dan detail wajah
     * lebih tinggi sehingga kualitas (Laplacian) juga membaik.
     */
    var CAPTURE_MAX_WIDTH = 1280;

    function clamp(v, lo, hi) { return v < lo ? lo : (v > hi ? hi : v); }

    function FaceCoach(options) {
        options = options || {};
        this.video = options.video;
        this.canvas = options.canvas || document.createElement('canvas');
        this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });
        this.canvas.width = 160;
        this.canvas.height = 120;

        this.mirror = options.mirror !== false;
        this.onUpdate = options.onUpdate || function () {};
        this.onReady = options.onReady || function () {};

        this.detector = null;
        this.landmarker = null;
        this.landmarks = null;
        this.landmarkAt = 0;
        this.landmarksAt = 0;

        // Deteksi face-api itu ASYNC: detectAllFaces() mengembalikan
        // ComposableTask (Promise), bukan array. Jadi coach tidak boleh
        // menunggu hasil di dalam satu frame rAF. Hasil disimpan di sini
        // lalu dipakai ulang pada frame berikutnya.
        this.detectCanvas = document.createElement('canvas');
        this.detectCtx = this.detectCanvas.getContext('2d');
        this.detectWidth = 0;
        this.detectHeight = 0;

        this.inputSize = 320;
        this.scoreThreshold = 0.3;
        this.detectIntervalMs = 70;
        this.landmarkIntervalMs = 180;
        this.detectAt = 0;
        this.detecting = false;
        this.box = null;
        this.boxAt = 0;
        this.rawBox = null;
        this.smooth = null;
        // Alpha untuk exponential moving average. TinyFaceDetector menghasilkan
        // kotak yang bergoyang beberapa piksel tiap frame; tanpa pelembatan,
        // uji "diam" selalu gagal karena drift kecil tapi terus-menerus.
        this.smoothAlpha = 0.45;
        this.boxStaleMs = 400;
        this.faceCount = 0;
        this.frameCount = 0;
        // Diagnostik penjaga: kapan terakhir detectBox() keluar DINI dan
        // lewat guard mana. Tanpa ini, "tidak terdeteksi" tidak bisa dibedakan
        // antara "model tidak termuat" vs "frame belum siap" vs "deteksi
        // sedang jalan" -- semuanya tampak sama di UI.
        this.detectAttempts = 0;
        this.skipReason = 'none';
        this.tickError = null;
        this.tickCount = 0;
        this.lastDetectError = null;
        this.detectorState = 'idle'; // idle | loading | ready | unavailable
        this.lastBox = null;
        this.stableFrames = 0;
        this.requiredFrames = options.requiredFrames || 8;
        this.history = [];
        this.poseIndex = 0;
        this.poseDx = 0;
        this.poseDy = 0;
        this.running = false;
        this.rafId = null;
        this.detectorPromise = null;
    }

    FaceCoach.prototype.setVideo = function (video) { this.video = video; };

    /**
     * Haluskan kotak wajah dengan exponential moving average.
     *
     * Kotak dari TinyFaceDetector selalu bergoyang 2-5 px tiap frame karena
     * sifat SSD-nya. Kalau drift diukur langsung dari kotak mentah, wajah
     * yang benar-benar diam tetap terbaca "sedikit bergerak". EMA membuat
     * coach mengukur gerakan secara stabil tanpa membuat kotak tertinggal jauh.
     */
    FaceCoach.prototype.smoothBox = function (raw) {
        if (!raw) {
            this.smooth = null;

            return null;
        }

        var prev = this.smooth;

        if (!prev) {
            this.smooth = { x: raw.x, y: raw.y, w: raw.w, h: raw.h, score: raw.score };

            return this.smooth;
        }

        var a = this.smoothAlpha;
        this.smooth = {
            x: prev.x + (raw.x - prev.x) * a,
            y: prev.y + (raw.y - prev.y) * a,
            w: prev.w + (raw.w - prev.w) * a,
            h: prev.h + (raw.h - prev.h) * a,
            score: raw.score,
        };

        return this.smooth;
    };

    /**
     * Muat TinyFaceDetector dari server lokal (public/models).
     * Gagal tidak mematikan fitur: coaching cahaya/ketajaman tetap jalan.
     */
    FaceCoach.prototype.loadDetector = function () {
        if (this.detectorPromise) return this.detectorPromise;
        var self = this;
        this.detectorState = 'loading';

        // PENTING: face-api.js `loadFromUri()` TIDAK mengembalikan objek net.
        // Implementasinya `async` dengan blok `this.loadFromWeightMap(...)`
        // tanpa nilai balik, jadi resolve-nya `undefined`.
        //
        // Karena itu `net` dari `.then(function (net) { self.detector = net })`
        // SELALU undefined: model benar-benar termuat, tetapi this.detector
        // tetap falsy. Akibatnya detectBox() menolak di guard "model belum
        // termuat" selamanya, sementara detectorState sudah 'ready' -- dua
        // kondisi yang tampak bertentangan tapi arise dari bug yang sama.
        //
        // Solusi: ambil objek net langsung dari faceapi.nets (loadFromUri
        // memutasi objek itu in-place lewat loadFromWeightMap).
        var tinyNet = global.faceapi
            ? global.faceapi.nets.tinyFaceDetector
            : null;
        var landmarkNet = global.faceapi
            ? global.faceapi.nets.faceLandmark68Net
            : null;

        this.detectorPromise = (tinyNet
            ? tinyNet.loadFromUri(options_model_path())
                .then(function () {
                    self.detector = tinyNet;
                    self.detectorState = 'ready';

                    // Landmark 68 titik dibutuhkan untuk liveness detection
                    // (kedip / putar kepala / senyum). Modelnya sudah ada di
                    // public/models, jadi tidak ada unduhan tambahan.
                    if (!landmarkNet) {
                        self.landmarker = null;

                        return null;
                    }

                    return landmarkNet.loadFromUri(options_model_path())
                        .then(function () { self.landmarker = landmarkNet; })
                        .catch(function () { self.landmarker = null; });
                })
                .catch(function () {
                    self.detector = null;
                    self.detectorState = 'unavailable';
                })
            : Promise.resolve().then(function () {
                self.detector = null;
                self.detectorState = 'unavailable';
            }));
        return this.detectorPromise;
    };

    function options_model_path() { return '/models'; }

    FaceCoach.prototype.start = function () {
        if (this.running) return;
        this.running = true;
        this.loadDetector();
        var self = this;
        var loop = function () {
            if (!self.running) return;

            // WAJIB. Tanpa try/catch di sini, satu exception apa pun di tick()
            // membuat requestAnimationFrame TIDAK pernah dipanggil lagi, sehingga
            // coach mati permanen: tidak ada error, tidak ada log, dan panel
            // diagnostik froze pada frame terakhir -- persis gejala "wajah tidak
            // terdeteksi" yang sangat sulit diagnosa.
            try {
                self.tick();
            } catch (e) {
                self.tickError = String(e && e.message ? e.message : e);
            }

            self.rafId = global.requestAnimationFrame(loop);
        };
        this.rafId = global.requestAnimationFrame(loop);
    };

    FaceCoach.prototype.stop = function () {
        this.running = false;
        if (this.rafId) global.cancelAnimationFrame(this.rafId);
        this.rafId = null;
    };

    FaceCoach.prototype.reset = function () {
        this.lastBox = null;
        this.stableFrames = 0;
        this.history = [];
        // Smoothing juga di-reset: sampel berikutnya harus dinilai dari
        // posisi terkini, bukan dari kotak yang masih membawa posisi sampel lama.
        this.smooth = null;
        this.rawBox = null;
    };
    /** Hitung metrik satu frame: luminans dan ketajaman. */
    FaceCoach.prototype.measureFrame = function () {
        var w = this.canvas.width, h = this.canvas.height;
        this.ctx.drawImage(this.video, 0, 0, w, h);
        var data = this.ctx.getImageData(0, 0, w, h).data;

        var sum = 0;
        var count = w * h;
        var gray = new Float32Array(count);

        for (var i = 0, p = 0; i < count; i++, p += 4) {
            var g = 0.299 * data[p] + 0.587 * data[p + 1] + 0.114 * data[p + 2];
            gray[i] = g;
            sum += g;
        }

        var brightness = sum / count;

        // Variance Laplacian: makin kecil, makin blur.
        var mean = 0, n = 0, values = [];
        for (var y = 1; y < h - 1; y += 2) {
            for (var x = 1; x < w - 1; x += 2) {
                var idx = y * w + x;
                var lap = 4 * gray[idx] - gray[idx - 1] - gray[idx + 1] - gray[idx - w] - gray[idx + w];
                values.push(lap);
                mean += lap;
                n++;
            }
        }
        mean /= n;
        var variance = 0;
        for (var k = 0; k < values.length; k++) {
            variance += (values[k] - mean) * (values[k] - mean);
        }
        variance /= n;

        return { brightness: brightness, sharpness: variance };
    };

    /**
     * Siapkan kanvas kerja untuk deteksi.
     *
     * Deteksi tidak dijalankan langsung pada <video> berukuran 1280x720:
     * tiap frame akan di-downsample ke ukuran kerja yang lebih kecil supaya
     * CPU laptop tidak tersedak. Kanvas ini juga menjadi sumber frame yang
     * sama untuk landmark, jadi cukup SATU pembacaan kamera per siklus.
     */
    FaceCoach.prototype.prepareWorkCanvas = function () {
        var vw = this.video.videoWidth;
        var vh = this.video.videoHeight;

        if (!vw || !vh) return false;

        // Batasi lebar kerja; rasio aspek video tetap terjaga.
        var w = Math.min(480, vw);
        var h = Math.round(w * (vh / vw));

        if (!h || h < 1) return false;

        if (this.detectWidth !== w || this.detectHeight !== h) {
            this.detectCanvas.width = w;
            this.detectCanvas.height = h;
            this.detectWidth = w;
            this.detectHeight = h;
        }

        this.detectCtx.drawImage(this.video, 0, 0, w, h);

        return true;
    };

    /**
     * Ambil kotak wajah lewat face-api.
     *
     * PENTING: `faceapi.detectAllFaces()` itu ASYNC. Di face-api.js ia
     * mengembalikan `DetectAllFacesTask` (objek yang bisa di-await), BUKAN
     * array. Memanggilnya secara sinkron seperti `results.length` membuat
     * hasilnya `undefined`, sehingga `results[0]` melempar TypeError yang
     * tertangkap try/catch dan wajah SELALU dianggap "tidak terdeteksi".
     *
     * Karena hasilnya asynchron, coach tidak menunggu di dalam rAF. Box
     * disimpan di `this.box` dan dibaca ulang pada frame berikutnya, jadi
     * UI tetap mulus.
     *
     * @returns {boolean} true bila siklus deteksi baru dijalankan.
     */
    FaceCoach.prototype.detectBox = function () {
        if (!this.detector) {
            this.skipReason = 'model belum termuat';

            return false;
        }

        if (!this.hasFreshFrame()) {
            this.skipReason = 'frame video belum siap (readyState<2)';

            return false;
        }

        var now = Date.now();

        // Jangan menumpuk deteksi: model ini berat, dan menumpuknya membuat
        // halaman macet serta hasil tertinggal dari frame yang sudah lewat.
        if (this.detecting) {
            this.skipReason = 'deteksi sebelumnya masih berjalan';

            return false;
        }

        if (now - this.detectAt < this.detectIntervalMs) {
            this.skipReason = 'menunggu interval';

            return false;
        }

        if (!this.prepareWorkCanvas()) {
            this.skipReason = 'gagal menyiapkan kanvas kerja';

            return false;
        }

        this.detectAttempts += 1;
        this.skipReason = 'none';
        this.detecting = true;
        this.detectAt = now;

        var self = this;
        var w = this.detectWidth;
        var h = this.detectHeight;

        // WAJIB bentuk OBJEK. face-api.js mendefinisikan
        // `TinyYolov2Options({ inputSize, scoreThreshold })` sebagai
        // destructuring objek, dan TinyFaceDetectorOptions mewarisi itu.
        //
        // Jika dipanggil posisional `new TinyFaceDetectorOptions(320, 0.3)`,
        // angka 320 diperlakukan sebagai OBJEK lalu di-destructure, sehingga
        // inputSize/scoreThreshold menjadi undefined dan diam-diam fallback ke
        // default (416 / 0.5). Akibatnya ambang 0.3 yang kita pannekan TIDAK
        // berlaku: wajah dengan keyakinan 0.3-0.5 dibuang, dan coach
        // menampilkan "Wajah belum terdeteksi" tanpa error apa pun.
        var options = new global.faceapi.TinyFaceDetectorOptions({
            inputSize: this.inputSize,
            scoreThreshold: this.scoreThreshold,
        });

        var task;

        try {
            task = global.faceapi.detectAllFaces(this.detectCanvas, options);
        } catch (e) {
            this.detecting = false;
            this.lastDetectError = String(e && e.message ? e.message : e);

            return false;
        }

        if (!task || typeof task.then !== 'function') {
            // sangat tidak mungkin, tapi jangan diamkan: ini membuat bug
            // "tidak terdeteksi" sulit didiagnosis.
            this.detecting = false;
            this.lastDetectError = 'faceapi.detectAllFaces tidak mengembalikan Promise.';

            return false;
        }

        var wantLandmarks = !!this.landmarker;
        var chain = wantLandmarks && typeof task.withFaceLandmarks === 'function'
            ? task.withFaceLandmarks()
            : task;

        chain.then(function (results) {
            self.detecting = false;
            self.lastDetectError = null;
            self.frameCount += 1;

            if (!results || results.length === 0) {
                self.rawBox = null;
                self.smooth = null;
                self.box = null;
                self.boxAt = now;
                self.faceCount = 0;

                return;
            }

            self.faceCount = results.length;

            if (results.length > 1) {
                self.rawBox = null;
                self.smooth = null;
                self.box = { multiple: true, count: results.length };
                self.boxAt = now;

                return;
            }

            var r = results[0];
            var b = r.detection ? r.detection.box : null;

            if (!b) {
                self.rawBox = null;
                self.smooth = null;
                self.box = null;
                self.boxAt = now;

                return;
            }

            // Skala dari kanvas kerja ke koordinat video asli supaya
            // perbandingan dengan guideRect() (yang memakai video asli) valid.
            var sx = self.video.videoWidth / w;
            var sy = self.video.videoHeight / h;

            self.rawBox = {
                x: b.x * sx,
                y: b.y * sy,
                w: b.width * sx,
                h: b.height * sy,
                score: r.detection.score,
            };

            // Yang dipakai coach adalah kotak yang sudah dihaluskan, supaya
            // gonjengan 2-5 px tiap frame tidak terbaca sebagai "bergerak".
            self.box = self.smoothBox(self.rawBox);
            self.box.landmarks = self.landmarks;
            self.boxAt = now;

            // Landmark diambil dari PASS yang sama, jadi tidak perlu deteksi
            // kedua kali (yang sebelumnya memakai API sinkron dan gagal).
            if (wantLandmarks && r.landmarks) {
                var lm = self.landmarksFrom(r);

                if (lm) self.landmarks = lm;
            }
        }).catch(function (e) {
            self.detecting = false;
            self.lastDetectError = String(e && e.message ? e.message : e);
        });

        return true;
    };

    /**
     * Ubah landmark face-api menjadi metrik liveness.
     */
    FaceCoach.prototype.landmarksFrom = function (result) {
        var positions = result && result.landmarks ? result.landmarks.positions : null;

        if (!positions || positions.length < 68) return null;

        var box = result.detection ? result.detection.box : null;
        var faceWidth = box && box.width ? box.width : 1;
        var ear = Math.min(
            this.ear(positions.slice(36, 42)),
            this.ear(positions.slice(42, 48))
        );
        var eyeMidX = (positions[36].x + positions[45].x) / 2;

        return {
            ear: ear,
            // Posisi hidung relatif terhadap garis mata, dinormalisasi lebar
            // wajah. Bergeser berarti kepala berputar.
            yaw: (positions[30].x - eyeMidX) / faceWidth,
            mouth: Math.hypot(
                positions[62].x - positions[66].x,
                positions[62].y - positions[66].y
            ),
            faceWidth: faceWidth,
            faceHeight: box ? box.height : 0,
            mirrored: this.mirror,
        };
    };

    /** Satu siklus: ukur -> deteksi -> nilai ambang ->Instruction. */
    /**
     * Apakah video sudah punya frame yang benar-benar ditampilkan.
     *
     * Penting: videoWidth > 0 TIDAK menjamin frame tersedia. Kalau stream
     * baru menempel dan belum sempat decode, drawImage() menghasilkan
     * kanvas HITAM. Karena itu readyState ikut diperiksa.
     */
    FaceCoach.prototype.hasFreshFrame = function () {
        return !!(this.video
            && this.video.videoWidth > 0
            && this.video.readyState >= 2); // HAVE_CURRENT_DATA
    };

    /**
     * Apakah hasil deteksi terakhir sudah terlalu basi untuk dipakai.
     *
     * Deteksi berjalan ~70 ms sekali dan selalu tertinggal 1-2 frame. Kalau
     * terlalu lama, `null` lebih aman daripada memamerkan kotak wajah yang
     * sudah tidak relevanan (mis. setelah wajah berganti arah).
     */
    FaceCoach.prototype.isBoxStale = function () {
        if (!this.box) return true;

        return (Date.now() - this.boxAt) > this.boxStaleMs;
    };

    /**
     * Box wajah hasil deteksi terakhir yang masih relevan.
     * Detector sudah tidak sinkron, jadi inilah sumber kebenaran untuk tick.
     */
    FaceCoach.prototype.currentBox = function () {
        if (this.isBoxStale()) return null;

        return this.box;
    };

    FaceCoach.prototype.tick = function () {
        this.tickCount += 1;

        // Penanda tahap: kalau ada exception, tickError melaporkan TAHAP mana
        // yang gagal. Tanpa ini kita hanya tahu "error" tanpa tahu asal-usulnya.
        var stage = 'frame';

        // PENTING: error dari frame SEBELUMNYA dibersihkan di awal tick.
        // Sebelumnya tickError hanya pernah DIISI dan tidak pernah dihapus,
        // sehingga satu error sesaat (mis. recursion pada applyLiveness) tetap
        // tertulis selamanya dan membuat user mengira coach rusak permanen,
        // padahal coach sudah normal lagi.
        this.tickError = null;

        try {
            if (!this.hasFreshFrame()) {
                // Belum ada frame: jangan hitung apa pun, jangan tambah skor.
                this.stableFrames = 0;

                return null;
            }

            stage = 'metrics';
            var metrics = this.measureFrame();

            // Luncurkan siklus deteksi (async, tidak memblokir rAF). Hasilnya
            // dibaca ulang pada frame berikutnya lewat `currentBox()`.
            stage = 'detect';
            this.detectBox();
            var box = this.currentBox();

            stage = 'guide';
            var guide = this.guideRect();
            stage = 'evaluate';
            var state = this.evaluate(metrics, box, guide);
            stage = 'display';
            var displayBox = box && !box.multiple ? this.toDisplay(box) : null;
            var displayGuide = this.toDisplay(guide);
            var inside = box && !box.multiple ? this.insideOval(box, guide) : false;
            stage = 'update';
            this.onUpdate({
            metrics: metrics,
            box: box,
            guide: guide,
            // Kotak & guide memakai pemetaan object-cover yang sama, sehingga
            // guide yang digambar di layar benar-benar sebanding dengan
            // perhitungan occupancy di evaluate().
            display: displayBox,
            landmarks: this.landmarks,
            insideOval: inside,
            displayGuide: displayGuide,
            // Dibawa ke UI supaya bisa menjelaskan kenapa tertahan.
            detectorState: this.detectorState,
            detectError: this.lastDetectError,
            diagnostics: {
                count: this.faceCount,
                score: box && !box.multiple ? box.score : null,
                detectorState: this.detectorState,
                detectError: this.lastDetectError,
                detecting: this.detecting,
                frames: this.frameCount,
                attempts: this.detectAttempts,
                skip: this.skipReason,
                ticks: this.tickCount,
                tickError: this.tickError,
                readyState: this.video ? this.video.readyState : -1,
                video: this.video.videoWidth + 'x' + this.video.videoHeight,
                work: this.detectWidth + 'x' + this.detectHeight,
                landmarked: !!this.landmarks,
                stale: this.isBoxStale(),
            },
            state: state,
        });

        if (state.ok) {
                this.stableFrames += 1;

                if (this.stableFrames >= this.requiredFrames) {
                    this.stableFrames = 0;
                    this.onReady();
                }
            } else {
                // PENTING: jangan di-RESET ke nol. Sebelumnya satu frame jelek (kedip
                // atau sedikit bergeser) langsung menghapus semua progres yang
                // sudah terkumpul, sehingga pengguna praktis tidak pernah bisa
                // menyelesaikan. Sekarang progres menyusut perlahan, jadi
                // guncangan kecil tidak menghapus hasil.
                this.stableFrames = Math.max(0, this.stableFrames - 2);
            }

            return state;
        } catch (e) {
            // Catat tahap gagalnya supaya akar masalah terlihat, bukan sekadar
            // "error". Coach tetap hidup: frame berikutnya akan mencoba lagi.
            this.tickError = stage + ': ' + String(e && e.message ? e.message : e);
            this.stableFrames = 0;

            return null;
        }
    };

    /**
     * Kotak guide (oval) dalam koordinat video asli.
     *
     * Guide digeser sedikit sesuai pose yang sedang diminta, supaya setiap
     * sampel diambil dengan posisi wajah yang sedikit berbeda.
     */
    FaceCoach.prototype.guideRect = function () {
        var vw = this.video.videoWidth;
        var vh = this.video.videoHeight;

        // Guide harus BERDIRI (tinggi > lebar) seperti kepala manusia.
        // Kalau guide ikut landscape, wajah portrait tidak pernah bisa
        // memenuhi dan UI akan terus bilang "LUAR AREA".
        var gh = vh * GUIDE.height;
        var gw = gh * GUIDE.aspect;

        // Jangan melebihi frame (kamera 4:3 punya tinggi lebih besar).
        var maxW = vw * GUIDE.maxWidth;

        if (gw > maxW) {
            gw = maxW;
            gh = gw / GUIDE.aspect;
        }

        if (gh > vh * 0.86) {
            gh = vh * 0.86;
            gw = gh * GUIDE.aspect;
        }

        var dx = (this.poseDx || 0) * gw;
        var dy = (this.poseDy || 0) * gh;

        return {
            x: Math.max(0, Math.min(vw - gw, (vw - gw) / 2 + dx)),
            y: Math.max(0, Math.min(vh - gh, (vh - gh) / 2 + dy)),
            w: gw,
            h: gh,
        };
    };

    /**
     * Ambang + urutan prioritas. Satu arahan saja per frame: yang paling
     * penting didahulukan (gelap > Faces > posisi > steadiness).
     */
    FaceCoach.prototype.evaluate = function (metrics, box, guide) {
        var out = {
            ok: false,
            level: 'wait',
            instruction: 'Arahkan kamera ke wajah Anda.',
            detail: '',
            brightness: metrics.brightness,
            sharpness: metrics.sharpness,
        };

        if (box && box.multiple) {
            out.level = 'error';
            out.instruction = 'Terlalu banyak wajah di kamera.';
            out.detail = 'Pastikan hanya wajah Anda yang terlihat (' + box.count + ' terdeteksi).';
            return out;
        }

        // 1. Cahaya paling kritis: tanpa cahaya, deteksi wajah gagal total.
        if (metrics.brightness < 40) {
            out.level = 'error';
            out.instruction = 'Cahaya kurang.';
            out.detail = 'Pindah ke tempat lebih terang atau nyalakan lampu.';
            return out;
        }

        if (metrics.brightness > 238) {
            out.level = 'error';
            out.instruction = 'Terlalu terang / kamera menyala.';
            out.detail = 'Hindari sinar matahari langsung atau cahaya tembak di wajah.';
            return out;
        }

        // 2. Wajah terdeteksi?
        if (!box) {
            out.level = 'error';
            out.instruction = 'Wajah belum terdeteksi.';
            out.detail = 'Pastikan wajah terlihat penuh di dalam guide, tanpa masker.';
            return out;
        }

        // 0.5 -> 0.42. Ambang deteksi 0.3 sudah menyaring wajah samar, jadi
        // 0.5 hanya menolak wajah yang sah saat pencahayaan redup. Pada
        // webcam laptop nilai score sering berada di 0.45-0.6, sehingga 0.5
        // membuat coach terus bergetar tanpa alasan yang berarti.
        if (box.score < 0.42) {
            out.level = 'warn';
            out.instruction = 'Wajah kurang jelas.';
            out.detail = 'Wajah terlihat, tapi keyakinan deteksi rendah. Pastikan pencahayaan merata.';
            return out;
        }

        // 3. Ketajaman: blur = menolak (tidak cukup tajam).
        if (metrics.sharpness < 22) {
            out.level = 'warn';
            out.instruction = 'Gambar kurang tajam.';
            out.detail = 'Tahan tangan tetap / jangan sampai kamera goyang, lalu diam beberapa detik.';
            return out;
        }

        // 4. Ukuran wajah terhadap guide.
        var occupancy = (box.w * box.h) / (guide.w * guide.h);

        if (occupancy < 0.32) {
            out.level = 'warn';
            out.instruction = 'Terlalu jauh.';
            out.detail = 'Dekati kamera sampai wajah memenuhi guide oval.';
            return out;
        }

        if (occupancy > 1.75) {
            out.level = 'warn';
            out.instruction = 'Terlalu dekat.';
            out.detail = 'Jauhkan sedikit supaya seluruh wajah terlihat.';
            return out;
        }

        // 5. Posisi: beri arahan arah yang konkret.
        var boxCx = box.x + box.w / 2;
        var boxCy = box.y + box.h / 2;
        var guideCx = guide.x + guide.w / 2;
        var guideCy = guide.y + guide.h / 2;
        var offX = (boxCx - guideCx) / guide.w;
        var offY = (boxCy - guideCy) / guide.h;

        // Toleransi ini longgar sengaja: yang menentukan "di dalam area" adalah
        // insideOval(). Di sini hanya beranjak bila posisi MELANJUT jauh,
        // supaya coach tidak berganti arahan tiap frame.
        if (Math.abs(offX) > 0.26 || Math.abs(offY) > 0.26) {
            out.level = 'warn';

            if (Math.abs(offX) > Math.abs(offY)) {
                out.instruction = offX > 0 ? 'Geser sedikit ke kiri.' : 'Geser sedikit ke kanan.';
            } else {
                out.instruction = offY > 0 ? 'Naikkan sedikit dagu.' : 'Turunkan sedikit dagu.';
            }

            out.detail = 'Posisikan wajah di tengah guide oval.';
            return out;
        }
        // 6. Stabilitas: mengukur pergerakan DARI FRAME SEBELUMNYA.
        //
        // Penting: lastBox harus diperbarui di SETIAP frame, termasuk ketika
        // pemeriksaan gagal. Kalau tidak, drift terus diukur terhadap posisi
        // lama sehingga begitu wajah digeser sedikit saja, sistem mengunci
        // "Jangan bergerak dulu."_selamanya (deadlock).
        var previous = this.lastBox;
        this.lastBox = { cx: boxCx, cy: boxCy, w: box.w, h: box.h };

        if (previous) {
            var drift = Math.abs(boxCx - previous.cx) + Math.abs(boxCy - previous.cy)
                + Math.abs(box.w - previous.w) * 0.5;

            // Drift dinormalisasi ke ukuran guide. Ambang piksel tetap (18)
            // berarti jauh lebih ketat pada frame 1280px daripada 640px,
            // sehingga pengguna tidak pernah bisa dianggap stabil.
            var scale = guide.w / 400;
            this.history.push(drift / scale);

            if (this.history.length > 10) this.history.shift();

            // RATA-RATA, bukan maksimum. Sebelumnya satu lonjakan sesaat
            // (kedip atau deteksi yang sedikit bergeser) sudah cukup
            // menggagalkan seluruh jendela 10 frame, sehingga coach menuntut
            // kemurnian yang mustahil dari webcam sungguhan.
            var sumDrift = 0;
            for (var d = 0; d < this.history.length; d++) {
                sumDrift += this.history[d];
            }

            var avgDrift = sumDrift / this.history.length;

            // 18 -> 55: jauh lebih toleran. Drift diukur pada kotak yang sudah
            // dihaluskan, jadi angka kecil ini tetap berarti gerakan nyata.
            if (avgDrift > 55) {
                out.level = 'warn';
                out.instruction = 'Jangan bergerak dulu.';
                out.detail = 'Tahan posisi sebentar sampai indikator siap.';
                return out;
            }
        }

        out.ok = true;
        out.level = 'ok';
        out.instruction = 'Siap - tetap diam.';
        out.detail = 'Sampel diambil otomatis.';

        return out;
    };

    /**
     * Ambil foto dari stream (dicerminkan supaya sama dengan preview) lalu
     * kosongkan detector supaya sampel berikutnya benar-benar baru.
     */
    FaceCoach.prototype.capture = function (width, height, done) {
        var self = this;

        if (!this.hasFreshFrame()) {
            if (typeof done === 'function') {
                done(null);
            }

            return;
        }

        var grab = function () {
            // Resolusi sampel mengikuti NATIVE kamera, bukan angka tetap.
            //
            // Sebelumnya di-hard-code 480x360. Dampaknya fatal: microservice
            // menolak wajah dengan sisi terpendek < 120px. Wajah 110x150px di
            // frame 1280x720 menjadi 82x113px setelah di-downscale, sehingga
            // kelima sampel ditolak ("wajah_terlalu_kecil") dan user melihat
            // "0/5 sampel terbaca" padahal thumbnail-nya jelas-jelas bagus.
            //
            // Di sini rasio asli kamera dipertahankan dan lebar dibatasi 1280
            // supaya payload base64 tidak membengkak.
            var vw = self.video.videoWidth;
            var vh = self.video.videoHeight;
            var w = width;
            var h = height;

            if (!w || !h) {
                // Rasio asli kamera (webcam 16:9, kamera laptop 4:3, dll).
                var ratio = vh / vw;
                w = Math.min(CAPTURE_MAX_WIDTH, vw);
                h = Math.round(w * ratio);
            }

            var canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            var ctx = canvas.getContext('2d');

            if (self.mirror) {
                ctx.translate(w, 0);
                ctx.scale(-1, 1);
            }

            ctx.drawImage(self.video, 0, 0, w, h);
            self.reset();

            if (typeof done === 'function') {
                done(canvas.toDataURL('image/jpeg', 0.9));
            }
        };

        // Tunggu frame yang benar-benar ditampilkan. Tanpa ini, selfie bisa
        // keluar hitam karena kanvas digambar sebelum ada frame ter-decode.
        if (typeof this.video.requestVideoFrameCallback === 'function') {
            this.video.requestVideoFrameCallback(grab);

            return;
        }

        grab();
    };

    global.FaceCoach = FaceCoach;
    /**
     * Ubah kotak dari koordinat VIDEO ke persen dari elemen yang ditampilkan.
     *
     * Dua hal yang sering terlewat dan membuat kotak tampak meleset:
     *   1. <video> memakai object-cover, jadi video diperbesar lalu DIPOTONG
     *      untuk memenuhi wadah. Persentase di dalam video tidak sama dengan
     *      persentase di dalam wadah.
     *   2. Preview dicermin (scaleX(-1)), jadi sisi kiri tertukar.
     *
     * @returns {{left:number,top:number,width:number,height:number}|null}
     */
    FaceCoach.prototype.toDisplay = function (box) {
        var vw = this.video.videoWidth;
        var vh = this.video.videoHeight;
        var rect = this.video.getBoundingClientRect();

        if (!vw || !vh || !rect.width || !rect.height) return null;

        var scale = Math.max(rect.width / vw, rect.height / vh);
        var dispW = vw * scale;
        var dispH = vh * scale;
        var offX = (rect.width - dispW) / 2;
        var offY = (rect.height - dispH) / 2;

        var x = box.x * scale + offX;
        var y = box.y * scale + offY;
        var w = box.w * scale;
        var h = box.h * scale;

        // Preview dicermin: koordinat x harus dibalik agar kotak tetap nempel.
        var left = this.mirror ? rect.width - (x + w) : x;

        return {
            left: Math.max(0, Math.min(100, (left / rect.width) * 100)),
            top: Math.max(0, Math.min(100, (y / rect.height) * 100)),
            width: Math.max(0, Math.min(100, (w / rect.width) * 100)),
            height: Math.max(0, Math.min(100, (h / rect.height) * 100)),
        };
    };

    /**
     * Pose yang diminta untuk sampel berikutnya.
     *
     * Tanpa variasi, semua sampel diambil dengan posisi wajah sama persis
     * sehingga vektorcjenis satu. Dengan rotasi posisi, sampel menjadi lebih
     * beragam sehingga vektor rata-rata lebih stabil untuk Recognition.
     *
     * Urutan: tengah, kiri, kanan, sedikit naik, sedikit turun, tengah.
     */
    FaceCoach.POSES = [
        { dx: 0.00, dy: 0.00 },
        { dx: -0.22, dy: 0.05 },
        { dx: 0.22, dy: -0.05 },
        { dx: 0.05, dy: 0.14 },
        { dx: -0.05, dy: -0.14 },
    ];

    FaceCoach.prototype.setPose = function (index) {
        var pose = FaceCoach.POSES[index % FaceCoach.POSES.length];
        this.poseIndex = index;
        this.poseDx = pose.dx;
        this.poseDy = pose.dy;
    };

    FaceCoach.prototype.poseLabel = function () {
        if (this.poseIndex === 0) {
            return 'Posisikan wajah di tengah guide, lalu diam.';
        }

        return 'Sedikit geser posisi wajah sesuai guide, lalu diam.';
    };
    /**
     * Eye Aspect Ratio dari 6 titik mata.
     *
     * Rumus standar memakai p1..p6 (1-indexed) yang dalam slice 0-indexed
     * menjadi pts[0]..pts[5]. Mengakses pts[6] akan undefined dan memicu
     * TypeError yang membuat liveness diam-diam gagal.
     */
    FaceCoach.prototype.ear = function (pts) {
        if (!pts || pts.length < 6) return 1;
        var d = function (a, b) { return Math.hypot(a.x - b.x, a.y - b.y); };
        var denominator = 2 * d(pts[0], pts[3]);

        if (denominator <= 0) return 1;

        return (d(pts[1], pts[5]) + d(pts[2], pts[4])) / denominator;
    };

    /**
     * Apakah wajah hasil deteksi model BERADA DI DALAM guide.
     *
     * Acuannya adalah KOTAK WAJAH YANG DIDETEKSI MODEL, bukan sekadar
     * kedekatan visual. Sebuah wajah dianggap di dalam area bila:
     *   1. keyakinan deteksi cukup tinggi,
     *   2. ukuran wajah wajar terhadap guide (tidak terlalu jauh/dekat),
     *   3. pusat wajah berada di dalam elips guide.
     *
     * Ada toleransi 1,25x supaya kesalahan kecil tidak ongoing fails.
     */
    FaceCoach.prototype.insideOval = function (box, guide) {
        if (!box || box.multiple) {
            return false;
        }

        // Ambang keyakinan harus sama dengan scoreThreshold yang dipakai
        // saat deteksi. Kalau deteksi sudah memfilter 0.3, maka 0.6 di sini
        // hanya menolak wajah yang sah saat pencahayaan redup.
        if (box.score < 0.3) {
            return false;
        }

        var occupancy = (box.w * box.h) / (guide.w * guide.h);

        if (occupancy < 0.28 || occupancy > 1.9) {
            return false;
        }

        // Elips dengan toleransi 1.35x: cukup longgar untuk koreksi kecil, tanpa
        // membiarkan wajah benar-benar di luar guide lolos.
        var tolerance = 1.35;
        var cx = box.x + box.w / 2;
        var cy = box.y + box.h / 2;
        var nx = (cx - (guide.x + guide.w / 2)) / ((guide.w / 2) * tolerance);
        var ny = (cy - (guide.y + guide.h / 2)) / ((guide.h / 2) * tolerance);

        return (nx * nx) + (ny * ny) <= 1;
    };
})(window);
