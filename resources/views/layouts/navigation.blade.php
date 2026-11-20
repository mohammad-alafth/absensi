@php
    $roleMenu = app(\App\Services\RoleMenuService::class);
    $menuUser = auth()->user();
    $desktopMenu = $roleMenu->menu(\App\Services\RoleMenuService::DESKTOP, $menuUser);
    $mobileMenu = $roleMenu->menu(\App\Services\RoleMenuService::MOBILE, $menuUser);
@endphp

<nav x-data="{ open: false }"
    class="bg-gradient-to-r from-[#0037b0] via-[#1d4ed8] to-[#0284c7]
    text-white relative rounded-b-[28px] sm:rounded-b-[35px] shadow-xl overflow-visible z-50 border-b border-blue-900/20">

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- HEADER -->
        <div class="flex justify-between items-center py-3 sm:py-4">

            <!-- LEFT -->
            <div class="flex items-center gap-3 sm:gap-4">

                <!-- Avatar with Clinical Presence Ring -->
                <div class="relative">

                    <div
                        class="relative w-11 h-11 sm:w-12 sm:h-12 rounded-full overflow-hidden border-2 border-white/60 shadow-md bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center select-none">

                        @php
                            $avatarInitials = collect(explode(' ', trim(Auth::user()->name)))
                                ->filter()
                                ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                ->take(2)
                                ->implode('');
                        @endphp

                        {{-- Inisial dirender lokal (tanpa request ke eksternal) --}}
                        <span class="text-white font-bold text-sm sm:text-base tracking-wide">
                            {{ $avatarInitials !== '' ? $avatarInitials : 'U' }}
                        </span>

                    </div>

                    <!-- Online Presence Pulse -->
                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-white rounded-full"></span>

                </div>

                <!-- Staff Info & Hospital Identity -->
                <div>

                    <div class="flex items-center gap-1.5 flex-wrap">

                        <p class="text-base sm:text-lg font-bold leading-tight tracking-tight">
                            {{ Auth::user()->name }}
                        </p>

                        @if(Auth::user()->role)
                        <span class="bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full backdrop-blur-sm border border-white/20">
                            {{ Auth::user()->role_label ?? Auth::user()->role }}
                        </span>
                        @endif

                    </div>

                    <p class="text-xs text-white/80 tracking-wide font-medium mt-0.5">
                        RS Mata PBEC • Eye Center
                    </p>

                </div>

            </div>

            <!-- DESKTOP MENU -->
            <div class="hidden sm:flex sm:items-center">

                <x-dropdown align="right" width="48">

                    <x-slot name="trigger">

                        <button
                            class="flex items-center gap-2 px-4 py-2 rounded-xl
                            bg-white/15 backdrop-blur-md border border-white/25
                            hover:bg-white/25 transition-all duration-300 shadow-sm active:scale-95">

                            <span class="text-sm font-semibold tracking-wide">
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

                        @foreach($desktopMenu as $node)
                            @if(!empty($node['children']))
                                <div class="px-4 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400">
                                    {{ $node['label'] }}
                                </div>
                                @foreach($node['children'] as $child)
                                    <x-dropdown-link :href="route($child['route'])">
                                        {{ $child['label'] }}
                                    </x-dropdown-link>
                                @endforeach
                            @else
                                <x-dropdown-link :href="route($node['route'])">
                                    {{ $node['label'] }}
                                </x-dropdown-link>
                            @endif
                        @endforeach

                        <div class="my-1 border-t border-gray-100"></div>

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
                    class="p-2.5 rounded-xl bg-white/15 backdrop-blur-md
                    border border-white/25 hover:bg-white/25
                    transition-all duration-300 active:scale-95 shadow-sm">

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
            class="bg-[#0b1c30]/95 backdrop-blur-xl border border-white/15
            rounded-2xl p-3 space-y-2 shadow-2xl">

            @foreach($mobileMenu as $node)
                @if(!empty($node['children']))
                    <div class="px-4 pb-1 pt-2 text-[10px] font-bold uppercase tracking-[0.12em] text-white/45">
                        {{ $node['label'] }}
                    </div>
                    @foreach($node['children'] as $child)
                        <a href="{{ route($child['route']) }}"
                            class="flex items-center gap-3 rounded-xl px-4 py-3 hover:bg-white/10 transition-all duration-300">
                            <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ $child['icon'] ?? 'chevron_right' }}</span>
                            <span class="font-medium text-white">{{ $child['label'] }}</span>
                        </a>
                    @endforeach
                @else
                    <a href="{{ route($node['route']) }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 hover:bg-white/10 transition-all duration-300">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ $node['icon'] ?? 'chevron_right' }}</span>
                        <span class="font-medium text-white">{{ $node['label'] }}</span>
                    </a>
                @endif
            @endforeach

            <div class="my-1 border-t border-white/10"></div>

            <!-- Profile (account utility, outside feature-menu permissions) -->
            <a href="{{ route('profile.edit') }}"
                class="flex items-center gap-3 px-4 py-3 rounded-xl
                hover:bg-white/10 transition-all duration-300">

                <span class="material-symbols-outlined text-xl" aria-hidden="true">person</span>

                <span class="font-medium text-white">
                    Profile
                </span>

            </a>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    onclick="event.preventDefault(); this.closest('form').submit();"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl
                    hover:bg-red-500/20 transition-all duration-300 text-left text-white">

                    <span class="text-xl">🚪</span>

                    <span class="font-medium">
                        Logout
                    </span>

                </button>

            </form>

        </div>

    </div>

</nav>
