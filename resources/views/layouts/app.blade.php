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
    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
        rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white overflow-x-hidden">

    <!-- LOADING SCREEN -->
    <div
        id="loading-screen"
        class="fixed inset-0 z-[9999] overflow-y-auto bg-black/20 backdrop-blur-[2px] transition-opacity duration-[2000ms]"> <!-- Glow -->
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
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    <!-- ANIME JS -->
    <script type="module">
        import {
            createTimeline
        } from 'https://esm.sh/animejs';

        /*
        |--------------------------------------------------------------------------
        | Animation Timeline
        |--------------------------------------------------------------------------
        */

        const tl = createTimeline({
            defaults: {
                ease: 'inOutExpo',
                duration: 2000, // 2 detik
                loop: true,
                alternate: true,
            }
        });

        tl
            .add('.triangle', {
                x: '13rem',
                rotate: '2turn',
                scale: [0.8, 1.2],
            })

            .add('.square', {
                x: '13rem',
                rotate: '-2turn',
                borderRadius: ['1rem', '3rem'],
                scale: [1, 1.3],
            }, '-=1500')

            .add('.circle', {
                x: '13rem',
                scale: [1, 1.5],
            }, '-=1500');

        /*
        |--------------------------------------------------------------------------
        | Hide Loader
        |--------------------------------------------------------------------------
        */

        window.addEventListener('load', function() {

            const loader =
                document.getElementById(
                    'loading-screen'
                );

            // tampil minimal 2 detik
            setTimeout(() => {

                loader.classList.add(
                    'opacity-0'
                );

                setTimeout(() => {

                    loader.style.display =
                        'none';

                }, 1000);

            }, 2000);

        });
    </script>

    <!-- Mobile Bottom Navbar -->
    <x-mobile-bottom-nav />

</body>

</html>