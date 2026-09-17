import { createTimeline } from 'animejs';

/**
 * Animasi loading screen (dibundle Vite, tanpa CDN eksternal).
 * Aman dipanggil di halaman mana pun: jika #loading-screen tidak ada,
 * seluruh animasi dilewati.
 *
 * API global `window.absensiLoading`:
 *   - stop()  : hentikan animasi (dipanggil layout saat loader disembunyikan
 *               agar animejs tidak terus menghitung frame di background).
 *   - start() : jalankan kembali animasi bila diperlukan.
 *
 * Animasi juga otomatis DIJEDA saat tab tidak aktif (visibilitychange) supaya
 * tidak membebani CPU saat pengguna berpindah tab.
 */

const loader = document.getElementById('loading-screen');

let timeline = null;

if (loader) {
    timeline = createTimeline({
        defaults: {
            ease: 'inOutExpo',
            duration: 2000,
            loop: true,
            alternate: true,
        },
    });

    timeline.add('.triangle', {
        x: '13rem',
        rotate: '2turn',
        scale: [0.8, 1.2],
    }).add('.square', {
        x: '13rem',
        rotate: '-2turn',
        borderRadius: ['1rem', '3rem'],
        scale: [1, 1.3],
    }, '-=1500').add('.circle', {
        x: '13rem',
        scale: [1, 1.5],
    }, '-=1500');
}

// ---------------------------------------------------------------------------
// Hemat CPU: jeda animasi saat tab tidak terlihat, lanjutkan saat kembali
// terlihat (hanya bila loader masih tampil).
// ---------------------------------------------------------------------------
document.addEventListener('visibilitychange', function () {
    if (!timeline || !loader || loader.style.display === 'none') return;

    if (document.hidden) {
        timeline.pause();
    } else {
        timeline.play();
    }
});

// API untuk layout (hide loader -> hentikan animasi)
window.absensiLoading = {
    stop: function () {
        if (timeline) timeline.pause();
    },
    start: function () {
        if (timeline && loader && loader.style.display !== 'none') timeline.play();
    },
};
