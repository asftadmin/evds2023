<?php
// CLI: reglas puras y, con --db, migración/modelo reales en esquema sintético desechable.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
class Conectar { public static $pruebaDb; protected function Conexion() { return self::$pruebaDb; } }
require_once __DIR__.'/../models/Planner.php';
require_once __DIR__.'/../controller/PlannerService.php';
$checks=0;
function comprobarPlanner($condicion,$mensaje) {
    global $checks;
    if (!$condicion) { throw new RuntimeException($mensaje); } $checks++;
}
function rechazarPlanner(callable $fn) {
    try { $fn(); } catch (Throwable $e) { comprobarPlanner(true,'rechazo'); return; }
    throw new RuntimeException('Se aceptó una operación inválida.');
}
function tareaPlanner($id,$fin,$vence='2026-06-20T18:00:00Z',$porcentaje=100,$responsables=null) {
    return ['id'=>(string)$id,'title'=>'Tarea '.$id,'percentComplete'=>$porcentaje,'dueDateTime'=>$vence,
        'completedDateTime'=>$fin,'assignments'=>$responsables??['00000000-0000-4000-8000-000000000001'=>[]]];
}
$ahora=new DateTimeImmutable('2026-07-01T05:00:00Z');
$p='2026-06-01';
$casos=[
    [tareaPlanner(1,'2026-06-20T18:00:00Z'),100,'Puntual'],
    [tareaPlanner(2,'2026-06-20T18:00:01Z'),100,'Puntual'],
    [tareaPlanner(3,'2026-07-01T04:59:59.9999999Z'),50,'Tardía'],
    [tareaPlanner(4,'2026-07-01T05:00:00Z'),0,'Incumplida'],
    [tareaPlanner(5,null,'2026-06-20T18:00:00Z',0),0,'Incumplida'],
    [tareaPlanner(6,null,null,0),null,'Requiere revisión'],
    [tareaPlanner(7,null),null,'Requiere revisión'],
    [tareaPlanner(8,'no-fecha'),null,'Requiere revisión'],
    [tareaPlanner(9,'2026-06-21T18:00:00Z','2026-02-30T18:00:00Z'),null,'Requiere revisión'],
    [tareaPlanner(10,'2026-06-21T18:00:00Z','2026-06-20T18:00:00Z',20),null,'Requiere revisión'],
    [tareaPlanner(11,'2026-07-02T05:00:00Z'),null,'Requiere revisión'],
    [tareaPlanner(12,'2026-06-20T13:00:00-05:00'),100,'Puntual']
];
foreach ($casos as [$t,$nota,$estado]) {
    $r=PlannerService::evaluar($t,$p,$ahora);
    comprobarPlanner($r['nota']===$nota && $r['estado']===$estado,'Regla tarea '.$t['id']);
}
$r=PlannerService::evaluar(tareaPlanner(13,null,'2026-06-20T18:00:00Z',50),$p,new DateTimeImmutable('2026-06-25T00:00:00Z'));
comprobarPlanner($r['estado']==='En proceso' && $r['nota']===0,'Pendiente provisional');
comprobarPlanner(PlannerService::corte('2024-02-01')->format(DATE_ATOM)==='2024-03-01T00:00:00-05:00','Febrero bisiesto');
comprobarPlanner(PlannerService::corte('2026-12-01')->format('Y-m-d')==='2027-01-01','Cambio de año');
foreach ([[2026,0],[2026,13],['2026x',1],[[],1],[2026,[]]] as [$a,$m]) { rechazarPlanner(fn()=>PlannerService::periodo($a,$m)); }
$empleados=[['id_empl'=>11,'nomb_empl'=>'Uno','user_id'=>1,'user_graph_id'=>'00000000-0000-4000-8000-000000000001'],
    ['id_empl'=>12,'nomb_empl'=>'Dos','user_id'=>2,'user_graph_id'=>'00000000-0000-4000-8000-000000000002'],
    ['id_empl'=>13,'nomb_empl'=>'Sin tareas','user_id'=>3,'user_graph_id'=>null]];
