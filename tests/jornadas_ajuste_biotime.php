<?php
/** Pruebas de ajustes; PostgreSQL opcional en esquema desechable mediante JORNADAS_TEST_DSN. */
class Conectar {
    protected function Conexion() { return $GLOBALS['dbAjusteBiotime']; }
}
require_once __DIR__ . '/../models/JornadaCruceBiotime.php';
function comprobarAjuste($condicion, $mensaje) {
    if (!$condicion) throw new RuntimeException($mensaje);
    echo "OK: $mensaje\n";
}
function rechazarAjuste($accion, $mensaje) {
    try { $accion(); } catch (RuntimeException | InvalidArgumentException $e) {
        comprobarAjuste(true, $mensaje);
        return;
    }
    throw new RuntimeException('No se rechazó: ' . $mensaje);
}
$actual = ['jornada_id' => 1, 'empleado_id' => 1, 'jornada_version' => 2,
    'jornada_inicio' => '2026-09-01 03:00:00', 'jornada_fin' => '2026-09-01 16:00:00',
    'je_codigo' => 'PENDIENTE_LIQUIDACION'];
$snapshot = json_encode(['jornada_inicio' => $actual['jornada_inicio'], 'jornada_fin' => $actual['jornada_fin']]);
$fila = ['jornada' => 1, 'reporte' => 1, 'empleado_id' => 1, 'version_actual' => 2,
    'inicio_actual' => $actual['jornada_inicio'], 'fin_actual' => $actual['jornada_fin'],
    'estado_actual' => $actual['je_codigo'], 'snapshot_hash' => hash('sha256', $snapshot),
    'entrada_bio' => '2026-09-01 03:30:00', 'salida_bio' => '2026-09-01 16:30:00'];
$clave = 'clave-privada-solo-pruebas';
$token = JornadaCruceBiotime::autorizar_evidencia($fila, $clave, 7);
$evidencia = JornadaCruceBiotime::validar_evidencia($token, 'entrada', $clave, 7);
$ajuste = JornadaCruceBiotime::preparar_ajuste($actual, $evidencia, 'entrada');
comprobarAjuste($ajuste['nueva']['jornada_inicio'] === $fila['entrada_bio']
    && $ajuste['nueva']['jornada_fin'] === $actual['jornada_fin'], 'Entrada cambia sin tocar salida');
$ajuste = JornadaCruceBiotime::preparar_ajuste($actual, $evidencia, 'salida');
comprobarAjuste($ajuste['nueva']['jornada_fin'] === $fila['salida_bio']
    && $ajuste['nueva']['jornada_inicio'] === $actual['jornada_inicio'], 'Salida cambia sin tocar entrada');
rechazarAjuste(fn() => JornadaCruceBiotime::validar_evidencia($token . '0', 'entrada', $clave, 7), 'Rechaza evidencia alterada');
rechazarAjuste(fn() => JornadaCruceBiotime::validar_evidencia($token, 'entrada', 'otra-sesion', 7), 'Rechaza evidencia de otra sesión');
rechazarAjuste(fn() => JornadaCruceBiotime::validar_evidencia($token, 'entrada', $clave, 8), 'Rechaza evidencia de otro usuario');
rechazarAjuste(fn() => JornadaCruceBiotime::validar_evidencia($token, 'jornada_estado_id', $clave, 7), 'Rechaza campos distintos de entrada/salida');
$sinSalida = $fila;
$sinSalida['salida_bio'] = '';
$tokenParcial = JornadaCruceBiotime::autorizar_evidencia($sinSalida, $clave, 7);
rechazarAjuste(fn() => JornadaCruceBiotime::validar_evidencia($tokenParcial, 'salida', $clave, 7), 'No permite salida sin evidencia');
comprobarAjuste(JornadaCruceBiotime::validar_evidencia($tokenParcial, 'entrada', $clave, 7)['entrada'] === $fila['entrada_bio'], 'Permite entrada con marcación única');
$vencida = $evidencia;
$vencida['expira'] = time() - 1;
$textoVencido = base64_encode(json_encode($vencida));
$tokenVencido = $textoVencido . '.' . hash_hmac('sha256', $textoVencido, $clave);
rechazarAjuste(fn() => JornadaCruceBiotime::validar_evidencia($tokenVencido, 'entrada', $clave, 7), 'Rechaza evidencia vencida');
$concurrente = $actual;
$concurrente['jornada_version']++;
rechazarAjuste(fn() => JornadaCruceBiotime::preparar_ajuste($concurrente, $evidencia, 'entrada'), 'Rechaza versión obsoleta');
$anulada = $actual;
$anulada['je_codigo'] = 'ANULADO';
$evidenciaAnulada = $evidencia;
$evidenciaAnulada['estado'] = 'ANULADO';
rechazarAjuste(fn() => JornadaCruceBiotime::preparar_ajuste($anulada, $evidenciaAnulada, 'entrada'), 'Respeta estados no editables');
$intervaloInvalido = $evidencia;
$intervaloInvalido['entrada'] = '2026-09-01 17:00:00';
rechazarAjuste(fn() => JornadaCruceBiotime::preparar_ajuste($actual, $intervaloInvalido, 'entrada'), 'Rechaza entrada posterior a salida');

