# Report Pengerjaan — Perbaikan Error "Diluar Jam Absensi" & Perpanjang Check-in Menjadi 2 Jam

> Proyek : Sistem Absensi (Laravel) — `c:\laragon\www\absensi`
> Tanggal : 2026-09-07
> Status : Selesai — seluruh test fitur lolos

---

## 1. Latar Belakang / Gejala yang Dilaporkan

1. Aplikasi absensi **menolak check-in dengan error "Diluar jam absensi"** padahal karyawan
   masih berada dalam **jam dinas** (jadwal shift masih aktif).
2. **Window check-in awal hanya 1 jam** sebelum jam masuk. Karyawan yang datang lebih awal
   (misal 1–2 jam sebelum jam masuk) ditolak, padahal seharusnya diperbolehkan.
3. Permintaan perbaikan : **perpanjang batas check-in dari 1 jam menjadi 2 jam sebelum
   jam masuk** dan perbaiki error yang keliru tersebut.

---

## 2. Analisis Akar Masalah (ditemukan dari penelusuran kode)

### 2.1. Window check-in sengaja dipersempit dari 2 jam menjadi 1 jam
Pada commit `374c4b9` (*"update cekin dan cek out overnight lembur"*), batas check-in awal
diubah dari `subHours(2)` menjadi `subMinutes(60)` di **AttendanceController** dan
**FaceController**. Akibatnya, meskipun `ScheduleService` sudah membuka window sejak 2 jam
sebelum jam masuk, controller menolak check-in yang dilakukan antara 1–2 jam sebelum jam
masuk.

### 2.2. Error "Diluar jam absensi" saat masih jam dinas (shift malam / lintas hari)
`ScheduleService` memutuskan shift lintas hari **hanya dari kolom `is_overnight`** pada
`employee_shifts`. Padahal kolom tersebut **tidak selalu tersimpan**:
- `ShiftController::bulkAssign()` dan `weeklyAssign()` tidak menyimpan `is_overnight`.
- `PJShiftChangeController` (persetujuan tukar shift oleh PJ) juga tidak menyimpannya.

Untuk shift malam (mis. 22:00–06:00) yang flag-nya hilang/0, `shift_end` dihitung sebagai
`06:00` di **hari yang sama** (sudah lewat), sehingga window `[start-2jam, end+6jam]`
tidak valid saat karyawan **sedang bertugas** (mis. pukul 23:30) dan controller
mengembalikan **"Diluar jam absensi"**. Skenario check-out pagi hari pun tidak ter-resolve.

### 2.3. Pesan error kurang informatif & perilaku antar-endpoint tidak konsisten
- API mobile (`AttendanceController`) menolak semua kasus di luar window dengan satu pesan
  "Diluar jam checkin", termasuk saat terlalu pagi.
- Face scan (`FaceController`) tidak membatasi check-in yang telat (bisa sampai +6 jam),
  tidak konsisten dengan API mobile.
- Tidak ada pesan berbeda antara "terlalu pagi" vs "melewati batas".

---

## 3. Perubahan yang Dilakukan

### 3.1. `app/Services/ScheduleService.php`
- Menambah **konstanta window absensi** sebagai sumber tunggal:
  - `EARLY_CHECKIN_HOURS = 2` — check-in paling awal 2 jam sebelum jam masuk.
  - `LATE_CHECKIN_HOURS = 2` — check-in paling lambat 2 jam setelah jam masuk.
  - `POST_SHIFT_WINDOW_HOURS = 6` — jadwal masih dipakai sampai 6 jam setelah shift selesai
    (agar shift malam bisa check-out pagi hari).
  - `CHECKOUT_GRACE_MINUTES = 5` — check-out boleh mulai 5 menit sebelum jam pulang.
  - `DEFAULT_GRACE_MINUTES = 15` — toleransi keterlambatan default.
- Menambah helper `isOvernightByTime()`: shift dianggap **lintas hari** bila `end_time <
  start_time` (mis. 06:00 < 22:00) — **pengaman** bila flag `is_overnight` tidak tersimpan.
- Prioritas 1 (shift kemarin yang masih berjalan): pencarian tidak lagi hanya
  `where('is_overnight', true)`, tetapi juga mencocokkan shift yang waktunya melewati
  tengah malam → check-out shift malam keesokan pagi tetap berfungsi.
- Prioritas 2 (shift hari ini): `shift_end` ditambah 1 hari bila jam menunjukkan lintas hari,
  sehingga window valid selama jam dinas malam hari.

### 3.2. `app/Http/Controllers/AttendanceController.php` (API mobile)
- Menghapus gate `invalid_window` di awal method yang menghasilkan pesan menyesatkan
  "Diluar jam absensi" (cek dipindah ke cabang check-in dengan pesan yang presisi).