$tareas=[];
for ($i=0;$i<6;$i++) { $tareas[]=tareaPlanner($i,'2026-06-20T18:00:00Z'); }
for ($i=6;$i<8;$i++) { $tareas[]=tareaPlanner($i,'2026-06-21T18:00:00Z'); }
for ($i=8;$i<10;$i++) { $tareas[]=tareaPlanner($i,null,'2026-06-20T18:00:00Z',0); }
$tareas[]=tareaPlanner(10,null,null,0);
$r=PlannerService::calcular($tareas,$empleados,$p,$ahora);
comprobarPlanner($r['empleados'][0]['cumplimiento']===70.0,'Fórmula ejemplo 70%');
comprobarPlanner($r['empleados'][0]['evaluables']===10 && $r['empleados'][0]['excluidas']===1,'Exclusiones');
comprobarPlanner($r['empleados'][2]['cumplimiento']===null,'Sin calificación');
$compartida=tareaPlanner(99,'2026-06-20T18:00:00Z',responsables:array_fill_keys(array_column(array_slice($empleados,0,2),'user_graph_id'),[]));
$r=PlannerService::calcular([$compartida],$empleados,$p,$ahora);
comprobarPlanner(array_column($r['empleados'],'puntuales')===[1,1,0],'Responsables compartidos');
$duplicados=$empleados; $duplicados[1]['user_graph_id']=$duplicados[0]['user_graph_id'];
$r=PlannerService::calcular([$compartida],$duplicados,$p,$ahora);
comprobarPlanner(count($r['excepciones'])===2 && array_sum(array_column($r['empleados'],'asignadas'))===0,'Vínculo ambiguo y no vinculado');
rechazarPlanner(fn()=>PlannerService::calcular([$compartida,$compartida],$empleados,$p,$ahora));
$sinResponsables=tareaPlanner(101,null,null,0,[]);
comprobarPlanner(count(PlannerService::calcular([$sinResponsables],$empleados,$p,$ahora)['excepciones'])===1,'Sin responsables');
$muchas=[];
for ($i=0;$i<250;$i++) { $muchas[]=tareaPlanner('mas-'.$i,'2026-06-20T18:00:00Z'); }
comprobarPlanner(PlannerService::calcular($muchas,$empleados,$p,$ahora)['empleados'][0]['evaluables']===250,'Sin límite de cien tareas');
comprobarPlanner(PlannerService::evaluar(tareaPlanner('anterior','2026-05-31T23:00:00Z'),$p,$ahora)['nota']===100,'Entrega anterior al mes');
comprobarPlanner(PlannerService::evaluar(tareaPlanner('retraso','2026-06-29T18:00:00Z'),$p,$ahora)['nota']===50,'Tardía fija cincuenta');
comprobarPlanner(PlannerService::evaluar(tareaPlanner('corte','2026-07-01T05:00:00Z','2026-07-05T18:00:00Z'),$p,$ahora)['nota']===0,'Corte tiene prioridad sobre vencimiento');
comprobarPlanner(PlannerService::evaluar(tareaPlanner('precision','2026-06-20T18:00:00.1234567Z','2026-06-20T18:00:00.1234566Z'),$p,$ahora)['nota']===100,'Hora y fracciones del mismo día no generan retraso');
// CA-C01 a CA-C05: fechas UTC que cambian de día al convertirlas a Colombia.
$octubre='2026-10-01'; $noviembre=new DateTimeImmutable('2026-11-17T12:00:00-05:00');
$correcciones=[
    [tareaPlanner('C01','2026-10-31T04:42:00Z','2026-10-30T05:00:00Z'),100],
    [tareaPlanner('C02','2026-10-21T17:00:00Z','2026-10-20T05:00:00Z'),50],
    [tareaPlanner('C03','2026-11-01T05:00:00Z','2026-10-20T05:00:00Z'),0],
    [tareaPlanner('C04','2026-10-21T04:59:59Z','2026-10-20T05:00:00Z'),100],
    [tareaPlanner('medianoche','2026-10-21T05:00:00Z','2026-10-20T05:00:00Z'),50],
    [tareaPlanner('ultimo-dia','2026-11-01T04:59:59.9999999Z','2026-10-31T05:00:00Z'),100],
    [tareaPlanner('sin-vencimiento',null,null,0),null]
];
foreach ($correcciones as [$t,$esperada]) {
    comprobarPlanner(PlannerService::evaluar($t,$octubre,$noviembre)['nota']===$esperada,'Corrección '.$t['id']);
}
$horaAuditada=PlannerService::evaluar($correcciones[0][0],$octubre,$noviembre);
comprobarPlanner($horaAuditada['finalizacion']==='2026-10-30T23:42:00-05:00','Auditoría conserva hora real');
$recalculado=PlannerService::calcular(array_column(array_slice($correcciones,0,3),0),$empleados,$octubre,$noviembre)['empleados'][0];
comprobarPlanner($recalculado['cumplimiento']===50.0 && $recalculado['puntuales']===1 && $recalculado['tardias']===1 && $recalculado['incumplidas']===1,'Consolidado con regla corregida');
$incompleto=new PlannerService(new Planner(),static fn($url)=>(object)['value'=>[],'@odata.nextLink'=>'https://graph.microsoft.com/v1.0/groups?next=1']);
rechazarPlanner(fn()=>$incompleto->grupos());