// Integración real: el esquema temporal no contiene datos de producción y se elimina al terminar.
if (!getenv('JORNADAS_TEST_DSN')) {
    echo "PENDIENTE: integración PostgreSQL; configure JORNADAS_TEST_DSN.\n";
    exit(0);
}
$dbAjusteBiotime = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$schemaAjuste = 'test_biotime_' . bin2hex(random_bytes(6));
try {
    $dbAjusteBiotime->exec('CREATE SCHEMA ' . $schemaAjuste);
    $dbAjusteBiotime->exec('SET search_path TO ' . $schemaAjuste);
    $dbAjusteBiotime->exec("CREATE TABLE jornada_estados (je_id integer PRIMARY KEY, je_codigo varchar(40));
        INSERT INTO jornada_estados VALUES (1, 'PENDIENTE_LIQUIDACION');
        CREATE TABLE jornadas_trabajo (jornada_id bigint PRIMARY KEY, empleado_id integer,
            jornada_inicio timestamp, jornada_fin timestamp, jornada_version integer,
            jornada_estado_id integer, jornada_fecha_actualizacion timestamp DEFAULT CURRENT_TIMESTAMP,
            jornada_minutos_ordinarios integer DEFAULT 720, CHECK (jornada_fin > jornada_inicio));
        INSERT INTO jornadas_trabajo (jornada_id, empleado_id, jornada_inicio, jornada_fin, jornada_version, jornada_estado_id)
            VALUES (1, 1, '2026-09-01 03:00', '2026-09-01 16:00', 2, 1);
        CREATE TABLE jornada_reportes_firma (jrf_id bigint PRIMARY KEY, empleado_id integer, jrf_estado varchar(20));
        INSERT INTO jornada_reportes_firma VALUES (1, 1, 'FIRMADO');
        CREATE TABLE empleados (id_empl integer PRIMARY KEY, cedu_empl varchar(30), nomb_empl varchar(100));
        INSERT INTO empleados VALUES (1, '123', 'Prueba'), (2, '456', 'Continuidad');
        CREATE TABLE jornada_reporte_detalle (jrd_id bigserial PRIMARY KEY, jrf_id bigint, jornada_id bigint, jrd_snapshot jsonb, jrd_jornada_version integer);
        CREATE TABLE jornada_auditoria (jornada_id bigint, jaud_accion varchar(60), jaud_estado_anterior varchar(40),
            jaud_estado_nuevo varchar(40), jaud_datos_anteriores jsonb, jaud_datos_nuevos jsonb,
            jaud_motivo text, jaud_usuario_id integer)");
    $stmt = $dbAjusteBiotime->prepare('INSERT INTO jornada_reporte_detalle (jrf_id, jornada_id, jrd_snapshot, jrd_jornada_version) VALUES (1, 1, CAST(? AS jsonb), 1)');
    $stmt->execute([$snapshot]);
    // PostgreSQL normaliza JSONB: se autentica exactamente la representación consultada.
    $snapshotOriginal = $dbAjusteBiotime->query('SELECT jrd_snapshot FROM jornada_reporte_detalle')->fetchColumn();
    $fila['snapshot_hash'] = hash('sha256', $snapshotOriginal);
    $token = JornadaCruceBiotime::autorizar_evidencia($fila, $clave, 7);
    $modelo = new JornadaCruceBiotime();
    $r = $modelo->ajustar_desde_biotime($token, 'entrada', $clave, 7);
    comprobarAjuste($r['version_actual'] === 3 && $r['inicio_actual'] === $fila['entrada_bio']
        && $r['fin_actual'] === $actual['jornada_fin'], 'Transacción incrementa versión y modifica solo entrada');
    comprobarAjuste($dbAjusteBiotime->query('SELECT jrd_snapshot FROM jornada_reporte_detalle')->fetchColumn() === $snapshotOriginal,
        'Snapshot firmado permanece intacto');
    comprobarAjuste((int)$dbAjusteBiotime->query('SELECT COUNT(*) FROM jornada_auditoria')->fetchColumn() === 1,
        'Genera auditoría del ajuste');
    rechazarAjuste(fn() => $modelo->ajustar_desde_biotime($token, 'salida', $clave, 7), 'No permite reutilizar versión anterior');
    $fila['version_actual'] = 3;
    $fila['inicio_actual'] = $r['inicio_actual'];
    $token = JornadaCruceBiotime::autorizar_evidencia($fila, $clave, 7);
    $r = $modelo->ajustar_desde_biotime($token, 'salida', $clave, 7);
    comprobarAjuste($r['version_actual'] === 4 && $r['inicio_actual'] === $fila['entrada_bio']
        && $r['fin_actual'] === $fila['salida_bio'], 'Permite ambas acciones consecutivas con evidencia actualizada');
    comprobarAjuste((int)$dbAjusteBiotime->query('SELECT jornada_minutos_ordinarios FROM jornadas_trabajo WHERE jornada_id=1')->fetchColumn() === 720,
        'No cambia cálculos de horas');

    // La selección incluye ambos extremos aunque el periodo corte el bloque a medianoche.
    $dbAjusteBiotime->exec("INSERT INTO jornada_reportes_firma VALUES (16, 2, 'FIRMADO');
        INSERT INTO jornadas_trabajo (jornada_id, empleado_id, jornada_inicio, jornada_fin, jornada_version, jornada_estado_id)
        VALUES (255, 2, '2026-10-06 20:30', '2026-10-07 00:00', 1, 1),
               (256, 2, '2026-10-07 00:00', '2026-10-07 16:00', 1, 1),
               (257, 2, '2026-10-08 08:00', '2026-10-08 16:00', 1, 1);
        INSERT INTO jornada_reporte_detalle (jrf_id, jornada_id, jrd_snapshot, jrd_jornada_version)
        SELECT 16, jornada_id, jsonb_build_object('jornada_inicio', jornada_inicio, 'jornada_fin', jornada_fin), 1
        FROM jornadas_trabajo WHERE empleado_id = 2");
    $vecinas = $modelo->reportes('2026-10-07', '2026-10-07', 2);
    comprobarAjuste(array_map('intval', array_column($vecinas, 'jornada_id')) === [255, 256],
        'Incluye la jornada anterior al periodo cuando continúa a medianoche');
    comprobarAjuste(array_map('intval', array_column($modelo->reportes('2026-10-06', '2026-10-06', 2), 'jornada_id')) === [255, 256],
        'Incluye la jornada posterior al periodo sin incorporar días desconectados');
    comprobarAjuste($modelo->reportes('2026-10-07', '2026-10-07', 1) === [], 'La consulta de vecinos respeta el empleado');
    comprobarAjuste(count($modelo->reportes('2026-10-07', '2026-10-07')) === 2, 'La consulta de continuidad permite todos los empleados');

    $dbAjusteBiotime->exec("INSERT INTO jornadas_trabajo (jornada_id, empleado_id, jornada_inicio, jornada_fin, jornada_version, jornada_estado_id)
        VALUES (511, 2, '2026-10-09 20:00', '2026-10-10 07:00', 1, 1),
               (512, 2, '2026-10-10 07:00', '2026-10-10 16:00', 1, 1),
               (513, 2, '2026-10-10 16:00', '2026-10-10 20:00', 1, 1);
        INSERT INTO jornada_reporte_detalle (jrf_id, jornada_id, jrd_snapshot, jrd_jornada_version)
        SELECT 16, jornada_id, jsonb_build_object('jornada_inicio', jornada_inicio, 'jornada_fin', jornada_fin), 1
        FROM jornadas_trabajo WHERE jornada_id IN (511, 512, 513)");
    comprobarAjuste(array_map('intval', array_column($modelo->reportes('2026-10-09', '2026-10-09', 2), 'jornada_id')) === [511, 512, 513],
        'Incluye continuaciones fuera del periodo con cortes a las 07:00 y 16:00');
    comprobarAjuste(array_map('intval', array_column($modelo->reportes('2026-10-10', '2026-10-10', 2), 'jornada_id')) === [511, 512, 513],
        'Recupera el inicio anterior al periodo mediante un corte distinto de medianoche');

    $fila['version_actual'] = 4;
    $fila['fin_actual'] = $r['fin_actual'];
    $fila['entrada_bio'] = '2026-09-01 04:00:00';
    $token = JornadaCruceBiotime::autorizar_evidencia($fila, $clave, 7);
    $dbAjusteBiotime->exec("ALTER TABLE jornada_auditoria ADD CONSTRAINT prueba_fallo CHECK (jaud_usuario_id <> 7) NOT VALID");
    try { $modelo->ajustar_desde_biotime($token, 'entrada', $clave, 7); }
    catch (PDOException $e) { /* Falla inyectada después del UPDATE para verificar rollback. */ }
    comprobarAjuste((int)$dbAjusteBiotime->query('SELECT jornada_version FROM jornadas_trabajo WHERE jornada_id=1')->fetchColumn() === 4,
        'Fallo de auditoría revierte el cambio de jornada');
} finally {
    if ($dbAjusteBiotime->inTransaction()) $dbAjusteBiotime->rollBack();
    $dbAjusteBiotime->exec('DROP SCHEMA IF EXISTS ' . $schemaAjuste . ' CASCADE');
}
