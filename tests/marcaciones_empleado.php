<?php
// Usa PostgreSQL aislado mediante JORNADAS_TEST_DSN y respuestas BioTime simuladas.
if (!getenv('JORNADAS_TEST_DSN')) {
    fwrite(STDERR, "Defina JORNADAS_TEST_DSN hacia una base de pruebas.\n");
    exit(1);
}
$schema = 'test_marcaciones_' . bin2hex(random_bytes(5));
class Conectar {
    protected function Conexion() {
        global $schema;
        $db = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $db->exec('SET search_path TO ' . $schema);
        return $db;
    }
}
require_once __DIR__ . '/../models/MarcacionesEmpleado.php';
function verificarMarcacion($condicion, $mensaje) {
    if (!$condicion) throw new RuntimeException($mensaje);
    echo "OK: $mensaje\n";
}
function rechazarMarcacion($accion) {
    try { $accion(); } catch (RuntimeException | DomainException $e) { return; }
    throw new RuntimeException('La operación debía rechazarse.');
}
function marcaEmpleado($hora, $documento = '111', $fecha = '2026-10-19') {
    return (object) array('emp_code' => $documento, 'att_date' => $fecha, 'punch_time' => $hora, 'punch_state' => 'Entrada');
}
function modeloMarcaciones(array $filas) {
    return new MarcacionesEmpleado(function () use ($filas) { return (object) array('count' => count($filas), 'data' => $filas); });
}
$db = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
$db->exec('CREATE SCHEMA ' . $schema);
$db->exec('SET search_path TO ' . $schema);
try {
    $db->exec("CREATE TABLE empleados (id_empl integer, user_empl integer, cedu_empl text, nomb_empl text, esta_empl integer, carg_empl integer);
        INSERT INTO empleados VALUES (1,11,'111','Empleado propio',1,1),(2,22,'222','Empleado ajeno',1,NULL),(3,33,'333','Inactivo',0,NULL);
        CREATE TABLE cargo (codi_carg integer, nomb_carg text, grem_carg integer);
        CREATE TABLE grupoempleados (codi_grem integer, nomb_grem text);
        INSERT INTO cargo VALUES (1,'Auxiliar',1); INSERT INTO grupoempleados VALUES (1,'Produccion')");
    $filas = array(marcaEmpleado('17:30'), marcaEmpleado('08:00'), marcaEmpleado('13:00'), marcaEmpleado('12:00'),
        marcaEmpleado('hora ajena no válida', '222'));
    $datos = modeloMarcaciones($filas)->consultar_propias(11, '2026-10-19', '2026-10-19');
    verificarMarcacion($datos['totales']['marcaciones'] === 4 && $datos['empleado']['nombre'] === 'Empleado propio',
        'Solo devuelve el empleado asociado al usuario, aunque BioTime ignore el filtro');
    verificarMarcacion($datos['resumen'][0]['entrada'] === '08:00:00' && $datos['resumen'][0]['salida'] === '17:30:00' &&
        $datos['resumen'][0]['tiempo_bruto'] === '09:30:00', 'Resumen diario usa primera y última hora sin descontar almuerzo');
    verificarMarcacion(count($datos['detalle']) === 4 && $datos['detalle'][0]['hora'] === '17:30:00' &&
        $datos['detalle'][0]['cargo'] === 'Auxiliar' && $datos['detalle'][0]['grupo'] === 'Produccion',
        'Detalle muestra una fila por marcación con cargo y grupo, de la más reciente a la más antigua');
    verificarMarcacion(!isset($datos['empleado']['documento']) && !isset($datos['detalle'][0]['documento']), 'El documento no se envía en la respuesta personal');
    $impar = modeloMarcaciones(array(marcaEmpleado('08:00'), marcaEmpleado('12:00'), marcaEmpleado('17:00')))
        ->consultar_propias(11, '2026-10-19', '2026-10-19');
    verificarMarcacion(count($impar['detalle']) === 3 && $impar['totales']['incompletos'] === 1,
        'Conserva todas las marcaciones impares y advierte sobre el día incompleto');
    $nocturnas = modeloMarcaciones(array(marcaEmpleado('23:00'), marcaEmpleado('01:00', '111', '2026-10-20')))
        ->consultar_propias(11, '2026-10-19', '2026-10-20');
    verificarMarcacion($nocturnas['totales']['incompletos'] === 2, 'No mezcla días distintos al emparejar por hora');
    $consultas = array();
    $paginado = new MarcacionesEmpleado(function ($url) use (&$consultas) {
        parse_str($url, $parametros);
        $consultas[] = $parametros;
        return (object) array('count' => 2, 'data' => array(marcaEmpleado($parametros['page'] === '1' ? '08:00' : '17:00')));
    });
    $datos = $paginado->consultar_propias(11, '2026-10-19', '2026-10-19');
    verificarMarcacion(count($consultas) === 2 && $consultas[0]['emp_code'] === '111' && $consultas[0]['departments'] === '1' &&
        $consultas[0]['areas'] === '2' && $datos['resumen'][0]['tiempo_bruto'] === '09:00:00',
        'Completa la paginación conservando documento y configuración de departments/areas');
    $fallo = new MarcacionesEmpleado(function () { return null; });
    rechazarMarcacion(function () use ($fallo) { $fallo->consultar_propias(11, '2026-10-19', '2026-10-19'); });
    rechazarMarcacion(function () { modeloMarcaciones(array(marcaEmpleado('25:99')))->consultar_propias(11, '2026-10-19', '2026-10-19'); });
    rechazarMarcacion(function () { modeloMarcaciones(array())->consultar_propias(33, '2026-10-19', '2026-10-19'); });
    rechazarMarcacion(function () { modeloMarcaciones(array())->consultar_propias(99, '2026-10-19', '2026-10-19'); });
    verificarMarcacion(true, 'Rechaza API fallida, horas inválidas y usuarios sin un empleado activo');
    $vacio = modeloMarcaciones(array())->consultar_propias(11, '2026-10-19', '2026-10-19');
    verificarMarcacion($vacio['detalle'] === array() && $vacio['resumen'] === array(), 'Una consulta válida sin marcaciones devuelve listas vacías');

    // Ejecuta el controlador real en procesos aislados con sesión y transporte simulados.
    $ruta = tempnam(sys_get_temp_dir(), 'marcaciones_control_');
    $fuente = file_get_contents(__DIR__ . '/../controller/marcaciones_empleado.php');
    $fuente = preg_replace('/^require_once .*;\R/m', '', $fuente);
    $modeloRuta = var_export(realpath(__DIR__ . '/../models/MarcacionesEmpleado.php'), true);
    $dsn = var_export(getenv('JORNADAS_TEST_DSN'), true);
    $esquema = var_export($schema, true);
    $codigo = '<?php $caso=json_decode(base64_decode($argv[1]),true); $_SESSION=$caso["sesion"]; $_GET=$caso["get"]; $_SERVER["REQUEST_METHOD"]=$caso["metodo"];'
        . 'class Conectar { protected function Conexion() { $db=new PDO(' . $dsn . ',null,null,array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION)); $db->exec("SET search_path TO " . ' . $esquema . '); return $db; } }'
        . 'class CurlController { public static function requestBiotime($url,$method) { return (object)array("count"=>1,"data"=>array((object)array("emp_code"=>"111","att_date"=>"2026-10-19","punch_time"=>"08:00"))); } }'
        . 'require ' . $modeloRuta . '; register_shutdown_function(function(){fwrite(STDERR,"HTTP_STATUS=".http_response_code());}); ?>' . $fuente;
    file_put_contents($ruta, $codigo);
    try {
        $base = array('sesion' => array('user_id' => 11), 'get' => array('desde' => '2026-10-19', 'hasta' => '2026-10-19'), 'metodo' => 'GET');
        $casos = array(array($base, 200));
        $caso = $base; $caso['sesion'] = array(); $casos[] = array($caso, 401);
        $caso = $base; $caso['get']['empleado_id'] = '2'; $casos[] = array($caso, 422);
        $caso = $base; $caso['get']['desde'] = '2026-02-30'; $casos[] = array($caso, 422);
        $caso = $base; $caso['get']['desde'] = '2025-01-01'; $casos[] = array($caso, 422);
        $caso = $base; $caso['metodo'] = 'POST'; $casos[] = array($caso, 405);
        $caso = $base; $caso['sesion']['user_id'] = 33; $casos[] = array($caso, 403);
        foreach ($casos as $prueba) {
            $pipes = array();
            $proceso = proc_open(array(PHP_BINARY, $ruta, base64_encode(json_encode($prueba[0]))), array(1 => array('pipe','w'), 2 => array('pipe','w')), $pipes);
            $json = stream_get_contents($pipes[1]); $estado = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]); $salida = proc_close($proceso);
            verificarMarcacion($salida === 0 && is_array(json_decode($json, true)) && $estado === 'HTTP_STATUS=' . $prueba[1],
                'Controlador retorna JSON limpio con HTTP ' . $prueba[1]);
        }
    } finally { unlink($ruta); }
} finally { $db->exec('DROP SCHEMA ' . $schema . ' CASCADE'); }
