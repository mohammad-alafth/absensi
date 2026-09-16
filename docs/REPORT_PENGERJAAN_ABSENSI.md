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


---

## 8. Perbaikan Lanjutan: Pengajuan Change Shift "Hari Libur" / Tidak Ada Shift

### Gejala

Karyawan `work_type = shift` memilih opsi **"Hari Libur / Tidak Ada Shift (Jadwal Kosong)"**
pada form pengajuan perubahan shift, lalu muncul error:

```text
SQLSTATE[23000]: Integrity constraint violation: 1048
Column 'requested_shift_id' cannot be null (Connection: mysql, Database: absensi_rs,
SQL: insert into `shift_change_requests` (...) values (114, 2026-09-13, 26, ?, Perubahan jadwal, pending, ...))
```

### Akar Masalah

1. Fitur pengajuan libur memang menyimpan `requested_shift_id = NULL`
   (`ShiftChangeController::store()` menerjemahkan nilai `off` → `null`).
2. Commit `c6085f5` membuat kolom tersebut nullable, **tetapi** commit `5d9cbd6`
   ("hapus migration") menghapus file migration-nya tanpa memindahkan perubahan ke
   migration `create_shift_change_requests_table`.
3. Akibatnya DB.local yang sempat menjalankan alter tetap nullable, tetapi DB yang
   **belum** pernah menjalankannya (mis. server hasil deploy, atau `migrate:fresh`)
   tetap `NOT NULL` dan tidak bisa diperbaiki karena file migration-nya sudah tidak ada
   di repo → pengajuan libur selalu gagal.

### Perbaikan

| File | Perubahan |
| --- | --- |
| `database/migrations/2026_08_31_100000_create_shift_change_requests_table.php` | `requested_shift_id` kini `nullable()->constrained('shifts')->nullOnDelete()` (fresh install benar sejak awal) |
| `database/migrations/2026_09_11_120000_alter_shift_change_requests_make_requested_shift_id_nullable.php` | **Baru** — alter kolom menjadi nullable untuk DB yang sudah ada; idempotent (dilewati bila sudah nullable) dengan guard `Schema::getColumns()` |
| `tests/Feature/ShiftChangeOffRequestTest.php` | +2 test regresi: kolom wajib nullable & pengajuan libur oleh karyawan yang belum punya shift (current & requested sama-sama NULL) |

### Cara Menerapkan di Environment Lain

```bash
php artisan migrate --force
```

Cukup satu langkah: migration baru akan mengubah kolom menjadi
`bigint unsigned DEFAULT NULL` + FK `ON DELETE SET NULL`.
Alur approval PJ (`PJShiftChangeController::approve()`) sudah benar: saat pengajuan
libur disetujui, jadwal `employee_shifts` pada tanggal tersebut dihapus.

---

## 9. Fitur Baru: Kolom "DENDA KETERLAMBATAN" pada Export Rekap HRD (2026-09-16)

### 9.1 Permintaan

Pada export rekap HRD (`HRD > Export Excel`, route `hrd.export.excel`) ditambahkan
**satu kolom setelah "TOTAL JAM KERJA"** yang berisi **formula** perhitungan denda
keterlambatan.

### 9.2 Ketentuan Denda

- Toleransi akumulasi keterlambatan per bulan: **30 menit** (tidak terkena denda).
- Kelebihan di atas 30 menit didenda **Rp10.000 untuk setiap kelipatan 5 menit**.
- Kelebihan yang bukan kelipatan 5 menit dibulatkan **ke atas** ke kelipatan berikutnya.

| Akumulasi | Kelebihan | Perhitungan | Denda |
| --- | --- | --- | --- |
| 30 menit | 0 | tidak ada kelebihan | Rp0 |
| 34 menit | 4 | dibulatkan ke 5 -> 1 x Rp10.000 | Rp10.000 |
| 35 menit | 5 | 1 x Rp10.000 | Rp10.000 |
| 40 menit | 10 | 2 x Rp10.000 | Rp20.000 |
| 45 menit | 15 | 3 x Rp10.000 | Rp30.000 |
| 50 menit | 20 | 4 x Rp10.000 | Rp40.000 |
| 82 menit | 52 | dibulatkan ke 55 -> 11 x Rp10.000 | Rp110.000 |

