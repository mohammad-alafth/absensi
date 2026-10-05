# Watchdog mikroservice face recognition.
#
# Dijalankan oleh Windows Task Scheduler (At startup) DAN tiap 2 menit.
# Tujuannya satu: JANGAN BIARAN ada window lama di mana verifikasi wajah
# mati tanpa ada yang tahu.
#
# Kenapa perlu: kalau uvicorn crash, tidak ada supervisor yang menyalakannya.
# Selama itu terjadi, FaceResult::unavailable() membuat absen fail-open -
# siapa pun bisa absen tanpa dicek wajahnya, dan tidak ada yang diberi tahu.
#
# Perilaku: kalau /health tidak merespons, jalankan startup.ps1.

$Port   = 8100
$Root   = 'c:\laragon\www\absensi\face-service'
$Python = Join-Path $Root '.venv\Scripts\python.exe'
$Log    = Join-Path $Root 'service.log'
$Health = "http://127.0.0.1:$Port/health"

# Tidak perlu install ulang .venv (sudah ada).
if (-not (Test-Path $Python)) {
    exit 1
}

# --- 1. Kalau sehat, tidak ada yang perlu dilakukan. ---
try {
    Invoke-WebRequest $Health -TimeoutSec 5 -UseBasicParsing | Out-Null
    exit 0
} catch {
    # lanjut ke bawah
}

# --- 2. Port masih dipakai proses yang belum siap. Jangan nyalakan dua kali. ---
$listening = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue

if ($listening) {
    # Port dipakai tapi /health belum hidup = model masih dimuat.
    # Beri waktu; jangan bunuh proses yang sedang baik-baik saja.
    exit 0
}

# --- 3. Service benar-benar mati: nyalakan ulang. ---

# Token WAJIB konsisten dengan face.service_token di Laravel. Kalau env ini
# sudah diisi (mis. oleh UI admin lewat FaceServiceProcess), nilai itu yang
# dipakai. Kalau kosong, keduanya kosong dan autentikasi dilewati.
if (-not $env:FACE_SERVICE_TOKEN) {
    $env:FACE_SERVICE_TOKEN = ''
}

Start-Process `
    -FilePath $Python `
    -ArgumentList '-m', 'uvicorn', 'app:app', '--host', '127.0.0.1', '--port', $Port `
    -WorkingDirectory $Root `
    -WindowStyle Hidden `
    -RedirectStandardOutput $Log `
    -RedirectStandardError "$Log.err"

exit 0