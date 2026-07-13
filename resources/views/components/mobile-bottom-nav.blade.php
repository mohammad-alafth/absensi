<div id="mobileBottomNav" class="fixed bottom-0 left-0 right-0 md:hidden z-50 px-2 pb-3 transition-all duration-300">

    @php
    $isHrd = auth()->user()->role === 'hrd';
    $menuCount = $isHrd ? 4 : 3;
    @endphp
    <div class="relative bg-white/80 backdrop-blur-xl border border-white/40 shadow-2xl rounded-2xl p-1 overflow-hidden">

        <div
            class="absolute top-1 bottom-1 rounded-xl transition-all duration-500 ease-out shadow-lg
    {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-indigo-500 to-violet-500' : '' }}
    {{ request()->routeIs('history') ? 'bg-gradient-to-r from-pink-500 to-rose-500' : '' }}
    {{ request()->routeIs('profile.edit') ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : '' }}
    {{ request()->routeIs('hrd.users.approval') ? 'bg-gradient-to-r from-amber-500 to-orange-500' : '' }}"
            style="
        width: calc(100% / {{ $menuCount }});
        left:
        @if(request()->routeIs('dashboard'))
            0%;
        @elseif(request()->routeIs('history'))
            calc(100% / {{ $menuCount }});
        @elseif(request()->routeIs('profile.edit'))
            calc(200% / {{ $menuCount }});
        @elseif(request()->routeIs('hrd.users.approval'))
            calc(300% / {{ $menuCount }});
        @endif
    ">
        </div>

        <!-- {{-- Tambahkan conditional pada grid-cols agar kolom menyesuaikan jumlah menu --}} -->
        <div class="relative grid {{ auth()->user()->role === 'hrd' ? 'grid-cols-4' : 'grid-cols-3' }} z-10">

            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center py-2 rounded-xl transition-all active:scale-95 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-600' }}">
                <div class="text-lg">🏠</div>
                <p class="text-[9px] font-bold mt-0.5">Home</p>
            </a>

            <a href="{{ route('history') }}" class="flex flex-col items-center justify-center py-2 rounded-xl transition-all active:scale-95 {{ request()->routeIs('history') ? 'text-white' : 'text-gray-600' }}">
                <div class="text-lg">📋</div>
                <p class="text-[9px] font-bold mt-0.5">History</p>
            </a>

            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center py-2 rounded-xl transition-all active:scale-95 {{ request()->routeIs('profile.edit') ? 'text-white' : 'text-gray-600' }}">
                <div class="text-lg">👤</div>
                <p class="text-[9px] font-bold mt-0.5">Profile</p>
            </a>

            @if(auth()->user()->role === 'hrd')
            <a href="{{ route('hrd.users.approval') }}" class="flex flex-col items-center justify-center py-2 rounded-xl transition-all active:scale-95 {{ request()->routeIs('hrd.users.approval') ? 'text-white' : 'text-gray-600' }}">
                <div class="text-lg">✅</div>
                <p class="text-[9px] font-bold mt-0.5">Approval</p>
            </a>
            @endif

        </div>
    </div>
</div>