#!/bin/sh
# Membuat .venv mikroservice face recognition di server produksi (Linux/Plesk).
#
# Jalankan SEKALI lewat SSH atau Terminal Plesk, dari folder ini:
#     cd /var/www/vhosts/DOMAIN/HTTPDOCS/face-service
#     sh setup.sh
#
# Kenapa perlu: .venv dan models/ ada di .gitignore (lihat baris 26-27), jadi
# TIDAK ikut ter-deploy lewat git. venv yang dibuat di Windows juga tidak bisa
# dipakai di Linux (pyvenv.cfg menunjuk python.exe dengan path Windows).
#
# Setelah selesai: sh start.sh, atau tekan tombol "Nyalakan" di
# /admin/face-settings.

set -e
cd "$(dirname "$0")"

PY="${PYTHON:-python3}"

echo "== 1/4 Mengecek Python =="
if ! command -v "$PY" >/dev/null 2>&1; then
    echo "ERROR: '$PY' tidak ditemukan." >&2
    echo "  Plesk        : Extensions > Python > Add Python, lalu jalankan" >&2
    echo "                 PYTHON=python3.x sh setup.sh" >&2
    echo "  Ubuntu/Debian: sudo apt install python3 python3-venv python3-dev build-essential" >&2
    echo "  AlmaLinux/RHEL: sudo dnf install python3 python3-devel gcc gcc-c++ make" >&2
    exit 1
fi

# onnxruntime 1.18.1 dan numpy 1.26.4 belum punya wheel untuk Python 3.13+,
# jadi batasi pada 3.10-3.12 (3.11/3.12 paling aman).
"$PY" - <<'PY'
import sys
v = sys.version_info
if v < (3, 10) or v >= (3, 13):
    sys.exit("ERROR: Python %d.%d.%d tidak didukung. Pakai 3.10, 3.11 atau 3.12."
             % v[:3])
print("    Python %d.%d.%d OK" % v[:3])
PY

echo "== 2/4 Membuat virtual environment =="
if [ -x .venv/bin/python ]; then
    echo "    .venv sudah ada - dilewati."
else
    "$PY" -m venv .venv
    echo "    .venv dibuat."
fi

echo "== 3/4 Memasang dependensi =="
# insightface 0.7.3 hanya tersedia sebagai sdist dan butuh compiler.
# Bila langkah ini gagal, install dulu build-essential / gcc-c++ lalu ulangi.
./.venv/bin/python -m pip install --upgrade pip wheel setuptools
./.venv/bin/python -m pip install -r requirements.txt

echo "== 4/4 Selesai =="
echo
echo "  Interpreter : $PWD/.venv/bin/python"
echo "  Jalankan    : sh start.sh"
echo "  atau        : tekan tombol 'Nyalakan' di /admin/face-settings"
echo
echo "  Catatan:"
echo "   - Model buffalo_l (~275 MB) diunduh otomatis saat /health dipanggil"
echo "     PERTAMA KALI, jadi server butuh akses internet. Kalau server offline,"
echo "     salin folder models/buffalo_l dari mesin dev ke face-service/models/."
echo "   - Pastikan 'face.service_url' di /admin/face-settings menunjuk"
echo "     http://127.0.0.1:8100 dan token-nya sama dengan FACE_SERVICE_TOKEN."
