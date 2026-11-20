@props([])

{{--
    Pesan hasil aksi (sukses / gagal / validasi) untuk halaman Riwayat.
    Layout aplikasi tidak mencetak session flash secara global.
--}}
@php
    $hasErrors = isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any();
@endphp

@if(session('success'))
<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold leading-relaxed text-emerald-700 shadow-sm flex items-start gap-2.5">
    <span class="text-base select-none">✅</span>
    <div class="flex-1">{{ session('success') }}</div>
</div>
@endif

@if(session('error'))
<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-relaxed text-red-700 shadow-sm flex items-start gap-2.5">
    <span class="text-base select-none">⛔</span>
    <div class="flex-1">{{ session('error') }}</div>
</div>
@endif

@if($hasErrors)
<div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold leading-relaxed text-amber-800 shadow-sm">
    <div class="flex items-center gap-2 mb-1.5">
        <span class="text-base select-none">⚠️</span>
        <p class="font-bold">Perubahan tidak tersimpan:</p>
    </div>
    <ul class="list-disc pl-6 space-y-1 font-medium text-amber-900 text-xs sm:text-sm">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
