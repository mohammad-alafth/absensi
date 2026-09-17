/*
|--------------------------------------------------------------------------
| SIGNATURE PAD — VERSI LOKAL (DIBUNDEL VITE)
|--------------------------------------------------------------------------
| Dipakai untuk tanda tangan digital pada halaman cuti, lembur, dan izin
| (HRD maupun PJ).
|
| Sebelumnya diambil dari cdn.jsdelivr.net secara sinkron sehingga pada
| jaringan tanpa internet script menggantung dan menunda DOMContentLoaded.
| Kini dibundel lokal dan mengekspos `window.SignaturePad`.
|--------------------------------------------------------------------------
*/

import SignaturePad from 'signature_pad';

window.SignaturePad = SignaturePad;

export default SignaturePad;