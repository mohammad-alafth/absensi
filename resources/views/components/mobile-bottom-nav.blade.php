@php
    $isHistoryPage = request()->routeIs('history*')
        || request()->routeIs('cuti.history')
        || request()->routeIs('izin.history')
        || request()->routeIs('lembur.history')
        || request()->routeIs('shift-change.history');

    $roleMenu = app(\App\Services\RoleMenuService::class);
    $items = collect($roleMenu->leaves(\App\Services\RoleMenuService::BOTTOM, auth()->user()))
        ->map(function (array $item) use ($isHistoryPage): array {
            $active = match ($item['route']) {
                'dashboard' => request()->routeIs('dashboard'),
                'face.scan' => request()->routeIs('face') || request()->routeIs('face.scan*'),
                'history' => $isHistoryPage,
                default => false,
            };

            return [
                'route' => $item['route'],
                'label' => match ($item['route']) {
                    'dashboard' => 'Beranda',
                    default => $item['label'],
                },
                'icon' => $item['icon'] ?? 'chevron_right',
                'active' => $active,
            ];
        })
        ->values()
        ->all();

    $items[] = [
        'route' => 'profile.edit',
        'label' => 'Profil',
        'icon' => 'person',
        'active' => request()->routeIs('profile.*'),
    ];

    $columnCount = max(1, count($items));
@endphp

<nav id="mobileBottomNav" aria-label="Navigasi utama" class="font-app-body fixed inset-x-0 bottom-0 z-50 border border-[#c4c5d7]/50 bg-white/95 shadow-[0_-2px_10px_rgba(15,23,42,0.08)] backdrop-blur-md md:hidden">
    <div class="mx-auto grid h-[68px] w-full items-end px-1 pt-1 pb-[calc(0.25rem+env(safe-area-inset-bottom))]" style="grid-template-columns: repeat({{ $columnCount }}, minmax(0, 1fr));">
        @foreach($items as $index => $item)
            @php $isCenterTab = $item['route'] === 'face.scan'; @endphp
            <a
                href="{{ route($item['route']) }}"
                class="relative flex min-w-0 -translate-y-1 flex-col items-center justify-end gap-0.5 rounded-xl py-1.5 transition active:scale-95 {{ $isCenterTab || $item['active'] ? '-mt-5' : '' }} {{ $item['active'] ? 'font-bold text-[#0037b0]' : 'text-[#565e74] hover:text-[#0037b0]' }}"
                @if($item['active']) aria-current="page" @endif
            >
                @if($item['active'])
                    <span class="mb-0.5 flex h-14 w-14 items-center justify-center rounded-full border-4 border-white bg-[#1d4ed8] text-white shadow-[0_6px_16px_-4px_rgba(29,78,216,0.55)]">
                        <span class="material-symbols-outlined text-[27px]">{{ $item['icon'] }}</span>
                    </span>
                @else
                    <span class="material-symbols-outlined text-[22px]" @if($item['active']) style="font-variation-settings: 'FILL' 1;" @endif>{{ $item['icon'] }}</span>
                @endif

                <span class="max-w-full truncate text-[10px] leading-3">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
