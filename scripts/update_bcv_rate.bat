@echo off
setlocal
set "PHP_EXE=C:\xampp\php\php.exe"
set "JOB_SCRIPT=%~dp0update_bcv_rate.php"

if not exist "%PHP_EXE%" (
    echo [BCV] No se encontro PHP en "%PHP_EXE%".
    exit /b 9009
)

"%PHP_EXE%" "%JOB_SCRIPT%"
exit /b %ERRORLEVEL%
