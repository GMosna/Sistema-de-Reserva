@echo off
set LOG=C:\xampp\htdocs\reserva-salas\scripts\mysql-shutdown.log
echo %DATE% %TIME% - Shutdown iniciado >> "%LOG%"
"C:\xampp\mysql\bin\mysqladmin.exe" -u root shutdown 2>>"%LOG%"
if %ERRORLEVEL% EQU 0 (
    echo %DATE% %TIME% - MySQL parado com sucesso >> "%LOG%"
) else (
    echo %DATE% %TIME% - Falha (ERRORLEVEL=%ERRORLEVEL%) >> "%LOG%"
)
