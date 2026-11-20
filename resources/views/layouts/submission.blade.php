<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PBEC · Pengajuan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/vendor-signature-pad.js'])
</head>

<body class="bg-[#f8f9ff] text-[#0b1c30] antialiased overflow-x-hidden">
    <div class="min-h-screen w-full">
        {{-- Keep the application's existing top navigation and its Alpine/menu/logout behavior. --}}
        @include('layouts.navigation')

        <div class="w-full flex justify-center">
            <div class="w-full max-w-lg min-h-screen flex flex-col bg-[#f8f9ff] relative shadow-xl pb-24">
            <main class="flex-1">
                @yield('content')
            </main>

            <x-system-announcement />
            <x-mobile-bottom-nav />
            </div>
        </div>
    </div>

    {{-- Preserve the authenticated application's idle-session behavior for this selective shell. --}}
    <script>
        (function () {
            const IDLE_MS = 30 * 60 * 1000;
            const WARNING_MS = 30 * 1000;
            let lastActivityAt = performance.now();
            let warningDeadline = lastActivityAt + IDLE_MS - WARNING_MS;
            let logoutDeadline = lastActivityAt + IDLE_MS;
            let phaseTimer = null;
            let countdownInterval = null;
            let warningOpen = false;
            let isLoggingOut = false;
            let dialogGeneration = 0;
            let activeDialog = null;

            function clearPhaseTimer() {
                if (phaseTimer !== null) clearTimeout(phaseTimer);
                phaseTimer = null;
            }

            function clearCountdown() {
                if (countdownInterval !== null) clearInterval(countdownInterval);
                countdownInterval = null;
                if (activeDialog) activeDialog.countdownTimer = null;
            }

            function clearDialogCountdown(dialog) {
                if (!dialog || dialog.countdownTimer === null) return;
                clearInterval(dialog.countdownTimer);
                if (countdownInterval === dialog.countdownTimer) countdownInterval = null;
                dialog.countdownTimer = null;
            }

            function isPopupEvent(event) {
                if (!event || typeof Swal === 'undefined' || typeof Swal.getPopup !== 'function') return false;
                const popup = Swal.getPopup();
                return Boolean(popup && event.target && popup.contains(event.target));
            }

            function forceLogout() {
                if (isLoggingOut) return;
                isLoggingOut = true;
                clearPhaseTimer();
                clearCountdown();

                try { localStorage.removeItem('token'); } catch (error) { /* storage may be unavailable */ }

                const csrf = document.querySelector('meta[name="csrf-token"]');
                fetch('/logout', {
                    method: 'POST',
                    redirect: 'manual',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf ? csrf.content : '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ _token: csrf ? csrf.content : '' })
                }).catch(function () {}).finally(function () {
                    window.location.href = '/login?session_expired=1';
                });
            }

            function showWarning() {
                if (isLoggingOut) return;
                if (typeof Swal === 'undefined') {
                    forceLogout();
                    return;
                }

                const dialog = {
                    id: ++dialogGeneration,
                    suppressed: false,
                    countdownTimer: null,
                };
                warningOpen = true;
                let remaining = 30;
                clearCountdown();
                activeDialog = dialog;
                dialog.countdownTimer = setInterval(function () {
                    remaining -= 1;
                    const countdown = document.getElementById('session-countdown');
                    if (countdown) countdown.textContent = Math.max(remaining, 0);
                }, 1000);
                countdownInterval = dialog.countdownTimer;

                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Akan Berakhir',
                    html: 'Tidak ada aktivitas selama <b>30 menit</b>.<br><br>Sistem akan logout otomatis dalam <b id="session-countdown">30</b> detik.',
                    confirmButtonText: 'Tetap Login',
                    showCancelButton: true,
                    cancelButtonText: 'Logout Sekarang',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    timer: WARNING_MS,
                    timerProgressBar: true
                }).then(function (result) {
                    clearDialogCountdown(dialog);
                    if (!activeDialog || activeDialog.id !== dialog.id) {
                        return;
                    }

                    activeDialog = null;
                    if (dialog.suppressed) return;

                    warningOpen = false;
                    if (result.isConfirmed) resetActivity();
                    else forceLogout();
                });
            }

            function schedulePhaseTimer() {
                if (isLoggingOut || phaseTimer !== null) return;

                const now = performance.now();
                const deadline = warningOpen ? logoutDeadline : warningDeadline;
                phaseTimer = setTimeout(checkDeadline, Math.max(0, deadline - now));
            }

            function checkDeadline() {
                phaseTimer = null;
                if (isLoggingOut) return;

                const now = performance.now();

                if (now < warningDeadline) {
                    schedulePhaseTimer();
                    return;
                }

                if (now < logoutDeadline) {
                    if (!warningOpen) showWarning();
                    schedulePhaseTimer();
                    return;
                }

                forceLogout();
            }

            function resetActivity(event) {
                if (isLoggingOut) return;
                if (isPopupEvent(event)) return;

                lastActivityAt = performance.now();
                warningDeadline = lastActivityAt + IDLE_MS - WARNING_MS;
                logoutDeadline = lastActivityAt + IDLE_MS;

                if (warningOpen && typeof Swal !== 'undefined') {
                    if (activeDialog) activeDialog.suppressed = true;
                    warningOpen = false;
                    Swal.close();
                }
                schedulePhaseTimer();
            }

            ['mousemove', 'mousedown', 'keydown', 'keypress', 'click', 'scroll', 'touchstart', 'pointerdown']
                .forEach(function (event) { document.addEventListener(event, resetActivity, { passive: true }); });
            document.addEventListener('visibilitychange', function () { if (!document.hidden) resetActivity(); });
            schedulePhaseTimer();
        })();
    </script>
</body>

</html>
