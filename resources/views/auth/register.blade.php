<x-guest-layout>
    <div class="-mx-6 -my-6 min-h-[680px] bg-[#f8f9ff] px-4 py-4 text-[#0b1c30] sm:px-6 sm:py-6">
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
                        <p class="mt-1 text-xs font-medium text-blue-100">Pendaftaran Akun Pegawai</p>
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
                    <h2 class="mt-1 text-xl font-bold tracking-tight text-[#0b1c30]" style="font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;">Registrasi akun pegawai</h2>
                    <p class="mt-1 text-sm leading-5 text-[#434655]">Lengkapi data dasar untuk membuat akun presensi. Akun baru akan menunggu persetujuan HRD sebelum dapat digunakan.</p>
                </div>

                <form id="registerForm" method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <div class="space-y-1.5">
                        <label for="name" class="text-sm font-semibold text-[#0b1c30]">Nama lengkap</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#565e74]" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><circle cx="12" cy="8" r="3.5" /><path d="M4.5 20c.8-3.2 3.2-5 7.5-5s6.7 1.8 7.5 5" /></svg>
                            </span>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Masukkan nama lengkap"
                                class="h-12 w-full rounded-xl border border-[#c4c5d7] bg-white px-4 pl-11 text-sm text-[#0b1c30] outline-none transition placeholder:text-[#747686] focus:border-[#1d4ed8] focus:ring-2 focus:ring-[#1d4ed8]/20">
                        </div>
                        <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="email" class="text-sm font-semibold text-[#0b1c30]">Email</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#565e74]" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m4 7 8 6 8-6" /></svg>
                            </span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Masukkan email pegawai"
                                class="h-12 w-full rounded-xl border border-[#c4c5d7] bg-white px-4 pl-11 text-sm text-[#0b1c30] outline-none transition placeholder:text-[#747686] focus:border-[#1d4ed8] focus:ring-2 focus:ring-[#1d4ed8]/20">
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="text-sm font-semibold text-[#0b1c30]">Password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#565e74]" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                            </span>
                            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Masukkan password akun"
                                class="h-12 w-full rounded-xl border border-[#c4c5d7] bg-white px-11 text-sm text-[#0b1c30] outline-none transition placeholder:text-[#747686] focus:border-[#1d4ed8] focus:ring-2 focus:ring-[#1d4ed8]/20">
                            <button type="button" id="togglePasswordButton" onclick="toggleRegisterPassword('password', this)" aria-label="Tampilkan password" class="absolute inset-y-0 right-3 flex items-center rounded-lg px-1 text-[#565e74] transition hover:text-[#0037b0] focus:outline-none focus:ring-2 focus:ring-[#1d4ed8]/30">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" /></svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="password_confirmation" class="text-sm font-semibold text-[#0b1c30]">Konfirmasi password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#565e74]" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /><path d="m9 15 2 2 4-4" /></svg>
                            </span>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi password akun"
                                class="h-12 w-full rounded-xl border border-[#c4c5d7] bg-white px-11 text-sm text-[#0b1c30] outline-none transition placeholder:text-[#747686] focus:border-[#1d4ed8] focus:ring-2 focus:ring-[#1d4ed8]/20">
                            <button type="button" id="togglePasswordConfirmationButton" onclick="toggleRegisterPassword('password_confirmation', this)" aria-label="Tampilkan konfirmasi password" class="absolute inset-y-0 right-3 flex items-center rounded-lg px-1 text-[#565e74] transition hover:text-[#0037b0] focus:outline-none focus:ring-2 focus:ring-[#1d4ed8]/30">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" /><circle cx="12" cy="12" r="2.5" /></svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
                    </div>

                    <div class="rounded-xl border border-[#c4c5d7] bg-[#eff4ff] px-3.5 py-3">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#dae2fd] text-[#0037b0]" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.5 2.9 8.1 7 10 4.1-1.9 7-5.5 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-4" /></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-[#0b1c30]">Persetujuan HRD diperlukan</h2>
                                <p class="mt-1 text-xs leading-5 text-[#434655]">Setelah dikirim, data akun akan ditinjau oleh HRD. Anda dapat masuk setelah akun disetujui.</p>
                            </div>
                        </div>
                    </div>

                    <button id="registerSubmitButton" type="submit" class="flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-[#1d4ed8] px-4 text-sm font-semibold text-white shadow-[0_8px_20px_-4px_rgba(29,78,216,0.35)] transition hover:bg-[#0037b0] focus:outline-none focus:ring-4 focus:ring-[#1d4ed8]/20 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70">
                        <svg id="registerLoadingIcon" viewBox="0 0 24 24" class="hidden h-5 w-5 animate-spin fill-none stroke-current" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9" class="opacity-25" /><path d="M21 12a9 9 0 0 1-9 9" /></svg>
                        <svg id="registerIcon" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M19 8v6M22 11h-6" /></svg>
                        <span id="registerSubmitLabel">Daftarkan akun pegawai</span>
                    </button>

                    <div class="text-center">
                        <p class="text-sm text-[#565e74]">Sudah memiliki akun?</p>
                        <a href="{{ route('login') }}" class="mt-1 inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-sm font-semibold text-[#0037b0] transition hover:bg-[#eff4ff] hover:underline focus:outline-none focus:ring-2 focus:ring-[#1d4ed8]/30">
                            Kembali ke Login
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-[#c4c5d7] bg-white p-4 shadow-[0_2px_8px_rgba(15,23,42,0.04)]" aria-labelledby="registration-security-title">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#dae2fd] text-[#0037b0]" aria-hidden="true">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.5 2.9 8.1 7 10 4.1-1.9 7-5.5 7-10V6l-7-3Z" /><path d="M12 8v4M12 16h.01" /></svg>
                    </div>
                    <div>
                        <h2 id="registration-security-title" class="text-sm font-semibold text-[#0b1c30]">Data akun diproses secara aman</h2>
                        <p class="mt-1 text-xs leading-5 text-[#434655]">Gunakan email aktif milik Anda. Persetujuan akun dilakukan oleh HRD sebelum akses presensi diberikan.</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-[#c4c5d7] pt-3 text-xs text-[#565e74]">
                    <span>PBEC · Pendaftaran Pegawai</span>
                    <span class="font-semibold text-[#0037b0]">Bantuan HRD</span>
                </div>
            </section>

            <p class="pb-1 text-center text-xs text-[#747686]">Sistem Presensi Klinis RS Mata PBEC</p>
        </div>
    </div>

    <script>
        const registerForm = document.getElementById('registerForm');
        const registerSubmitButton = document.getElementById('registerSubmitButton');
        const registerSubmitLabel = document.getElementById('registerSubmitLabel');
        const registerIcon = document.getElementById('registerIcon');
        const registerLoadingIcon = document.getElementById('registerLoadingIcon');

        registerForm.addEventListener('submit', () => {
            registerSubmitButton.disabled = true;
            registerSubmitButton.setAttribute('aria-busy', 'true');
            registerSubmitLabel.textContent = 'Mengirim pendaftaran...';
            registerIcon.classList.add('hidden');
            registerLoadingIcon.classList.remove('hidden');
        });

        function toggleRegisterPassword(id, button) {
            const input = document.getElementById(id);
            const isPassword = input.type === 'password';

            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');
        }
    </script>
</x-guest-layout>
