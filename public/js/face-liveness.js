/**
 * Face Liveness: memastikan orang di depan kamera benar-benar MANUSIA HIDUP.
 *
 * Digunakan pada registrasi wajah. Prinsipnya "active liveness"
 * (challenge-response): sistem memberi perintah, lalu mengukur apakah
 * gerakan nyata terjadi. Foto cetak, cetak foto di kertas, maupun rekaman
 * video TIDAK bisa mengikuti perintah ini, sehingga langsung ditolak.
 *
 *
 * Semua pengukuran memakai face-api landmark 68 titik yang sudah ada di
 * public/models - tanpa model eksternal dan tanpa satu pun panggilan ke
 * server saat pemeriksaan berjalan.
 *
 * Tantangan yang dipakai (urutan diacak tiap sesi):
 *   blink      : kedipkan mata                     -> Eye Aspect Ratio
 *   turn_left  : putar kepala sedikit ke kiri      -> pergeseran hidung
 *   turn_right : putar kepala sedikit ke kanan     -> pergeseran hidung
 *   smile      : senyum / buka mulut               -> jarak bibir dalam
 *   closer     : dekatkan sedikit ke kamera        -> lebar kotak wajah
 */
(function (global) {
    'use strict';

    function clamp(v, lo, hi) { return v < lo ? lo : (v > hi ? hi : v); }
    function dist(a, b) { return Math.hypot(a.x - b.x, a.y - b.y); }

    /**
     * Eye Aspect Ratio. Nilai kecil = mata tertutup, nilai besar = terbuka.
     */
    function eyeAspectRatio(pts) {
        if (!pts || pts.length < 6) return 1;
        var denominator = 2 * dist(pts[0], pts[3]);

        if (denominator <= 0) return 1;

        return (dist(pts[1], pts[5]) + dist(pts[2], pts[4])) / denominator;
    }

    function mouthOpen(pts) {
        if (!pts || pts.length < 68) return 0;
        return dist(pts[62], pts[66]);   // bibir dalam atas <-> bawah
    }

    function shuffle(list) {
        var a = list.slice();
        for (var i = a.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = a[i]; a[i] = a[j]; a[j] = t;
        }
        return a;
    }

    var CHALLENGES = {
        blink:      { label: 'Kedipkan mata',        hint: 'Kedipkan mata dua kali.' },
        turn_left:  { label: 'Putar ke kiri',         hint: 'Putar kepala sedikit ke kiri, lalu kembali.' },
        turn_right: { label: 'Putar ke kanan',        hint: 'Putar kepala sedikit ke kanan, lalu kembali.' },
        smile:      { label: 'Senyum',                hint: 'Senyum atau buka mulut sedikit.' },
        closer:     { label: 'Dekatkan sedikit',      hint: 'Dekatkan wajah ke kamera sedikit.' },
    };
    function Liveness(options) {
        options = options || {};
        this.count = options.count || 3;                 // berapa tantangan per sesi
        this.onUpdate = options.onUpdate || function () {};
        this.onPass = options.onPass || function () {};
        this.onFail = options.onFail || function () {};

        this.order = shuffle(Object.keys(CHALLENGES)).slice(0, this.count);
        this.index = 0;
        this.state = 'idle';     // idle | running | passed | failed
        this.progress = 0;
        this.detail = '';
        this.baseline = null;
        this.blinkArmed = false;
        this.blinks = 0;
        this.peak = 0;
        this.turnedFrames = 0;
        this.startedAt = 0;
        this.timeoutMs = options.timeoutMs || 15000;

        // Riwayat EAR untuk deteksi kedip berbasis jendela waktu.
        this.earWindow = [];
        this.lastBlinkAt = 0;

        // Baseline EAR mata terbuka milik user ini. Dipakai untuk
        // ambang relatif, sehingga orang bermata sipit tetap bisa
        // kedip-nya terdeteksi.
        this.openEar = 0;
        this.earCurrent = 0;
        this.earMin = 0;
    }

    Liveness.CHALLENGES = CHALLENGES;

    Liveness.prototype.start = function () {
        if (this.state === 'passed') return;
        this.state = 'running';
        this.startedAt = Date.now();
        this.baseline = null;
        this.index = 0;
        this.progress = 0;
        this.blinks = 0;
        this.earWindow = [];
        this.lastBlinkAt = 0;
        this.openEar = 0;
        this.emit();
    };

    Liveness.prototype.current = function () {
        var key = this.order[this.index];
        return key ? { key: key, text: CHALLENGES[key] } : null;
    };

    Liveness.prototype.emit = function () {
        this.onUpdate({
            state: this.state,
            index: this.index,
            total: this.order.length,
            progress: this.progress,
            challenge: this.current(),
            detail: this.detail,
            // Angka EAR dikirim ke panel diagnostik. Tanpa ini, sulit
            // memastikan kenapa kedip tidak terbaca pada bentuk mata
            // tertentu (EAR terbuka bisa sangat rendah).
            ear: {
                baseline: this.openEar,
                current: this.earCurrent,
                min: this.earMin,
                blinks: this.blinks,
            },
        });
    };

    /**
     * Dipanggil tiap frame oleh coach.
     *
     * @param {object} m  landmarkLive {ear, yaw, mouth, faceWidth, faceHeight}
     */
    Liveness.prototype.update = function (m) {
        if (this.state !== 'running' || !m || !m.ok) return;

        if (Date.now() - this.startedAt > this.timeoutMs * this.order.length) {
            return this.fail('Wajah tidak merespons perintah. Pastikan Anda sendirian, tanpa foto atau video.');
        }

        var cur = this.current();
        if (!cur) { return this.pass(); }

        if (!this.baseline) {
            this.baseline = { yaw: m.yaw, mouth: m.mouth, width: m.faceWidth };
            this.detail = 'Siap - '+cur.text.hint;
            this.emit();
            return;
        }

        this.detail = cur.text.hint;
        var done = false;

        if (cur.key === 'blink') { done = this.trackBlink(m); }
        if (cur.key === 'turn_left') { done = this.trackTurn(m, -1); }
        if (cur.key === 'turn_right') { done = this.trackTurn(m, 1); }
        if (cur.key === 'smile') { done = this.trackSmile(m); }
        if (cur.key === 'closer') { done = this.trackCloser(m); }

        this.progress = done ? 1 : clamp(this.peak, 0, 0.95);
        this.emit();

        if (done) {
            this.index += 1;
            this.baseline = null;
            this.blinks = 0;
            this.peak = 0;
            this.blinkArmed = false;
            this.turnedFrames = 0;

            // Tantangan berikutnya belum boleh mewarisi riwayat kedip
            // dari tantangan ini, kalau tidak satu kedipan bisa
            // menyelesaikan dua tantangan sekaligus.
            this.earWindow = [];
            this.lastBlinkAt = 0;
            this.openEar = 0;

            if (this.index >= this.order.length) { this.pass(); }
        }
    };

    /**
     * Deteksi kedip dari JENDELA WAKTU, bukan dua sample berturut-turut.
     *
     * Kenapa tidak boleh dua sample berturut-turut? Landmark hanya
     * diperbarui sekitar 5x/detik (deteksi TinyFaceDetector + landmark
     * 68 titik itu berat di CPU), sedangkan satu kedipan hanya lasts
     * 100-150 ms. Dari lima sample per detik, peluang sebuah kedipan
     * terlihat utuh sebagai "tertutup lalu terbuka" hanya sekitar
     * 50-70%. Versi lama karena itu sering tidak merespons meski
     * pengguna sudah berkedip dua kali.
     *
     * Solusinya: simpan riwayat EAR sebentar, lalu nilai POLANYA. Satu
     * kedipan dihitung bila nilai terendah di jendela berada di bawah
     * ambang tertutup, dan ada sample SESUDAH nilai terendah itu yang
     * sudah kembali terbuka. Dengan begitu sampling yang jarang tidak
     * menjadi masalah: mata yang menutup tidak sempurna di tengah jalan
     * tetap terdeteksi, karena yang dicari adalah pola turun-naik.
     */
    Liveness.prototype.trackBlink = function (m) {
        var WINDOW_MS = 900;    // cukup memuat satu siklus kedip

        // PENTING: ambang dihitung dari baseline ORANG YANG SEDANG DIAM.
        //
        // Ambang absolut (mis. "mata tertutup < 0.20") tidak berlaku
        // untuk semua orang. Bentuk mata berbeda-beda: mata sipit secara
        // alami bisa punya EAR terbuka hanya 0.14-0.18, sehingga ambang
        // 0.20 tidak akan PERNAH tercapai dan kedip selalu gagal.
        //
        // Karena itu dipakai ambang RELATIF terhadap baseline mata
        // terbuka milik user itu sendiri, ditambah penurunan mutlak
        // minimum supaya noise kecil tidak dianggap kedip.
        var CLOSE_RATIO = 0.75; // dianggap menutup bila turun ke 75% baseline
        var OPEN_RATIO = 0.85;  // dianggap kembali terbuka bila naik ke 85%
        var MIN_DROP = 0.025;   // minimal penurunan absolut (anti noise)

        var now = Date.now();

        if (!this.earWindow) this.earWindow = [];

        this.earWindow.push({ ear: m.ear, at: now });

        // Buang sampel yang terlalu lama.
        while (this.earWindow.length && now - this.earWindow[0].at > WINDOW_MS) {
            this.earWindow.shift();
        }

        // Baseline = EAR rata-rata saat mata terbola terbuka. Di-update
        // pelan (EWMA) dan HANYA dari sampel yang terlihat terbuka,
        // supaya satu kedipan tidak ikut menarik baseline ke bawah.
        if (!this.openEar) {
            this.openEar = m.ear;
        } else if (m.ear >= this.openEar * OPEN_RATIO) {
            this.openEar = this.openEar * 0.85 + m.ear * 0.15;
        }

        this.earCurrent = m.ear;

        var closedAt = this.openEar * CLOSE_RATIO;
        var openAt = this.openEar * OPEN_RATIO;

        // Nilai terendah di jendela, hanya untuk ditampilkan di panel
        // diagnostik.
        var lowest = Infinity;
        for (var k = 0; k < this.earWindow.length; k++) {
            if (this.earWindow[k].ear < lowest) { lowest = this.earWindow[k].ear; }
        }
        this.earMin = lowest === Infinity ? m.ear : lowest;

        // Cari PENURUNAN TERAKHIR yang diikuti sampel terbuka. Dicari dari
        // belakang, bukan nilai terendah global: mata bisa tertutup
        // beberapa frame berturut-turut, sehingga titik terendah tidak
        // selalu diikuti frame yang sudah terbuka.
        var minAt = 0;
        var found = false;

        for (var i = this.earWindow.length - 2; i >= 0; i--) {
            var dip = this.earWindow[i].ear;
            var recovered = this.earWindow[i + 1].ear;

            var isClosed = dip < closedAt && (this.openEar - dip) >= MIN_DROP;
            var isOpenAgain = recovered >= openAt;

            if (isClosed && isOpenAgain) {
                minAt = this.earWindow[i].at;
                found = true;
                break;
            }
        }

        if (!found) { return false; }

        // Satu penurunan = satu kedip. Timestamp dipakai supaya kedip
        // yang sama tidak terhitung berkali-kali selama jendela yang
        // sama masih aktif.
        if (this.lastBlinkAt && minAt <= this.lastBlinkAt) {
            return false;
        }

        this.lastBlinkAt = minAt;
        this.blinks += 1;
        this.progress = Math.min(1, this.blinks / 2);
        this.peak = 1;

        return this.blinks >= 2;
    };

    /**
     * Tantangan putar kepala.
     *
     * Two-step: wajah harus benar-benar bergerak ke arah yang diminta, lalu
     * boleh kembali ke tengah. Ada toleransi waktu supaya orchestrator tetap
     * lanjut meskipun pengguna membiarkan kepalanya sedikit miring.
     */
    Liveness.prototype.trackTurn = function (m, sign) {
        var delta = sign * (m.yaw - this.baseline.yaw);
        this.peak = Math.max(this.peak, delta / 0.18);

        if (delta > 0.16) {
            this.turnedFrames = (this.turnedFrames || 0) + 1;
            this.peak = 1;

            return false;
        }

        // Sudah pernah bergerak ke arah yang diminta?
        if ((this.turnedFrames || 0) > 0) {
            if (Math.abs(delta) < 0.06) {
                return true;
            }

            if (this.turnedFrames > 45) {
                return true;
            }
        }

        return false;
    };

    /**
     * Tantangan senyum / buka mulut.
     *
     * Diukur ABSOLUT (bukan dibanding baseline) karena pengguna bisa saja
     * sudah tersenyum ketika perintah diberikan. Dengan ambil mutlak, challenge
     * tetap bisa diselesaikan.
     */
    Liveness.prototype.trackSmile = function (m) {
        var ratio = m.mouth / (m.faceWidth || 1);
        this.peak = Math.max(this.peak, ratio / 0.055);

        return ratio > 0.055;
    };

    /**
     * Tantangan mendekatkan wajah.
     *
     * Baseline diambil saat challenge dimulai. Karena tahap sebelumnya
     * memaksa wajah berada di dalam oval dengan ukuran tertentu, posisi awal
     * selalu normal sehingga perbandingan ini valid.
     */
    Liveness.prototype.trackCloser = function (m) {
        var ratio = m.faceWidth / (this.baseline.width || 1);
        this.peak = Math.max(this.peak, (ratio - 1) / 0.22);

        return ratio > 1.22;
    };

    Liveness.prototype.pass = function () {
        this.state = 'passed';
        this.progress = 1;
        this.detail = 'Wajah terverifikasi sebagai manusia asli.';
        this.emit();
        this.onPass();
    };

    Liveness.prototype.fail = function (reason) {
        this.state = 'failed';
        this.detail = reason;
        this.emit();
        this.onFail(reason);
    };

    global.FaceLiveness = Liveness;
})(window);
