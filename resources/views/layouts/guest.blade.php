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
    | Stylesheet fonts.bunny.net sebelumnya render-blocking: browser menahan
    | render (dan event `load`) sampai file font selesai diunduh. Pada jaringan
    | internal / tanpa internet (kasus production), permintaan ini bisa
    | menggantung sehingga halaman login terasa "loading lama".
    |
    | media="print" -> dianggap tidak relevan untuk layar (tidak memblokir)
    | onload         -> segera ditukar ke media="all" setelah selesai dimuat
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
    | Library pihak ketiga (face-api, dsb) dimuat dengan `async` supaya tidak
    | menunda DOMContentLoaded/`load` pada jaringan internal tanpa internet.
    | Kode yang membutuhkan library tersebut dibungkus helper ini:

    |   absensiWaitFor(function () { return window.faceapi; }, function () {
    |       faceapi.nets.tinyFaceDetector.loadFromUri('/models');
    |   });

    | Bila library tidak terjangkau (CDN diblokir), callback tetap dipanggil
    | dengan argumen `true` (mode terbatas) agar halaman tidak terkunci.
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

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">

    <!-- Background -->
    <div class="min-h-screen flex flex-col justify-center items-center relative px-4 overflow-hidden">

        <!-- Background -->
        <img
            src="{{ asset('storage/pbec/pbechall.jpeg') }}"
            alt="Background"
            class="absolute inset-0 w-full h-full object-cover">

        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50"></div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col items-center w-full">

            <!-- Card -->
            <div
                class="w-full sm:max-w-md px-6 py-6 bg-white/95 backdrop-blur shadow-2xl rounded-3xl">

                {{ $slot }}

            </div>

        </div>

    </div>

</body>

</html>