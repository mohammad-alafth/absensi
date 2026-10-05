# Menghentikan mikroservice face recognition.
#
# Jalankan:
#   powershell -ExecutionPolicy Bypass -File .\face-service\stop.ps1

$ErrorActionPreference = 'SilentlyContinue'

$Port = 8100

# Cari proses python yang listens di port mikroservice, lalu hentikan.
$conns = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue

if (-not $conns) {
    Write-Host 'Mikroservice tidak sedang berjalan.' -ForegroundColor Yellow
    exit 0
}

$pids = $conns | Select-Object -ExpandProperty OwningProcess -Unique

foreach ($procId in $pids) {
    try {
        Stop-Process -Id $procId -Force
        Write-Host "Proses $procId dihentikan." -ForegroundColor Green
    } catch {
        Write-Host "Gagal menghentikan proses $procId." -ForegroundColor Red
    }
}

Start-Sleep -Seconds 2

try {
    Invoke-WebRequest "http://127.0.0.1:$Port/health" -TimeoutSec 3 -UseBasicParsing | Out-Null
    Write-Host 'Masih merespons. Periksa proses lain.' -ForegroundColor Yellow
} catch {
    Write-Host 'Mikroservice sudah berhenti.' -ForegroundColor Green
}