<?php
/** Integración PostgreSQL aislada. Requiere JORNADAS_TEST_DSN; nunca carga conexion.php. */
if (!getenv('JORNADAS_TEST_DSN')) {
    fwrite(STDERR, "Defina JORNADAS_TEST_DSN hacia una base de pruebas.\n");
    exit(1);
}
$schema = 'test_firma_' . bin2hex(random_bytes(5));
class Conectar {
    public function set_names() {}
    protected function Conexion() {
        global $schema;
        $db = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('SET search_path TO ' . $schema);
        return $db;
    }
}
require_once __DIR__ . '/../models/Jornada.php';
require_once __DIR__ . '/../models/JornadaCruceBiotime.php';
require_once __DIR__ . '/../models/JornadaReporteContable.php';
function comprobarFirma($condicion, $mensaje) {
    if (!$condicion) throw new RuntimeException($mensaje);
    echo "OK: $mensaje\n";
}
function rechazarFirma($accion) {
    try { $accion(); } catch (RuntimeException | InvalidArgumentException $e) { return $e->getMessage(); }
    throw new RuntimeException('La operación debía fallar.');
}
function datosFirma($dia) {
    return ['jornada_id' => null, 'fecha' => $dia, 'inicio' => "$dia 08:00:00", 'fin' => "$dia 17:00:00",
        'minutos_ordinarios' => 480, 'ubicacion' => 'Sede principal', 'actividad' => 'Trabajo', 'observaciones' => ''];
}
function nuevaFirma($modelo, $dia, $empleado = 1) {
    return $modelo->guardar_borradores_equipo_masivo($empleado, 3, 9, [datosFirma($dia)])['jornadas_creadas'][0];
}
$db = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE SCHEMA ' . $schema);
$db->exec('SET search_path TO ' . $schema);
try {
    $db->exec(<<<'SQL'
CREATE TABLE empleados (id_empl integer PRIMARY KEY, esta_empl integer DEFAULT 1, cedu_empl text, nomb_empl text,
 carg_empl integer, fecha_naci_empl date, gene_empl integer);
INSERT INTO empleados (id_empl,cedu_empl,nomb_empl) VALUES (1,'111','Uno'),(2,'222','Dos'),(3,'333','Jefe');
CREATE TABLE empleado_jefe (empleado_id integer, jefe_id integer, ej_estado integer DEFAULT 1);
INSERT INTO empleado_jefe VALUES (1,3,1),(2,3,1);
CREATE TABLE firma_usuario (user_id integer, firma_base64 text);
CREATE TABLE cargo (codi_carg integer, nomb_carg text);
CREATE TABLE genero (id_gene integer, desc_gene text);
CREATE TABLE jornada_estados (je_id serial PRIMARY KEY, je_codigo text UNIQUE, je_nombre text, je_estado integer DEFAULT 1);
INSERT INTO jornada_estados (je_codigo,je_nombre) VALUES ('BORRADOR','Borrador'),('CONFIRMADO_EMPLEADO','Confirmado'),
 ('APROBADO','Aprobado'),('PENDIENTE_LIQUIDACION','Pendiente liquidación'),('LIQUIDADO','Liquidado'),
 ('PENDIENTE_APROBACION','Pendiente aprobación'),('RECHAZADO','Rechazado'),('ANULADO','Anulado');
CREATE TABLE jornadas_trabajo (
 jornada_id bigserial PRIMARY KEY, empleado_id integer REFERENCES empleados, jornada_inicio timestamp, jornada_fin timestamp,
 jornada_minutos_ordinarios integer, jornada_ubicacion text, jornada_actividad text, jornada_observaciones text,
 jornada_origen text, jornada_estado_id integer REFERENCES jornada_estados, jornada_creado_por integer,
 jornada_version integer DEFAULT 1, jornada_fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
 jornada_fecha_actualizacion timestamp DEFAULT CURRENT_TIMESTAMP, jornada_inconsistente integer DEFAULT 0,
 jornada_inconsistencia_detalle text
);
CREATE TABLE jornada_auditoria (
 jaud_id bigserial PRIMARY KEY, jornada_id bigint, jaud_accion text, jaud_estado_anterior text, jaud_estado_nuevo text,
 jaud_datos_anteriores jsonb, jaud_datos_nuevos jsonb, jaud_motivo text, jaud_usuario_id integer
);
CREATE TABLE jornada_aprobaciones (
 jap_id bigserial PRIMARY KEY, jornada_id bigint, jap_etapa text, jap_decision text, jap_usuario_id integer,
 jap_empleado_id integer, jap_motivo text, jap_fecha timestamp DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE jornada_confirmaciones_obra (
 jco_id bigserial PRIMARY KEY, empleado_id integer, jco_fecha_desde date, jco_fecha_hasta date,
 jco_firma_base64 text, jco_estado text, jco_confirmado_por integer,
 jco_fecha_confirmacion timestamp DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE jornada_confirmacion_obra_detalle (
 jcod_id bigserial PRIMARY KEY, jco_id bigint, jornada_id bigint, jcod_jornada_version integer, jcod_snapshot jsonb,
 jcod_fecha_registro timestamp DEFAULT CURRENT_TIMESTAMP, jcod_estado text DEFAULT 'VIGENTE',
 jcod_fecha_invalidacion timestamp, jcod_invalidado_por integer, jcod_motivo_invalidacion text
);
CREATE TABLE jornada_reportes_firma (
 jrf_id bigserial PRIMARY KEY, empleado_id integer, jrf_fecha_desde date, jrf_fecha_hasta date,
 jrf_firma_base64 text, jrf_estado text, jrf_firmado_por integer, jrf_fecha_firma timestamp DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE jornada_reporte_detalle (
 jrd_id bigserial PRIMARY KEY, jrf_id bigint, jornada_id bigint UNIQUE, jrd_jornada_version integer, jrd_snapshot jsonb,
 CHECK (jrd_snapshot->>'jornada_actividad' <> 'FALLO_REPORTE')
);
SQL
    );
    $modelo = new Jornada();
    $imagenFirma = imagecreatetruecolor(100, 30);
    imagefill($imagenFirma, 0, 0, imagecolorallocate($imagenFirma, 255, 255, 255));
    imageline($imagenFirma, 5, 20, 95, 5, imagecolorallocate($imagenFirma, 0, 0, 0));
    ob_start();
    imagepng($imagenFirma);
    $firma = 'data:image/png;base64,' . base64_encode(ob_get_clean());
    imagedestroy($imagenFirma);
    $stmt = $db->prepare('INSERT INTO firma_usuario VALUES (3, ?)');
    $stmt->execute([$firma]);
    $uno = nuevaFirma($modelo, '2026-10-19');
    $dos = nuevaFirma($modelo, '2026-10-20');
    $modelo->confirmar_jornadas_obra(1, 11, '2026-10-19', '2026-10-19', $firma);
    $modelo->confirmar_jornadas_obra(1, 11, '2026-10-20', '2026-10-20', $firma);
    $db->exec("UPDATE jornada_confirmaciones_obra SET jco_fecha_confirmacion='2026-10-20 18:00:00'");
    comprobarFirma((int)$db->query('SELECT count(*) FROM jornada_reportes_firma')->fetchColumn() === 0,
        'La confirmación firma una sola vez y espera la aprobación antes de publicar reportes');
    $resultado = $modelo->registrar_aprobar_equipo_masivo(1, 3, 9, '2026-10-19', '2026-10-20');
    comprobarFirma($resultado['aprobadas'] === 2 && $resultado['estado'] === 'PENDIENTE_LIQUIDACION', 'La aprobación del jefe completa el paso a liquidación');
    comprobarFirma((int)$db->query("SELECT count(*) FROM jornada_reportes_firma WHERE jrf_firmado_por=11 AND jrf_fecha_firma='2026-10-20 18:00:00'")->fetchColumn() === 2,
        'Conserva firmante y fecha originales incluso con varias confirmaciones');
    comprobarFirma($db->query('SELECT jrf_firma_base64 FROM jornada_reportes_firma LIMIT 1')->fetchColumn() === $firma, 'La firma publicada es la original del empleado');
    comprobarFirma((int)$db->query('SELECT count(*) FROM jornada_reporte_detalle r JOIN jornada_confirmacion_obra_detalle c USING(jornada_id)
        WHERE r.jrd_snapshot=c.jcod_snapshot AND r.jrd_jornada_version=c.jcod_jornada_version')->fetchColumn() === 2,
        'Snapshots y versiones firmadas se conservan exactamente');
    comprobarFirma((int)$db->query("SELECT count(*) FROM jornada_aprobaciones WHERE jap_usuario_id=9 AND jap_decision='APROBADO'")->fetchColumn() === 2,
        'La aprobación se atribuye al jefe y la firma al empleado');
    comprobarFirma(count($modelo->listar_jornadas_para_firma(1, '2026-10-19', '2026-10-20')) === 0, 'No vuelve a solicitar estas firmas en Mis Jornadas');
    $biotime = new JornadaCruceBiotime();
    comprobarFirma(count($biotime->reportes('2026-10-19', '2026-10-20', 1)) === 2, 'BioTime reconoce ambas jornadas con su evidencia firmada');
    $antes = (int)$db->query('SELECT count(*) FROM jornada_reportes_firma')->fetchColumn();
    rechazarFirma(fn() => $modelo->registrar_aprobar_equipo_masivo(1, 3, 9, '2026-10-19', '2026-10-20'));
    comprobarFirma((int)$db->query('SELECT count(*) FROM jornada_reportes_firma')->fetchColumn() === $antes, 'Reintentar la aprobación no duplica reportes');

    $tres = nuevaFirma($modelo, '2026-10-21');
    $confirmacion = $modelo->confirmar_jornadas_obra(1, 11, '2026-10-21', '2026-10-21', $firma);
    $edicion = datosFirma('2026-10-21');
    $edicion['jornada_id'] = $tres;
    $edicion['actividad'] = 'Actividad corregida';
    $modelo->guardar_borradores_equipo_masivo(1, 3, 9, [$edicion]);
    comprobarFirma($db->query('SELECT jco_estado FROM jornada_confirmaciones_obra WHERE jco_id=' . $confirmacion['confirmacion_id'])->fetchColumn() === 'INVALIDADA',
        'Editar después de firmar invalida la evidencia anterior');
    rechazarFirma(fn() => $modelo->registrar_aprobar_equipo_masivo(1, 3, 9, '2026-10-21', '2026-10-21'));
    $modelo->confirmar_jornadas_obra(1, 11, '2026-10-21', '2026-10-21', $firma);
    comprobarFirma($modelo->registrar_aprobar_equipo_masivo(1, 3, 9, '2026-10-19', '2026-10-21')['aprobadas'] === 1,
        'Tras firmar la corrección puede aprobarse sin bloquearse por jornadas ya procesadas');

    $cuatro = nuevaFirma($modelo, '2026-10-22');
    $cinco = nuevaFirma($modelo, '2026-10-23');
    $db->exec("UPDATE jornadas_trabajo SET jornada_actividad='FALLO_REPORTE' WHERE jornada_id=$cinco");
    $modelo->confirmar_jornadas_obra(1, 11, '2026-10-22', '2026-10-22', $firma);
    $modelo->confirmar_jornadas_obra(1, 11, '2026-10-23', '2026-10-23', $firma);
    $antes = (int)$db->query('SELECT count(*) FROM jornada_reportes_firma')->fetchColumn();
    $auditorias = (int)$db->query('SELECT count(*) FROM jornada_auditoria')->fetchColumn();
    rechazarFirma(fn() => $modelo->registrar_aprobar_equipo_masivo(1, 3, 9, '2026-10-22', '2026-10-23'));
    comprobarFirma((int)$db->query('SELECT count(*) FROM jornada_reportes_firma')->fetchColumn() === $antes &&
        (int)$db->query('SELECT count(*) FROM jornada_auditoria')->fetchColumn() === $auditorias,
        'Un fallo en el segundo reporte revierte toda la publicación y aprobación');
    comprobarFirma($modelo->obtener_mi_jornada($cuatro, 1)['estado_codigo'] === 'CONFIRMADO_EMPLEADO', 'El rollback conserva el estado confirmado');
    $db->exec("UPDATE jornadas_trabajo SET jornada_actividad='Alterada sin firmar' WHERE jornada_id=$cuatro");
    rechazarFirma(fn() => $modelo->registrar_aprobar_equipo_masivo(1, 3, 9, '2026-10-22', '2026-10-22'));
    $db->exec("UPDATE jornadas_trabajo SET jornada_actividad='Trabajo' WHERE jornada_id=$cuatro");
    $db->exec("UPDATE jornada_confirmacion_obra_detalle SET jcod_estado='INVALIDADA' WHERE jornada_id=$cuatro");
    rechazarFirma(fn() => $modelo->registrar_aprobar_equipo_masivo(1, 3, 9, '2026-10-22', '2026-10-22'));
    comprobarFirma(true, 'Rechaza datos alterados y firmas invalidadas aunque el estado aparente estar confirmado');

    // Flujo del trabajador: conserva envío, aprobación y firma posterior independientes.
    $propia = $modelo->guardar_borrador_propio(null, 2, 22, '2026-10-19 08:00:00', '2026-10-19 17:00:00', 480, 'Sede principal', 'Propia', '');
    $modelo->enviar_aprobacion_propia($propia, 2, 22);
    $modelo->decidir_jornada_jefe($propia, 3, 9, 'APROBAR', '');
    comprobarFirma(count($modelo->listar_jornadas_para_firma(2, '2026-10-19', '2026-10-19')) === 1, 'Mis Jornadas conserva su firma posterior para registros del trabajador');
    $modelo->firmar_reporte_jornadas(2, 22, '2026-10-19', '2026-10-19', $firma);
    comprobarFirma($modelo->obtener_mi_jornada($propia, 2)['estado_codigo'] === 'PENDIENTE_LIQUIDACION', 'El flujo propio completa su firma y paso a liquidación');

    // Los reportes contables deben incluir pendientes de liquidación y jornadas ya liquidadas.
    $db->exec(<<<'SQL'
CREATE TABLE jornada_lotes_reporte (jlot_id integer, jlot_lote_origen_id integer, jlot_tipo text, jlot_estado text);
INSERT INTO jornada_lotes_reporte VALUES (1,NULL,'NORMAL','BORRADOR');
CREATE TABLE jornada_lote_empleados (jle_id integer, jlot_id integer, empleado_id integer, jle_desde date, jle_hasta date,
 jle_estado text, jle_cantidad_jornadas integer, jle_cantidad_pendientes integer, jle_minutos_reportables integer,
 jle_diagnostico text, jle_actualizado_por integer, jle_fecha_actualizacion timestamp);
INSERT INTO jornada_lote_empleados (jle_id,jlot_id,empleado_id,jle_desde,jle_hasta) VALUES (1,1,1,'2026-10-19','2026-10-20');
CREATE TABLE jornada_conceptos (jcon_id integer, jcon_codigo text, jcon_nombre text, jcon_codigo_contable text);
INSERT INTO jornada_conceptos VALUES (1,'ORD','Ordinarias','ORD');
CREATE TABLE jornada_clasificaciones (jcla_id serial, jornada_id bigint, jcon_id integer, jcla_inicio timestamp, jcla_fin timestamp, jcla_minutos integer);
INSERT INTO jornada_clasificaciones (jornada_id,jcon_id,jcla_inicio,jcla_fin,jcla_minutos)
 SELECT jornada_id,1,jornada_inicio,jornada_fin,540 FROM jornadas_trabajo WHERE empleado_id=1 AND jornada_inicio::date IN ('2026-10-19','2026-10-20');
SQL
    );
    $contable = new JornadaReporteContable();
    $recalcular = new ReflectionMethod($contable, 'recalcular_fila');
    $fila = $recalcular->invoke($contable, $db, 1, 9);
    comprobarFirma($fila['jle_estado'] === 'LISTO' && (int)$fila['jle_cantidad_jornadas'] === 2, 'Reporte contable reconoce las pendientes firmadas y clasificadas como listas');
    $construir = new ReflectionMethod($contable, 'construir_snapshot');
    comprobarFirma(count($construir->invoke($contable, $db, 1)['jornadas']) === 2, 'El snapshot para exportación contiene las pendientes de liquidación');
    $db->exec("UPDATE jornadas_trabajo SET jornada_estado_id=(SELECT je_id FROM jornada_estados WHERE je_codigo='LIQUIDADO') WHERE jornada_id IN ($uno,$dos)");
    comprobarFirma($recalcular->invoke($contable, $db, 1, 9)['jle_estado'] === 'LISTO', 'Las jornadas liquidadas siguen disponibles para cierre contable');
    $borrador = $modelo->obtener_borrador_equipo(3, 1, '2026-10-19', '2026-10-20');
    comprobarFirma($borrador['jefe_nombre'] === 'Jefe' && $borrador['firma_jefe'] === $firma,
        'El borrador obtiene el nombre del jefe y su firma configurada por id_empl');
    comprobarFirma($borrador['firma_empleado'] === $firma && $borrador['jornadas_firmadas'] === 2,
        'El borrador incorpora una sola firma vigente del trabajador y cuenta las jornadas cubiertas');
    $sinFirma = $modelo->obtener_borrador_equipo(3, 1, '2026-10-22', '2026-10-22');
    comprobarFirma($sinFirma['firma_empleado'] === null && $sinFirma['jornadas_firmadas'] === 0,
        'El borrador no muestra una firma invalidada');
    $lote = $modelo->obtener_borradores_equipo_lote(3, [1, 2, 1], '2026-10-19', '2026-10-20');
    comprobarFirma(count($lote) === 2, 'El PDF lote reúne empleados seleccionados sin duplicarlos');
    comprobarFirma(count($modelo->obtener_borradores_equipo_lote(3, [], '2026-10-19', '2026-10-20', true)) === 2,
        'Todos los empleados se resuelve con los subordinados activos del jefe');
    rechazarFirma(fn() => $modelo->obtener_borradores_equipo_lote(3, [1, 3], '2026-10-19', '2026-10-20'));
    rechazarFirma(fn() => $modelo->obtener_borradores_equipo_lote(3, [1], '2025-01-01', '2025-01-15'));
    comprobarFirma(true, 'Rechaza selecciones ajenas y periodos sin jornadas');
    $db->exec('ALTER TABLE jornada_auditoria ADD COLUMN jaud_fecha timestamp DEFAULT CURRENT_TIMESTAMP');
    $personal = $modelo->obtener_reporte_propio_pdf(1, '2026-10-19', '2026-10-20', 'LIQUIDADO');
    comprobarFirma(count($personal['jornadas']) === 2 && $personal['empleado'] === 'Uno',
        'El PDF personal obtiene solo las jornadas propias del periodo y estado indicados');
    comprobarFirma($personal['jefe_nombre'] === 'Jefe' && $personal['firma_jefe'] === $firma
        && $personal['firma_empleado'] === $firma && $personal['jornadas_firmadas'] === 2,
        'El PDF personal conserva el jefe y una sola firma del trabajador desde las evidencias de jornadas');
    comprobarFirma(!isset($personal['biotime']) && $personal['totales']['ORD'] > 0,
        'El formato personal utiliza horas guardadas, sin marcaciones ni indicadores BioTime');
    require_once __DIR__ . '/../view/PDF/jornadas_formato.php';
    $pdfPersonal = je_crear_pdf([$personal], ['jlot_nombre' => 'Reporte personal de jornadas']);
    comprobarFirma($pdfPersonal->getNumPages() === 2 && substr($pdfPersonal->Output('', 'S'), 0, 5) === '%PDF-',
        'Las jornadas propias generan el PDF institucional completo de dos páginas');
    $otro = $modelo->obtener_reporte_propio_pdf(2, '2026-10-19', '2026-10-20');
    comprobarFirma($otro['empleado'] === 'Dos' && !array_intersect(
        array_column($personal['jornadas'], 'jornada_id'), array_column($otro['jornadas'], 'jornada_id')
    ), 'La exportación personal mantiene separados los registros de cada trabajador');
    rechazarFirma(fn() => $modelo->obtener_reporte_propio_pdf(1, '2026-10-19', '2026-10-20', 'APROBADO'));
    rechazarFirma(fn() => $modelo->obtener_reporte_propio_pdf(1, '2025-01-01', '2025-01-15'));
    comprobarFirma(true, 'El PDF personal informa periodos o estados sin jornadas');
} finally {
    $db->exec('DROP SCHEMA ' . $schema . ' CASCADE');
}
