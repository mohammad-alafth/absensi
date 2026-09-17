/*
|--------------------------------------------------------------------------
| SWEETALERT2 — VERSI LOKAL (DIBUNDEL VITE)
|--------------------------------------------------------------------------
| Sebelumnya SweetAlert2 diambil dari cdn.jsdelivr.net. Pada jaringan
| internal / tanpa akses internet (kasus production) permintaan ke CDN
| menggantung sampai timeout sehingga halaman terasa "loading lama"
| (script CDN yang sinkron menunda DOMContentLoaded).
|
| Kini library ini ikut dibundel bersama app.js sehingga selalu tersedia
| dari server sendiri (tanpa internet), cepat, dan bisa di-cache browser.
|
| Stub `window.Swal` di layouts/app.blade.php tetap dipertahankan sebagai
| cadangan bila bundel JS gagal dimuat; di sini stub tersebut ditimpa
| dengan implementasi asli.
|--------------------------------------------------------------------------
*/

import Swal from 'sweetalert2';

window.Swal = Swal;

export default Swal;