### 9.3 Implementasi

File: `app/Exports/HRDRekapExport.php`

1. `headings()` menambah kolom ke-7 **"DENDA KETERLAMBATAN"**, `columnWidths()`
   menambah `'G' => 28`.
2. `collection()` mengisi elemen ke-7 dengan `null` (placeholder). Formula **tidak**
   ditulis di sini karena saat `collection()` berjalan baris data masih berada di
   baris 1; header 5 baris baru di-insert pada event `AfterSheet`, sehingga referensi
   baris (`$E7`, `$E8`, ...) akan keliru.
3. `registerEvents()` (`AfterSheet`) menulis formula untuk setiap baris data
   (baris 7 s/d baris terakhir):

   ```excel
   =IF( <menit> <=30, 0, CEILING((<menit>-30)/5, 1) * 10000 )
   ```

   `<menit>` = total menit hasil parsing kolom **E** ("TOTAL WAKTU TERLAMBAT", teks
   `HH:MM`) memakai `FIND(":")` + `LEFT`/`MID`:

   ```excel
   (VALUE(LEFT($E7,FIND(":",$E7)-1))*60+VALUE(MID($E7,FIND(":",$E7)+1,2)))
   ```

   `TIMEVALUE()` sengaja tidak dipakai karena salah untuk akumulasi lebih dari 24 jam
   (mis. `120:30`).
4. Format sel `"Rp"#,##0` supaya tampil `Rp10.000`; **nilai tetap numerik** sehingga
   masih bisa di-`SUM`/filter di Excel.
5. Layout disesuaikan karena kolom bertambah satu (tabel kini A-G): merge header
   `B1:F1` s/d `B4:F4`, styling baris 6 `A6:G6`, border `A6:G{baris terakhir}`, dan
   logo kanan dipindah dari `F1` ke `G1`.

### 9.4 Pengujian

Test baru: `tests/Feature/HRDRekapDendaColumnTest.php` (14 test, 32 assertion) -
menguji header kolom G, keberadaan formula per baris (`$E7`, `$E8`), perhitungan
akumulasi nyata dari absensi, seluruh contoh ketentuan denda, dan unduhan lewat
route HRD. Detail hasil ada di `docs/HASIL_PENGUJIAN_ABSENSI.md` bagian 8.

---

## 10. Temuan Kritis: Suite Test Menghapus Database Dev (Sudah Diperbaiki)

### 10.1 Gejala

Saat menjalankan seluruh suite (`phpunit` / `php artisan test`), isi database dev
`absensi_rs` **terhapus seluruhnya** (users, attendances, shifts, employee_shifts,
shift_change_requests = 0 baris).

### 10.2 Akar Masalah

1. `tests/Feature/ProfileTest.php` dan `tests/Feature/Auth/*Test.php` memakai trait
   `RefreshDatabase`, yang menjalankan `migrate:fresh` (menghapus semua tabel).
2. `phpunit.xml` masih **mengomentari** baris `DB_CONNECTION=sqlite` /
   `DB_DATABASE=:memory:`, sehingga test memakai database dari `.env`, yaitu
   **database dev `absensi_rs`**.

### 10.3 Perbaikan

`phpunit.xml`: test diarahkan ke **database terpisah** `absensi_rs_test` (driver
`mysql`). Dengan begitu `migrate:fresh` pada test tidak lagi menyentuh data dev.

Persiapan sekali per environment:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS absensi_rs_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
DB_DATABASE=absensi_rs_test php artisan migrate
```

Catatan: opsi `sqlite` in-memory **tidak bisa dipakai** pada proyek ini karena
migration memakai `->change()` (menghasilkan `MODIFY`, khusus MySQL) dan test
`DatabaseTransactions` memerlukan skema yang sudah dimigrasi.

### 10.4 Verifikasi

- Sebelum perbaikan: `Tests: 64, Failures: 15` **dan** database dev terhapus.
- Sesudah perbaikan: `Tests: 64, Assertions: 202, Failures: 6` (6 kegagalan adalah
  test scaffolding bawaan yang sudah gagal sebelum perubahan ini - lihat
  `docs/HASIL_PENGUJIAN_ABSENSI.md` bagian 8.3), dan isi database dev `absensi_rs`
  **tidak berubah**.

