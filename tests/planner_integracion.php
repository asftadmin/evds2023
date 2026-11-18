<?php
// Lecturas reales de Apache/Graph. Crea y elimina solamente una sesión de prueba nueva.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
if (!in_array('--live',$argv,true)) { fwrite(STDERR,"Uso: php tests/planner_integracion.php --live\n"); exit(1); }
ob_start();
eval(str_replace('session_start();','',substr(file_get_contents(__DIR__.'/../config/conexion.php'),5)));
require_once __DIR__.'/../models/Usuarios.php';
require_once __DIR__.'/../models/Planner.php';
require_once __DIR__.'/../controller/PlannerService.php';
class ConexionPlannerLive extends Conectar { public function abrir() { return parent::Conexion(); } }
function httpPlannerLive($ruta,$sesion=null,$datos=null) {
    $ch=curl_init('http://127.0.0.1/evds2023/'.$ruta);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>180,CURLOPT_FOLLOWLOCATION=>false]);
    if ($sesion) { curl_setopt($ch,CURLOPT_COOKIE,session_name().'='.$sesion); }
    if ($datos!==null) { curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($datos)); }
    $texto=curl_exec($ch); $codigo=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if ($texto===false) { throw new RuntimeException('Apache no disponible.'); }
    return [$codigo,$texto];
}
function jsonPlannerLive($r,$codigo) {
    if ($r[0]!==$codigo) { throw new RuntimeException('Código HTTP inesperado: '.$r[0]); }
    return json_decode($r[1],true,512,JSON_THROW_ON_ERROR);
}
$sesion=null; $salida=0;
try {
    $db=(new ConexionPlannerLive())->abrir();
    $usuarios=$db->query('SELECT user_id FROM public.usuarios ORDER BY user_id')->fetchAll(PDO::FETCH_COLUMN);
    $actor=null; $modelo=new Usuario();
    foreach ($usuarios as $id) { if ($modelo->autorizado_microsoft365($id)) { $actor=$id; break; } }
    if (!$actor) { throw new RuntimeException('Falta administrador autorizado.'); }
    $csrf=bin2hex(random_bytes(32)); $sesion=session_create_id('hu03-');
    session_id($sesion); session_start(); $_SESSION=['user_id'=>$actor,'planner_csrf'=>$csrf]; session_write_close();
    jsonPlannerLive(httpPlannerLive('controller/planner.php?op=inicial'),401);
    jsonPlannerLive(httpPlannerLive('controller/planner.php?op=inicial',$sesion),405);
    jsonPlannerLive(httpPlannerLive('controller/planner.php?op=inicial',$sesion,['csrf_token'=>'incorrecto']),403);
    $datos=['csrf_token'=>$csrf];
    $inicial=jsonPlannerLive(httpPlannerLive('controller/planner.php?op=inicial',$sesion,$datos),200)['data'];
    $asociacionesAntes=(new Planner())->asociaciones();
    $grupos=jsonPlannerLive(httpPlannerLive('controller/planner.php?op=grupos',$sesion,$datos),200)['data'];
    $consultas=0; $tareas=0;
    foreach ($grupos as $g) {
        if (!str_contains(mb_strtoupper($g['displayName']??'','UTF-8'),'OCTUBRE')) { continue; }
        $planes=jsonPlannerLive(httpPlannerLive('controller/planner.php?op=planes',$sesion,$datos+['grupo_id'=>$g['id']]),200)['data'];
        $planes=array_values(array_filter($planes,static function ($plan) {
            try { return !PlannerService::periodoDelPlan($plan['title'],'2026-10-01'); } catch (DomainException $e) { return false; }
        }));
        if (!$planes) { continue; }
        // La consulta incorrecta se rechaza desde Apache aun si el cliente envía IDs reales.
        $incorrecta=jsonPlannerLive(httpPlannerLive('controller/planner.php?op=consultar',$sesion,
            $datos+['anio'=>'2026','mes'=>'11','grupo_id'=>$g['id'],'plan_id'=>$planes[0]['id']]),409);
        if (!str_contains($incorrecta['data']['message'],'11/2026')) { throw new RuntimeException('Faltó rechazar el período incorrecto.'); }
        jsonPlannerLive(httpPlannerLive('controller/planner.php?op=asociar',$sesion,
            $datos+['anio'=>'2026','mes'=>'11','grupo_id'=>$g['id'],'plan_id'=>$planes[0]['id'],'confirmar_periodo'=>'1',
                'motivo_periodo'=>'Confirmación sintética que no puede omitir la coincidencia obligatoria.']),409);
        $uso=array_values(array_filter((new Planner())->asociaciones(),static fn($p)=>$p['plan_id']===$planes[0]['id'] && $p['periodo']!=='2026-10-01'));
        // Consulta previa explícita: no valida ni persiste asociación productiva.
        $r=jsonPlannerLive(httpPlannerLive('controller/planner.php?op=consultar',$sesion,
            $datos+['anio'=>'2026','mes'=>'10','grupo_id'=>$g['id'],'plan_id'=>$planes[0]['id']]),$uso?409:200)['data'];
        if ($uso && !str_contains($r['message'],'ya está asociado')) { throw new RuntimeException('Faltó informar el uso previo del plan.'); }
        if (!$uso && $r['estado']!=='Consulta histórica reconstruida') { throw new RuntimeException('Origen histórico no identificado.'); }
        $consultas++; $tareas=count($r['tareas_graph']??[]);
        if ($uso) { echo "OK: asociación previa incompatible detectada, conservada y bloqueada.\n"; break; }
        if ($tareas || $consultas>=10) { break; }
    }
    if (!$consultas) { throw new RuntimeException('No se encontró un plan para probar tareas.'); }
    if ((new Planner())->asociaciones()!==$asociacionesAntes) { throw new RuntimeException('Una asociación cambió durante la prueba.'); }
    echo json_encode(['resultado'=>'OK','grupos'=>count($grupos),'comprobaciones_plan'=>$consultas,'tareas_obtenidas'=>$tareas,
        'empleados'=>count($inicial['empleados']),'asociaciones_conservadas'=>true,'escrituras_productivas'=>0]),PHP_EOL;
} catch (Throwable $e) { fwrite(STDERR,'Validación Planner real fallida: '.$e->getMessage().PHP_EOL); $salida=1; }
finally { if ($sesion) { session_id($sesion); session_start(); $_SESSION=[]; session_destroy(); } }
ob_end_flush(); exit($salida);
