import { createTimeline } from 'animejs';

/**
 * Animasi loading screen (dibundle Vite, tanpa CDN eksternal).
 * Aman dipanggil di halaman mana pun: jika #loading-screen tidak ada,
 * seluruh animasi dilewati.
 */

const loader = document.getElementById('loading-screen');

if (loader) {
    const tl = createTimeline({
        defaults: {
            ease: 'inOutExpo',
            duration: 2000,
            loop: true,
            alternate: true,
        },
    });

    tl.add('.triangle', {
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
