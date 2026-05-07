<div class="fixed bottom-0 left-0 right-0 md:hidden z-50 px-1 pb-3">

    <div
        class="relative bg-white/80 backdrop-blur-xl border border-white/40
        shadow-2xl rounded-xl p-1.5 overflow-hidden">

        <!-- ACTIVE SLIDER -->
        <div
            class="absolute top-1.5 bottom-1.5 w-[31%] rounded-xl
            transition-all duration-500 ease-out shadow-lg

            {{ request()->routeIs('dashboard')
                ? 'left-[2%] bg-gradient-to-r from-indigo-500 to-violet-500'
                : (request()->routeIs('history')
                    ? 'left-[34.5%] bg-gradient-to-r from-pink-500 to-rose-500'
                    : 'left-[67%] bg-gradient-to-r from-emerald-500 to-teal-500')
            }}">
        </div>

        <!-- MENU -->
        <div class="relative grid grid-cols-3 z-1">

            <!-- HOME -->
            <a href="{{ route('dashboard') }}"
                class="flex flex-col items-center justify-center py-1.5 rounded-xl
                transition-all duration-300 active:scale-95

                {{ request()->routeIs('dashboard')
                    ? 'text-white'
                    : 'text-gray-600'
                }}">

                <div class="text-xl">🏠</div>

                <p class="text-[10px] font-medium mt-0.5">
                    Home
                </p>

            </a>

            <!-- HISTORY -->
            <a href="{{ route('history') }}"
                class="flex flex-col items-center justify-center py-1.5 rounded-xl
                transition-all duration-300 active:scale-95

                {{ request()->routeIs('history')
                    ? 'text-white'
                    : 'text-gray-600'
                }}">

                <div class="text-xl">📋</div>

                <p class="text-[10px] font-medium mt-0.5">
                    History
                </p>

            </a>

            <!-- PROFILE -->
            <a href="{{ route('profile.edit') }}"
                class="flex flex-col items-center justify-center py-1.5 rounded-xl
                transition-all duration-300 active:scale-95

                {{ request()->routeIs('profile.edit')
                    ? 'text-white'
                    : 'text-gray-600'
                }}">

                <div class="text-xl">👤</div>

                <p class="text-[10px] font-medium mt-0.5">
                    Profile
                </p>

            </a>

        </div>

    </div>

</div>