if (in_array('--db',$argv,true)) {
    $codigo=str_replace(['class Conectar','session_start();'],['class ConexionRealPlanner',''],file_get_contents(__DIR__.'/../config/conexion.php'));
    eval(substr($codigo,5));
    class ConexionTestPlanner extends ConexionRealPlanner { public function abrir() { return parent::Conexion(); } }
    $db=(new ConexionTestPlanner())->abrir(); $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    Conectar::$pruebaDb=$db;
    $esquema='hu03_test_'.bin2hex(random_bytes(6));
    $db->exec('CREATE SCHEMA '.$esquema);
    try {
        $db->exec("CREATE TABLE $esquema.usuarios (user_id integer PRIMARY KEY,user_graph_id uuid)");
        $db->exec("CREATE TABLE $esquema.empleados (id_empl integer PRIMARY KEY,nomb_empl text,user_empl integer)");
        $db->exec("INSERT INTO $esquema.usuarios VALUES (1,'00000000-0000-4000-8000-000000000001')");
        $db->exec("INSERT INTO $esquema.empleados VALUES (11,'Empleado sintético',1)");
        $sql=file_get_contents(__DIR__.'/../database/migrations/20261117_planner_cumplimiento.sql');
        $db->exec(str_replace('public.',$esquema.'.',$sql));
        $codigo=file_get_contents(__DIR__.'/../models/Planner.php');
        $codigo=str_replace(['class Planner extends Conectar','public.',"table_schema='public'"],
            ['class PlannerAislado extends Planner',$esquema.'.',"table_schema='$esquema'"],$codigo);
        eval(substr($codigo,5));
        $m=new PlannerAislado();
        $plan=['grupo_id'=>'grupo-prueba','grupo_nombre'=>'JUNIO','plan_id'=>'plan-prueba','plan_nombre'=>'JUNIO 2026','periodo_ambiguo'=>false];
        $m->asociar($p,$plan,1); $m->asociar($p,$plan,1);
        comprobarPlanner($m->periodo($p)['plan_id']==='plan-prueba','Asociación idempotente');
        rechazarPlanner(fn()=>$m->asociar($p,array_replace($plan,['plan_id'=>'otro']),1));
        comprobarPlanner($m->bloquear($p),'Bloqueo adquirido'); $m->desbloquear($p);
        comprobarPlanner($m->bloquear($p),'Bloqueo para concurrencia');
        $segunda=(new ConexionTestPlanner())->abrir();
        comprobarPlanner(!$segunda->query('SELECT pg_try_advisory_lock(303,202606)')->fetchColumn(),'Otra conexión no obtiene bloqueo');
        $m->desbloquear($p); $segunda=null;
        $captura=$m->capturar($p,['prueba'=>true],'2026-06-30T23:58:00-05:00','2026-06-30T23:59:00-05:00');
        comprobarPlanner((int)$m->ultimaCaptura($p,'2026-07-01T00:00:00-05:00')['captura_id']===$captura,'Evidencia anterior al corte');
        $fallo=true;
        $graph=static function ($url) use (&$fallo,$p) {
            if ($fallo) { throw new RuntimeException('Fallo remoto sintético'); }
            if (str_starts_with($url,'groups?')) { return (object)['value'=>[(object)['id'=>'grupo-prueba','displayName'=>'JUNIO']]]; }
            if (str_starts_with($url,'groups/')) { return (object)['value'=>[(object)['id'=>'plan-prueba','title'=>'JUNIO 2026']]]; }
            if (str_ends_with($url,'/tasks')) { return (object)['value'=>[json_decode(json_encode(tareaPlanner(1,'2026-06-21T04:42:00Z')))]]; }
            return (object)['id'=>'plan-prueba','title'=>'JUNIO 2026'];
        };
        $servicio=new PlannerService($m,$graph);
        rechazarPlanner(fn()=>$servicio->ejecutar($p,true));
        comprobarPlanner($m->periodo($p)['estado']==='Abierto','Fallo Graph no cierra');
        $fallo=false;
        comprobarPlanner($servicio->ejecutar($p,true)==='cerrado','Recuperación del cierre');
        comprobarPlanner(json_decode($m->periodo($p)['informe'],true)['empleados'][0]['cumplimiento']===100,'Cierre CLI comparte la regla por día');
        $antes=$m->periodo($p)['informe'];
        comprobarPlanner($servicio->ejecutar($p,true)==='ya_cerrado','Duplicado no escribe');
        $fallo=true;
        comprobarPlanner($servicio->consultar($p)['estado']==='Cerrado con observaciones','Histórico sin Graph');
        comprobarPlanner($m->periodo($p)['informe']===$antes,'Histórico conservado');
        comprobarPlanner((int)$db->query("SELECT count(*) FROM $esquema.planner_resultados")->fetchColumn()===1,'Resultado único');
        rechazarPlanner(fn()=>$db->exec("UPDATE $esquema.planner_resultados SET object_id=NULL"));
        rechazarPlanner(fn()=>$db->exec("DELETE FROM $esquema.planner_capturas"));
        rechazarPlanner(fn()=>$db->exec("UPDATE $esquema.planner_periodos SET estado='Abierto'"));
        comprobarPlanner((int)$db->query("SELECT count(*) FROM $esquema.planner_ejecuciones WHERE resultado='fallo_reintentable'")->fetchColumn()===1,'Bitácora de fallo');
        // Un cierre previo con nota antigua se devuelve íntegro, sin recalcularlo al consultar.
        $m->asociar($octubre,array_replace($plan,['plan_id'=>'plan-octubre','plan_nombre'=>'OCTUBRE 2026']),1);
        $historico=json_decode($antes,true);
        $historico['periodo']=$octubre;
        $historico['empleados'][0]['cumplimiento']=50;
        $historico['empleados'][0]['tareas'][0]['nota']=50;
        $capturaAntigua=$m->capturar($octubre,$historico,'2026-11-01T00:01:00-05:00','2026-11-01T00:01:00-05:00');
        $m->cerrar($octubre,$historico,$capturaAntigua);
        $guardado=$m->periodo($octubre)['informe'];
        comprobarPlanner($servicio->consultar($octubre)['empleados'][0]['cumplimiento']===50,'No recalcular cierre antiguo');
        comprobarPlanner($m->periodo($octubre)['informe']===$guardado,'Sin escritura de históricos ante cambio de regla');
        comprobarPlanner(count($m->historicos())===2,'Auditoría lista cierres sin modificar sus informes');
        // CA-03: rechazo comprensible antes del INSERT, preservando el uso único del Plan ID.
        try { $m->verificarPlanDisponible('plan-prueba','2026-07-01'); throw new RuntimeException('Plan reutilizado'); }
        catch (DomainException $e) { comprobarPlanner(str_contains($e->getMessage(),'06/2026'),'Mensaje de Plan ID ya asociado'); }
        rechazarPlanner(fn()=>$m->asociar('2026-07-01',$plan,1));
        comprobarPlanner($m->periodo('2026-07-01')===null,'Reutilización no crea período');
        // CA-04: decisión con autor/motivo/evidencia y asociación en una sola transacción.
        $ambiguo=array_replace($plan,['plan_id'=>'plan-ambiguo','plan_nombre'=>'AGOSTO','periodo_ambiguo'=>true]);
        rechazarPlanner(fn()=>$m->asociar('2026-08-01',$ambiguo,1));
        comprobarPlanner($m->periodo('2026-08-01')===null,'Sin confirmación no hay asociación parcial');
        $m->asociar('2026-08-01',$ambiguo,1,'Período verificado con el responsable de las actividades.');
        $asociacionAntes=$m->periodo('2026-08-01');
        comprobarPlanner($m->confirmacionPeriodo('2026-08-01',$ambiguo),'Confirmación persistida');
        $m->asociar('2026-08-01',$ambiguo,1);
        comprobarPlanner($m->periodo('2026-08-01')===$asociacionAntes,'Repetir no modifica asociación');
        comprobarPlanner((int)$db->query("SELECT count(*) FROM $esquema.planner_revisiones")->fetchColumn()===1,'Decisión idempotente');
        comprobarPlanner(!$m->confirmacionPeriodo('2026-08-01',array_replace($ambiguo,['plan_nombre'=>'AGOSTO REVISADO'])),'Decisión no se reutiliza para un nombre diferente');
        $auditoria=$db->query("SELECT autorizado_por,motivo,evidencia FROM $esquema.planner_revisiones")->fetch(PDO::FETCH_ASSOC);
        comprobarPlanner((int)$auditoria['autorizado_por']===1 && json_decode($auditoria['evidencia'],true)['periodo']==='2026-08-01','Autor y período documentados');
    } finally {
        if ($db->inTransaction()) { $db->rollBack(); }
        // Nombre generado internamente y restringido; solo limpia el esquema sintético.
        if (preg_match('/\Ahu03_test_[0-9a-f]{12}\z/',$esquema)) { $db->exec('DROP SCHEMA '.$esquema.' CASCADE'); }
    }
}
echo "OK: $checks verificaciones Planner.\n";
