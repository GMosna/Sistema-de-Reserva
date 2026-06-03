# Executar como Administrador
# Registra tarefa que para MySQL limpo antes do Windows desligar

$taskName = "MySQL Limpo Shutdown"
$scriptPath = "C:\xampp\htdocs\reserva-salas\scripts\parar-mysql-silent.bat"

# Verifica se está rodando como admin
if (-NOT ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Error "Execute este script como Administrador."
    exit 1
}

schtasks /create `
    /tn $taskName `
    /tr $scriptPath `
    /sc onevent `
    /ec System `
    /mo "*[System[Provider[@Name='User32'] and EventID=1074]]" `
    /ru "SYSTEM" `
    /f

if ($LASTEXITCODE -eq 0) {
    Write-Host "Tarefa '$taskName' registrada com sucesso." -ForegroundColor Green
    Write-Host "MySQL sera parado automaticamente antes de cada shutdown/restart."
} else {
    Write-Error "Falha ao registrar tarefa."
}
