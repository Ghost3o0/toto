@echo off
echo ========================================
echo    PhoneStock - Demarrage
echo ========================================
echo.

REM Configurer le PATH pour PHP
if exist "C:\php\php.exe" (
    set PATH=C:\php;%PATH%
)

REM Vérifier PHP
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERREUR] PHP non trouve dans le PATH
    echo Installez PHP dans C:\php
    pause
    exit /b 1
)

echo [OK] PHP trouve
echo.
echo Demarrage du serveur sur http://localhost:8000
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo ========================================
echo.

cd /d "%~dp0"
start http://localhost:8000
php -S localhost:8000
