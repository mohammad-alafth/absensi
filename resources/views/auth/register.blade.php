<x-guest-layout>

    <form
        method="POST"
        action="{{ route('register') }}"
        class="space-y-5">

        @csrf

        <!-- NAME -->
        <div>

            <label
                for="name"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Name

            </label>

            <div class="relative">

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Masukkan nama lengkap"
                    class="w-full rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none hover:border-indigo-300">

                <div
                    class="absolute inset-y-0 right-4 flex items-center text-slate-400">

                    👤

                </div>

            </div>

            <x-input-error
                :messages="$errors->get('name')"
                class="mt-2" />

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
                    value="{{ old('email') }}"
                    required
                    autocomplete="username"
                    placeholder="Masukkan email"
                    class="w-full rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none hover:border-indigo-300">

                <div
                    class="absolute inset-y-0 right-4 flex items-center text-slate-400">

                    ✉️

                </div>

            </div>

            <x-input-error
                :messages="$errors->get('email')"
                class="mt-2" />

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
                    autocomplete="new-password"
                    placeholder="Masukkan password"
                    class="w-full rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none hover:border-indigo-300">

                <button
                    type="button"
                    onclick="togglePassword('password')"
                    class="absolute inset-y-0 right-4 flex items-center text-slate-400 hover:text-indigo-500 transition">

                    👁️

                </button>

            </div>

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2" />

        </div>

        <!-- CONFIRM PASSWORD -->
        <div>

            <label
                for="password_confirmation"
                class="block text-sm font-semibold text-slate-700 mb-2">

                Confirm Password

            </label>

            <div class="relative">

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Konfirmasi password"
                    class="w-full rounded-2xl border border-slate-200 bg-white/80 backdrop-blur-sm px-4 py-3 text-sm shadow-sm transition-all duration-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 focus:outline-none hover:border-indigo-300">

                <button
                    type="button"
                    onclick="togglePassword('password_confirmation')"
                    class="absolute inset-y-0 right-4 flex items-center text-slate-400 hover:text-indigo-500 transition">

                    👁️

                </button>

            </div>

            <x-input-error
                :messages="$errors->get('password_confirmation')"
                class="mt-2" />

        </div>

        <!-- ACTION -->
        <div class="flex items-center justify-between pt-2">

            <a
                href="{{ route('login') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition">

                Already registered?

            </a>

            <button
                type="submit"
                class="group relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-6 py-3 font-semibold text-white shadow-lg transition-all duration-300 hover:scale-[1.01] hover:shadow-xl active:scale-[0.99]">

                <span class="relative z-10">
                    Register
                </span>

                <div
                    class="absolute inset-0 translate-y-full bg-white/10 transition-transform duration-300 group-hover:translate-y-0">
                </div>

            </button>

        </div>

    </form>

    <script>
        function togglePassword(id) {

            const input =
                document.getElementById(id);

            input.type =
                input.type === 'password' ?
                'text' :
                'password';
        }
    </script>

</x-guest-layout>