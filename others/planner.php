<?php
// Exclusivo CLI: ni cierre ni diagnóstico se exponen como URL pública.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/Planner.php';
require_once __DIR__.'/../controller/curl.php';
require_once __DIR__.'/../controller/PlannerService.php';
$modelo = new Planner();
try {
    $op = $argv[1] ?? '';
    if ($op === 'migrar' && in_array('--aplicar',$argv,true)) {
        $modelo->aplicarMigracion();
        echo "Migración HU-03 aplicada. No se asociaron planes ni se crearon cierres.\n";
        exit;
    }
    if ($op === 'esquema') {
        echo json_encode($modelo->esquema(),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
        exit;
    }
    if ($op === 'sondeo') {
        $s = new PlannerService($modelo);
        $grupos = $s->grupos(); $planes=0;
        foreach ($grupos as $g) { $planes += count($s->planes($g['id'])); }
        echo json_encode(['grupos'=>count($grupos),'planes'=>$planes]),PHP_EOL;
        exit;
    }
    if ($op !== 'programado') { throw new InvalidArgumentException('Uso: php others/planner.php esquema|sondeo|programado|migrar --aplicar'); }
    if (!$modelo->disponible()) { throw new DomainException('Migración HU-03 pendiente de autorización/despliegue.'); }
    $s = new PlannerService($modelo);
    $ahora = new DateTimeImmutable('now',new DateTimeZone('America/Bogota'));
    $actual = $ahora->format('Y-m-01');
    $anterior = $ahora->modify('first day of last month')->format('Y-m-01');
    $fallos = 0;
    // Reintenta abiertos vencidos; el último mes también se registra si aún no tiene plan.
    $periodos = array_unique(array_merge($modelo->pendientes($actual),[$anterior,$actual]));
    foreach ($periodos as $periodo) {
        $inicio = (new DateTimeImmutable())->format(DATE_ATOM);
        try {
            if (!$modelo->periodo($periodo)) {
                $modelo->registrar($periodo,$periodo<$actual?'cierre':'captura','sin_asociacion',$inicio);
                echo $periodo," sin_asociacion\n"; $fallos++; continue;
            }
            echo $periodo,' ',$s->ejecutar($periodo,$periodo<$actual),PHP_EOL;
        } catch (Throwable $e) {
            // No imprimir mensajes externos ni traza que pueda incluir argumentos confidenciales.
            fwrite(STDERR,$periodo." fallo_reintentable\n"); $fallos++;
        }
    }
    exit($fallos ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR,"Planner: operación fallida (".get_class($e)."). Revise configuración y bitácora.\n");
    exit(1);
}
