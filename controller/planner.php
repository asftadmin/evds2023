<?php
ob_start();
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$plannerRespondido = false;
/** Garantiza una única respuesta JSON y descarta salidas de componentes heredados. */
function responderPlanner($data, int $codigo=200): never
{
    $GLOBALS['plannerRespondido']=true;
    if (ob_get_length()) { ob_clean(); }
    http_response_code($codigo);
    echo json_encode(['status'=>$codigo<400?'success':'error','data'=>$data],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
register_shutdown_function(static function () {
    if (!$GLOBALS['plannerRespondido']) { responderPlanner(['message'=>'No fue posible completar la operación.'],500); }
});
set_exception_handler(static function ($e) {
    $validacion = $e instanceof DomainException || $e instanceof InvalidArgumentException;
    if (!$validacion) { error_log('[Planner] Operación fallida: '.get_class($e)); }
    $datos=['message'=>$validacion ? $e->getMessage() : 'No fue posible consultar Planner. Intente nuevamente.'];
    if ($e instanceof PlannerConfirmacionPeriodo) {
        $datos['code']='confirmacion_periodo_requerida';
        $datos['plan']=array_intersect_key($e->plan,array_flip(['grupo_id','grupo_nombre','plan_id','plan_nombre']));
    }
    responderPlanner($datos,$validacion ? 409 : 502);
});
require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/Usuarios.php';
require_once __DIR__.'/../models/Planner.php';
require_once __DIR__.'/curl.php';
require_once __DIR__.'/PlannerService.php';
$actor=filter_var($_SESSION['user_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$actor) { responderPlanner(['message'=>'Debe iniciar sesión.'],401); }
if (!(new Usuario())->autorizado_microsoft365($actor)) { responderPlanner(['message'=>'Acceso administrativo no autorizado.'],403); }
if (($_SERVER['REQUEST_METHOD']??'')!=='POST') { header('Allow: POST'); responderPlanner(['message'=>'Utilice POST.'],405); }
$csrf=$_POST['csrf_token']??null;
if (!is_string($csrf) || empty($_SESSION['planner_csrf']) || !hash_equals($_SESSION['planner_csrf'],$csrf)) {
    responderPlanner(['message'=>'El formulario venció. Recargue la página.'],403);
}
$op=$_GET['op']??null;
if (!is_string($op) || !in_array($op,['inicial','grupos','planes','consultar','asociar'],true)) {
    responderPlanner(['message'=>'Operación inválida.'],400);
}
session_write_close();
(new Usuario())->verificar_esquema_microsoft365();
$m=new Planner(); $s=new PlannerService($m);
if ($op==='inicial') { responderPlanner(['empleados'=>$m->empleados(),'persistencia'=>$m->disponible()]); }
if ($op==='grupos') { responderPlanner($s->grupos()); }
if ($op==='planes') { responderPlanner($s->planes(PlannerService::identificador($_POST['grupo_id']??null))); }
$periodo=PlannerService::periodo($_POST['anio']??null,$_POST['mes']??null);
if ($op==='asociar') {
    $motivo=$_POST['motivo_periodo']??'';
    if (!is_string($motivo)) { throw new InvalidArgumentException('Motivo de validación inválido.'); }
    $s->asociar($periodo,PlannerService::identificador($_POST['grupo_id']??null),
        PlannerService::identificador($_POST['plan_id']??null),$actor,($_POST['confirmar_periodo']??null)==='1',$motivo);
    responderPlanner(['message'=>'Asociación mensual validada y conservada.']);
}
$plan=null;
if (!empty($_POST['grupo_id']) && !empty($_POST['plan_id'])) {
    $plan=['grupo_id'=>PlannerService::identificador($_POST['grupo_id']),'plan_id'=>PlannerService::identificador($_POST['plan_id'])];
}
responderPlanner($s->consultar($periodo,$plan));
