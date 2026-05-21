@echo off
setlocal enabledelayedexpansion

:: ============================================================
::  BACKUP PRE-REFATORACAO — reserva-salas
:: ============================================================

set TIMESTAMP=%DATE:~6,4%%DATE:~3,2%%DATE:~0,2%_%TIME:~0,2%%TIME:~3,2%%TIME:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set DEST=C:\BACKUP_RESERVA_SALAS_%TIMESTAMP%
set SRC=C:\xampp\htdocs\reserva-salas
set MYSQL=C:\xampp\mysql\bin
set RELATORIO=%DEST%\RELATORIO_BACKUP.txt

echo.
echo ============================================================
echo  BACKUP PRE-REFATORACAO
echo  Destino: %DEST%
echo ============================================================
echo.

:: --- Criar estrutura de destino ---
mkdir "%DEST%" 2>nul
mkdir "%DEST%\sql" 2>nul
mkdir "%DEST%\uploads" 2>nul
mkdir "%DEST%\codigo" 2>nul

echo [1/5] Copiando codigo fonte...
xcopy "%SRC%" "%DEST%\codigo" /E /I /Q /EXCLUDE:%SRC%\backup_exclude.txt 2>nul
if not exist "%SRC%\backup_exclude.txt" (
    xcopy "%SRC%" "%DEST%\codigo" /E /I /Q 2>nul
)
echo     OK

echo [2/5] Exportando banco reserva_salas...
"%MYSQL%\mysqldump.exe" -u root --databases reserva_salas --routines --triggers --single-transaction > "%DEST%\sql\reserva_salas.sql"
if %ERRORLEVEL% EQU 0 (
    echo     OK
) else (
    echo     FALHA - verifique se MySQL esta rodando
)

echo [3/5] Exportando todos os bancos...
"%MYSQL%\mysqldump.exe" -u root --all-databases --routines --triggers --single-transaction > "%DEST%\sql\all_databases.sql"
if %ERRORLEVEL% EQU 0 (
    echo     OK
) else (
    echo     FALHA
)

echo [4/5] Copiando uploads...
if exist "%SRC%\public\uploads" (
    xcopy "%SRC%\public\uploads" "%DEST%\uploads" /E /I /Q 2>nul
    echo     OK
) else (
    echo     Diretorio uploads nao encontrado - pulando
)

echo [5/5] Gerando relatorio...
(
    echo RELATORIO DE BACKUP PRE-REFATORACAO
    echo =====================================
    echo Data/Hora: %DATE% %TIME%
    echo Origem: %SRC%
    echo Destino: %DEST%
    echo.
    echo --- ARQUIVOS PHP ---
    dir /B /S "%DEST%\codigo\*.php" 2>nul
    echo.
    echo --- BANCO DE DADOS ---
    if exist "%DEST%\sql\reserva_salas.sql" (
        echo reserva_salas.sql: OK
        for %%F in ("%DEST%\sql\reserva_salas.sql") do echo Tamanho: %%~zF bytes
    )
    if exist "%DEST%\sql\all_databases.sql" (
        echo all_databases.sql: OK
        for %%F in ("%DEST%\sql\all_databases.sql") do echo Tamanho: %%~zF bytes
    )
    echo.
    echo --- UPLOADS ---
    dir /B /S "%DEST%\uploads" 2>nul
) > "%RELATORIO%"
echo     OK

:: --- Gerar script de restauracao ---
(
    echo @echo off
    echo echo Restaurando backup de %TIMESTAMP%...
    echo echo.
    echo echo [1/3] Restaurando banco de dados...
    echo "%MYSQL%\mysql.exe" -u root ^< "%DEST%\sql\reserva_salas.sql"
    echo echo [2/3] Copiando codigo...
    echo xcopy "%DEST%\codigo" "%SRC%" /E /I /Y /Q
    echo echo [3/3] Restaurando uploads...
    echo xcopy "%DEST%\uploads" "%SRC%\public\uploads" /E /I /Y /Q
    echo echo.
    echo echo Restauracao concluida.
    echo pause
) > "%DEST%\RESTAURAR_BACKUP.bat"

echo.
echo ============================================================
echo  BACKUP CONCLUIDO
echo  Destino: %DEST%
echo  Relatorio: %RELATORIO%
echo ============================================================
echo.
pause
