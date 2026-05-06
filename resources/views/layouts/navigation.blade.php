<nav x-data="{ open: false }" class="bg-indigo-500 text-white pb-6 relative rounded-b-[35px]">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- FIX: HAPUS h-16 -->
        <div class="flex justify-between items-center py-3">

            <!-- LEFT -->
            <div class="flex items-center gap-4">

                <!-- Avatar -->
                <div class="w-12 h-12 rounded-full overflow-hidden bg-white">
                    <img src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}"
                        class="w-full h-full object-cover">
                </div>

                <!-- Name -->
                <div>
                    <p class="text-sm opacity-90">Hello,</p>
                    <p class="text-lg font-bold">
                        {{ Auth::user()->name }}
                    </p>
                </div>

            </div>

            <!-- RIGHT DESKTOP -->
            <div class="hidden sm:flex sm:items-center">

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-1 text-white text-sm font-medium focus:outline-none">

                            <span>Menu</span>

                            <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
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
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Logout
                            </x-dropdown-link>
                        </form>

                    </x-slot>
                </x-dropdown>

            </div>

            <!-- MOBILE BUTTON -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="p-2 rounded-md text-white hover:bg-indigo-400 focus:outline-none">

                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />

                        <path :class="{'hidden': ! open, 'inline-flex': open }"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>

                </button>
            </div>

        </div>

    </div>

    <!-- LENGKUNGAN BAWAH -->
    <!-- <div class="absolute bottom-0 left-0 w-full h-10 bg-indigo-500 rounded-b-[35px]"></div> -->

    <!-- MOBILE MENU -->
    <div :class="{'block': open, 'hidden': ! open}"
        class="hidden sm:hidden bg-indigo-500 px-4 pb-6 pt-2">

        <a href="{{ route('dashboard') }}" class="block text-white py-2">
            Dashboard
        </a>

        <a href="{{ route('profile.edit') }}" class="block text-white py-2">
            Profile
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button onclick="event.preventDefault(); this.closest('form').submit();"
                class="w-full text-left text-white py-2">
                Logout
            </button>
        </form>

    </div>

</nav>