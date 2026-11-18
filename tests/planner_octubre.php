<?php
// Caso real solicitado, solo lectura. No vincula usuarios, valida asociaciones ni recalcula cierres.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!in_array('--live',$argv,true)) { fwrite(STDERR,"Uso: php tests/planner_octubre.php --live\n"); exit(1); }
require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/Planner.php';
require_once __DIR__.'/../controller/curl.php';
require_once __DIR__.'/../controller/PlannerService.php';
/** Exige colección completa del transporte existente antes de comprobar tareas reales. */
function coleccionOctubre(string $url): array {
    $r=CurlController::requestMicrosoftGraph($url,'GET');
    if (!isset($r->value) || !is_array($r->value) || isset($r->{'@odata.nextLink'})) { throw new RuntimeException('Colección incompleta.'); }
    return json_decode(json_encode($r->value,JSON_THROW_ON_ERROR),true,512,JSON_THROW_ON_ERROR);
}
/** Compara nombres completos sin inferir identidades a partir de nombres parciales. */
function nombreOctubre(string $nombre): string { return mb_strtoupper(trim(preg_replace('/\s+/u',' ',$nombre)),'UTF-8'); }
try {
    $m=new Planner(); $s=new PlannerService($m); $periodo='2026-10-01';
    $objetivo='CRISTIAN GIOVANNY ARCINIEGAS PORTILLA';
    $empleados=$m->empleados();
    $locales=array_values(array_filter($empleados,static fn($e)=>nombreOctubre($e['nomb_empl']??'')===$objetivo));
    if (count($locales)!==1) { throw new RuntimeException('El nombre completo del empleado no resuelve una identidad local única.'); }
    $oid=$locales[0]['user_graph_id']; $vinculado=(bool)$oid;
    if (!$oid) {
        // Diagnóstico de identidad Graph sin crear una vinculación interna por nombre.
        $usuarios=coleccionOctubre('users?$select=id,displayName');
        $coincidencias=array_values(array_filter($usuarios,static fn($u)=>nombreOctubre($u['displayName']??'')===$objetivo));
        if (count($coincidencias)!==1) { throw new RuntimeException('Sin Object ID vinculado ni identidad Graph de nombre completo único.'); }
        $oid=$coincidencias[0]['id'];
    }
    $p=$m->periodo($periodo); $antes=$p ? hash('sha256',json_encode($p,JSON_THROW_ON_ERROR)) : null;
    $candidatos=[]; $ambiguos=[];
    foreach ($s->grupos() as $g) {
        if ($p ? $g['id']!==$p['grupo_id'] : !str_contains(nombreOctubre($g['displayName']??''),'OCTUBRE')) { continue; }
        foreach ($s->planes($g['id']) as $plan) {
            if ($p && $plan['id']!==$p['plan_id']) { continue; }
            $nombre=nombreOctubre(($g['displayName']??'').' '.($plan['title']??''));
            if (!$p && !preg_match('/\b2026\b/',$nombre)) { $ambiguos[]=['grupo'=>$g['displayName'],'plan'=>$plan['title']]; continue; }
            $candidatos[]=['grupo_id'=>$g['id'],'grupo_nombre'=>$g['displayName'],'plan_id'=>$plan['id'],'plan_nombre'=>$plan['title']];
        }
    }
    if (count($candidatos)!==1) {
        echo json_encode(['planes_con_anio'=>count($candidatos),'nombres_sin_anio'=>$ambiguos],JSON_UNESCAPED_UNICODE),PHP_EOL;
        throw new RuntimeException('El plan OCTUBRE 2026 no se identificó inequívocamente; no se supone el año.');
    }
    $plan=$candidatos[0]; $s->validarPlan($plan['grupo_id'],$plan['plan_id'],$periodo);
    $tareas=coleccionOctubre('planner/plans/'.$plan['plan_id'].'/tasks');
    $asignadas=array_values(array_filter($tareas,static fn($t)=>array_key_exists(strtolower($oid),array_change_key_case($t['assignments']??[],CASE_LOWER))));
    $ahora=new DateTimeImmutable('now',new DateTimeZone('America/Bogota'));
    $mismoDia=[];
    foreach ($asignadas as $t) {
        $r=PlannerService::evaluar($t,$periodo,$ahora);
        if ($r['vencimiento'] && $r['finalizacion'] && substr($r['vencimiento'],0,10)===substr($r['finalizacion'],0,10)) {
            $mismoDia[]=['tarea'=>$t['title'],'vencimiento'=>$r['vencimiento'],'finalizacion'=>$r['finalizacion'],'nota'=>$r['nota']];
        }
    }
    $informe=PlannerService::calcular($asignadas,$empleados,$periodo,$ahora);
    $empleado=array_values(array_filter($informe['empleados'],static fn($e)=>$e['id_empl']===$locales[0]['id_empl']))[0];
    $despues=$m->periodo($periodo);
    if (($despues ? hash('sha256',json_encode($despues,JSON_THROW_ON_ERROR)) : null)!==$antes) { throw new RuntimeException('El período cambió durante la comprobación.'); }
    $cumplidas=count(array_filter($mismoDia,static fn($t)=>$t['nota']===100));
    echo json_encode(['plan'=>$plan['plan_nombre'],'grupo'=>$plan['grupo_nombre'],'vinculo_interno'=>$vinculado,
        'tareas_asignadas'=>count($asignadas),'mismo_dia'=>count($mismoDia),'mismo_dia_100'=>$cumplidas,
        'cumplimiento_interno'=>$empleado['cumplimiento'],'estado_historico'=>$p['estado']??'Sin asociación persistida',
        'historico_conservado'=>true,'detalle_mismo_dia'=>$mismoDia],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
    if (count($mismoDia)!==7 || $cumplidas!==7) { throw new RuntimeException('Los datos actuales no confirman las siete tareas esperadas al 100%. Revise el detalle.'); }
    echo "OK: siete tareas reales del mismo día reciben 100%; sin escrituras.\n";
} catch (Throwable $e) {
    fwrite(STDERR,'Validación OCTUBRE: '.$e->getMessage().PHP_EOL); exit(1);
}
