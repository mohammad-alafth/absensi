<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PBEC</title>

    <!-- Favicon -->
    <link rel="icon"
        type="image/png"
        href="{{ asset('storage/pbec/pbec.png') }}">

    <!-- Fonts -->
    <!--
    |--------------------------------------------------------------------------
    | FONT EKSTERNAL — DIBUAT NON-BLOCKING
    |--------------------------------------------------------------------------
    | Sebelumnya stylesheet fonts.bunny.net dimuat secara render-blocking:
    | browser menahan render (dan event `load`) sampai file font selesai
    | diunduh. Pada jaringan internal / tanpa akses internet (kasus
    | production), host ini bisa menggantung puluhan detik sehingga halaman
    | terasa "loading lama".
    |
    | Pola berikut memuat CSS font tanpa memblokir:
    |   media="print" -> browser menganggapnya tidak relevan untuk layar
    |   onload         -> langsung ditukar menjadi media="all" saat selesai
    | <noscript> menjaga tampilan tetap benar bila JavaScript dimatikan.
    |--------------------------------------------------------------------------
    -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    <link
        rel="stylesheet"
        href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
        media="print"
        onload="this.media='all'">

    <noscript>
        <link
            rel="stylesheet"
            href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap">
    </noscript>

    <!--
    |--------------------------------------------------------------------------
    | HELPER: TUNGGU LIBRARY EKSTERNAL TANPA MEMBLOKIR HALAMAN
    |--------------------------------------------------------------------------
    | Library pihak ketiga (flatpickr, signature_pad, face-api, dsb) dimuat
    | dengan `async` supaya tidak menunda event DOMContentLoaded/`load` -
    | penting pada jaringan internal tanpa akses internet (kasus production),
    | karena script sinkron yang menggantung akan menahan seluruh halaman.

    | Konsekuensinya library bisa belum tersedia saat kode halaman berjalan,
    | sehingga kode yang membutuhkannya dibungkus helper ini:

    |   absensiWaitFor(
    |       function () { return window.flatpickr; },
    |       function () { flatpickr('#jam_mulai', {...}); }
    |   );

    | Bila library tidak pernah datang (CDN diblokir), callback tetap dipanggil
    | dengan argumen `true` (mode terbatas) supaya halaman tidak terkunci.
    |--------------------------------------------------------------------------
    -->
    <script>
        window.absensiWaitFor = function (isReady, callback, options) {
            var opts = options || {};
            var timeoutMs = opts.timeoutMs || 8000;
            var intervalMs = opts.intervalMs || 50;
            var startedAt = Date.now();

            (function check() {
                var ok = false;

                try {
                    ok = !!isReady();
                } catch (e) {
                    ok = false;
                }

                if (ok) {
                    callback(false);
                    return;
                }

                if (Date.now() - startedAt >= timeoutMs) {
                    callback(true); // library tidak terjangkau -> lanjut apa adanya
                    return;
                }

                window.setTimeout(check, intervalMs);
            })();
        };
    </script>

    <!--
    |--------------------------------------------------------------------------
    | JARING PENGAMAN LOADING SCREEN (ADAPTIF KUALITAS JARINGAN)
    |--------------------------------------------------------------------------
    | Ditulis di <head>, SEBELUM aset apa pun, dengan alasan penting:

    | Script ini tidak menunggu unduhan apa pun, jadi timer-nya selalu terpasang
    | lebih dulu. Sebelumnya timer pengaman dipasang di akhir <body> dan
    | "disarm" oleh resources/js/loading.js (module hasil build Vite). Bila
    | bundle JS lambat diunduh (jaringan lambat), timer itu belum sempat
    | terpasang / belum sempat memutus dirinya sehingga loading screen bisa
    | menggantung sampai batas maksimum pada setiap halaman.

    | Batas waktu mengikuti kualitas jaringan (Network Information API):
    |   2G / slow-2g : 5000 ms  (perangkat/jaringan sangat lambat)
    |   3G           : 3000 ms
    |   lainnya/4G   : 2000 ms
    | Node yang tidak mendukung API ini memakai nilai default 2000 ms.

    | Timer TIDAK dibatalkan oleh script di <body>. Bila halaman ternyata sudah
    | siap lebih dulu, loader sudah disembunyikan dan pemanggilan berikutnya
    | tidak berpengaruh (idempotent). Sebaliknya - bila ada aset yang benar-benar
    | macet - loader tetap dipaksa hilang tepat waktu.
    |--------------------------------------------------------------------------
    -->
    <script>
        (function () {
            var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection || {};
            var type = String(conn.effectiveType || conn.type || '').toLowerCase();

            var maxWait = 2000;                  // jaringan normal
            if (type === '3g') maxWait = 3000;   // jaringan sedang
            if (type === '2g' || type === 'slow-2g') maxWait = 5000; // jaringan sangat lambat

            var state = window.__absensiLoader = {
                network: type || 'unknown',
                maxWait: maxWait,
                shownAt: Date.now(),
                hide: null,
                escaped: false
            };

            state.timer = window.setTimeout(function () {
                // Script di <body> sudah siap -> pakai jalur normal (dengan fade).
                if (typeof state.hide === 'function') {
                    state.hide();
                    return;
                }

                // Script di <body> belum jalan (aset menggantung) -> paksa hilang
                // secepatnya tanpa animasi agar pengguna tidak terjebak.
                var el = document.getElementById('loading-screen');
                if (el) {
                    state.escaped = true;
                    el.style.display = 'none';
                }
            }, maxWait);
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white overflow-x-hidden">

    <!-- LOADING SCREEN -->
    <div
        id="loading-screen"
        class="fixed inset-0 z-[9999] overflow-y-auto bg-black/20 backdrop-blur-[2px]">
        <!-- Glow -->
        <div class="absolute w-[500px] h-[500px] bg-indigo-500/20 blur-3xl rounded-full"></div>

        <div class="relative w-80 h-48 flex items-center justify-center">

            <!-- Triangle -->
            <div
                class="triangle absolute left-0 w-0 h-0
                   border-l-[40px] border-r-[40px]
                   border-b-[70px]
                   border-l-transparent
                   border-r-transparent
                   border-b-cyan-400 drop-shadow-[0_0_25px_rgba(34,211,238,0.9)]">
            </div>

            <!-- Square -->
            <div
                class="square absolute left-0 w-16 h-16 bg-fuchsia-500 rounded-2xl shadow-[0_0_35px_rgba(217,70,239,0.9)]">
            </div>

            <!-- Circle -->
            <div
                class="circle absolute left-0 w-16 h-16 bg-emerald-400 rounded-full shadow-[0_0_35px_rgba(52,211,153,0.9)]">
            </div>

            <!-- Text -->
            <div class="absolute -bottom-16 text-center">
                <h1 class="text-3xl font-bold text-white tracking-[6px]">
                    PBEC
                </h1>

                <p class="text-indigo-200 text-sm mt-2 tracking-widest">
                    LOADING....
                </p>
            </div>

        </div>

    </div>

    <div class="min-h-screen w-full overflow-x-hidden">

        <!-- Navigation -->
        @include('layouts.navigation')

        <!-- Header -->
        @isset($header)
        <header class="bg-white border-b border-gray-100">
            <div class="max-w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endisset

        <!-- Content -->
        <main class="mt-6 z-10 pb-0 bg-white min-h-screen">
            {{ $slot }}
        </main>

    </div>

    <!-- AUTO LOGOUT / RESET SESSION -->
    <!--
    |--------------------------------------------------------------------------
    | RESET SESSION OTOMATIS (IDLE 30 MENIT)
    |--------------------------------------------------------------------------
    | - Jika TIDAK ADA AKTIVITAS selama 30 menit, sistem otomatis logout.
    | - 30 detik sebelum logout, muncul peringatan "Tetap Login".
    | - Logout dilakukan sungguhan (POST /logout): session web dihapus dan
    |   token Sanctum yang dibuat saat login ikut di-revoke (reset token).
    |--------------------------------------------------------------------------
    -->
    <!--
    |--------------------------------------------------------------------------
    | SWEETALERT2 — NON-BLOCKING + STUB ANTI-ERROR
    |--------------------------------------------------------------------------
    | Sebelumnya script ini dimuat secara SINKRON sehingga memblokir parsing
    | HTML dan menunda event DOMContentLoaded/`load`. Pada jaringan internal
    | tanpa akses ke cdn.jsdelivr.net, permintaan menggantung sampai timeout
    | dan membuat loading screen tertahan lama di SEMUA halaman.
    |
    | `async` : tidak memblokir parsing maupun DOMContentLoaded.
    | Stub    : bila CDN belum/lambat/tidak tersedia, pemanggilan Swal tidak
    |           memicu "Swal is not defined" (halaman tetap berfungsi, dialog
    |           dilewati). Saat library asli selesai dimuat, stub ditimpa.
    |--------------------------------------------------------------------------
    -->
    <script>
        window.Swal = window.Swal || {
            fire: function () {
                return Promise.resolve({ isConfirmed: false, isDismissed: true });
            },
            close: function () {},
            mixin: function () { return window.Swal; }
        };
    </script>

    <!-- SweetAlert2 asli dibundel LOKAL lewat resources/js/vendor-sweetalert.js
         (di-import oleh app.js) - tidak ada lagi permintaan ke cdn.jsdelivr.net,
         sehingga halaman tetap cepat dan berfungsi di jaringan internal. -->

    <script>
        (function () {
            // Durasi idle sebelum logout otomatis (menit)
            const IDLE_MINUTES = 30;
            // Durasi peringatan sebelum logout (detik)
            const WARNING_SECONDS = 30;

            const IDLE_MS = IDLE_MINUTES * 60 * 1000;
            const WARNING_MS = WARNING_SECONDS * 1000;

            let logoutTimer = null;
            let warningTimer = null;
            let countdownInterval = null;
            let isLoggingOut = false;
            let warningOpen = false;
            let suppressResult = false;

            function clearTimers() {
                if (logoutTimer) clearTimeout(logoutTimer);
                if (warningTimer) clearTimeout(warningTimer);
                logoutTimer = null;
                warningTimer = null;
            }

            // Jadwalkan peringatan + waktu logout otomatis
            function armTimers() {
                clearTimers();
                clearInterval(countdownInterval);

                // Peringatan muncul 30 detik sebelum waktu logout
                warningTimer = setTimeout(showWarning, IDLE_MS - WARNING_MS);
                logoutTimer = setTimeout(forceLogout, IDLE_MS);
            }

            // Dipanggil setiap kali ada aktivitas user -> reset hitung mundur
            function resetActivity() {
                if (isLoggingOut) return;

                // Jika peringatan sedang terbuka lalu user kembali aktif, tutup
                if (warningOpen && typeof Swal !== 'undefined') {
                    suppressResult = true;
                    warningOpen = false;
                    Swal.close();
                }

                armTimers();
            }

            function showWarning() {
                if (isLoggingOut) return;

                if (typeof Swal === 'undefined') {
                    forceLogout();
                    return;
                }

                let remaining = WARNING_SECONDS;
                warningOpen = true;
                clearInterval(countdownInterval);

                countdownInterval = setInterval(function () {
                    remaining--;
                    const el = document.getElementById('session-countdown');
                    if (el) el.textContent = Math.max(remaining, 0);
                }, 1000);

                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Akan Berakhir',
                    html: 'Tidak ada aktivitas selama <b>' + IDLE_MINUTES +
                        ' menit</b>.<br><br>' +
                        'Sistem akan logout otomatis dalam <b id="session-countdown">' +
                        remaining + '</b> detik.',
                    confirmButtonText: 'Tetap Login',
                    showCancelButton: true,
                    cancelButtonText: 'Logout Sekarang',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    timer: WARNING_MS,
                    timerProgressBar: true,
                    willClose: function () {
                        clearInterval(countdownInterval);
                    }
                }).then(function (result) {
                    if (suppressResult) {
                        suppressResult = false;
                        return;
                    }

                    warningOpen = false;

                    if (result.isConfirmed) {
                        // User memilih "Tetap Login"
                        resetActivity();
                    } else if (result.dismiss) {
                        // Tombol "Logout Sekarang" / waktu peringatan habis
                        forceLogout();
                    }
                });
            }

            // Logout sungguhan: hapus session web + revoke token Sanctum
            function forceLogout() {
                if (isLoggingOut) return;
                isLoggingOut = true;

                clearTimers();
                clearInterval(countdownInterval);

                // Hapus token API (Sanctum) dari localStorage browser
                try {
                    localStorage.removeItem('token');
                } catch (e) {
                    // abaikan bila localStorage tidak tersedia
                }

                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                fetch('/logout', {
                    method: 'POST',
                    redirect: 'manual',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: csrfToken })
                })
                    .catch(function () {
                        // Session di server mungkin sudah kedaluwarsa;
                        // tetap arahkan ke halaman login.
                    })
                    .finally(function () {
                        window.location.href = '/login?session_expired=1';
                    });
            }

            // Event aktivitas user yang me-reset timer idle
            const ACTIVITY_EVENTS = [
                'mousemove', 'mousedown', 'keydown', 'keypress',
                'click', 'dblclick', 'scroll', 'wheel',
                'touchstart', 'touchmove', 'pointerdown'
            ];

            ACTIVITY_EVENTS.forEach(function (evt) {
                document.addEventListener(evt, resetActivity, { passive: true });
            });

            // Kembali ke tab / halaman dianggap aktif kembali
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) resetActivity();
            });

            // Mulai hitung mundur begitu halaman selesai dimuat
            armTimers();
        })();
    </script>

    <!--
    |--------------------------------------------------------------------------
    | HIDE LOADING SCREEN (ADAPTIF JARINGAN)
    |--------------------------------------------------------------------------
    | Masalah sebelumnya: loader HANYA menghilang pada event `load`. Event itu
    | baru menyala setelah SELURUH aset selesai diunduh — termasuk aset pihak
    | ketiga (fonts/CDN) yang pada jaringan internal tanpa internet akan
    | menggantung sampai daftar tunggu berikutnya.

    | Akibatnya loader bertahan lama di setiap halaman. Ditambah lagi, sebuah
    | <script>/<link> yang MACET karena tidak pernah menerima respons
    | (DNS tidak terjawab) bahkan BUKAN hanya menunda
    | `load` selamanya — `load` bisa tidak pernah menyala.

    | Strategi baru — loader hilang saat halaman SUDAH BISA DILIHAT:
    |   1. `DOMContentLoaded` : struktur HTML + CSS + JS bundle selesai
    |      (aset eksternal async/print tidak dihitung) -> loader hilang.
    |   2. `load`             : bila ternyata selesai lebih dulu (semua aset
    |      lokal cepat) -> loader hilang lebih awal lagi.
    |   3. `pageshow`         : menangani pemulihan dari bfcache (tombol Back).
    |
    | Batas waktu (jaring pengaman) TIDAK dipasang di sini, tetapi di <head>
    | sebelum aset apa pun dimuat, agar tidak bisa tertunda oleh unduhan.
    | Nilainya mengikuti kualitas jaringan (lihat blok di <head>):
    | 2 detik jaringan normal, 3 detik 3G, 5 detik 2G/slow-2g.
    |
    | Timer tidak dipasang berulang: `hidden` menjamin aksi hanya sekali.
    | Animasi animejs dihentikan saat loader disembunyikan supaya tidak
    | membebani CPU di background (resources/js/loading.js).
    |--------------------------------------------------------------------------
    -->
    <script>
        (function () {
            var loader = document.getElementById('loading-screen');
            if (!loader) return;

            var state = window.__absensiLoader || {
                network: 'unknown',
                maxWait: 2000,
                shownAt: Date.now(),
                escaped: false
            };
            window.__absensiLoader = state;

            var MIN_DISPLAY_MS = 250;
            var FADE_MS = 250;

            var hidden = false;

            function stopAnimation() {
                // Hentikan animasi animejs agar tidak membebani CPU saat
                // loader sudah tidak terlihat (resources/js/loading.js).
                if (window.absensiLoading) {
                    window.absensiLoading.stop();
                }
            }

            function finish() {
                loader.style.display = 'none';
                stopAnimation();
            }

            function hideLoader() {
                if (hidden) return;
                hidden = true;

                var remaining = Math.max(0, MIN_DISPLAY_MS - (Date.now() - state.shownAt));

                setTimeout(function () {
                    loader.style.transition = 'opacity ' + FADE_MS + 'ms ease';
                    loader.classList.add('opacity-0');

                    setTimeout(finish, FADE_MS);
                }, remaining);
            }

            // Jaring pengaman di <head> memakai fungsi ini bila sudah tersedia.
            state.hide = hideLoader;

            // Kasus ekstrem: jaring pengaman di <head> sudah menutup loader
            // sebelum script ini jalan (aset menggantung sangat lama).
            if (state.escaped) {
                hidden = true;
                finish();

                return;
            }

            // 1 & 2. Halaman sudah bisa dilihat -> hilangkan loader
            document.addEventListener('DOMContentLoaded', hideLoader);
            window.addEventListener('load', hideLoader);

            // 3. Kembali dari bfcache (tombol Back) -> pastikan loader hilang
            window.addEventListener('pageshow', function (event) {
                if (event.persisted) hideLoader();
            });

            // Dokumen ternyata sudah siap sebelum script ini dieksekusi
            if (document.readyState !== 'loading') {
                hideLoader();
            }
        })();
    </script>

    <!-- Mobile Bottom Navbar -->
    <x-mobile-bottom-nav />

</body>

</html>