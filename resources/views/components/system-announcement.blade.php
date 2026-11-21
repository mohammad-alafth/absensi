@php
    $announcement = \App\Models\SystemAnnouncement::current();
@endphp

<!-- 1. BANNER DI HALAMAN (Inline Alert) -->
<div id="system-announcement-banner" class="{{ $announcement ? '' : 'hidden' }} mb-4 overflow-hidden rounded-2xl border border-amber-300/80 bg-gradient-to-r from-amber-50 via-amber-50/90 to-orange-50 p-4 shadow-sm" style="{{ $announcement ? '' : 'display: none;' }}" role="alert" aria-live="assertive">
    <div class="flex items-start gap-3">
        <div class="relative mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">campaign</span>
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-amber-600"></span>
            </span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-200/70 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-900">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-700"></span>
                    <span id="announcement-banner-title">{{ $announcement->title ?? 'Pemberitahuan Sistem' }}</span>
                </span>
                <span id="announcement-banner-time" class="text-[11px] font-semibold text-amber-800/80">
                    {{ $announcement ? $announcement->created_at->diffForHumans() : '' }}
                </span>
            </div>
            <p id="announcement-banner-message" class="mt-1.5 text-sm font-bold leading-snug text-slate-900 break-words">
                {!! $announcement ? nl2br(e($announcement->message)) : '' !!}
            </p>
        </div>
    </div>
</div>

<!-- 2. POPUP NOTIFIKASI (gaya alert/error, tidak menutupi seluruh halaman) -->
<div id="system-announcement-modal"
     style="position: fixed; top: 16px; right: 16px; z-index: 999999; display: {{ $announcement ? 'block' : 'none' }}; width: min(420px, calc(100vw - 32px));"
     role="dialog"
     aria-modal="false"
     aria-live="assertive">

    <div style="position: relative; width: 100%; background-color: #ffffff; border-radius: 16px; padding: 16px; box-shadow: 0 18px 40px -12px rgba(15, 23, 42, 0.35); border: 1px solid #fcd34d; font-family: system-ui, -apple-system, sans-serif;">
        <!-- Header Modal -->
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <div style="position: relative; width: 40px; height: 40px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 12px; background-color: #f59e0b; color: #ffffff; font-size: 21px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                📢
            </div>
            <div style="min-width: 0; flex: 1;">
                <span style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #d97706;">
                    Pemberitahuan Resmi
                </span>
                <h3 id="modal-announcement-title" style="margin: 2px 0 0 0; font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1.3; word-break: break-word;">
                    {{ $announcement->title ?? 'Pemberitahuan Sistem' }}
                </h3>
            </div>
            <button type="button" onclick="closeAnnouncementModal()" aria-label="Tutup pemberitahuan" style="flex-shrink: 0; border: 0; background: transparent; color: #92400e; font-size: 20px; line-height: 1; cursor: pointer; padding: 2px 4px;">&times;</button>
        </div>

        <!-- Isi Pesan -->
        <div style="margin: 12px 0 0 52px; border-radius: 12px; border: 1px solid #fde68a; background-color: #fffbeb; padding: 12px;">
            <p id="modal-announcement-message" style="margin: 0; font-size: 13px; font-weight: 700; color: #1e293b; line-height: 1.5; word-break: break-word;">
                {!! $announcement ? nl2br(e($announcement->message)) : '' !!}
            </p>
            <p id="modal-announcement-time" style="margin: 8px 0 0 0; text-align: right; font-size: 10px; font-weight: 600; color: #92400e;">
                {{ $announcement ? $announcement->created_at->diffForHumans() : '' }}
            </p>
        </div>

        <!-- Tombol Tutup -->
        <div style="margin-top: 12px; margin-left: 52px;">
            <button type="button"
                    onclick="closeAnnouncementModal()"
                    style="width: 100%; border: none; border-radius: 10px; background-color: #1D4ED8; padding: 9px 14px; font-size: 12px; font-weight: 700; color: #ffffff; cursor: pointer; box-shadow: 0 4px 12px rgba(29, 78, 216, 0.25); transition: background-color 0.2s;">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>

<script>
function closeAnnouncementModal() {
    const modal = document.getElementById('system-announcement-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    let currentAnnouncementId = '{{ $announcement->id ?? '' }}';

    function openModalWithData(title, message, time, id) {
        const modal = document.getElementById('system-announcement-modal');
        const titleEl = document.getElementById('modal-announcement-title');
        const msgEl = document.getElementById('modal-announcement-message');
        const timeEl = document.getElementById('modal-announcement-time');

        if (titleEl) titleEl.textContent = title || 'Pemberitahuan Sistem';
        if (msgEl) msgEl.innerHTML = String(message).replace(/\n/g, '<br>');
        if (timeEl) timeEl.textContent = time || '';

        if (modal) {
            modal.style.display = 'block';
        }
    }

    // Pengecekan real-time setiap 3 detik
    setInterval(function () {
        fetch("{{ url('/api/announcement/active') }}")
            .then(res => res.json())
            .then(data => {
                const banner = document.getElementById('system-announcement-banner');
                const bannerTitle = document.getElementById('announcement-banner-title');
                const bannerMsg = document.getElementById('announcement-banner-message');
                const bannerTime = document.getElementById('announcement-banner-time');

                if (data.active) {
                    if (banner) {
                        banner.style.display = 'block';
                        banner.classList.remove('hidden');
                    }
                    if (bannerTitle) bannerTitle.textContent = data.title;
                    if (bannerMsg) bannerMsg.innerHTML = String(data.message).replace(/\n/g, '<br>');
                    if (bannerTime) bannerTime.textContent = data.time;

                    // Jika ada pengumuman aktif baru atau modal belum dibuka
                    if (String(data.id) !== String(currentAnnouncementId)) {
                        currentAnnouncementId = String(data.id);
                        openModalWithData(data.title, data.message, data.time, data.id);
                    }
                } else {
                    if (banner) {
                        banner.style.display = 'none';
                        banner.classList.add('hidden');
                    }
                    closeAnnouncementModal();
                    currentAnnouncementId = '';
                }
            })
            .catch(() => {});
    }, 3000);
});
</script>
