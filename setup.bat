@echo off
echo ========================================
echo    PhoneStock - Configuration
echo ========================================
echo.

REM Vérifier PHP
where php >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\php\php.exe" (
        set PATH=C:\php;%PATH%
    ) else (
        echo [ERREUR] PHP non trouve.
        echo Installez PHP dans C:\php
        echo Telechargez depuis: https://windows.php.net/download/
        pause
        exit /b 1
    )
)

echo [OK] PHP trouve
php -v
echo.

REM Vérifier PostgreSQL
set PGPATH=C:\Program Files\PostgreSQL\18\bin
if exist "%PGPATH%\psql.exe" (
    set PATH=%PGPATH%;%PATH%
    echo [OK] PostgreSQL trouve
) else (
    echo [ERREUR] PostgreSQL non trouve
    pause
    exit /b 1
)

echo.
echo ========================================
echo    Creation de la base de donnees
echo ========================================
echo.
echo Entrez le mot de passe PostgreSQL (utilisateur postgres):
set /p PGPASSWORD=

REM Créer la base de données
echo.
echo Creation de la base phone_stock_db...
"%PGPATH%\psql.exe" -U postgres -c "CREATE DATABASE phone_stock_db;" 2>nul
if %errorlevel% equ 0 (
    echo [OK] Base de donnees creee
) else (
    echo [INFO] La base existe peut-etre deja
)

REM Exécuter le script SQL
echo.
echo Execution du script SQL...
"%PGPATH%\psql.exe" -U postgres -d phone_stock_db -f "%~dp0sql\schema.sql"
if %errorlevel% equ 0 (
    echo [OK] Tables creees avec succes
) else (
    echo [ERREUR] Echec de creation des tables
    pause
    exit /b 1
)

echo.
echo ========================================
echo    Configuration terminee!
echo ========================================
echo.
echo Pour lancer le serveur, executez: start.bat
echo.
pause
