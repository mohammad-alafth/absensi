<section class="space-y-5">
    <header>
        <h2 class="text-base font-bold text-[#0b1c30] flex items-center gap-2">
            <span class="material-symbols-outlined text-[#1D4ED8] text-[20px]">manage_accounts</span>
            Informasi Profil
        </h2>

        <p class="mt-1 text-xs text-[#565e74]">
            Perbarui nama dan alamat email akun Anda.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nama" class="text-xs font-semibold text-[#565e74]" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-xl border-[#c4c5d7] text-sm focus:border-[#1D4ED8] focus:ring-[#1D4ED8]" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" class="text-xs font-semibold text-[#565e74]" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-xl border-[#c4c5d7] text-sm focus:border-[#1D4ED8] focus:ring-[#1D4ED8]" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-xs mt-2 text-[#565e74]">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-xs text-[#1D4ED8] hover:text-[#0037b0] rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1D4ED8]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-xs text-emerald-700">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button class="rounded-xl bg-[#1D4ED8] hover:bg-[#0037b0] text-xs">Simpan Perubahan</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-xs text-emerald-700"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
