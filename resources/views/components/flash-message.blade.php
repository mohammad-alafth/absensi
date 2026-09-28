@props([])

{{--
    Pesan hasil aksi (sukses / gagal / validasi) untuk halaman Riwayat.
    Layout aplikasi tidak mencetak session flash secara global.
--}}
@php
    $hasErrors = isset($errors) && $errors instanceof \Illuminate\Support\ViewErrorBag && $errors->any();
@endphp

@if(session('success'))
<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
    ✅ {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">
    ⛔ {{ session('error') }}
</div>
@endif

@if($hasErrors)
<div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-800">
    <p class="mb-1">⚠️ Perubahan tidak tersimpan:</p>
    <ul class="list-disc pl-5 space-y-0.5 font-medium">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
