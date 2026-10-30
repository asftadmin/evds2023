<?php
/** Exportaciones reales con datos sintéticos; PostgreSQL opcional en esquema desechable. */
require_once __DIR__ . '/../view/PDF/jornadas_formato.php';

function snapshotHistoricoPrueba() {
    return ['empleado_id' => 1, 'empleado' => 'PRUEBA HISTORICA', 'documento' => '123456', 'cargo' => 'TECNICO',
        'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Masculino', 'estado' => 'LISTO',
        'firma_empleado' => null, 'firma_jefe' => null, 'firmas_jefes' => [], 'mostrar_firmas' => true,
        'desde' => '2026-09-16', 'hasta' => '2026-09-30', 'totales' => ['ORD' => 540],
        'jornadas' => [['jornada_id' => 1, 'jornada_inicio' => '2026-09-20 08:00:00',
            'jornada_fin' => '2026-09-20 17:00:00', 'jornada_minutos_ordinarios' => 540,
            'jornada_ubicacion' => 'Sede principal', 'jornada_actividad' => 'Trabajo de prueba',
            'jornada_observaciones' => '', 'jornada_origen' => 'EMPLEADO', 'aprobado_por' => 'Jefe',
            'segmentos' => [['codigo' => 'ORD', 'concepto' => 'Ordinarias', 'codigo_contable' => '100',
                'inicio' => '2026-09-20 08:00:00', 'fin' => '2026-09-20 17:00:00', 'minutos' => 540]]]]];
}

// Ejecuta el exportador Excel real en un subproceso porque termina con exit.
// Solo carga sus funciones: no ejecuta conexión, sesión ni despacho HTTP del controlador.
if (($argv[1] ?? '') === '--excel') {
    $fuente = file_get_contents(__DIR__ . '/../controller/jornada_exportar.php');
    $inicio = strpos($fuente, 'function je_fallar(');
    $fin = strpos($fuente, "\ntry {", $inicio);
    if ($inicio === false || $fin === false) throw new RuntimeException('No se encontró el exportador.');
    eval(substr($fuente, $inicio, $fin - $inicio));
    je_excel([snapshotHistoricoPrueba()], ['jlot_nombre' => 'LOTE HISTORICO', 'jlot_estado' => $argv[2],
        'jlot_fecha_corte' => '2026-09-30']);
}

function comprobarHistorico($condicion, $mensaje) {
    if (!$condicion) throw new RuntimeException($mensaje);
    echo "OK: $mensaje\n";
}
function rechazarHistorico($accion, $mensaje) {
    try { $accion(); } catch (RuntimeException $e) { comprobarHistorico(true, $mensaje); return; }
    throw new RuntimeException($mensaje);
}

