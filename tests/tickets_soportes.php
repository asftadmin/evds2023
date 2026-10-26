<?php

// Integración con PostgreSQL temporal: no escribe tablas ni archivos de producción.
require_once __DIR__ . '/../../preoperacional/config/conexion.php';
$conexion = (new Conectar())->getConexion();
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
class ConexionSoportesPrueba
{
    // Los modelos bajo prueba comparten exclusivamente la conexión temporal.
    protected function Conexion() { return $GLOBALS['conexion']; }
}
$cuenta = 0;
function comprobarSoporte($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    $GLOBALS['cuenta']++;
}
function rechazarSoporte($accion)
{
    try { $accion(); } catch (InvalidArgumentException $e) { comprobarSoporte(true, 'Rechazo'); return; }
    throw new RuntimeException('Se aceptó una operación inválida.');
}

// Cambia únicamente el destino SQL de las copias evaluadas, nunca los archivos originales.
foreach (['TicketsSoportes', 'TicketsSistemas'] as $archivo) {
    $codigo = file_get_contents(__DIR__ . '/../../preoperacional/api/model/' . $archivo . '.php');
    $codigo = preg_replace('/^require_once .*;\r?\n/m', '', $codigo);
    $codigo = str_replace(['<?php', 'extends Conectar', 'public.'], ['', 'extends ConexionSoportesPrueba', 'pg_temp.'], $codigo);
    eval($codigo);
}
$directorio = sys_get_temp_dir() . '/tickets_soportes_prueba_' . bin2hex(random_bytes(8));
$anterior = getenv('TICKETS_SOPORTES_DIR');
putenv('TICKETS_SOPORTES_DIR=' . $directorio);
try {
    // Copia solo columnas de las tablas reales verificadas en los esquemas proporcionados.
    foreach (['tickets_sistemas', 'tickets_sistemas_categorias', 'tickets_sistemas_seguimientos',
        'tickets_sistemas_notificaciones', 'usuarios', 'permiso', 'menu'] as $tabla) {
        $conexion->exec('CREATE TEMP TABLE ' . $tabla . ' AS SELECT * FROM public.' . $tabla . ' WITH NO DATA');
    }
    $conexion->exec('CREATE TEMP SEQUENCE tickets_sistemas_consecutivo_seq');
    $conexion->exec('CREATE TEMP SEQUENCE tickets_prueba_id');
    $conexion->exec("ALTER TABLE pg_temp.tickets_sistemas ALTER COLUMN ticket_id SET DEFAULT nextval('pg_temp.tickets_prueba_id')");
    $conexion->exec('ALTER TABLE pg_temp.tickets_sistemas ADD PRIMARY KEY (ticket_id)');
    $conexion->exec("INSERT INTO pg_temp.tickets_sistemas_categorias (categoria_id, nombre, activo) VALUES (1, 'Prueba', true)");
    $migracion = file_get_contents(__DIR__ . '/../database/migrations/20261026_preoperacional_tickets_soportes.sql');
    $conexion->exec(str_replace('public.', 'pg_temp.', $migracion));
    $soportes = new TicketsSoportesApi();
    $tickets = new TicketsSistemasApi();
    $token = str_repeat('a', 64);
    $doc = '900000001';
    $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';
    $primero = $soportes->subir($doc, $token, 'foto.png', $png);
    $segundo = $soportes->subir($doc, $token, 'foto.png', $png);
    comprobarSoporte($primero['soporte_id'] !== $segundo['soporte_id'], 'Nombres duplicados deben conservar ambos archivos.');
    comprobarSoporte(count(glob($directorio . '/*')) === 2, 'Se guardaron dos archivos privados.');
    rechazarSoporte(function () use ($soportes, $doc, $token) { $soportes->subir($doc, $token, 'falso.jpg', base64_encode('%PDF-1.4')); });
    rechazarSoporte(function () use ($soportes, $doc, $token) { $soportes->subir($doc, $token, 'vacio.png', ''); });
    rechazarSoporte(function () use ($soportes, $doc, $token) { $soportes->subir($doc, $token, 'grande.pdf', base64_encode('%PDF-1.4' . str_repeat('a', 10485760))); });
    rechazarSoporte(function () use ($soportes, $primero) { $soportes->descargar('900000002', $primero['soporte_id']); });
    rechazarSoporte(function () use ($soportes, $primero, $token) { $soportes->eliminar('900000002', $token, $primero['soporte_id']); });
    $soportes->eliminar($doc, $token, $segundo['soporte_id']);
    comprobarSoporte(count(glob($directorio . '/*')) === 1, 'Eliminar temporal retira el archivo físico.');
    $datos = ['empleado_documento' => $doc, 'empleado_nombre' => 'Prueba', 'tipo' => 'SOLICITUD',
        'categoria_id' => 1, 'asunto' => 'Prueba aislada', 'descripcion' => 'Soporte', 'prioridad' => 'MEDIA', 'soporte_token' => $token];
    $creado = $tickets->crearTicket($datos);
    comprobarSoporte($creado['success'], 'Se creó ticket con soporte.');
    $id = $creado['data']['ticket_id'];
    comprobarSoporte(count($soportes->listar($doc, $id)) === 1, 'Asociación al crear ticket.');
    comprobarSoporte(count($soportes->listar('900000002', $id)) === 0, 'No lista archivos de otro propietario.');
    comprobarSoporte($soportes->descargar($doc, $primero['soporte_id'])['contenido'] === $png, 'Descarga conserva bytes.');
    rechazarSoporte(function () use ($soportes, $doc, $token, $primero) { $soportes->eliminar($doc, $token, $primero['soporte_id']); });
    rechazarSoporte(function () use ($soportes, $doc, $token, $png) { $soportes->subir($doc, $token, 'tarde.png', $png); });
    comprobarSoporte($tickets->obtenerDetalleEmpleado($id, '900000002') === null, 'Detalle protege propietario.');
    comprobarSoporte($tickets->obtenerDetalleEmpleado($id, $doc)['ticket']['ticket_id'] == $id, 'Detalle propio disponible.');
    comprobarSoporte($tickets->crearTicket($datos)['data']['ticket_id'] == $id, 'Reintento no duplica ticket con soportes.');
    $otroToken = str_repeat('b', 64);
    for ($i = 0; $i < 10; $i++) { $soportes->subir($doc, $otroToken, 'foto.png', $png); }
    rechazarSoporte(function () use ($soportes, $doc, $otroToken, $png) { $soportes->subir($doc, $otroToken, 'once.png', $png); });
    // La siguiente carga retira temporales vencidos sin tocar soportes asociados.
    $vencidoToken = str_repeat('c', 64);
    $vencido = $soportes->subir($doc, $vencidoToken, 'vencido.png', $png);
    $conexion->exec("UPDATE pg_temp.tickets_sistemas_soportes SET soporte_fecha = CURRENT_TIMESTAMP - INTERVAL '25 hours'
        WHERE soporte_token = '" . $vencidoToken . "'");
    $nuevo = $soportes->subir($doc, $vencidoToken, 'nuevo.png', $png);
    $stmt = $conexion->prepare('SELECT count(*) FROM pg_temp.tickets_sistemas_soportes WHERE soporte_id = ?');
    $stmt->execute([$vencido['soporte_id']]);
    comprobarSoporte((int) $stmt->fetchColumn() === 0, 'Limpieza retira referencia temporal vencida.');
    comprobarSoporte(count(glob($directorio . '/*')) === 12, 'Limpieza retira archivo vencido y conserva asociados.');
    $soportes->eliminar($doc, $vencidoToken, $nuevo['soporte_id']);
    // Fuerza un error posterior a insertar: ticket y asociación deben revertirse juntos.
    $conexion->exec('DROP TABLE pg_temp.tickets_sistemas_notificaciones');
    $datos['soporte_token'] = $otroToken;
    comprobarSoporte(!$tickets->crearTicket($datos)['success'], 'Fallo de persistencia devuelve error.');
    comprobarSoporte((int) $conexion->query('SELECT count(*) FROM pg_temp.tickets_sistemas')->fetchColumn() === 1, 'Rollback no deja ticket parcial.');
    comprobarSoporte((int) $conexion->query('SELECT count(*) FROM pg_temp.tickets_sistemas_soportes WHERE ticket_id IS NULL')->fetchColumn() === 10, 'Rollback conserva temporales para reintentar.');
    echo $cuenta . " verificaciones correctas en tablas temporales.\n";
} finally {
    // Solo elimina los archivos generados en el directorio temporal único de esta prueba.
    foreach (glob($directorio . '/*') ?: [] as $ruta) { unlink($ruta); }
    if (is_dir($directorio)) { rmdir($directorio); }
    putenv($anterior === false ? 'TICKETS_SOPORTES_DIR' : 'TICKETS_SOPORTES_DIR=' . $anterior);
}
