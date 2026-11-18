<?php
// Auditoría de solo lectura sobre evidencias congeladas; nunca ejecuta cierres ni Graph.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!in_array('--live',$argv,true)) { fwrite(STDERR,"Uso: php tests/planner_historicos.php --live\n"); exit(1); }
require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/Planner.php';
require_once __DIR__.'/../controller/PlannerService.php';
try {
    $m=new Planner(); $originales=$m->historicos(); $impacto=[];
    foreach ($originales as $p) {
        $r=json_decode($p['informe'],true,512,JSON_THROW_ON_ERROR);
        if (!isset($r['tareas_graph'],$r['empleados'],$r['consultado_en'])) {
            $impacto[]=['periodo'=>$p['periodo'],'requiere_revision'=>'Evidencia insuficiente para simular']; continue;
        }
        // Usa identidades, asignaciones y momento de consulta originales, nunca el estado Graph actual.
        $nuevo=PlannerService::calcular($r['tareas_graph'],$r['empleados'],$p['periodo'],new DateTimeImmutable($r['consultado_en']));
        $porId=array_column($nuevo['empleados'],null,'id_empl'); $cambios=[];
        foreach ($r['empleados'] as $e) {
            $n=$porId[$e['id_empl']];
            if ($e['cumplimiento'] != $n['cumplimiento']) {
                $cambios[]=['id_empl'=>$e['id_empl'],'anterior'=>$e['cumplimiento'],'propuesto'=>$n['cumplimiento']];
            }
        }
        $impacto[]=['periodo'=>$p['periodo'],'empleados_afectados'=>count($cambios),'simulacion_sin_guardar'=>$cambios];
    }
    if ($m->historicos()!==$originales) { throw new RuntimeException('Los cierres cambiaron durante la auditoría.'); }
    echo json_encode(['cierres_revisados'=>count($originales),'historicos_conservados'=>true,'impacto'=>$impacto],
        JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR,'No fue posible auditar los cierres: '.get_class($e).PHP_EOL); exit(1);
}
