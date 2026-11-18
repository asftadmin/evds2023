# Comprueba el Programador real con datos sintéticos; elimina solo su tarea temporal.
$ErrorActionPreference='Stop'
$raizPlanner=[IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$nombrePlanner='HU03-Prueba-'+[guid]::NewGuid().ToString('N')
$salidaPlanner=Join-Path $raizPlanner ('.cache\'+$nombrePlanner+'.txt')
$runnerPlanner=Join-Path $raizPlanner ('.cache\'+$nombrePlanner+'.ps1')
$registradaPlanner=$false
try {
    # Ningún valor proviene de entrada de usuario. Crea un runner privado de prueba.
    $contenidoPlanner = "& 'C:\xampp\php\php.exe' '"+(Join-Path $PSScriptRoot 'planner.php')+"' --db *> '"+$salidaPlanner+"'`r`nexit `$LASTEXITCODE"
    Set-Content -LiteralPath $runnerPlanner -Value $contenidoPlanner -Encoding UTF8
    $accionPlanner=New-ScheduledTaskAction -Execute 'powershell.exe' -Argument ('-NoProfile -NonInteractive -WindowStyle Hidden -File "{0}"' -f $runnerPlanner)
    $identidadPlanner=[Security.Principal.WindowsIdentity]::GetCurrent().Name
    $principalPlanner=New-ScheduledTaskPrincipal -UserId $identidadPlanner -LogonType S4U -RunLevel Limited
    $triggerPlanner=New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(5)
    Register-ScheduledTask -TaskName $nombrePlanner -Action $accionPlanner -Trigger $triggerPlanner -Principal $principalPlanner | Out-Null
    $registradaPlanner=$true
    Start-ScheduledTask -TaskName $nombrePlanner
    $limitePlanner=(Get-Date).AddSeconds(45)
    do {
        Start-Sleep -Milliseconds 500
        $estadoPlanner=Get-ScheduledTask -TaskName $nombrePlanner
    } while ($estadoPlanner.State -eq 'Running' -and (Get-Date) -lt $limitePlanner)
    $infoPlanner=Get-ScheduledTaskInfo -TaskName $nombrePlanner
    if ($estadoPlanner.State -eq 'Running' -or $infoPlanner.LastTaskResult -ne 0 -or -not (Test-Path -LiteralPath $salidaPlanner)) {
        throw ('La ejecución programada no terminó correctamente. Código: '+$infoPlanner.LastTaskResult)
    }
    $resultadoPlanner=Get-Content -LiteralPath $salidaPlanner -Raw
    if ($resultadoPlanner -notmatch 'OK: \d+ verificaciones Planner') { throw 'La suite programada no informó éxito.' }
    Write-Output ('OK: Programador de tareas real -> PHP CLI -> PostgreSQL aislado. '+$resultadoPlanner.Trim())
} finally {
    if ($registradaPlanner) {
        Stop-ScheduledTask -TaskName $nombrePlanner -ErrorAction SilentlyContinue
        Unregister-ScheduledTask -TaskName $nombrePlanner -Confirm:$false
    }
    # Rutas exactas dentro de .cache, sin operaciones recursivas.
    Remove-Item -LiteralPath $runnerPlanner,$salidaPlanner -Force -ErrorAction SilentlyContinue
}
