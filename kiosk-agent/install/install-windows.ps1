#Requires -RunAsAdministrator
<#
  Instala el kiosk-agent para que arranque solo cada vez que la PC del
  kiosko prende o el usuario inicia sesion, usando el Programador de
  Tareas de Windows (no un servicio): el agente imprime con pdf-to-printer,
  que necesita correr en la sesion del usuario que tiene la impresora
  mapeada, no como SYSTEM en segundo plano sin sesion interactiva.

  Uso (PowerShell como Administrador):
    .\install-windows.ps1 -ExePath "C:\kiosk-agent\kiosk-agent-win.exe"

  Requisitos previos:
    - El ejecutable ya construido (npm run build:exe) copiado a la PC del kiosko.
    - Un .env junto al .exe con KIOSK_API_TOKEN, CENTRAL_URL, PRINTER_NAME, etc.
    - La PC configurada con inicio de sesion automatico del usuario del kiosko
      (o se ejecuta este script ya logueado como ese usuario).
#>

param(
    [Parameter(Mandatory = $true)]
    [string]$ExePath,

    [string]$TaskName = "KioskAgent",

    [string]$UserId = "$env:USERDOMAIN\$env:USERNAME"
)

if (-not (Test-Path $ExePath)) {
    Write-Error "No se encontro el ejecutable en '$ExePath'. Compila primero con 'npm run build:exe' y copia el .exe a esta PC."
    exit 1
}

$workingDir = Split-Path -Path $ExePath -Parent

if (-not (Test-Path (Join-Path $workingDir ".env"))) {
    Write-Warning "No hay un .env junto al ejecutable en '$workingDir'. El agente no podra autenticarse sin KIOSK_API_TOKEN."
}

# Si ya existe una tarea con este nombre (reinstalacion), la reemplazamos.
Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue

$action = New-ScheduledTaskAction -Execute $ExePath -WorkingDirectory $workingDir

# Arranca al iniciar sesion el usuario del kiosko (necesita ver la impresora
# mapeada en esa sesion) y tambien reintenta si la tarea "termina" sola.
$trigger = New-ScheduledTaskTrigger -AtLogOn -User $UserId

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit (New-TimeSpan -Days 0)

$principal = New-ScheduledTaskPrincipal -UserId $UserId -LogonType Interactive -RunLevel Limited

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Force

Write-Host "Tarea '$TaskName' registrada. Arrancara automaticamente cuando '$UserId' inicie sesion, y Windows la reintentara sola si el proceso se cae (hasta 999 veces, cada 1 minuto)." -ForegroundColor Green
Write-Host "Para arrancarla ahora sin reiniciar: Start-ScheduledTask -TaskName '$TaskName'"
Write-Host "Para ver su estado: Get-ScheduledTask -TaskName '$TaskName' | Get-ScheduledTaskInfo"
