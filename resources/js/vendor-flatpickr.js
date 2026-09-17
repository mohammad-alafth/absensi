/*
|--------------------------------------------------------------------------
| FLATPICKR — VERSI LOKAL (DIBUNDEL VITE)
|--------------------------------------------------------------------------
| Dipakai oleh: lembur, permission/create, hrd/shifts, dan halaman lain
| yang memakai input waktu.
|
| Sebelumnya dimuat dari cdn.jsdelivr.net secara SINKRON (script + CSS),
| sehingga pada jaringan tanpa internet permintaan menggantung dan
| menunda DOMContentLoaded -> loading screen tertahan lama.
|
| Dengan dibundel lokal (termasuk CSS-nya), halaman tidak lagi bergantung
| pada CDN dan `window.flatpickr` selalu tersedia untuk script halaman.
|--------------------------------------------------------------------------
*/

import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.flatpickr = flatpickr;

export default flatpickr;
