@echo off
echo Parando MySQL (shutdown limpo)...
"C:\xampp\mysql\bin\mysqladmin.exe" -u root shutdown
if %ERRORLEVEL% EQU 0 (
    echo MySQL parado com sucesso.
) else (
    echo Falha ao parar MySQL. Tente parar pelo XAMPP Control Panel.
)
pause
