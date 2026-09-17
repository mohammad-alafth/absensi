/*
|--------------------------------------------------------------------------
| FULLCALENDAR — VERSI LOKAL (DIBUNDEL VITE)
|--------------------------------------------------------------------------
| Dipakai oleh: jadwal shift (shifts/index) dan modal kalender pada
| rekap HRD (hrd/rekap/index).
|
| Sebelumnya diambil dari cdn.jsdelivr.net (sinkron di shifts/index dan
| dynamic import di rekap HRD). Pada jaringan internal tanpa internet,
| kalender gagal dimuat dan script sinkron menunda DOMContentLoaded.
|
| Berkas ini TIDAK ikut app.js karena ukurannya besar; dimuat hanya pada
| halaman yang butuh kalender, lewat @vite('resources/js/vendor-fullcalendar.js').
| Mengekspos `window.FullCalendar = { Calendar }` seperti versi global CDN
| sehingga script halaman lama tidak perlu diubah.
|--------------------------------------------------------------------------
*/

import { Calendar } from 'fullcalendar';

window.FullCalendar = { Calendar };

export default { Calendar };