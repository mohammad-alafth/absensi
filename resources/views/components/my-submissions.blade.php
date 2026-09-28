@props(['type' => 'permission', 'submissions' => null])

{{--
    Daftar "Pengajuan Saya" untuk user yang sedang login.

    Dipasang di halaman pengajuan (izin / cuti / lembur) supaya pengaju bisa:
      - melihat status verifikasi pengajuannya tanpa membuka menu Riwayat,
      - membaca alasan penolakan dari approver,
      - merevisi lalu mengirim ulang lewat tombol `Edit Pengajuan`
        (komponen x-submission-edit-form hanya tampil bila masih bisa diubah).

    Props:
      - type        : permission | leave | overtime
      - submissions : koleksi pengajuan milik user (terbaru lebih dulu)
--}}
@php
    $items = collect($submissions ?? []);

    $historyRoute = match ($type) {
        'leave' => 'cuti.history',
        'overtime' => 'lembur.history',
        default => 'izin.history',
    };

    $judul = match ($type) {
        'leave' => 'Pengajuan Cuti Saya',
        'overtime' => 'Pengajuan Lembur Saya',
        default => 'Pengajuan Izin Saya',
    };

    $kosong = match ($type) {
        'leave' => 'Belum ada pengajuan cuti.',
        'overtime' => 'Belum ada pengajuan lembur.',
        default => 'Belum ada pengajuan izin.',
    };

    $revisi = $items->filter(
        fn($item) => \App\Support\SubmissionStatus::isEditable($item)
    )->count();

    $tanggal = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d M Y') : '-';
    $jam = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : '';
@endphp

<div class="mt-4 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="border-b border-slate-200 bg-gray-50/50 px-5 py-4 flex items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                🗂️ {{ $judul }}
                @if($revisi > 0)
                <span class="text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-full">
                    {{ $revisi }} dapat direvisi
                </span>
                @endif
            </h2>
            <p class="text-[11px] text-slate-500 mt-0.5">
                Pengajuan yang ditolak atau masih menunggu verifikasi bisa Anda perbaiki sendiri lewat tombol Edit Pengajuan.
            </p>
        </div>
        <a href="{{ route($historyRoute) }}" class="shrink-0 text-[11px] font-bold text-[#1E40AF] hover:underline">
            Lihat Semua →
        </a>
    </div>

    <div class="divide-y divide-slate-100">
        @forelse($items as $submission)
        <div class="px-5 py-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-800">
                        @if($type === 'leave')
                        Cuti {{ $submission->leave_type }}
                        @elseif($type === 'overtime')
                        Lembur {{ $submission->day_type === 'hari_libur' ? 'Hari Libur' : 'Hari Kerja' }}
                        @else
                        {{ ucfirst((string) $submission->jenis) }}
                        @endif
                    </p>

                    <p class="text-[11px] text-slate-500 mt-0.5">
                        @if($type === 'leave')
                        {{ $tanggal($submission->start_date) }} – {{ $tanggal($submission->end_date) }} &middot; {{ $submission->total_days }} hari
                        @elseif($type === 'overtime')
                        {{ $tanggal($submission->overtime_date) }} &middot; {{ $jam($submission->start_time) }}–{{ $jam($submission->end_time) }} &middot; {{ $submission->total_hours }} jam
                        @else
                        {{ $tanggal($submission->tanggal) }}@if($submission->tanggal_selesai && (string) $submission->tanggal_selesai !== (string) $submission->tanggal) – {{ $tanggal($submission->tanggal_selesai) }}@elseif($submission->jam_mulai) &middot; {{ $jam($submission->jam_mulai) }}–{{ $jam($submission->jam_selesai) }}@endif
                        @endif
                    </p>
                </div>

                <span class="shrink-0 text-[10px] font-bold border px-2 py-1 rounded-full {{ \App\Support\SubmissionStatus::statusTone($submission) }}">
                    {{ \App\Support\SubmissionStatus::statusLabel($submission) }}
                </span>
            </div>

            <p class="text-[11px] text-slate-600 mt-2 bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 italic line-clamp-2">
                {{ $type === 'permission' ? $submission->alasan : $submission->reason }}
            </p>

            <x-rejection-banner :submission="$submission" />

            <x-submission-edit-form :type="$type" :submission="$submission" />
        </div>
        @empty
        <div class="px-5 py-6 text-center">
            <p class="text-xs text-slate-400 font-medium">
                {{ $kosong }} Pengajuan baru akan muncul di sini beserta status verifikasinya.
            </p>
        </div>
        @endforelse
    </div>
</div>
