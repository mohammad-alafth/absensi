@props([
    'title',
    'subtitle' => 'RS Mata PBEC · Eye Center',
    'backRoute' => 'dashboard',
])

<header class="sticky top-0 z-40 bg-white/95 border-b border-[#c4c5d7]/50 shadow-sm backdrop-blur-md">
    <div class="flex items-center justify-between gap-2 px-4 py-2.5">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route($backRoute) }}" aria-label="Kembali"
                class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center text-[#0b1c30] hover:bg-[#eff4ff] active:scale-95 transition">
                <span class="material-symbols-outlined text-[22px]">arrow_back</span>
            </a>
            <div class="min-w-0">
                <h1 class="font-['Plus_Jakarta_Sans'] text-[16px] leading-5 font-bold tracking-tight truncate">{{ $title }}</h1>
                <p class="text-[11px] leading-4 text-[#565e74] truncate">{{ $subtitle }}</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <span class="text-[10px] font-semibold text-[#2151da] bg-[#e5eeff] border border-[#dae2fd] px-2 py-1 rounded-full">{{ now()->format('Y') }}</span>
            <span aria-hidden="true" class="w-9 h-9 rounded-xl flex items-center justify-center text-[#565e74]">
                <span class="material-symbols-outlined text-[21px]">notifications</span>
            </span>
        </div>
    </div>
</header>
