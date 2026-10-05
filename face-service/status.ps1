# Cek status mikroservice face recognition.
#
# Jalankan:
#   powershell -ExecutionPolicy Bypass -File .\face-service\status.ps1

$Port   = 8100
$Health = "http://127.0.0.1:$Port/health"

try {
    $resp = Invoke-WebRequest $Health -TimeoutSec 5 -UseBasicParsing
    Write-Host 'STATUS  : SIAP' -ForegroundColor Green
    Write-Host "RESPONS : $($resp.Content)"
    Write-Host 'Artinya: verifikasi wajah AKTIF. Absen memakai pencocokan wajah sungguhan.'
    exit 0
} catch {
    Write-Host 'STATUS  : MATI' -ForegroundColor Red
    Write-Host 'Artinya: verifikasi wajah TIDAK aktif. Siapa pun bisa absen tanpa dicek wajah.'
    Write-Host ''
    Write-Host 'Nyalakan dengan:' -ForegroundColor Yellow
    Write-Host '  powershell -ExecutionPolicy Bypass -File .\face-service\start.ps1'
    exit 1
}