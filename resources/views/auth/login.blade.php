<x-guest-layout>

    <!-- Error Alert -->
    <div
        id="login-error"
        class="hidden mb-4 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl">
    </div>

    <form id="loginForm" class="space-y-5">

        @csrf

        <!-- Error Alert -->
        <div
            id="login-error"
            class="hidden bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-2xl text-sm">
        </div>

        <!-- EMAIL -->
        <div>

            <label
                for="email"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Email

            </label>

            <div class="relative">

                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Masukkan email"
                    class="w-full rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none hover:border-indigo-300">

                <div
                    class="absolute inset-y-0 right-4 flex items-center text-slate-400">

                    ✉️

                </div>

            </div>

        </div>

        <!-- PASSWORD -->
        <div>

            <label
                for="password"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Password

            </label>

            <div class="relative">

                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan password"
                    class="w-full rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none hover:border-indigo-300">

                <button
                    type="button"
                    onclick="togglePassword()"
                    class="absolute inset-y-0 right-4 flex items-center text-slate-400 hover:text-indigo-500 transition">

                    👁️

                </button>

            </div>

        </div>

        <!-- REMEMBER -->
        <div class="flex items-center justify-between">

            <label
                for="remember_me"
                class="inline-flex items-center gap-2 cursor-pointer">

                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500">

                <span class="text-sm text-slate-600">
                    Remember me
                </span>

            </label>

            @if(Route::has('password.request'))

            <a
                href="{{ route('password.request') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition">

                Forgot Password?

            </a>

            @endif

        </div>

        <!-- BUTTON -->
        <button
            type="submit"
            class="group relative w-full overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-4 py-3 font-semibold text-white shadow-lg transition-all duration-300 hover:scale-[1.01] hover:shadow-xl active:scale-[0.99]">

            <span class="relative z-10">
                Log In
            </span>

            <div
                class="absolute inset-0 translate-y-full bg-white/10 transition-transform duration-300 group-hover:translate-y-0">
            </div>

        </button>

    </form>


    <script>
        document
            .getElementById('loginForm')
            .addEventListener('submit', async function(e) {

                e.preventDefault();

                const email =
                    document.getElementById(
                        'email'
                    ).value;

                const password =
                    document.getElementById(
                        'password'
                    ).value;

                const remember =
                    document.getElementById(
                        'remember_me'
                    ).checked;


                try {

                    const response = await fetch(
                        '/login', {

                            method: 'POST',

                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    .content
                            },

                            body: JSON.stringify({
                                email,
                                password,
                                remember
                            })

                        });

                    const data =
                        await response.json();


                    if (response.ok) {

                        /*
                        |-----------------------------------
                        | Simpan token
                        |-----------------------------------
                        */
                        if (data.token) {

                            localStorage
                                .setItem(
                                    'token',
                                    data.token
                                );
                        }

                        /*
                        |-----------------------------------
                        | Redirect dashboard
                        |-----------------------------------
                        */
                        window.location.href =
                            '/dashboard';

                    } else {

                        showError(
                            data.message ||
                            'Email atau password salah'
                        );

                    }

                } catch (error) {

                    showError(
                        'Terjadi kesalahan sistem'
                    );

                }

            });


        function showError(message) {

            const errorBox =
                document.getElementById(
                    'login-error'
                );

            errorBox.innerHTML =
                message;

            errorBox.classList.remove(
                'hidden'
            );

        }

        function togglePassword() {

            const passwordInput =
                document.getElementById(
                    'password'
                );

            passwordInput.type =
                passwordInput.type ===
                'password' ?
                'text' :
                'password';
        }
    </script>

</x-guest-layout>