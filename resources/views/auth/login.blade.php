<x-guest-layout>
    <div class="fixed inset-0 z-50 min-h-screen overflow-y-auto bg-[#f8f9ff] px-4 py-4 text-[#0b1c30] sm:px-6 sm:py-6">
        <div class="mx-auto flex w-full max-w-md flex-col gap-4">
            <header class="relative isolate overflow-hidden rounded-2xl border border-[#c4c5d7] bg-[#0b1c30] shadow-[0_8px_24px_-12px_rgba(15,23,42,0.3)]">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(29,78,216,0.8),transparent_55%),linear-gradient(145deg,#0b1c30,#0037b0)]"></div>
                <div class="relative flex min-h-[168px] flex-col items-center justify-end gap-3 px-5 py-5 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-white/25 bg-[#1d4ed8]/90 text-white shadow-[0_8px_20px_-4px_rgba(29,78,216,0.55)]" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-7 w-7 fill-none stroke-current" stroke-width="1.8">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="2.5" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <h1 class="font-semibold tracking-tight text-white" style="font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;">RS MATA PBEC</h1>
                            <span class="rounded-full border border-blue-200/30 bg-[#1d4ed8] px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.14em] text-white">Eye Center</span>
                        </div>
                        <p class="mt-1 text-xs font-medium text-blue-100">Sistem Presensi &amp; Roster Klinis</p>
                    </div>
                </div>
            </header>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <section class="rounded-2xl border border-[#c4c5d7] bg-white p-4 shadow-[0_2px_8px_rgba(15,23,42,0.05)] sm:p-5">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#565e74]">Portal akses pegawai</p>
                    <h2 class="mt-1 text-xl font-bold tracking-tight text-[#0b1c30]" style="font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;">Masuk ke sistem presensi</h2>
                    <p class="mt-1 text-sm leading-5 text-[#434655]">Gunakan kredensial akun pegawai yang telah terdaftar.</p>
                </div>

                <div id="login-error" class="mb-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert" aria-live="polite"></div>

                <form id="loginForm" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between gap-3">
                            <label for="email" class="text-sm font-semibold text-[#0b1c30]">Email</label>
                            <span class="text-[11px] font-medium text-[#565e74]">Akun pegawai</span>
                        </div>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#565e74]" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m4 7 8 6 8-6" /></svg>
                            </span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Masukkan email pegawai"
                                class="h-12 w-full rounded-xl border border-[#c4c5d7] bg-white px-4 pl-11 text-sm text-[#0b1c30] outline-none transition placeholder:text-[#747686] focus:border-[#1d4ed8] focus:ring-2 focus:ring-[#1d4ed8]/20">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between gap-3">
                            <label for="password" class="text-sm font-semibold text-[#0b1c30]">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#0037b0] hover:underline">Lupa password?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#565e74]" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                            </span>
                            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password akun"
                                class="h-12 w-full rounded-xl border border-[#c4c5d7] bg-white px-11 text-sm text-[#0b1c30] outline-none transition placeholder:text-[#747686] focus:border-[#1d4ed8] focus:ring-2 focus:ring-[#1d4ed8]/20">
                            <button id="togglePasswordButton" type="button" onclick="togglePassword()" aria-label="Tampilkan password" class="absolute inset-y-0 right-3 flex items-center rounded-lg px-1 text-[#565e74] transition hover:text-[#0037b0] focus:outline-none focus:ring-2 focus:ring-[#1d4ed8]/30">
                                <svg id="eyeIcon" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" /></svg>
                            </button>
                        </div>
                    </div>

                    <label for="remember_me" class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#c4c5d7] bg-[#eff4ff] px-3 py-3">
                        <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-[#747686] text-[#1d4ed8] focus:ring-[#1d4ed8]">
                        <span>
                            <span class="block text-sm font-semibold text-[#0b1c30]">Ingat saya</span>
                            <span class="block text-xs text-[#565e74]">Tetap masuk di perangkat ini.</span>
                        </span>
                    </label>

                    <button id="loginSubmitButton" type="submit" class="flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-[#1d4ed8] px-4 text-sm font-semibold text-white shadow-[0_8px_20px_-4px_rgba(29,78,216,0.35)] transition hover:bg-[#0037b0] focus:outline-none focus:ring-4 focus:ring-[#1d4ed8]/20 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70">
                        <svg id="loginLoadingIcon" viewBox="0 0 24 24" class="hidden h-5 w-5 animate-spin fill-none stroke-current" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" class="opacity-25" /><path d="M21 12a9 9 0 0 1-9 9" /></svg>
                        <svg id="loginIcon" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M10 17l5-5-5-5" /><path d="M15 12H3" /><path d="M21 19V5a2 2 0 0 0-2-2h-6" /></svg>
                        <span id="loginSubmitLabel">Masuk ke sistem presensi</span>
                    </button>

                    <div class="pt-1 text-center">
                        <p class="text-sm text-[#565e74]">Belum memiliki akun terdaftar?</p>
                        <a href="{{ route('register') }}" class="mt-1 inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-sm font-semibold text-[#0037b0] transition hover:bg-[#eff4ff] hover:underline focus:outline-none focus:ring-2 focus:ring-[#1d4ed8]/30">
                            Registrasi / Aktivasi Pegawai Baru
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-[#c4c5d7] bg-white p-4 shadow-[0_2px_8px_rgba(15,23,42,0.04)]" aria-labelledby="security-title">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#dae2fd] text-[#0037b0]" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.5 2.9 8.1 7 10 4.1-1.9 7-5.5 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-4" /></svg>
                    </div>
                    <div>
                        <h2 id="security-title" class="text-sm font-semibold text-[#0b1c30]">Akses aman untuk presensi klinis</h2>
                        <p class="mt-1 text-xs leading-5 text-[#434655]">Gunakan perangkat tepercaya dan jaringan internal sesuai kebijakan keamanan RS Mata PBEC.</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-[#c4c5d7] pt-3 text-xs text-[#565e74]">
                    <span>PBEC · Sistem Presensi</span>
                    <span class="font-semibold text-[#0037b0]">Helpdesk IT</span>
                </div>
            </section>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const loginSubmitButton = document.getElementById('loginSubmitButton');
        const loginSubmitLabel = document.getElementById('loginSubmitLabel');
        const loginIcon = document.getElementById('loginIcon');
        const loginLoadingIcon = document.getElementById('loginLoadingIcon');
        let loginSubmitting = false;

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (loginSubmitting) return;

            loginSubmitting = true;
            loginSubmitButton.disabled = true;
            loginSubmitButton.setAttribute('aria-busy', 'true');
            loginSubmitLabel.textContent = 'Memproses...';
            loginIcon.classList.add('hidden');
            loginLoadingIcon.classList.remove('hidden');

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember_me').checked;

            try {
                const response = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email,
                        password,
                        remember
                    })
                });

                const data = await response.json();
                if (response.ok) {
                    if (data.token) localStorage.setItem('token', data.token);
                    window.location.href = '/dashboard';
                } else {
                    showError(data.message || 'Email atau password salah');
                }
            } catch {
                showError('Terjadi kesalahan sistem');
            } finally {
                loginSubmitting = false;
                loginSubmitButton.disabled = false;
                loginSubmitButton.removeAttribute('aria-busy');
                loginSubmitLabel.textContent = 'Masuk ke sistem presensi';
                loginIcon.classList.remove('hidden');
                loginLoadingIcon.classList.add('hidden');
            }
        });

        function showError(message) {
            const errorBox = document.getElementById('login-error');
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.getElementById('togglePasswordButton');
            const eyeIcon = document.getElementById('eyeIcon');
            const isPassword = password.type === 'password';

            password.type = isPassword ? 'text' : 'password';
            toggle.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
            eyeIcon.innerHTML = isPassword
                ? '<path d="M3 3 21 21" /><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" /><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6 0 9.5 7 9.5 7a17.8 17.8 0 0 1-3.1 3.9M6.2 6.2C3.8 8.1 2.5 12 2.5 12s3.5 7 9.5 7c1.3 0 2.5-.3 3.5-.8" />'
                : '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" />';
        }
    </script>
</x-guest-layout>
