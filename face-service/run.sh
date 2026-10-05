#!/bin/sh
# Helper opsional: menyalakan mikroservice face recognition.
# Dipakai kalau tidak ingin mengetik perintah panjang.
set -e
cd "$(dirname "$0")"

if [ ! -d .venv ]; then
    echo "Membuat virtual environment..."
    python3 -m venv .venv
    ./.venv/bin/python -m pip install -r requirements.txt
fi

exec ./.venv/bin/python -m uvicorn app:app --host 127.0.0.1 --port "${FACE_PORT:-8100}"