# Menyalakan mikroservice face recognition (InsightFace buffalo_l).
#
# Jalankan:
#   powershell -ExecutionPolicy Bypass -File .\face-service\start.ps1
#
# Aman dipanggil berkali-kali: kalau servicenya sudah hidup, script ini
# hanya melaporkan status lalu keluar (tidak terjadi bentrok port).

$ErrorActionPreference = 'Stop'

$Root    = $PSScriptRoot
$Python  = Join-Path $Root '.venv\Scripts\python.exe'
$Port    = 8100
$Health  = "http://127.0.0.1:$Port/health"
$LogFile = Join-Path $Root 'service.log'

if (-not (Test-Path $Python)) {
    Write-Host 'Virtual environment belum ada.' -ForegroundColor Red
    Write-Host 'Jalankan sekali saja:' -ForegroundColor Yellow
    Write-Host "  cd $Root"
    Write-Host '  python -m venv .venv'
    Write-Host '  .\.venv\Scripts\python.exe -m pip install -r requirements.txt'
    exit 1
}

# Sudah hidup? Jangan nyalakan dua kali karena port akan bentrok.
try {
    $resp = Invoke-WebRequest $Health -TimeoutSec 5 -UseBasicParsing
    Write-Host "Sudah aktif: $($resp.Content)" -ForegroundColor Green
    exit 0
} catch {
    # lanjut nyalakan
}

Write-Host 'Menyalakan mikroservice face recognition...' -ForegroundColor Cyan

# Token untuk microservice.
#
# Kalau env ini sudah diisi (mis. oleh UI admin lewat FaceServiceProcess),
# nilai itu yang dipakai, sehingga token microservice dan token Laravel
# (face.service_token) selalu sama persis. Kalau kosong, keduanya kosong
# juga sehingga autentikasi dilewati (default sistem).
if (-not $env:FACE_SERVICE_TOKEN) {
    $env:FACE_SERVICE_TOKEN = ''
}

Start-Process `
    -FilePath $Python `
    -ArgumentList '-m', 'uvicorn', 'app:app', '--host', '127.0.0.1', '--port', $Port `
    -WorkingDirectory $Root `
    -WindowStyle Hidden `
    -RedirectStandardOutput $LogFile `
    -RedirectStandardError "$LogFile.err"

# InsightFace memuat model (~275 MB) saat start-up, jadi beri waktu.
Write-Host 'Menunggu model siap (start-up pertama bisa 30-60 detik)...' -ForegroundColor Yellow

for ($i = 1; $i -le 60; $i++) {
    Start-Sleep -Seconds 2
    try {
        $resp = Invoke-WebRequest $Health -TimeoutSec 5 -UseBasicParsing
        Write-Host "SIAP: $($resp.Content)" -ForegroundColor Green
        exit 0
    } catch {
        # lanjut menunggu
    }
}

Write-Host 'Belum siap setelah 120 detik. Periksa log:' -ForegroundColor Red
Write-Host "  $LogFile"
Write-Host "  ${LogFile}.err"
exit 1

if (-not (Test-Path $Python)) {
    Write-Host 'Virtual environment belum ada.' -ForegroundColor Red
    Write-Host 'Jalankan sekali saja:' -ForegroundColor Yellow
    Write-Host "  cd $Root"
    Write-Host '  python -m venv .venv'
    Write-Host '  .\.venv\Scripts\python.exe -m pip install -r requirements.txt'
    exit 1
}

# Sudah hidup? Jangan nyalakan dua kali karena port akan bentrok.
try {
    $resp = Invoke-WebRequest $Health -TimeoutSec 5 -UseBasicParsing
    Write-Host "Sudah aktif: $($resp.Content)" -ForegroundColor Green
    exit 0
} catch {
    # lanjut nyalakan
}

Write-Host 'Menyalakan mikroservice face recognition...' -ForegroundColor Cyan

# Ganti bila face.service_token di Laravel diisi nilai lain.
$env:FACE_SERVICE_TOKEN = 'token-rahasia-anda'

Start-Process `
    -FilePath $Python `
    -ArgumentList '-m', 'uvicorn', 'app:app', '--host', '127.0.0.1', '--port', $Port `
    -WorkingDirectory $Root `
    -WindowStyle Hidden `
    -RedirectStandardOutput $LogFile `
    -RedirectStandardError "$LogFile.err"

# InsightFace memuat model (~275 MB) saat start-up, jadi beri waktu.
Write-Host 'Menunggu model siap (start-up pertama bisa 30-60 detik)...' -ForegroundColor Yellow

for ($i = 1; $i -le 60; $i++) {
    Start-Sleep -Seconds 2
    try {
        $resp = Invoke-WebRequest $Health -TimeoutSec 5 -UseBasicParsing
        Write-Host "SIAP: $($resp.Content)" -ForegroundColor Green
        exit 0
    } catch {
        # lanjut menunggu
    }
}

Write-Host 'Belum siap setelah 120 detik. Periksa log:' -ForegroundColor Red
Write-Host "  $LogFile"
Write-Host "  ${LogFile}.err"
exit 1