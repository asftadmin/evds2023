<?php
// Motor real y Graph/modelo sintéticos: el controlador real se prueba en procesos aislados.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
if (($argv[1]??'')==='--caso') {
    $caso=$argv[2];
    $_SERVER['REQUEST_METHOD']=$caso==='metodo'?'GET':'POST';
    $_SESSION=$caso==='sin_sesion'?[]:['user_id'=>1,'planner_csrf'=>'csrf-prueba'];
    $_GET['op']=str_starts_with($caso,'asociar')?'asociar':'consultar';
    if ($caso==='op_invalida') { $_GET['op']='borrar'; }
    $_POST=['anio'=>'2026','mes'=>str_contains($caso,'noviembre')?'11':'10','grupo_id'=>'grupo','plan_id'=>'plan',
        'csrf_token'=>$caso==='csrf'?'incorrecto':'csrf-prueba'];
    if ($caso==='asociar_ambiguo_confirmado' || $caso==='asociar_ambiguo_sin_motivo') {
        $_POST['confirmar_periodo']='1';
        $_POST['motivo_periodo']=$caso==='asociar_ambiguo_sin_motivo'?'':'Verificado por el administrador con el responsable del plan.';
    }
    if ($caso==='historico_noviembre' || $caso==='historico_correcto') { unset($_POST['grupo_id'],$_POST['plan_id']); }
    register_shutdown_function(static function () { fwrite(STDERR,'HTTP='.http_response_code()); });
    class Usuario {
        public function autorizado_microsoft365($id) { return $GLOBALS['caso']!=='sin_permiso'; }
        public function verificar_esquema_microsoft365() {}
    }
    class Planner {
        public function periodo($p) {
            if (!in_array($GLOBALS['caso'],['historico_noviembre','historico_correcto'],true)) { return null; }
            return ['estado'=>'Cerrado','plan_id'=>'plan','grupo_id'=>'grupo','plan_nombre'=>'OCTUBRE 2026',
                'informe'=>json_encode(['periodo'=>$p,'empleados'=>[],'plan'=>['plan_nombre'=>'OCTUBRE 2026']])];
        }
        public function verificarPlanDisponible($id,$p) {
            if ($GLOBALS['caso']==='plan_reutilizado') { throw new DomainException('Este Plan ID ya está asociado al período 09/2026. No puede reutilizarse en otro mes.'); }
        }
        public function confirmacionPeriodo($p,$plan) { return $GLOBALS['caso']==='consultar_ambiguo_validado'; }
        public function empleados() { return []; }
        public function asociar($p,$plan,$actor,$motivo=null) {
            if ($plan['periodo_ambiguo'] && (!$motivo || $actor!==1)) { throw new RuntimeException('Se omitió la decisión administrativa.'); }
        }
    }
    class CurlController {
        public static function requestMicrosoftGraph($url,$method) {
            if ($method!=='GET' || str_starts_with($GLOBALS['caso'],'historico')) { throw new RuntimeException('No debe consultarse Graph en el histórico.'); }
            if ($GLOBALS['caso']==='graph_error') { throw new RuntimeException('secreto-remoto-no-publicar'); }
            $titulo=str_contains($GLOBALS['caso'],'ambiguo')?'OCTUBRE':'OCTUBRE 2026';
            if (str_starts_with($url,'groups?')) { return (object)['value'=>[(object)['id'=>'grupo','displayName'=>'GRUPO 2026']]]; }
            if (str_starts_with($url,'groups/')) { return (object)['value'=>[(object)['id'=>'plan','title'=>$titulo]]]; }
            if (str_ends_with($url,'/tasks')) { return (object)['value'=>[]]; }
            return (object)['id'=>'plan','title'=>$titulo,'createdDateTime'=>'2026-10-01T00:00:00Z'];
        }
    }
    require_once __DIR__.'/../controller/PlannerService.php';
    $codigo=preg_replace('/^require_once .*;\r?$/m','',file_get_contents(__DIR__.'/../controller/planner.php'));
    eval(substr($codigo,5)); exit;
}
class Conectar {}
require_once __DIR__.'/../models/Planner.php';
require_once __DIR__.'/../controller/PlannerService.php';
$checks=0;
/** Verifica títulos mensuales sin depender del entorno, del grupo ni de datos de creación. */
foreach (['OCTUBRE 2026','Plan octubre2026','OCT. 2026','2026-10','10/2026','October 2026'] as $nombre) {
    if (PlannerService::periodoDelPlan($nombre,'2026-10-01')) { throw new RuntimeException('Título explícito marcado ambiguo: '.$nombre); }
    $checks++;
}
foreach (['OCTUBRE','Plan de actividades','OCTUBRE 2025 / OCTUBRE 2026','OCTUBRE-NOVIEMBRE 2026','2026'] as $nombre) {
    if (!PlannerService::periodoDelPlan($nombre,'2026-10-01')) { throw new RuntimeException('Se aceptó un título ambiguo: '.$nombre); }
    $checks++;
}
foreach (['OCTUBRE 2026','OCTUBRE','2026-10','10/2026','NOVIEMBRE 2025'] as $nombre) {
    try { PlannerService::periodoDelPlan($nombre,'2026-11-01'); }
    catch (DomainException $e) { $checks++; continue; }
    throw new RuntimeException('Se aceptó un período incorrecto: '.$nombre);
}
foreach (['OCTUBRE 2026 (revisión 2025)','2026-10 copia 2025'] as $nombre) {
    try { PlannerService::periodoDelPlan($nombre,'2025-10-01'); }
    catch (DomainException $e) { $checks++; continue; }
    throw new RuntimeException('Un año incidental permitió omitir la pareja explícita: '.$nombre);
}
try { PlannerService::periodoDelPlan('OCTUBRE 2026 NOVIEMBRE 2027','2026-11-01'); }
catch (DomainException $e) { $checks++; $cruceRechazado=true; }
if (empty($cruceRechazado)) { throw new RuntimeException('Se mezclaron meses y años de dos períodos distintos.'); }
$casos=['sin_sesion'=>401,'sin_permiso'=>403,'metodo'=>405,'csrf'=>403,'op_invalida'=>400,
    'consultar_octubre'=>200,'consultar_noviembre'=>409,'asociar_octubre'=>200,'asociar_noviembre'=>409,
    'consultar_ambiguo'=>409,'asociar_ambiguo'=>409,'asociar_ambiguo_sin_motivo'=>409,
    'asociar_ambiguo_confirmado'=>200,'consultar_ambiguo_validado'=>200,'plan_reutilizado'=>409,
    'historico_noviembre'=>409,'historico_correcto'=>200,'graph_error'=>502];
foreach ($casos as $caso=>$codigo) {
    $proceso=proc_open([PHP_BINARY,__FILE__,'--caso',$caso],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    $salida=stream_get_contents($pipes[1]); $errores=stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); proc_close($proceso);
    $r=json_decode($salida,true,512,JSON_THROW_ON_ERROR);
    if (!str_contains($errores,'HTTP='.$codigo) || $r['status']!==($codigo===200?'success':'error')
        || str_contains($salida,'secreto-remoto-no-publicar')) { throw new RuntimeException('Error HTTP/JSON: '.$caso); }
    if (in_array($caso,['consultar_ambiguo','asociar_ambiguo'],true) && ($r['data']['code']??null)!=='confirmacion_periodo_requerida') {
        throw new RuntimeException('Falta solicitud explícita de confirmación: '.$caso);
    }
    $checks++;
}
echo "OK: $checks verificaciones de período/HTTP, sin escrituras productivas.\n";