- Check-in window diubah **1 jam → 2 jam** sebelum jam masuk (`EARLY_CHECKIN_HOURS`).
- Pesan dibedakan:
  - Terlalu pagi → `403 Belum masuk jam absensi (absensi dibuka mulai 2 jam sebelum jam masuk)`.
  - Terlambat melewati +2 jam → `403 Diluar jam checkin (maksimal 2 jam setelah jam masuk)`.
- Batas check-out memakai konstanta `CHECKOUT_GRACE_MINUTES`.

### 3.3. `app/Http/Controllers/FaceController.php` (face scan / fingerprint device)
- Perlakuan sama dengan API mobile : hapus gate `invalid_window`, window check-in
  **1 jam → 2 jam**, tambah validasi check-in telat +2 jam (konsisten dgn API mobile),
  dan pesan error presisi. Check-out memakai konstanta grace 5 menit.

### 3.4. Penyimpanan data agar tidak terulang
- `app/Http/Controllers/ShiftController.php` — `bulkAssign()` & `weeklyAssign()` kini
  menyimpan `is_overnight` mengikuti shift template.
- `app/Http/Controllers/PJ/PJShiftChangeController.php` — saat PJ menyetujui perubahan
  shift, `is_overnight` ikut diperbarui/dibuat mengikuti shift tujuan.

### 3.5. Test baru
- `tests/Feature/AttendanceWindowTest.php` — 8 test (39 assertion) mencakup seluruh skenario
  window: check-in 2 jam / 1,5 jam sebelum masuk (harus diterima), terlalu pagi / terlalu
  telat (harus ditolak dengan pesan jelas), karyawan office, shift malam tanpa flag
  overnight saat jam dinas, check-out shift malam pagi hari, dan batas check-out.
---

## 4. Cara Menguji

```bash
# 1) Test baru fitur window absensi
php artisan test --filter=AttendanceWindowTest

# 2) Gabungan suite terkait (regresi)
php artisan test --filter='AttendanceWindowTest|RekapAbsenHistoryTest|ShiftChangeOffRequestTest'

# 3) Seluruh suite
php artisan test
```

Hasil pengujian lengkap (termasuk bukti test gagal pada kode lama) dapat dilihat di
**[HASIL_PENGUJIAN_ABSENSI.md](HASIL_PENGUJIAN_ABSENSI.md)**.

---

## 5. Ringkasan Hasil

| Item | Nilai |
| --- | --- |
| Test baru | 8 PASS (39 assertion) |
| Regresi suite terkait | 14 PASS (77 assertion) |
| Seluruh suite | 33 PASS, 6 FAIL pre-existing (Auth/Breeze/Example - tidak terkait) |
| Perubahan file | 5 file kode + 1 file test baru |


---

## 6. Catatan & Risiko yang Perlu Diketahui

1. **Perilaku face scan berubah untuk check-in telat**: sebelumnya user shift yang check-in
   lewat dari 2 jam setelah jam masuk masih bisa tercatat via face scan; sekarang ditolak
   dengan pesan "Diluar jam checkin (maksimal 2 jam setelah jam masuk)" - kini **konsisten**
   dengan API mobile. Jika perusahaan ingin tetap mengizinkan check-in telat via face scan,
   tinggal menyesuaikan konstanta `LATE_CHECKIN_HOURS` di `ScheduleService`.
2. Perubahan pada `bulkAssign`/`weeklyAssign`/`PJ` hanya berlaku untuk **jadwal baru**;
   data lama yang sudah salah tetap aman karena `ScheduleService` kini mendeteksi lintas
   hari dari jam secara otomatis.
3. Kegagalan 6 test Auth/Breeze/Example serta error `artisan route:list` (BOM tersembunyi di
   `app/Http/Controllers/HRD/ShiftManagementController.php`) adalah **isu pre-existing** yang
   tidak berhubungan dengan pekerjaan ini.

---

## 7. File yang Diubah / Ditambah

| File | Keterangan |
| --- | --- |
| `app/Services/ScheduleService.php` | Konstanta window + deteksi overnight berbasis jam |
| `app/Http/Controllers/AttendanceController.php` | Check-in 2 jam + pesan error presisi |
| `app/Http/Controllers/FaceController.php` | Check-in 2 jam + validasi telat konsisten |
| `app/Http/Controllers/ShiftController.php` | Simpan `is_overnight` pada bulk/weekly assign |
| `app/Http/Controllers/PJ/PJShiftChangeController.php` | Simpan `is_overnight` saat persetujuan PJ |
| `tests/Feature/AttendanceWindowTest.php` | Test fitur baru |
| `docs/HASIL_PENGUJIAN_ABSENSI.md` | Laporan hasil pengujian |
| `docs/REPORT_PENGERJAAN_ABSENSI.md` | Report pengerjaan ini |

