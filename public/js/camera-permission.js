(function (global) {
    'use strict';

    // The browser remains the source of truth. A granted permission is reused
    // by later getUserMedia calls for this origin without another prompt.
    global.openAbsensiCamera = async function (constraints) {
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            throw new Error('camera-unavailable');
        }

        if (navigator.permissions && typeof navigator.permissions.query === 'function') {
            try {
                const permission = await navigator.permissions.query({ name: 'camera' });
                if (permission.state === 'denied') {
                    const error = new Error('camera-denied');
                    error.name = 'NotAllowedError';
                    throw error;
                }
            } catch (error) {
                // Safari and older browsers may not support camera queries.
                if (error && error.message === 'camera-denied') throw error;
            }
        }

        return navigator.mediaDevices.getUserMedia(constraints || {
            video: { facingMode: 'user' },
            audio: false,
        });
    };
})(window);
