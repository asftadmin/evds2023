param(
    [switch]$Aplicar,
    [string]$Php = 'C:\xampp\php\php.exe',
    [string]$Nombre = 'ControlPersonal-Planner-HU03'
)
$ErrorActionPreference = 'Stop'
# Instalación explícita, sin credenciales en argumentos, XML ni registros.
$raizPlanner = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$scriptPlanner = Join-Path $raizPlanner 'others\planner.php'
if (-not (Test-Path -LiteralPath $Php) -or -not (Test-Path -LiteralPath $scriptPlanner)) {
    throw 'Revise las rutas PHP y proyecto.'
}
$accion = New-ScheduledTaskAction -Execute $Php -Argument ('"{0}" programado' -f $scriptPlanner) -WorkingDirectory $raizPlanner
# Inicio de cada minuto: la primera ejecución posterior a 00:00 cierra el mes anterior.
$inicioPlanner = (Get-Date).Date.AddMinutes(1)
$trigger = New-ScheduledTaskTrigger -Once -At $inicioPlanner -RepetitionInterval (New-TimeSpan -Minutes 1)
$ajustes = New-ScheduledTaskSettingsSet -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 15) -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)
# S4U ejecuta sin sesión abierta y sin almacenar contraseña; Graph utiliza Client Credentials.
$identidadPlanner = [Security.Principal.WindowsIdentity]::GetCurrent().Name
$principal = New-ScheduledTaskPrincipal -UserId $identidadPlanner -LogonType S4U -RunLevel Limited
$tarea = New-ScheduledTask -Action $accion -Trigger $trigger -Settings $ajustes -Principal $principal -Description 'HU03: capturas Planner por minuto y cierre mensual idempotente. Requiere entorno Graph propio de PHP CLI.'
if (-not $Aplicar) {
    [pscustomobject]@{Nombre=$Nombre; PHP=$Php; Script=$scriptPlanner; Frecuencia='Cada minuto'; Identidad=$identidadPlanner; Aplicada=$false}
    exit
}
if (Get-ScheduledTask -TaskName $Nombre -ErrorAction SilentlyContinue) {
    throw 'La tarea ya existe. Revise su configuración antes de sustituirla.'
}
Register-ScheduledTask -TaskName $Nombre -InputObject $tarea | Out-Null
Start-ScheduledTask -TaskName $Nombre
Get-ScheduledTask -TaskName $Nombre | Select-Object TaskName,State
