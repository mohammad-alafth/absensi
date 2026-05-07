import { createTimeline } from 'https://esm.sh/animejs';

const tl = createTimeline({
    defaults: {
        ease: 'inOutExpo',
        duration: 500,
        loop: 2,
        reversed: true,
        alternate: true,
    }
})
    .add('.square', { x: '15rem' })
    .add('.circle', { x: '15rem' })
    .add('.triangle', { x: '15rem' });