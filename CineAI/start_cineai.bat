@echo off
echo Starting CineAI Services...

:: Kill existing processes if any
taskkill /f /im php.exe >nul 2>&1
taskkill /f /im mysqld.exe >nul 2>&1

:: Start MySQL
echo Starting MySQL...
start /b "" "C:\xampp\mysql\bin\mysqld.exe" --console

:: Wait for MySQL to warm up
timeout /t 5 /nobreak >nul

:: Start PHP Server
echo Starting PHP Server at http://localhost:8000 ...
start /b "" "C:\xampp\php\php.exe" -S localhost:8000

echo Done! CineAI is running at http://localhost:8000
pause
