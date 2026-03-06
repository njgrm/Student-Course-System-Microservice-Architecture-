# serve-all.ps1 — Start all three microservices in separate terminal windows
# Usage: .\serve-all.ps1
# Stops: Close each terminal window, or press Ctrl+C in the main terminal

Write-Host ""
Write-Host "  Starting all microservices..." -ForegroundColor Cyan
Write-Host "  ──────────────────────────────────────" -ForegroundColor DarkGray
Write-Host "  📚 Student Service    → http://localhost:8001" -ForegroundColor Magenta
Write-Host "  📖 Course Service     → http://localhost:8002" -ForegroundColor Green
Write-Host "  🎓 Enrollment Service → http://localhost:8003" -ForegroundColor Yellow
Write-Host "  ──────────────────────────────────────" -ForegroundColor DarkGray
Write-Host ""

$root = $PSScriptRoot

# Launch each service in its own terminal window
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Set-Location '$root\services\student-service'; Write-Host '📚 Student Service (port 8001)' -ForegroundColor Magenta; php artisan serve --port=8001"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Set-Location '$root\services\course-service'; Write-Host '📖 Course Service (port 8002)' -ForegroundColor Green; php artisan serve --port=8002"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "Set-Location '$root\services\enrollment-service'; Write-Host '🎓 Enrollment Service (port 8003)' -ForegroundColor Yellow; php artisan serve --port=8003"

Write-Host "  All services started! Press Enter to stop..." -ForegroundColor Cyan
Read-Host
