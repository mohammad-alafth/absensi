<x-guest-layout>
    @if(request()->query('session_expired') === '1')
    <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
        ⏰ Sesi Anda berakhir karena tidak ada aktivitas selama 30 menit.
        Silakan login kembali untuk melanjutkan.
    </div>
    @endif

    <div id="login-error" class="hidden mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm">
    </div>

    <form id="loginForm" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
            <div class="relative">
                <input id="email" type="email" name="email" required autofocus autocomplete="username"
                    placeholder="Masukkan email"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm transition-all focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none">
                <div class="absolute inset-y-0 right-4 flex items-center text-slate-400">✉️</div>
            </div>
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
            <div class="relative">
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    placeholder="Masukkan password"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm transition-all focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none">
                <button type="button" onclick="togglePassword()"
                    class="absolute inset-y-0 right-4 flex items-center text-slate-400 hover:text-indigo-500 transition">
                    👁️
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="text-sm text-slate-600">Remember me</span>
            </label>
        </div>

        <button type="submit"
            class="group relative w-full overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-4 py-3 font-semibold text-white shadow-lg transition-transform duration-300 hover:scale-[1.01] active:scale-[0.98]">
            <span class="relative z-10">Log In</span>
        </button>

        <div class="text-center space-y-2 mt-6">
            @if(Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="block text-sm text-indigo-600 hover:text-indigo-800 font-medium transition">
                Forgot Password?
            </a>
            @endif
            <p class="text-sm text-slate-600">
                Belum punya akun?
                <a href="{{ route('register') }}" class="text-indigo-600 font-bold hover:underline">Daftar akun</a>
            </p>
        </div>
    </form>

    <script>
        // Logika Login
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
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
            }
        });

        function showError(message) {
            const errorBox = document.getElementById('login-error');
            errorBox.innerHTML = message;
            errorBox.classList.remove('hidden');
        }

        function togglePassword() {
            const p = document.getElementById('password');
            p.type = p.type === 'password' ? 'text' : 'password';
        }
    </script>
</x-guest-layout>