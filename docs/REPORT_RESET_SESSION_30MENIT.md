# Report Pengerjaan — Reset Session Otomatis (Idle 30 Menit → Auto Logout / Logout)

> Proyek : Sistem Absensi (Laravel) — `c:\laragon\www\absensi`
> Tanggal : 2026-09-08
> PHP    : `C:\laragon\bin\php\php-8.2.23-nts-Win32-vs16-x64\php.exe`
> Status : Selesai — test fitur lolos

---

## 1. Latar Belakang / Permintaan

1. Permintaan berulang dari user : **"Reset session — jika tidak ada aktivitas selama
   30 menit, sistem auto close / logout"**.
2. Aplikasi web (Breeze Blade) maupun panggilan API absensi memakai **dua lapis auth**:
   - **Session web** (cookie, `SESSION_DRIVER=database`, `SESSION_LIFETIME=30`).
   - **Token Sanctum** (`auth_token`) yang dibuat saat login dan disimpan di
     `localStorage`, dipakai panggilan `POST /api/attendance`, face scan, dll.
3. Logout yang benar harus me-reset **keduanya** (session web + token), bukan sekadar
   pindah halaman `/login`.

---

## 2. Kondisi Sebelumnya (Masalah)

Pada `resources/views/layouts/app.blade.php` sudah ada *auto-logout* lama, tetapi :

1. **Hanya redirect `window.location.href = "/login"` tanpa logout sungguhan.**
   - Jika session web masih hidup (atau *remember me* aktif), Laravel akan
     mengarahkan user yang sudah login kembali ke dashboard — efeknya **tidak logout**.
2. **Token Sanctum (`localStorage.token`) tidak di-reset** saat logout.
   - `AuthenticatedSessionController::destroy()` hanya menghapus session web; token
     Sanctum yang dibuat saat login tetap valid dan bisa dipakai memanggil `/api/*`.
3. Timer lama tidak memberi peringatan; tidak ada kesempatan memperpanjang sesi.

---

## 3. Solusi yang Diimplementasikan

### 3.1. Idle watcher baru — `layouts/app.blade.php`

- Hitung mundur **30 menit** sejak aktivitas user terakhir di semua halaman yang memakai
  `<x-app-layout>` (dashboard, HRD, PJ, face, dsb).
- Aktivitas yang me-reset timer : `mousemove`, `mousedown`, `keydown`, `keypress`,
  `click`, `dblclick`, `scroll`, `wheel`, `touchstart`, `touchmove`, `pointerdown`,
  dan `visibilitychange` (kembali ke tab dianggap aktif).
- **30 detik sebelum timeout** muncul SweetAlert berisi hitung mundur:
  - tombol **"Tetap Login"** → timer di-reset (sesi dilanjutkan);
  - tombol **"Logout Sekarang"** / waktu habis → auto logout.
- Saat auto logout, halaman melakukan **`POST /logout` sungguhan** dengan CSRF token,
  menghapus `localStorage.token`, lalu redirect ke `/login?session_expired=1`.
- Aman jika SweetAlert CDN tidak termuat (langsung logout), dan memakai
  `redirect: 'manual'` agar tidak ikut mengikuti rantai redirect saat session habis.

### 3.2. Revoke token Sanctum saat logout — `AuthenticatedSessionController`

- **Login (`store()`)** : token `auth_token` dibuat lalu **ID-nya disimpan di session**
  (`auth_access_token_id`).
- **Logout (`destroy()`)** : selain `Auth::logout()` + invalidate session, **token
  Sanctum milik sesi web ikut dihapus** (revoke) → token lama tidak bisa dipakai lagi
  memanggil `/api/*` dari browser yang sama.
- Token yang di-revoke **hanya token milik sesi web tersebut** — token perangkat lain
  (mobile / face device) tidak terpengaruh.

### 3.3. Notifikasi di halaman login — `auth/login.blade.php`

