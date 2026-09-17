import './bootstrap';

import Alpine from 'alpinejs';
import './loading';

// SweetAlert2 versi lokal (tanpa CDN) - dipakai hampir di semua halaman,
// termasuk dialog peringatan idle/logout di layouts/app.blade.php.
import './vendor-sweetalert';

window.Alpine = Alpine;

Alpine.start();
