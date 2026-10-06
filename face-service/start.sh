#!/bin/sh
# Menyalakan mikroservice face recognition di Linux (Plesk / production).
#
# Dipanggil oleh UI admin (FaceServiceProcess) dan bisa juga dari SSH.
# Prosesnya HARUS bertahan setelah request PHP selesai, jadi uvicorn dijalankan
# lewat `setsid -f` (session baru) sehingga tidak ikut mati saat response
# HTTP dikirim. Tanpa ini tombol "Nyalakan" di UI akan mematikan service
# beberapa milidetik kemudian.

cd "$(dirname "$0")" || exit 1

PORT="${FACE_PORT:-8100}"
LOG="$PWD/service.log"
ERR="$PWD/service.log.err"
PY="$PWD/.venv/bin/python"

if [ ! -x "$PY" ]; then
    echo "Virtual environment belum ada. Jalankan sekali: sh setup.sh" >&2
    exit 1
fi

# Sudah hidup? Jangan nyalakan dua kali karena port akan bentrok.
if "$PY" -c "import socket,sys
try:
    socket.create_connection(('127.0.0.1', int(sys.argv[1])), 2).close()
except OSError:
    sys.exit(1)
sys.exit(0)" "$PORT" 2>/dev/null; then
    echo "Sudah aktif di port $PORT."
    exit 0
fi

# Token harus sama persis dengan face.service_token di Laravel. Kalau env ini
# sudah diisi (mis. oleh UI admin lewat FaceServiceProcess), nilai itu dipakai;
# kalau kosong, keduanya kosong dan autentikasi dilewati.
FACE_SERVICE_TOKEN="${FACE_SERVICE_TOKEN:-}"
export FACE_SERVICE_TOKEN

echo "Menyalakan mikroservice di port $PORT (model butuh 30-60 detik)..."

if command -v setsid >/dev/null 2>&1; then
    # setsid -f: fork ke session baru, proses induk langsung keluar.
    setsid -f "$PY" -m uvicorn app:app --host 127.0.0.1 --port "$PORT" \
        >>"$LOG" 2>>"$ERR" </dev/null
else
    nohup "$PY" -m uvicorn app:app --host 127.0.0.1 --port "$PORT" \
        >>"$LOG" 2>>"$ERR" </dev/null &
fi

# Keluar segera: caller (Symfony Process) tidak boleh menunggu uvicorn.
exit 0
