<nav x-data="{ open: false }"
    class="bg-gradient-to-r from-indigo-500 via-purple-500 to-fuchsia-500
    text-white relative rounded-b-[35px] shadow-xl overflow-visible z-50">

    <!-- Glow Background -->
    <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-32 h-32 bg-cyan-300/10 rounded-full blur-2xl"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- HEADER -->
        <div class="flex justify-between items-center py-4">

            <!-- LEFT -->
            <div class="flex items-center gap-4">

                <!-- Avatar -->
                <div class="relative">

                    <div
                        class="absolute inset-0 rounded-full bg-white/30 blur-md">
                    </div>

                    <div
                        class="relative w-12 h-12 rounded-full overflow-hidden border-2 border-white/40 shadow-lg">

                        <img
                            src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}"
                            class="w-full h-full object-cover">

                    </div>

                </div>

                <!-- Name -->
                <div>

                    <p class="text-xs text-white/80 tracking-wide">
                        Welcome Back 👋
                    </p>

                    <p class="text-lg font-bold leading-tight">
                        {{ Auth::user()->name }}
                    </p>

                </div>

            </div>

            <!-- DESKTOP MENU -->
            <div class="hidden sm:flex sm:items-center">

                <x-dropdown align="right" width="48">

                    <x-slot name="trigger">

                        <button
                            class="flex items-center gap-2 px-4 py-2 rounded-2xl
                            bg-white/10 backdrop-blur-md border border-white/20
                            hover:bg-white/20 transition-all duration-300">

                            <span class="text-sm font-semibold">
                                Menu
                            </span>

                            <svg
                                class="fill-current h-4 w-4"
                                viewBox="0 0 20 20">

                                <path
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />

                            </svg>

                        </button>

                    </x-slot>

                    <x-slot name="content">

                        <x-dropdown-link :href="route('dashboard')">
                            Dashboard
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('profile.edit')">
                            Profile
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">

                                Logout

                            </x-dropdown-link>

                        </form>

                    </x-slot>

                </x-dropdown>

            </div>

            <!-- MOBILE BUTTON -->
            <div class="flex items-center sm:hidden">

                <button
                    @click="open = !open"
                    class="p-3 rounded-2xl bg-white/10 backdrop-blur-md
                    border border-white/20 hover:bg-white/20
                    transition-all duration-300 active:scale-95">

                    <svg
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24">

                        <path
                            :class="{'hidden': open, 'inline-flex': ! open }"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />

                        <path
                            :class="{'hidden': ! open, 'inline-flex': open }"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />

                    </svg>

                </button>

            </div>

        </div>

    </div>

    <!-- MOBILE MENU -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="sm:hidden px-4 pb-5">

        <div
            class="bg-white/10 backdrop-blur-xl border border-white/20
            rounded-3xl p-3 space-y-2 shadow-2xl">

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-2xl
                hover:bg-white/10 transition-all duration-300">

                <span class="text-xl">🏠</span>

                <span class="font-medium">
                    Dashboard
                </span>

            </a>

            <!-- Profile -->
            <a href="{{ route('profile.edit') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-2xl
                hover:bg-white/10 transition-all duration-300">

                <span class="text-xl">👤</span>

                <span class="font-medium">
                    Profile
                </span>

            </a>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    onclick="event.preventDefault(); this.closest('form').submit();"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl
                    hover:bg-red-500/20 transition-all duration-300 text-left">

                    <span class="text-xl">🚪</span>

                    <span class="font-medium">
                        Logout
                    </span>

                </button>

            </form>

        </div>

    </div>

</nav>