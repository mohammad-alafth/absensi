<x-guest-layout>

    <!-- Error Alert -->
    <div
        id="login-error"
        class="hidden mb-4 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl">
    </div>

    <form id="loginForm">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label
                for="email"
                :value="__('Email')" />

            <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username" />

            <x-input-error
                :messages="$errors->get('email')"
                class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label
                for="password"
                :value="__('Password')" />

            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password" />

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">

            <label
                for="remember_me"
                class="inline-flex items-center">

                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">

                <span class="ms-2 text-sm text-gray-600">
                    {{ __('Remember me') }}
                </span>

            </label>

        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between mt-4">

            <div class="flex gap-3">

                <!-- Forgot Password -->
                @if(Route::has('password.request'))
                <a
                    class="underline text-sm text-gray-600 hover:text-gray-900"
                    href="{{ route('password.request') }}">

                    Forgot Password?

                </a>
                @endif

            </div>

            <!-- Login Button -->
            <x-primary-button type="submit">
                {{ __('Log in') }}
            </x-primary-button>

        </div>

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
    </script>

</x-guest-layout>