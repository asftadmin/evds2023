<?php
/** Integración de periodos sugeridos en PostgreSQL aislado; nunca carga conexion.php. */
if (!getenv('JORNADAS_TEST_DSN')) {
    fwrite(STDERR, "Defina JORNADAS_TEST_DSN hacia una base de pruebas.\n");
    exit(1);
}
class Conectar {
    protected function Conexion() { return $GLOBALS['dbInicioReporte']; }
}
require_once __DIR__ . '/../models/JornadaReporteContable.php';

function comprobarInicioReporte($condicion, $mensaje) {
    if (!$condicion) throw new RuntimeException($mensaje);
    echo "OK: $mensaje\n";
}

$dbInicioReporte = new PDO(getenv('JORNADAS_TEST_DSN'), null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$schemaInicioReporte = 'test_inicio_reporte_' . bin2hex(random_bytes(6));
$dbInicioReporte->exec('CREATE SCHEMA ' . $schemaInicioReporte);
$dbInicioReporte->exec('SET search_path TO ' . $schemaInicioReporte);
try {
    // Subconjunto de columnas reales usadas por la creación, diagnóstico y corrección.
    $dbInicioReporte->exec(<<<'SQL'
CREATE TABLE empleados (id_empl integer PRIMARY KEY, esta_empl integer NOT NULL DEFAULT 1);
CREATE TABLE jornada_estados (je_id serial PRIMARY KEY, je_codigo varchar(40) UNIQUE NOT NULL);
ALTER SEQUENCE jornada_estados_je_id_seq RESTART WITH 101;
INSERT INTO jornada_estados (je_codigo) VALUES ('BORRADOR'), ('APROBADO'), ('LIQUIDADO'), ('ANULADO'), ('RECHAZADO');
CREATE TABLE jornadas_trabajo (
    jornada_id bigserial PRIMARY KEY, empleado_id integer REFERENCES empleados,
    jornada_inicio timestamp, jornada_fin timestamp, jornada_estado_id integer REFERENCES jornada_estados,
    jornada_inconsistente integer DEFAULT 0
);
CREATE TABLE jornada_lotes_reporte (
    jlot_id bigserial PRIMARY KEY, jlot_nombre varchar(120) NOT NULL, jlot_fecha_corte date NOT NULL,
    jlot_estado varchar(20) DEFAULT 'BORRADOR', jlot_tipo varchar(20) DEFAULT 'NORMAL',
    jlot_lote_origen_id bigint, jlot_version integer DEFAULT 1,
    jlot_version_formato varchar(20) DEFAULT 'GH-F-19 v3', jlot_creado_por integer,
    jlot_fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP, UNIQUE (jlot_nombre, jlot_fecha_corte)
);
CREATE TABLE jornada_lote_empleados (
    jle_id bigserial PRIMARY KEY, jlot_id bigint REFERENCES jornada_lotes_reporte,
    empleado_id integer REFERENCES empleados, jle_desde date, jle_hasta date NOT NULL,
    jle_origen_periodo varchar(30) DEFAULT 'SUGERIDO', jle_motivo_ajuste text,
    jle_estado varchar(30) DEFAULT 'PENDIENTE', jle_cantidad_jornadas integer DEFAULT 0,
    jle_cantidad_pendientes integer DEFAULT 0, jle_minutos_reportables integer DEFAULT 0,
    jle_diagnostico text, jle_snapshot jsonb, jle_actualizado_por integer,
    jle_fecha_actualizacion timestamp DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (jlot_id, empleado_id), CHECK (jle_desde IS NULL OR jle_hasta >= jle_desde)
);
CREATE TABLE jornada_conceptos (jcon_id integer PRIMARY KEY, jcon_codigo varchar(30));
CREATE TABLE jornada_clasificaciones (jornada_id bigint, jcon_id integer, jcla_minutos integer);
CREATE TABLE jornada_lote_auditoria (
    jla_id bigserial PRIMARY KEY, jlot_id bigint, jle_id bigint, jla_accion varchar(50),
    jla_datos_anteriores jsonb, jla_datos_nuevos jsonb, jla_motivo text, jla_usuario_id integer
);
INSERT INTO empleados (id_empl) SELECT generate_series(1, 13);
UPDATE empleados SET esta_empl = 0 WHERE id_empl = 13;
INSERT INTO jornada_lotes_reporte (jlot_nombre, jlot_fecha_corte, jlot_estado)
VALUES ('Cierre previo', '2026-10-15', 'CERRADO');
INSERT INTO jornada_lote_empleados (jlot_id, empleado_id, jle_desde, jle_hasta, jle_snapshot)
SELECT 1, id_empl, '2026-10-01', '2026-10-15', '{"prueba":"inmutable"}'::jsonb
FROM empleados WHERE id_empl IN (1, 2, 3, 4, 5, 8, 10, 11, 12, 13);
SQL
    );
    // Los estados se resuelven por código, con IDs distintos de los habituales.
    $insertar = $dbInicioReporte->prepare("INSERT INTO jornadas_trabajo
        (empleado_id, jornada_inicio, jornada_fin, jornada_estado_id)
        SELECT ?, ?::timestamp, ?::timestamp, je_id FROM jornada_estados WHERE je_codigo = ?");
    $datos = [
        [1, '2026-10-16', 'BORRADOR'], [2, '2026-10-17', 'BORRADOR'], [3, '2026-10-20', 'BORRADOR'],
        [4, '2026-10-10', 'BORRADOR'], [4, '2026-11-01', 'BORRADOR'],
        [5, '2026-10-16', 'ANULADO'], [5, '2026-10-17', 'RECHAZADO'], [5, '2026-10-20', 'APROBADO'],
        [6, '2026-07-01', 'ANULADO'], [6, '2026-07-02', 'RECHAZADO'], [6, '2026-08-01', 'BORRADOR'],
        [7, '2026-10-16', 'ANULADO'], [7, '2026-10-17', 'RECHAZADO'],
        [8, '2026-11-01', 'BORRADOR'], [9, '2026-11-01', 'BORRADOR'],
        [10, '2026-10-15', 'BORRADOR'], [10, '2026-10-20', 'BORRADOR'],
        [11, '2026-10-31', 'BORRADOR'], [11, '2026-11-01', 'BORRADOR'],
        [12, '2026-10-17', 'BORRADOR'], [13, '2026-10-16', 'BORRADOR']
    ];
    foreach ($datos as [$empleado, $dia, $estado]) {
        $insertar->execute([$empleado, $dia . ' 08:00:00', $dia . ' 17:00:00', $estado]);
    }
    $dbInicioReporte->exec(<<<'SQL'
INSERT INTO jornada_lotes_reporte (jlot_nombre, jlot_fecha_corte, jlot_estado) VALUES
    ('Cierre al corte', '2026-10-31', 'CERRADO'),
    ('Reemplazado', '2026-10-25', 'REEMPLAZADO'),
    ('Anulado', '2026-10-28', 'ANULADO'),
    ('Borrador', '2026-10-30', 'BORRADOR');
INSERT INTO jornada_lote_empleados (jlot_id, empleado_id, jle_desde, jle_hasta) VALUES
    (2, 8, '2026-10-16', '2026-10-31'), (3, 12, '2026-10-16', '2026-10-25'),
    (4, 12, '2026-10-16', '2026-10-28'), (5, 12, '2026-10-16', '2026-10-30');
SQL
    );
    $modeloInicio = new JornadaReporteContable();
    $calcularInicio = new ReflectionMethod($modeloInicio, 'calcular_inicio_sugerido');
    $esperados = [1 => '2026-10-16', 2 => '2026-10-17', 3 => '2026-10-20', 4 => null,
        5 => '2026-10-20', 6 => '2026-08-01', 7 => null, 8 => null, 9 => null,
        10 => '2026-10-20', 11 => '2026-10-31', 12 => '2026-10-17'];
    foreach ($esperados as $empleado => $esperado) {
        $inicio = $calcularInicio->invoke($modeloInicio, $dbInicioReporte, $empleado, '2026-10-31');
        comprobarInicioReporte($inicio === $esperado,
            'Inicio sugerido empleado ' . $empleado . ': ' . ($esperado ?? 'sin jornada elegible'));
    }
    // Comprueba el flujo público completo, sin sustituir recalcular_fila().
    $historico = $dbInicioReporte->query('SELECT * FROM jornada_lote_empleados ORDER BY jle_id')->fetchAll(PDO::FETCH_ASSOC);
    $nuevoLote = $modeloInicio->crear_lote('Nuevo periodo real', '2026-10-31', 9);
    $consultaFilas = $dbInicioReporte->prepare('SELECT * FROM jornada_lote_empleados WHERE jlot_id = ? ORDER BY empleado_id');
    $consultaFilas->execute([$nuevoLote]);
    $nuevas = $consultaFilas->fetchAll(PDO::FETCH_ASSOC);
    $fechas = [];
    foreach ($nuevas as $nueva) {
        $fechas[(int)$nueva['empleado_id']] = $nueva['jle_desde'];
        comprobarInicioReporte($nueva['jle_hasta'] === '2026-10-31' && $nueva['jle_origen_periodo'] === 'SUGERIDO'
            && $nueva['jle_desde'] <= $nueva['jle_hasta'], 'Corte común y periodo SUGERIDO válido: ' . $nueva['empleado_id']);
    }
    comprobarInicioReporte($fechas === array_filter($esperados, function ($fecha) { return $fecha !== null; }),
        'crear_lote inserta solo empleados con jornada real elegible y omite inactivos');
    comprobarInicioReporte($nuevas[0]['jle_estado'] === 'PENDIENTE', 'El diagnóstico contable existente se conserva');
    $consultaHistorico = $dbInicioReporte->prepare('SELECT * FROM jornada_lote_empleados WHERE jlot_id <> ? ORDER BY jle_id');
    $consultaHistorico->execute([$nuevoLote]);
    comprobarInicioReporte($consultaHistorico->fetchAll(PDO::FETCH_ASSOC) === $historico,
        'Crear el lote no modifica periodos, estados ni snapshots anteriores');
    // La corrección sigue copiando el periodo cerrado, sin volver a sugerirlo.
    $correccion = $modeloInicio->crear_correccion(1, 9);
    $consultaFilas->execute([$correccion]);
    $filasCorreccion = $consultaFilas->fetchAll(PDO::FETCH_ASSOC);
    comprobarInicioReporte(count($filasCorreccion) === 10, 'La corrección conserva todos los empleados del lote origen');
    foreach ($filasCorreccion as $filaCorreccion) {
        comprobarInicioReporte($filaCorreccion['jle_desde'] === '2026-10-01' && $filaCorreccion['jle_hasta'] === '2026-10-15'
            && $filaCorreccion['jle_origen_periodo'] === 'CORRECCION',
            'La corrección conserva el periodo original: ' . $filaCorreccion['empleado_id']);
    }
    $consultaFilas->execute([1]);
    comprobarInicioReporte($consultaFilas->fetchAll(PDO::FETCH_ASSOC) === array_values(array_filter($historico, function ($fila) {
        return (int)$fila['jlot_id'] === 1;
    })), 'El lote cerrado original permanece inmutable tras crear la corrección');

    // El borrador se crea antes de que existan las jornadas del empleado faltante.
    $loteTardio = $modeloInicio->crear_lote('Octubre con ingreso posterior', '2026-10-15', 9);
    $consultaFilas->execute([$loteTardio]);
    $filasAntes = $consultaFilas->fetchAll(PDO::FETCH_ASSOC);
    comprobarInicioReporte(count($filasAntes) === 1, 'El borrador inicial contiene solo el empleado elegible al crearlo');
    $filaAjustada = $modeloInicio->actualizar_periodo((int)$filasAntes[0]['jle_id'],
        '2026-09-16', '2026-10-15', 'Ajuste manual del periodo', 9);
    $dbInicioReporte->exec('INSERT INTO empleados (id_empl) SELECT generate_series(14, 18)');
    $dbInicioReporte->exec('UPDATE empleados SET esta_empl=0 WHERE id_empl=15');
    foreach ([[14, '2026-09-23', 'LIQUIDADO'], [15, '2026-09-23', 'BORRADOR'],
        [16, '2026-09-23', 'ANULADO'], [17, '2026-09-23', 'RECHAZADO'], [18, '2026-11-01', 'BORRADOR']] as [$emp, $dia, $estado]) {
        $insertar->execute([$emp, $dia . ' 08:00:00', $dia . ' 17:00:00', $estado]);
    }
    $dbInicioReporte->exec("INSERT INTO jornada_conceptos VALUES (1,'ORD');
        INSERT INTO jornada_clasificaciones SELECT jornada_id, 1, 540 FROM jornadas_trabajo WHERE empleado_id=14");
    $validacion = $modeloInicio->refrescar_lote($loteTardio, 9);
    comprobarInicioReporte($validacion['empleados_agregados'] === 1, 'Validar de nuevo incorpora al empleado registrado después de crear el lote');
    $consultaFilas->execute([$loteTardio]);
    $filasDespues = $consultaFilas->fetchAll(PDO::FETCH_ASSOC);
    comprobarInicioReporte(array_map('intval', array_column($filasDespues, 'empleado_id')) === [6, 14],
        'No incorpora inactivos, anuladas, rechazadas ni jornadas posteriores al corte');
    comprobarInicioReporte($filasDespues[1]['jle_desde'] === '2026-09-23' && $filasDespues[1]['jle_hasta'] === '2026-10-15'
        && $filasDespues[1]['jle_origen_periodo'] === 'SUGERIDO' && $filasDespues[1]['jle_estado'] === 'LISTO',
        'El empleado nuevo usa su primera jornada real, el corte común y el diagnóstico existente');
    comprobarInicioReporte($filasDespues[0]['jle_desde'] === $filaAjustada['jle_desde']
        && $filasDespues[0]['jle_hasta'] === $filaAjustada['jle_hasta']
        && $filasDespues[0]['jle_origen_periodo'] === 'MANUAL'
        && $filasDespues[0]['jle_motivo_ajuste'] === 'Ajuste manual del periodo',
        'Validar de nuevo conserva las fechas, origen y motivo de los periodos ajustados');
    $repetida = $modeloInicio->refrescar_lote($loteTardio, 9);
    comprobarInicioReporte($repetida['empleados_agregados'] === 0, 'Repetir la validación no duplica empleados');
    $auditoria = $dbInicioReporte->prepare("SELECT COUNT(*) FROM jornada_lote_auditoria WHERE jlot_id=? AND jla_accion='AGREGAR_EMPLEADOS'");
    $auditoria->execute([$loteTardio]);
    comprobarInicioReporte((int)$auditoria->fetchColumn() === 1, 'Se audita la incorporación una sola vez');
    $validacionCorreccion = $modeloInicio->refrescar_lote($correccion, 9);
    $consultaFilas->execute([$correccion]);
    comprobarInicioReporte($validacionCorreccion['empleados_agregados'] === 0
        && array_column($consultaFilas->fetchAll(PDO::FETCH_ASSOC), 'empleado_id') === array_column($filasCorreccion, 'empleado_id'),
        'Validar una corrección no agrega empleados ajenos al lote origen');
    foreach ([1, 3, 4] as $loteProtegido) {
        $rechazado = false;
        try { $modeloInicio->refrescar_lote($loteProtegido, 9); }
        catch (RuntimeException $e) { $rechazado = true; }
        comprobarInicioReporte($rechazado, 'No permite validar lotes cerrados, reemplazados o anulados: ' . $loteProtegido);
    }

    // Una falla de auditoría debe revertir tanto el alta como la revalidación.
    $dbInicioReporte->exec('INSERT INTO empleados (id_empl) VALUES (19)');
    $insertar->execute([19, '2026-09-24 08:00:00', '2026-09-24 17:00:00', 'BORRADOR']);
    $consultaFilas->execute([$loteTardio]);
    $antesFallo = $consultaFilas->fetchAll(PDO::FETCH_ASSOC);
    $dbInicioReporte->exec("ALTER TABLE jornada_lote_auditoria ADD CONSTRAINT fallo_auditoria
        CHECK (jla_accion <> 'AGREGAR_EMPLEADOS') NOT VALID");
    $fallo = false;
    try { $modeloInicio->refrescar_lote($loteTardio, 9); }
    catch (PDOException $e) { $fallo = true; }
    $consultaFilas->execute([$loteTardio]);
    comprobarInicioReporte($fallo && $consultaFilas->fetchAll(PDO::FETCH_ASSOC) === $antesFallo,
        'Una falla revierte la incorporación y todos los cambios de diagnóstico');
    $dbInicioReporte->exec('ALTER TABLE jornada_lote_auditoria DROP CONSTRAINT fallo_auditoria');
    comprobarInicioReporte($modeloInicio->refrescar_lote($loteTardio, 9)['empleados_agregados'] === 1,
        'La validación puede reintentarse después de un rollback');
} finally {
    if ($dbInicioReporte->inTransaction()) $dbInicioReporte->rollBack();
    $dbInicioReporte->exec('DROP SCHEMA ' . $schemaInicioReporte . ' CASCADE');
}