- Bila diarahkan ke `/login?session_expired=1`, muncul banner kuning :
  *"Sesi Anda berakhir karena tidak ada aktivitas selama 30 menit. Silakan login
  kembali."*

### 3.4. Lapisan server (tetap berlaku)

- `config/session.php` + `.env` : `SESSION_LIFETIME=30`, `SESSION_EXPIRE_ON_CLOSE=true`,
  `SESSION_DRIVER=database`. Laravel otomatis menganggap session **kedaluwarsa setelah
  30 menit tanpa request** — watcher JS di atas membuat logout terjadi lebih dini dan
  bersih dari sisi UX.

---

## 4. Berkas yang Diubah / Ditambah

| Berkas | Perubahan |
| --- | --- |
| `resources/views/layouts/app.blade.php` | Idle watcher 30 menit diganti versi kokoh + peringatan 30 detik + POST `/logout` sungguhan |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Simpan ID token Sanctum saat login; revoke token saat logout |
| `resources/views/auth/login.blade.php` | Banner "sesi berakhir karena tidak ada aktivitas 30 menit" |
| `tests/Feature/Auth/SessionTokenLogoutTest.php` | Test baru: logout web me-revoke token Sanctum |

---

## 5. Hasil Pengujian

### 5.1. Test baru `SessionTokenLogoutTest`

```text
PASS  Tests\Feature\Auth\SessionTokenLogoutTest
  PASS  web logout revokes sanctum token created at login      5.03s
  PASS  logout still works when no web token was created       0.06s

Tests:    2 passed (7 assertions)
Duration: 7.97s
```

Catatan : test memakai `DatabaseTransactions` (rollback, tanpa `migrate:fresh`)
sehingga **tidak menghapus data** di database pengembangan.

### 5.2. Validasi sintaks & kompilasi Blade

```text
php -l  AuthenticatedSessionController.php  -> No syntax errors detected
node --check (JS idle watcher)              -> JS syntax OK
php artisan view:cache                      -> Blade templates cached successfully
php artisan view:clear                      -> Compiled views cleared successfully
```

### 5.3. Test terdahulu yang masih belum lolos

Kegagalan 6 test `Auth`/`Example` yang tercatat sebelumnya **tidak terkait** fitur ini
(konfigurasi session/mailer Breeze & `GET /` yang redirect karena belum login).
Salah satu akar masalah test `users can authenticate` : kolom `users.is_approved`
default `false` sehingga login difilter — sesuai desain aplikasi.

---

## 6. Alur / Skenario Setelah Perubahan

1. User login → session web dibuat + token Sanctum (`auth_token`) disimpan, ID token
   disimpan di session.
2. User aktif (klik/ketik/sentuh/scroll) → timer 30 menit di-reset terus.
3. User berhenti beraktivitas:
   - **menit ke 29:30** → SweetAlert peringatan dengan hitung mundur 30 detik;
   - pilih **Tetap Login** → timer di-reset (sesi dilanjutkan);
   - pilih **Logout Sekarang** / tidak melakukan apa-apa → **30 menit** → auto logout.
4. Auto logout → `POST /logout`:
   - session web di-invalidate;
   - **token Sanctum sesi web di-revoke** (reset token session);
   - `localStorage.token` dihapus dari browser;
   - diarahkan ke `/login?session_expired=1` (ada banner penjelasan).
5. User harus **login ulang** untuk memakai aplikasi / API absensi dari browser tsb.

---

## 7. Batasan / Catatan

- Watcher berjalan di semua halaman authenticated yang memakai `<x-app-layout>`.
  Halaman publik (login/register/forgot-password) tidak perlu di-logout.
- Token Sanctum milik perangkat lain (login API mobile terpisah) tidak ikut di-revoke.
- Jika browser ditutup, `SESSION_EXPIRE_ON_CLOSE=true` tetap memastikan session tidak
  bertahan.
- Durasi idle 30 menit dan peringatan 30 detik bisa diubah lewat konstanta
  `IDLE_MINUTES` / `WARNING_SECONDS` di `layouts/app.blade.php`.

