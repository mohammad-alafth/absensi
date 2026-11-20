<section class="space-y-5">
    <header>
        <h2 class="text-base font-bold text-[#0b1c30] flex items-center gap-2">
            <span class="material-symbols-outlined text-[#1D4ED8] text-[20px]">lock</span>
            Keamanan Akun
        </h2>

        <p class="mt-1 text-xs text-[#565e74]">
            Gunakan password yang kuat untuk menjaga keamanan akun.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Password Saat Ini" class="text-xs font-semibold text-[#565e74]" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full rounded-xl border-[#c4c5d7] text-sm focus:border-[#1D4ED8] focus:ring-[#1D4ED8]" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Password Baru" class="text-xs font-semibold text-[#565e74]" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full rounded-xl border-[#c4c5d7] text-sm focus:border-[#1D4ED8] focus:ring-[#1D4ED8]" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Konfirmasi Password Baru" class="text-xs font-semibold text-[#565e74]" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full rounded-xl border-[#c4c5d7] text-sm focus:border-[#1D4ED8] focus:ring-[#1D4ED8]" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button class="rounded-xl bg-[#1D4ED8] hover:bg-[#0037b0] text-xs">Perbarui Password</x-primary-button>

            @if (session('status') === 'password-updated')
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
