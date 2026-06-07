@echo off
echo === CineAI System Diagnostic ===
echo.

:: 1. Check if XAMPP exists
if exist "C:\xampp" (
    echo [OK] XAMPP folder found at C:\xampp
) else (
    echo [ERROR] XAMPP is NOT installed at C:\xampp! Please install XAMPP to C:\xampp.
)

:: 2. Check PHP
if exist "C:\xampp\php\php.exe" (
    echo [OK] PHP executable found.
    echo Testing PHP execution...
    "C:\xampp\php\php.exe" -v >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] PHP is working correctly.
    ) else (
        echo [ERROR] PHP exists but cannot run. (Maybe missing Visual C++ Redistributable?)
    )
) else (
    echo [ERROR] PHP executable NOT found at C:\xampp\php\php.exe
)

:: 3. Check MySQL
if exist "C:\xampp\mysql\bin\mysqld.exe" (
    echo [OK] MySQL executable found.
) else (
    echo [ERROR] MySQL executable NOT found at C:\xampp\mysql\bin\mysqld.exe
)

echo.
echo === Diagnostic Complete ===
pause
