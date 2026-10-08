# run_local.ps1 — تشغيل FastAPI محلياً (Laragon + Python 3.11 venv)
# الاستخدام: من Terminal داخل مجلد fastapi: .\run_local.ps1

Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "   🚀 Interview AI Engine — FastAPI (Local Mode)" -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host ""
Write-Host "⚠️  تأكد أن Laragon يعمل (PostgreSQL + Redis)" -ForegroundColor Yellow
Write-Host ""

$venvPython = "$PSScriptRoot\venv\Scripts\python.exe"
$venvUvicorn = "$PSScriptRoot\venv\Scripts\uvicorn.exe"

if (-not (Test-Path $venvPython)) {
    Write-Host "❌ لم يتم العثور على venv" -ForegroundColor Red
    Write-Host "   شغّل: python -m venv venv && venv\Scripts\pip install -r requirements.txt"
    exit 1
}

if (-not (Test-Path "$PSScriptRoot\recordings")) {
    New-Item -ItemType Directory "$PSScriptRoot\recordings" | Out-Null
    Write-Host "📁 Created recordings folder"
}

Write-Host "✅ Python: $(& $venvPython --version)"
Write-Host "✅ API:    http://localhost:8001"
Write-Host "✅ Docs:   http://localhost:8001/docs"
Write-Host "✅ Health: http://localhost:8001/health"
Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan

Set-Location $PSScriptRoot
& $venvUvicorn main:app --host 0.0.0.0 --port 8001 --reload
