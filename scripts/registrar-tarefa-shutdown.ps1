# Executar como Administrador
# Registra tarefas que param MySQL limpo antes de shutdown/restart/sleep

if (-NOT ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Error "Execute este script como Administrador."
    exit 1
}

$scriptPath = "C:\xampp\htdocs\reserva-salas\scripts\parar-mysql-silent.bat"

$tasks = @(
    @{
        Name    = "MySQL Limpo Shutdown"
        Descr   = "Para MySQL antes de shutdown/restart do Windows"
        EC      = "System"
        Filter  = "*[System[Provider[@Name='User32'] and EventID=1074]]"
    },
    @{
        Name    = "MySQL Limpo Sleep"
        Descr   = "Para MySQL antes de sleep/hibernate do Windows"
        EC      = "System"
        Filter  = "*[System[Provider[@Name='Microsoft-Windows-Kernel-Power'] and EventID=42]]"
    }
)

$ok = 0
foreach ($t in $tasks) {
    Write-Host "Registrando: $($t.Name)..." -NoNewline
    schtasks /create `
        /tn $t.Name `
        /tr "`"$scriptPath`"" `
        /sc onevent `
        /ec $t.EC `
        /mo $t.Filter `
        /ru "SYSTEM" `
        /f 2>&1 | Out-Null

    if ($LASTEXITCODE -eq 0) {
        Write-Host " OK" -ForegroundColor Green
        $ok++
    } else {
        Write-Host " FALHOU (code $LASTEXITCODE)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "$ok/2 tarefas registradas." -ForegroundColor $(if ($ok -eq 2) { "Green" } else { "Yellow" })
Write-Host "MySQL sera parado limpo antes de shutdown, restart e sleep."
