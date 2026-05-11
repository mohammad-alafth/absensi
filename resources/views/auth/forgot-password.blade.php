<x-guest-layout>

    <form
        method="POST"
        action="{{ route('password.email') }}"
        class="space-y-5">

        @csrf

        <!-- INFO -->
        <div class="text-sm text-slate-600 leading-relaxed">
            Forgot your password? No problem. Masukkan email akun Anda dan kami akan mengirimkan link reset password.
        </div>

        <!-- SESSION STATUS -->
        <x-auth-session-status
            class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-2xl text-sm"
            :status="session('status')" />

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
                    autofocus
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

        <!-- BUTTON -->
        <button
            type="submit"
            class="group relative w-full overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-500 px-4 py-3 font-semibold text-white shadow-lg transition-all duration-300 hover:scale-[1.01] hover:shadow-xl active:scale-[0.99]">

            <span class="relative z-10">
                Email Password Reset Link
            </span>

            <div
                class="absolute inset-0 translate-y-full bg-white/10 transition-transform duration-300 group-hover:translate-y-0">
            </div>

        </button>

        <!-- BACK -->
        <div class="text-center">

            <a
                href="{{ route('login') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition">

                ← Kembali ke Login

            </a>

        </div>

    </form>

</x-guest-layout>