$snapshot = snapshotHistoricoPrueba();
foreach (['ANULADO', 'CERRADO', 'REEMPLAZADO'] as $estado) {
    $pdf = je_crear_pdf([$snapshot], ['jlot_nombre' => 'LOTE HISTORICO', 'jlot_estado' => $estado]);
    $pdf->setCompression(false);
    $paginas = $pdf->getNumPages();
    $binario = $pdf->Output('', 'S');
    $marca = mb_convert_encoding('ANULADO - SOLO CONSULTA', 'UTF-16BE', 'UTF-8');
    $cantidad = substr_count($binario, $marca) + substr_count(strtolower($binario), bin2hex($marca));
    comprobarHistorico(substr($binario, 0, 5) === '%PDF-' && $paginas === 2, 'PDF histórico real de dos páginas: ' . $estado);
    comprobarHistorico($estado === 'ANULADO' ? $cantidad >= 2 : $cantidad === 0,
        'Advertencia de anulación en todas las páginas solo cuando corresponde: ' . $estado);

    $proceso = proc_open([PHP_BINARY, __FILE__, '--excel', $estado],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
    if (!is_resource($proceso)) throw new RuntimeException('No se pudo ejecutar el exportador Excel.');
    fclose($tuberias[0]);
    $xml = stream_get_contents($tuberias[1]);
    $errores = stream_get_contents($tuberias[2]);
    fclose($tuberias[1]);
    fclose($tuberias[2]);
    $codigo = proc_close($proceso);
    comprobarHistorico($codigo === 0 && $errores === '', 'Exportador Excel sin errores: ' . $estado);
    $dom = new DOMDocument();
    comprobarHistorico($dom->loadXML($xml), 'Excel XML válido: ' . $estado);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
    comprobarHistorico($xpath->query('//ss:Worksheet')->length === 4, 'Conserva las cuatro hojas: ' . $estado);
    $advertencias = $xpath->query('//ss:Worksheet/ss:Table/ss:Row[1]/ss:Cell/ss:Data[text()="ANULADO - SOLO CONSULTA HISTÓRICA"]');
    comprobarHistorico($advertencias->length === ($estado === 'ANULADO' ? 4 : 0), 'Identifica cada hoja anulada sin marcar los reportes vigentes: ' . $estado);
    comprobarHistorico(strpos($xml, 'PRUEBA HISTORICA') !== false && strpos($xml, 'Trabajo de prueba') !== false,
        'Conserva empleado y detalle del snapshot: ' . $estado);
}

if (!getenv('JORNADAS_TEST_DSN')) {
    echo "PENDIENTE: integración PostgreSQL; configure JORNADAS_TEST_DSN.\n";
    exit(0);
}
class Conectar {
    protected function Conexion() { return $GLOBALS['dbHistorico']; }
}
require_once __DIR__ . '/../models/JornadaReporteContable.php';
$dbHistorico = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$schemaHistorico = 'test_anulados_' . bin2hex(random_bytes(6));
$dbHistorico->exec('CREATE SCHEMA ' . $schemaHistorico);
$dbHistorico->exec('SET search_path TO ' . $schemaHistorico);
try {
    $dbHistorico->exec(<<<'SQL'
CREATE TABLE jornada_lotes_reporte (
    jlot_id integer PRIMARY KEY, jlot_nombre text, jlot_estado text, jlot_fecha_corte date,
    jlot_lote_origen_id integer
);
CREATE TABLE jornada_lote_empleados (jle_id integer PRIMARY KEY, jlot_id integer, jle_snapshot jsonb);
CREATE TABLE jornada_lote_auditoria (jlot_id integer, jle_id integer, jla_accion text,
    jla_datos_anteriores jsonb, jla_datos_nuevos jsonb, jla_motivo text, jla_usuario_id integer);
INSERT INTO jornada_lotes_reporte VALUES
    (1, 'Cierre histórico', 'CERRADO', '2026-09-30', NULL),
    (2, 'Borrador sin cierre', 'BORRADOR', '2026-09-30', NULL),
    (3, 'Reemplazado', 'REEMPLAZADO', '2026-09-30', NULL),
    (4, 'Borrador', 'BORRADOR', '2026-09-30', NULL);
INSERT INTO jornada_lote_empleados VALUES (2, 2, NULL);
SQL
    );
    $insertar = $dbHistorico->prepare('INSERT INTO jornada_lote_empleados VALUES (?, ?, ?::jsonb)');
    foreach ([1, 3, 4] as $id) $insertar->execute([$id, $id, json_encode($snapshot, JSON_UNESCAPED_UNICODE)]);
    $modelo = new JornadaReporteContable();
    comprobarHistorico($modelo->obtener_snapshot_empleado(1)['snapshot'] == $snapshot, 'El lote cerrado sigue exportando su snapshot');
    comprobarHistorico(count($modelo->obtener_snapshots_lote(3)['snapshots']) === 1, 'El lote reemplazado conserva su exportación');
    rechazarHistorico(fn() => $modelo->obtener_snapshot_empleado(4), 'Un borrador no exporta un PDF cerrado');
    rechazarHistorico(fn() => $modelo->obtener_snapshots_lote(4), 'Un borrador no exporta un lote cerrado');
    $antes = $dbHistorico->query('SELECT jle_snapshot::text FROM jornada_lote_empleados WHERE jle_id=1')->fetchColumn();
    $modelo->anular_lote(1, 'Corregir distribución del periodo', 9);
    $individual = $modelo->obtener_snapshot_empleado(1);
    $lote = $modelo->obtener_snapshots_lote(1);
    comprobarHistorico($individual['lote']['jlot_estado'] === 'ANULADO' && $individual['snapshot'] == $snapshot,
        'El PDF individual anulado usa la misma copia histórica');
    comprobarHistorico($lote['lote']['jlot_estado'] === 'ANULADO' && $lote['snapshots'] == [$snapshot],
        'PDF por lote y Excel recuperan los snapshots anulados');
    comprobarHistorico($antes === $dbHistorico->query('SELECT jle_snapshot::text FROM jornada_lote_empleados WHERE jle_id=1')->fetchColumn(),
        'Anular y exportar no alteran el snapshot guardado');
    comprobarHistorico((int)$dbHistorico->query("SELECT COUNT(*) FROM jornada_lote_auditoria WHERE jla_accion='ANULAR'")->fetchColumn() === 1,
        'Se conserva la auditoría de anulación');
    rechazarHistorico(fn() => $modelo->cerrar_lote(1, 9), 'La descarga histórica no permite volver a cerrar el anulado');
    rechazarHistorico(fn() => $modelo->crear_correccion(1, 9), 'La descarga histórica no permite corregir el anulado');
    rechazarHistorico(fn() => $modelo->refrescar_lote(1, 9), 'La descarga histórica no recalcula el anulado');
    $modelo->anular_lote(2, 'Borrador descartado', 9);
    rechazarHistorico(fn() => $modelo->obtener_snapshot_empleado(2), 'Borrador anulado sin snapshot no tiene PDF histórico');
    rechazarHistorico(fn() => $modelo->obtener_snapshots_lote(2), 'Borrador anulado sin snapshots no tiene PDF/Excel por lote');
} finally {
    if ($dbHistorico->inTransaction()) $dbHistorico->rollBack();
    $dbHistorico->exec('DROP SCHEMA ' . $schemaHistorico . ' CASCADE');
}
