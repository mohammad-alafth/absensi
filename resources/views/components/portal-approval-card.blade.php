@props([
    'queue',
    'href',
    'description' => 'Buka pusat approval untuk meninjau pengajuan sesuai tahap kewenangan Anda.',
])

{{--
|--------------------------------------------------------------------------
| KARTU AKSES PORTAL APPROVAL
|--------------------------------------------------------------------------
| Satu markup untuk semua role approver dan PJ Unit. Perbedaan kewenangan
| tetap ditentukan oleh URL dan label antrean yang dikirim dari dashboard.
--}}

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-[#c4c5d7]/60 bg-white p-4 shadow-sm sm:p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-[#565e74]">Portal Approval</p>
            <h2 class="font-app-heading mt-1 text-lg font-semibold leading-6 text-[#0b1c30]">Antrean {{ $queue }}</h2>
            <p class="mt-1 text-xs leading-5 text-[#565e74]">{{ $description }}</p>
        </div>
        <span class="material-symbols-outlined shrink-0 text-[25px] text-[#2151da]" aria-hidden="true">verified_user</span>
    </div>

    <a href="{{ $href }}"
       class="mt-4 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#1d4ed8] px-4 text-xs font-bold text-white shadow-[0_8px_20px_-4px_rgba(29,78,216,0.35)] transition hover:bg-[#0037b0] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1d4ed8]">
        Buka Portal Approval
        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_forward</span>
    </a>
</section>
