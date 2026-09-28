@props(['submission' => null, 'title' => 'Catatan & Alasan Penolakan'])

{{--
    Rincian catatan/alasan penolakan SEMUA tahap approval (PJ, HRD, Head,
    YANMED, Kabag, Manager, Direktur) untuk sebuah pengajuan.
--}}
@php
    $notes = $submission ? \App\Support\SubmissionStatus::notes($submission) : [];
@endphp

@if($notes !== [])
<div class="mt-4 text-xs">
    <p class="font-bold text-gray-700 mb-2 flex items-center gap-1">📑 {{ $title }}</p>

    <div class="grid grid-cols-1 gap-3">
        @foreach($notes as $note)
        <div class="p-3 rounded-xl border {{ $note['rejected'] ? 'bg-red-50 border-red-100 text-red-700' : ($note['revision'] ? 'bg-amber-50 border-amber-100 text-amber-700' : 'bg-slate-50 border-slate-200 text-slate-700') }}">
            <p class="font-bold mb-1">
                💬 @if($note['rejected']) Alasan Penolakan @elseif($note['revision']) Catatan Penolakan Sebelumnya @else Catatan @endif
                &mdash; {{ $note['label'] }}
            </p>
            <p class="italic text-gray-700">"{{ $note['note'] ?: 'Tidak ada catatan tertulis.' }}"</p>
            <p class="text-[10px] text-gray-400 mt-1.5 font-semibold">
                Oleh: {{ $note['approver'] ?: '-' }}
                @if($note['at']) &middot; {{ \Carbon\Carbon::parse($note['at'])->translatedFormat('d M Y H:i') }} @endif
            </p>
        </div>
        @endforeach
    </div>
</div>
@endif
