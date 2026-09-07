# Laporan Hasil Pengujian — Perbaikan Window Absensi (Check-in 2 Jam)

> Proyek : Sistem Absensi (Laravel) — `c:\laragon\www\absensi`
> Tanggal : 2026-09-07
> PHP    : `C:\laragon\bin\php\php-8.2.23-nts-Win32-vs16-x64\php.exe`
> Runner : `php artisan test` (PHPUnit via Laravel)

---

## 1. Ringkasan

| Kelompok Pengujian | Hasil |
| --- | --- |
| Test baru `AttendanceWindowTest` | **8 PASS / 8 (39 assertion)** |
| Bukti test mendeteksi bug lama (kode sebelum perbaikan) | **3 FAIL** sesuai dugaan |
| Gabungan suite terkait (3 class) | **14 PASS (77 assertion)** |
| Seluruh suite Feature | **33 PASS, 6 FAIL pre-existing** (tidak terkait) |

---

## 2. Test Baru: `AttendanceWindowTest`

File : `tests/Feature/AttendanceWindowTest.php`
Endpoint yang diuji : `POST /api/attendance` (AttendanceController) dengan auth Sanctum.

| # | Test | Skenario | Hasil |
| --- | --- | --- | --- |
| 1 | `test_checkin_dua_jam_sebelum_jam_masuk_diterima` | Shift 08:00–17:00, check-in 06:00 (tepat 2 jam awal) | PASS |
| 2 | `test_checkin_satu_setengah_jam_sebelum_jam_masuk_diterima` | Shift 08:00–17:00, check-in 06:30 (1,5 jam awal; **sebelumnya ditolak karena window 1 jam**) | PASS |
| 3 | `test_checkin_karyawan_office_dua_jam_sebelum_masuk_diterima` | `office_5` Senin 06:00 (2 jam sebelum 08:00) | PASS |
| 4 | `test_checkin_lebih_dari_dua_jam_sebelum_masuk_ditolak_dengan_pesan_jelas` | Shift 08:00, check-in 05:50 → 403 "Belum masuk jam absensi..." | PASS |
| 5 | `test_checkin_terlambat_lebih_dari_dua_jam_setelah_jam_masuk_ditolak` | Shift 08:00, check-in 10:10 → 403 "Diluar jam checkin..." | PASS |
| 6 | `test_shift_malam_tanpa_flag_overnight_tidak_ditolak_saat_jam_dinas` | Shift 22:00–06:00 **tanpa flag is_overnight**, check-in 23:30 saat jam dinas (sebelumnya 403 "Diluar jam absensi") | PASS |
| 7 | `test_checkout_shift_malam_pagi_harinya_tetap_bisa_walau_flag_overnight_hilang` | Shift malam kemarin tanpa flag, checkout 07:00 besok paginya (+60 menit lembur) | PASS |
| 8 | `test_checkout_terlalu_awal_ditolak_dan_sesudah_jam_selesai_diterima` | Check-out 16:00 ditolak; check-out 17:00 diterima (overtime 0) | PASS |

### Output PHPUnit (setelah perbaikan)

```text
PASS  Tests\Feature\AttendanceWindowTest
  PASS  checkin dua jam sebelum jam masuk diterima
  PASS  checkin satu setengah jam sebelum jam masuk diterima
  PASS  checkin karyawan office dua jam sebelum masuk diterima
  PASS  checkin lebih dari dua jam sebelum masuk ditolak dengan pesan jelas
  PASS  checkin terlambat lebih dari dua jam setelah jam masuk ditolak
  PASS  shift malam tanpa flag overnight tidak ditolak saat jam dinas
  PASS  checkout shift malam pagi harinya tetap bisa walau flag overnight hilang
  PASS  checkout terlalu awal ditolak dan sesudah jam selesai diterima

  Tests:    8 passed (39 assertions)
  Duration: 0.83s
```

---

## 3. Bukti Test Mendeteksi Bug Lama

Untuk membuktikan bahwa test benar-benar menangkap bug, perubahan diperbaiki sementara
dikembalikan ke versi lama (`git stash`), lalu 3 test kunci dijalankan. Ketiganya **GAGAL**:

```text
FAIL  Tests\Feature\AttendanceWindowTest
  FAIL  checkin satu setengah jam sebelum jam masuk diterima
        Expected response status code [200] but received 403.        <- window masih 1 jam
  FAIL  shift malam tanpa flag overnight tidak ditolak saat jam dinas
        Expected response status code [200] but received 403.        <- "Diluar jam absensi"
  FAIL  checkout shift malam pagi harinya tetap bisa walau flag overnight hilang
        Expected response status code [200] but received 403.        <- shift tidak ter-resolve

  Tests:    3 failed (3 assertions)
```

Setelah perbaikan dikembalikan (`git stash pop`), seluruh test **lolos kembali** —
konfirmasi bahwa kegagalan di atas memang berasal dari kode lama dan sudah teratasi.

---

## 4. Regresi Suite Terkait

```text
PASS  Tests\Feature\AttendanceWindowTest       (8 passed,  39 assertions)
PASS  Tests\Feature\RekapAbsenHistoryTest      (4 passed,  15 assertions)
PASS  Tests\Feature\ShiftChangeOffRequestTest  (2 passed,  23 assertions)

Tests:    14 passed (77 assertions)
Duration: 3.92s
```

---

## 5. Seluruh Suite Feature

```text
Tests:    6 failed, 33 passed (130 assertions)
Duration: 9.95s
```

Kegagalan 6 test adalah **kegagalan yang sudah ada sebelumnya** dan **tidak berhubungan
dengan fitur ini**:

| Test | Penyebab |
| --- | --- |
| `Auth\AuthenticationTest > users can authenticate` | `assertAuthenticated()` gagal — konfigurasi/session Breeze |
| `Auth\RegistrationTest > new users can register` | `assertAuthenticated()` gagal |
| `Auth\PasswordResetTest` (3 test) | notifikasi reset password tidak terkirim (mail/mailer test) |
| `ExampleTest > the application returns a successful response` | `GET /` mengarahkan (302) karena belum login |

Semua test milik fitur absensi (8 test baru + 6 test terkait) **lolos**.

---

## 6. Kesimpulan

1. Check-in kini boleh dilakukan **mulai 2 jam sebelum jam masuk shift** (sebelumnya 1 jam),
   berlaku untuk karyawan `shift` maupun `office_5`/`office_6` di API mobile dan face scan.
2. Error **"Diluar jam absensi" yang muncul saat karyawan masih di jam dinas sudah
   diperbaiki** untuk shift lintas hari (overnight) yang flag `is_overnight`-nya tidak
   tersimpan (data lama / bulk assign / persetujuan PJ).
3. Pesan error dibedakan dengan jelas: terlalu pagi → *"Belum masuk jam absensi..."*,
   melewati batas → *"Diluar jam checkin..."*.
4. Tidak ada regresi pada suite absensi/rekap/perubahan shift.
