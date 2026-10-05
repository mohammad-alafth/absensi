# Mikroservice Face Recognition (self-hosted)

Layanan kecil pengenalan wajah (InsightFace ArcFace `buffalo_l` versi ONNX, **CPU**).
Berjalan di server organisasi; **tidak ada data wajah yang dikirim ke cloud**.
Tidak memakai Docker — cukup Python + uvicorn.

## 1. Isi folder

```
face-service/
  app.py           # FastAPI: /health, /embed, /match
  requirements.txt
  run.sh           # helper Linux/macOS
  .venv/           # dibuat otomatis, tidak di-commit  (~630 MB)
  models/          # buffalo_l terunduh, tidak di-commit     (~325 MB)
```

> **Jangan dihapus `.venv/` dan `models/`.** Keduanya adalah dependency runtime.
> Menghapus `.venv` membuat uvicorn tidak bisa jalan; menghapus `models` membuat
> InsightFace perlu mengunduh ulang model dari internet. Keduanya memang tidak
> masuk commit karena `.gitignore`.

## 2. Menjalankan (sudah pernah diuji di mesin ini dengan Python 3.12)

```powershell
cd c:\laragon\www\absensi\face-service

# sekali saja
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt

# setiap kali server perlu hidup
$env:FACE_SERVICE_TOKEN = "token-rahasia-anda"      # opsional tapi disarankan
.\.venv\Scripts\python.exe -m uvicorn app:app --host 127.0.0.1 --port 8100
```

Linux/macOS: `./run.sh` (membuat `.venv` otomatis bila belum ada).

Saat `/health` dipanggil pertama kali, InsightFace mengunduh `buffalo_l`
(sekitar 275 MB) ke `face-service/models/`. Setelah diekstrak, arsip
`buffalo_l.zip` **boleh dihapus** (hemat 275 MB); service tetap jalan karena
memakai file `.onnx` yang sudah diekstrak. Setelah itu layanan bisa jalan
**offline**.

Cek apakah sudah hidup:

```powershell
Invoke-WebRequest http://127.0.0.1:8100/health | Select-Object -Expand Content
# {"success":true,"status":"ready","model":"buffalo_l"}
```

> `{"success": false, ...}` berarti model gagal dimuat (belum terunduh / rusak).
> `"status": "ready"` berarti semua model termuat dan siap dipakai.

## 3. Menghubungkan ke aplikasi

Buka **Pengaturan Wajah** (`/admin/face-settings`) sebagai role `admin`:

| Setting | Nilai |
| --- | --- |
| Aktifkan verifikasi wajah | centang |
| URL microservice | `http://127.0.0.1:8100` |
| Token API | sama persis dengan `FACE_SERVICE_TOKEN` (kosongkan bila layanan tanpa token) |
| Periksa anti-spoof | **biarkan tidak centang** (lihat catatan) |
| Ambang match / abu | 0.62 / 0.45 (bawaan) |

Tekan **Uji Layanan Wajah**. Pesan sukses berisi `Layanan wajah merespons: ready`.

Semua nilai tersimpan di tabel `attendance_settings`, bukan di kode. Nilai
microservice **tidak perlu diubah di kode** saat port/URL berubah.

## 4. Endpoint

| Method | Path | Token | Body | Balas |
| --- | --- | --- | --- | --- |
| GET | `/health` | tidak | - | `{success, status, model}` |
| POST | `/embed` | ya | `{image: base64}` | `{success, embedding: [512], quality}` |
| POST | `/match` | ya | `{image: base64, embedding: [512]}` | `{success, score}` |

Semua pemanggilan dari Laravel memakai `Authorization: Bearer <token>`.
Bila `FACE_SERVICE_TOKEN` kosong di layanan, token di pengaturan juga dikosongkan.

## 5. Variabel lingkungan

| Variabel | Bawaan | Keterangan |
| --- | --- | --- |
| `FACE_SERVICE_TOKEN` | (kosong) | Token API. Kosong = layanan terbuka tanpa autentikasi (hanya untuk localhost) |
| `FACE_MODEL_ROOT` | `face-service/models` | Folder tempat `buffalo_l` disimpan |
| `FACE_DET_SIZE` | `640` | Ukuran input deteksi wajah |
| `FACE_MIN_PX` | `120` | Wajah lebih kecil dari ini ditolak saat registrasi |

## 6. Kalau layanan tidak bisa dihubungi

1. `Invoke-WebRequest http://127.0.0.1:8100/health` — kalau connection refused,
   uvicorn belum jalan.
2. Cek log. Bila dijalankan dengan perintah di atas, log tampil di terminal yang
   sama. Bila dijalankan sebagai proses latar, log berada di folder TEMP:
   `$env:TEMP\face-service\uvicorn-err.log`.
3. Pastikan **tidak ada proses lain** memakai port 8100
   (`Get-NetTCPConnection -LocalPort 8100`).
4. Pastikan URL di pengaturan persis sama, termasuk `http://` dan port.
5. Bila token diisi di satu sisi saja, layanan membalas **401** dan Laravel
   menganggapnya gagal. Kosongkan token di kedua sisi untuk uji lokal.

## 7. Catatan akurasi & kepatuhan

- Wajah **frontal**, pencahayaan merata, satu wajah per bingkai.
- Ambang terbaik diukur dari kamera asli: sampling 10-20 foto per karyawan,
  lalu simpan ambangnya lewat UI pengaturan.
- Bila layanan mati, aplikasi tetap mencatat absen dengan
  `face_method = manual_fallback` dan masuk antrean verifikasi HRD.
- **Anti-spoof masih nonaktif.** Microservice ini belum punya endpoint
  `/detect`, dan model anti-spoof (mis. MiniFASNet) **wajib** dicek lisensi
  kode dan bobotnya sebelum dipakai produksi. Jangan centang
  "Periksa anti-spoof" sebelum endpoint tersebut benar-benar ada, karena
  pemeriksaan akan dilewati (fail-open) tanpa memberi tahu pengguna.
- Bobot `buffalo_l` berlisensi untuk penggunaan non-komersial riset;
  **periksa ulang lisensi model sebelum dipakai komersial**.