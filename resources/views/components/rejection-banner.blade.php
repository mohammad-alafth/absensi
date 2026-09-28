@props(['submission' => null])

{{--
    Ringkasan alasan penolakan untuk kartu di menu Riwayat.
    Tampil bila pengajuan ditolak pada salah satu tahap approval.
--}}
@php
    $rejections = $submission ? \App\Support\SubmissionStatus::rejections($submission) : [];
    $isRejected = $submission && $submission->status === 'rejected';
    $editable = $submission ? \App\Support\SubmissionStatus::isEditable($submission) : false;
@endphp

@if($submission && ($rejections !== [] || $isRejected))
<div class="mt-2 rounded-xl border border-red-100 bg-red-50 p-2.5 text-[10px] leading-snug text-red-700">
    @forelse($rejections as $rejection)
    <p class="font-bold flex items-start gap-1">
        <span>❌</span>
        <span>Ditolak {{ $rejection['label'] }}@if($rejection['approver']) &middot; {{ $rejection['approver'] }}@endif</span>
    </p>
    <p class="italic text-gray-700 mt-0.5">"{{ $rejection['note'] ?: 'Ditolak tanpa catatan alasan.' }}"</p>
    @empty
    <p class="font-bold flex items-start gap-1">
        <span>❌</span>
        <span>Pengajuan ditolak</span>
    </p>
    <p class="italic text-gray-700 mt-0.5">Alasan penolakan belum dicatat oleh approver.</p>
    @endforelse

    @if($editable)
    <p class="mt-1 font-semibold text-red-600">✏️ Perbaiki data lalu kirim ulang melalui tombol Edit Pengajuan.</p>
    @endif
</div>
@endif
