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

<!-- 2. POPUP MODAL DIALOG (Melompat di Tengah Layar dengan Inline Styles Anti-Gagal) -->
<div id="system-announcement-modal"
     style="position: fixed; inset: 0; z-index: 999999; display: {{ $announcement ? 'flex' : 'none' }}; align-items: center; justify-content: center; padding: 16px; background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"
     role="dialog"
     aria-modal="true">

    <div style="position: relative; width: 100%; max-width: 440px; background-color: #ffffff; border-radius: 24px; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); border: 1px solid #fde68a; font-family: system-ui, -apple-system, sans-serif;">
        <!-- Header Modal -->
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
            <div style="position: relative; width: 48px; height: 48px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 16px; background-color: #f59e0b; color: #ffffff; font-size: 26px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);">
                📢
            </div>
            <div>
                <span style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #d97706;">
                    Pemberitahuan Resmi
                </span>
                <h3 id="modal-announcement-title" style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1.2;">
                    {{ $announcement->title ?? 'Pemberitahuan Sistem' }}
                </h3>
            </div>
        </div>

        <!-- Isi Pesan -->
        <div style="margin: 16px 0; border-radius: 16px; border: 1px solid #fde68a; background-color: #fffbeb; padding: 16px;">
            <p id="modal-announcement-message" style="margin: 0; font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.5; word-break: break-word;">
                {!! $announcement ? nl2br(e($announcement->message)) : '' !!}
            </p>
            <p id="modal-announcement-time" style="margin: 8px 0 0 0; text-align: right; font-size: 11px; font-weight: 600; color: #92400e;">
                {{ $announcement ? $announcement->created_at->diffForHumans() : '' }}
            </p>
        </div>

        <!-- Tombol Tutup -->
        <div style="margin-top: 18px;">
            <button type="button"
                    onclick="closeAnnouncementModal()"
                    style="width: 100%; border: none; border-radius: 14px; background-color: #1D4ED8; padding: 14px 20px; font-size: 14px; font-weight: 700; color: #ffffff; cursor: pointer; box-shadow: 0 4px 14px rgba(29, 78, 216, 0.35); transition: background-color 0.2s;">
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
            modal.style.display = 'flex';
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
