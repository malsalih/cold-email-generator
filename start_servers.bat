@echo off
title ColdForge AI - System Startup
echo.
echo  ╔══════════════════════════════════════════════════╗
echo  ║   ColdForge AI Email ^& Warming System v4.0      ║
echo  ║   Safety-First Architecture                      ║
echo  ╚══════════════════════════════════════════════════╝
echo.

echo  [1/5] Starting ML Service (Spam Filter + Text Generator)...
echo        Port: 5050  ^|  Endpoints: /predict, /generate
cd ml_service

if not exist ".venv\Scripts\python.exe" (
    echo.
    echo  [!] Virtual environment not found on this machine. Creating it now...
    python -m venv .venv
    echo  [!] Installing Python dependencies... This might take a minute.
    .\.venv\Scripts\python.exe -m pip install -q -r requirements.txt
    echo  [!] Setup complete!
    echo.
)

start "ColdForge ML Service (Port 5050)" cmd /k ".\.venv\Scripts\python app.py"
cd ..
timeout /t 3 /nobreak >nul

echo  [2/5] Starting Laravel Backend...
echo        Port: 8000  ^|  Dashboard: http://127.0.0.1:8000
start "ColdForge Laravel (Port 8000)" cmd /k "php artisan serve"
timeout /t 2 /nobreak >nul

echo  [3/5] Starting Vite Frontend (HMR)...
start "ColdForge Vite" cmd /k "npm run dev"

echo  [4/5] Starting Laravel Scheduler...
start "ColdForge Scheduler" cmd /k "php artisan schedule:work"

echo  [5/5] Starting Laravel Scheduler...
start "ColdForge queue" cmd /k "php artisan queue:work"

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
