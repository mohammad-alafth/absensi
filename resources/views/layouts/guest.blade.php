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