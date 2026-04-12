@echo off
title ColdForge AI - System Startup
echo.
echo  ╔══════════════════════════════════════════════════╗
echo  ║   ColdForge AI Email ^& Warming System v4.0      ║
echo  ║   Safety-First Architecture                      ║
echo  ╚══════════════════════════════════════════════════╝
echo.

echo  [1/4] Starting ML Service (Spam Filter + Text Generator)...
echo        Port: 5050  ^|  Endpoints: /predict, /generate
start "ColdForge ML Service (Port 5050)" cmd /c "cd ml_service && .\.venv\Scripts\python app.py"
timeout /t 3 /nobreak >nul

echo  [2/4] Starting Laravel Backend...
echo        Port: 8000  ^|  Dashboard: http://127.0.0.1:8000
start "ColdForge Laravel (Port 8000)" cmd /c "php artisan serve"
timeout /t 2 /nobreak >nul

echo  [3/4] Starting Vite Frontend (HMR)...
start "ColdForge Vite" cmd /c "npm run dev"

echo  [4/4] Starting Laravel Scheduler...
start "ColdForge Scheduler" cmd /c "php artisan schedule:work"

echo.
echo  ╔══════════════════════════════════════════════════╗
echo  ║  ✅ All services started!                        ║
echo  ║                                                  ║
echo  ║  🌐 Dashboard:  http://127.0.0.1:8000            ║
echo  ║  🔥 Warming:    http://127.0.0.1:8000/warming    ║
echo  ║  🤖 ML API:     http://127.0.0.1:5050            ║
echo  ║                                                  ║
echo  ║  📝 Bot is controlled from the Dashboard.        ║
echo  ║     Go to Warming → click "Start Daily Round"    ║
echo  ║     or use "Quick Start" for manual targets.     ║
echo  ╚══════════════════════════════════════════════════╝
echo.
pause
