<?php

/**
 * Lotes documentales de jornadas. Los periodos pueden variar por empleado y,
 * al cerrar, se conserva un snapshot inmutable para reproducir los archivos.
 */
class JornadaReporteContable extends Conectar
{
    public function listar_horas_extras($desde, $hasta)
    {
        $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $desde);
        $fin = DateTimeImmutable::createFromFormat('!Y-m-d', $hasta);
        if (!$inicio || !$fin || $inicio->format('Y-m-d') !== $desde
            || $fin->format('Y-m-d') !== $hasta || $inicio->format('d') !== '16'
            || $inicio->modify('+1 month')->modify('-1 day')->format('Y-m-d') !== $hasta) {
            throw new InvalidArgumentException('Seleccione un periodo completo del 16 al 15.');
        }
        $db = parent::Conexion();
        $stmt = $db->prepare("SELECT emp.id_empl AS empleado_id,
                emp.nomb_empl AS empleado, emp.cedu_empl AS documento,
                COALESCE(SUM(c.jcla_minutos) FILTER (WHERE con.jcon_codigo = 'RN'), 0) / 60.0 AS rn,
                COALESCE(SUM(c.jcla_minutos) FILTER (WHERE con.jcon_codigo = 'HED'), 0) / 60.0 AS hed,
                COALESCE(SUM(c.jcla_minutos) FILTER (WHERE con.jcon_codigo = 'HEN'), 0) / 60.0 AS hen,
                COALESCE(SUM(c.jcla_minutos) FILTER (WHERE con.jcon_codigo = 'HEDF'), 0) / 60.0 AS hedf,
                COALESCE(SUM(c.jcla_minutos) FILTER (WHERE con.jcon_codigo = 'HENF'), 0) / 60.0 AS henf
            FROM jornadas_trabajo j
            INNER JOIN jornada_estados estado ON estado.je_id = j.jornada_estado_id
            INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
            LEFT JOIN jornada_clasificaciones c ON c.jornada_id = j.jornada_id
            LEFT JOIN jornada_conceptos con ON con.jcon_id = c.jcon_id
            WHERE estado.je_codigo = 'LIQUIDADO'
              AND j.jornada_inicio::date BETWEEN ?::date AND ?::date
            GROUP BY emp.id_empl, emp.nomb_empl, emp.cedu_empl
            ORDER BY emp.nomb_empl, emp.id_empl");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_lotes()
    {
        $db = parent::Conexion();
        $sql = "SELECT
                    l.*,
                    COUNT(e.jle_id)::integer AS empleados,
                    COUNT(e.jle_id) FILTER (WHERE e.jle_estado = 'LISTO')::integer AS listos,
                    COUNT(e.jle_id) FILTER (WHERE e.jle_estado = 'SIN_NOVEDAD')::integer AS sin_novedad,
                    COUNT(e.jle_id) FILTER (
                        WHERE e.jle_estado NOT IN ('LISTO', 'SIN_NOVEDAD')
                    )::integer AS bloqueados
                FROM jornada_lotes_reporte l
                LEFT JOIN jornada_lote_empleados e ON e.jlot_id = l.jlot_id
                GROUP BY l.jlot_id
                ORDER BY l.jlot_fecha_creacion DESC, l.jlot_id DESC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Prepara periodos sugeridos desde jornadas reales, conservando el corte común. */
    public function crear_lote($nombre, $fecha_corte, $usuario_id)
    {
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                'INSERT INTO jornada_lotes_reporte (
                    jlot_nombre, jlot_fecha_corte, jlot_creado_por
                 ) VALUES (?, ?::date, ?)
                 RETURNING jlot_id'
            );
            $stmt->execute([$nombre, $fecha_corte, $usuario_id]);
            $lote_id = (int) $stmt->fetchColumn();

            // Creación y validación comparten la incorporación de empleados elegibles.
            $filas_nuevas = $this->agregar_empleados_sugeridos($db, $lote_id, $fecha_corte, $usuario_id);
            foreach ($filas_nuevas as $fila_id) {
                $this->recalcular_fila($db, $fila_id, $usuario_id);
            }

            $this->auditar(
                $db,
                $lote_id,
                null,
                'CREAR_LOTE',
                null,
                ['nombre' => $nombre, 'fecha_corte' => $fecha_corte],
                null,
                $usuario_id
            );
            $db->commit();
            return $lote_id;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Crea una nueva versión completa de un lote cerrado. El original sigue
     * vigente hasta que la corrección consiga cerrarse correctamente.
     */
    public function crear_correccion($lote_origen_id, $usuario_id)
    {
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $origen = $this->bloquear_lote($db, $lote_origen_id);
            if ($origen['jlot_estado'] !== 'CERRADO') {
                throw new RuntimeException(
                    'Solo puede corregirse un lote cerrado y vigente.'
                );
            }

            $stmt = $db->prepare(
                "SELECT jlot_id
                 FROM jornada_lotes_reporte
                 WHERE jlot_lote_origen_id = ?
                   AND jlot_estado = 'BORRADOR'
                 LIMIT 1"
            );
            $stmt->execute([$lote_origen_id]);
            $existente = $stmt->fetchColumn();
            if ($existente) {
                throw new RuntimeException(
                    'El lote ya tiene una corrección en borrador.'
                );
            }

            $stmt = $db->prepare('SELECT MAX(jlot_version) FROM jornada_lotes_reporte WHERE jlot_lote_origen_id = ?');
            $stmt->execute([$lote_origen_id]);
            $version = max((int) $origen['jlot_version'], (int) $stmt->fetchColumn()) + 1;
            // Nombre y corte son únicos: conservar solo el prefijo de la versión actual.
            $nombreBase = preg_replace('/^(?:Corrección v\d+ - )+/u', '', $origen['jlot_nombre']);
            $nombre = 'Corrección v' . $version . ' - ' . $nombreBase;
            $stmt = $db->prepare(
                "INSERT INTO jornada_lotes_reporte (
                    jlot_nombre,
                    jlot_fecha_corte,
                    jlot_estado,
                    jlot_version_formato,
                    jlot_tipo,
                    jlot_lote_origen_id,
                    jlot_version,
                    jlot_creado_por
                 ) VALUES (?, ?::date, 'BORRADOR', ?, 'CORRECCION', ?, ?, ?)
                 RETURNING jlot_id"
            );
            $stmt->execute([
                $nombre,
                $origen['jlot_fecha_corte'],
                $origen['jlot_version_formato'],
                $lote_origen_id,
                $version,
                $usuario_id
            ]);
            $nuevo_id = (int) $stmt->fetchColumn();

            $stmt = $db->prepare(
                "INSERT INTO jornada_lote_empleados (
                    jlot_id,
                    empleado_id,
                    jle_desde,
                    jle_hasta,
                    jle_origen_periodo,
                    jle_motivo_ajuste,
                    jle_actualizado_por
                 )
                 SELECT
                    ?,
                    empleado_id,
                    jle_desde,
                    jle_hasta,
                    'CORRECCION',
                    'Periodo copiado del lote que se corrige',
                    ?
                 FROM jornada_lote_empleados
                 WHERE jlot_id = ?
                 ORDER BY jle_id
                 RETURNING jle_id"
            );
            $stmt->execute([$nuevo_id, $usuario_id, $lote_origen_id]);
            $filas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!$filas) {
                throw new RuntimeException(
                    'El lote original no contiene empleados para corregir.'
                );
            }
            foreach ($filas as $fila_id) {
                $this->recalcular_fila($db, (int) $fila_id, $usuario_id);
            }

            $this->auditar(
                $db,
                $nuevo_id,
                null,
                'CREAR_CORRECCION',
                [
                    'lote_origen_id' => (int) $lote_origen_id,
                    'version' => (int) $origen['jlot_version']
                ],
                [
                    'lote_correccion_id' => $nuevo_id,
                    'version' => $version
                ],
                'Corrección de una liquidación previamente cerrada',
                $usuario_id
            );
            $db->commit();
            return $nuevo_id;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function obtener_lote($lote_id)
    {
        $db = parent::Conexion();
        $stmt = $db->prepare(
            'SELECT * FROM jornada_lotes_reporte WHERE jlot_id = ?'
        );
        $stmt->execute([$lote_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listar_empleados_lote($lote_id)
    {
        $db = parent::Conexion();
        $stmt = $db->prepare(
            'SELECT
                le.*,
                emp.nomb_empl AS empleado,
                emp.cedu_empl AS documento,
                cargo.nomb_carg AS cargo
             FROM jornada_lote_empleados le
             INNER JOIN empleados emp ON emp.id_empl = le.empleado_id
             LEFT JOIN cargo ON cargo.codi_carg = emp.carg_empl
             WHERE le.jlot_id = ?
             ORDER BY emp.nomb_empl, emp.cedu_empl'
        );
        $stmt->execute([$lote_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizar_periodo(
        $fila_id,
        $desde,
        $hasta,
        $motivo,
        $usuario_id
    ) {
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                'SELECT
                    le.*,
                    l.jlot_estado,
                    l.jlot_fecha_corte,
                    l.jlot_lote_origen_id
                 FROM jornada_lote_empleados le
                 INNER JOIN jornada_lotes_reporte l ON l.jlot_id = le.jlot_id
                 WHERE le.jle_id = ?
                 FOR UPDATE OF le, l'
            );
            $stmt->execute([$fila_id]);
            $anterior = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$anterior) {
                throw new RuntimeException('No se encontró el periodo del empleado.');
            }
            if ($anterior['jlot_estado'] !== 'BORRADOR') {
                throw new RuntimeException('Un lote cerrado no puede modificarse.');
            }
            if ($hasta > $anterior['jlot_fecha_corte']) {
                throw new RuntimeException('La fecha final no puede superar el corte del lote.');
            }

            $stmt = $db->prepare(
                "SELECT lote.jlot_id, lote.jlot_nombre, lote.jlot_version,
                        lote.jlot_fecha_corte, otra.jle_desde, otra.jle_hasta
                    FROM jornada_lote_empleados otra
                    INNER JOIN jornada_lotes_reporte lote
                        ON lote.jlot_id = otra.jlot_id
                    WHERE otra.empleado_id = ?
                      AND otra.jle_id <> ?
                      AND lote.jlot_estado = 'CERRADO'
                      AND lote.jlot_id <> COALESCE(?, -1)
                      AND otra.jle_desde <= ?::date
                      AND otra.jle_hasta >= ?::date
                    ORDER BY lote.jlot_fecha_corte, lote.jlot_id"
            );
            $stmt->execute([
                $anterior['empleado_id'],
                $fila_id,
                $anterior['jlot_lote_origen_id'],
                $hasta,
                $desde
            ]);
            $cruces = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($cruces) {
                throw new RuntimeException(
                    $this->describir_cruces($cruces)
                );
            }

            $stmt = $db->prepare(
                "UPDATE jornada_lote_empleados
                 SET jle_desde = ?::date,
                     jle_hasta = ?::date,
                     jle_origen_periodo = 'MANUAL',
                     jle_motivo_ajuste = ?,
                     jle_snapshot = NULL,
                     jle_actualizado_por = ?,
                     jle_fecha_actualizacion = CURRENT_TIMESTAMP
                 WHERE jle_id = ?"
            );
            $stmt->execute([$desde, $hasta, $motivo, $usuario_id, $fila_id]);
            $nueva = $this->recalcular_fila($db, $fila_id, $usuario_id);
            $this->auditar(
                $db,
                (int) $anterior['jlot_id'],
                $fila_id,
                'AJUSTAR_PERIODO',
                [
                    'desde' => $anterior['jle_desde'],
                    'hasta' => $anterior['jle_hasta']
                ],
                ['desde' => $desde, 'hasta' => $hasta],
                $motivo,
                $usuario_id
            );
            $db->commit();
            return $nueva;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** Agrega faltantes a borradores normales y revalida sin cambiar periodos existentes. */
    public function refrescar_lote($lote_id, $usuario_id)
    {
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $lote = $this->bloquear_lote($db, $lote_id);
            if ($lote['jlot_estado'] !== 'BORRADOR') {
                throw new RuntimeException('El lote ya está cerrado.');
            }
            // Las correcciones mantienen exclusivamente los empleados y periodos copiados.
            $filas_nuevas = [];
            if ($lote['jlot_tipo'] === 'NORMAL') {
                $filas_nuevas = $this->agregar_empleados_sugeridos(
                    $db, $lote_id, $lote['jlot_fecha_corte'], $usuario_id
                );
            }
            $stmt = $db->prepare(
                'SELECT jle_id FROM jornada_lote_empleados WHERE jlot_id = ?'
            );
            $stmt->execute([$lote_id]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fila_id) {
                $this->recalcular_fila($db, (int) $fila_id, $usuario_id);
            }
            if ($filas_nuevas) {
                $this->auditar($db, $lote_id, null, 'AGREGAR_EMPLEADOS', null,
                    ['jle_ids' => $filas_nuevas, 'cantidad' => count($filas_nuevas)],
                    'Empleados faltantes incorporados al validar de nuevo', $usuario_id);
            }
            $db->commit();
            return ['empleados_agregados' => count($filas_nuevas)];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function anular_lote($lote_id, $motivo, $usuario_id)
    {
        $motivo = trim($motivo);
        if ($motivo === '' || mb_strlen($motivo) > 500) {
            throw new InvalidArgumentException('Indique un motivo de anulación de máximo 500 caracteres.');
        }
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $lote = $this->bloquear_lote($db, $lote_id);
            if (!in_array($lote['jlot_estado'], ['BORRADOR', 'CERRADO'], true)) {
                throw new RuntimeException('Solo puede anular lotes en borrador o cerrados vigentes.');
            }
            $stmt = $db->prepare("SELECT jlot_id FROM jornada_lotes_reporte
                WHERE jlot_lote_origen_id = ? AND jlot_estado = 'BORRADOR' LIMIT 1");
            $stmt->execute([$lote_id]);
            if ($stmt->fetchColumn()) {
                throw new RuntimeException('Anule primero la corrección en borrador de este lote.');
            }
            $stmt = $db->prepare("UPDATE jornada_lotes_reporte SET jlot_estado = 'ANULADO' WHERE jlot_id = ?");
            $stmt->execute([$lote_id]);
            $this->auditar($db, $lote_id, null, 'ANULAR',
                ['estado' => $lote['jlot_estado']], ['estado' => 'ANULADO'], $motivo, $usuario_id);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function cerrar_lote($lote_id, $usuario_id)
    {
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $lote = $this->bloquear_lote($db, $lote_id);
            if ($lote['jlot_estado'] !== 'BORRADOR') {
                throw new RuntimeException('El lote ya fue cerrado.');
            }
            $stmt = $db->prepare(
                'SELECT jle_id FROM jornada_lote_empleados
                 WHERE jlot_id = ? ORDER BY jle_id FOR UPDATE'
            );
            $stmt->execute([$lote_id]);
            $filas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!$filas) {
                throw new RuntimeException(
                    'El lote no contiene empleados para reportar.'
                );
            }

            $bloqueos = [];
            foreach ($filas as $fila_id) {
                $fila = $this->recalcular_fila(
                    $db,
                    (int) $fila_id,
                    $usuario_id
                );
                if (!in_array(
                    $fila['jle_estado'],
                    ['LISTO', 'SIN_NOVEDAD'],
                    true
                )) {
                    $bloqueos[] = $fila;
                }
            }
            if ($bloqueos) {
                throw new RuntimeException(
                    'No se puede cerrar: existen empleados pendientes o bloqueados.'
                );
            }

            foreach ($filas as $fila_id) {
                $snapshot = $this->construir_snapshot($db, (int) $fila_id);
                $stmt = $db->prepare(
                    'UPDATE jornada_lote_empleados
                     SET jle_snapshot = ?::jsonb,
                         jle_fecha_actualizacion = CURRENT_TIMESTAMP
                     WHERE jle_id = ?'
                );
                $stmt->execute([
                    json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                    (int) $fila_id
                ]);
            }
            $stmt = $db->prepare(
                "UPDATE jornada_lotes_reporte
                 SET jlot_estado = 'CERRADO',
                     jlot_cerrado_por = ?,
                     jlot_fecha_cierre = CURRENT_TIMESTAMP
                 WHERE jlot_id = ?"
            );
            $stmt->execute([$usuario_id, $lote_id]);

            if (
                $lote['jlot_tipo'] === 'CORRECCION' &&
                !empty($lote['jlot_lote_origen_id'])
            ) {
                $stmt = $db->prepare(
                    "UPDATE jornada_lotes_reporte
                     SET jlot_estado = 'REEMPLAZADO'
                     WHERE jlot_id = ?
                       AND jlot_estado = 'CERRADO'"
                );
                $stmt->execute([$lote['jlot_lote_origen_id']]);
                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'El lote original dejó de estar vigente; actualice la vista.'
                    );
                }
            }
            $this->auditar(
                $db,
                $lote_id,
                null,
                'CERRAR_LOTE',
                ['estado' => 'BORRADOR'],
                ['estado' => 'CERRADO'],
                'Cierre documental del lote',
                $usuario_id
            );
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** Exporta la copia histórica, incluso anulada, sin reconstruirla desde jornadas actuales. */
    public function obtener_snapshot_empleado($fila_id)
    {
        $db = parent::Conexion();
        $stmt = $db->prepare(
            "SELECT le.jle_snapshot::text AS snapshot, l.*
             FROM jornada_lote_empleados le
             INNER JOIN jornada_lotes_reporte l ON l.jlot_id = le.jlot_id
             WHERE le.jle_id = ?
               AND l.jlot_estado IN ('CERRADO', 'REEMPLAZADO', 'ANULADO')"
        );
        $stmt->execute([$fila_id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila || !$fila['snapshot']) {
            throw new RuntimeException('El reporte no conserva una copia cerrada para exportar.');
        }
        $snapshot = json_decode($fila['snapshot'], true);
        $snapshot = $this->completar_datos_empleado($db, $snapshot);
        $snapshot = $this->completar_firmas_documento($db, $snapshot, $fila);
        return [
            'lote' => $fila,
            'snapshot' => $snapshot
        ];
    }

    /** Recupera solo snapshots existentes; un borrador anulado sin cierre no es exportable. */
    public function obtener_snapshots_lote($lote_id)
    {
        $db = parent::Conexion();
        $lote = $this->obtener_lote($lote_id);
        if (
            !$lote ||
            !in_array(
                $lote['jlot_estado'],
                ['CERRADO', 'REEMPLAZADO', 'ANULADO'],
                true
            )
        ) {
            throw new RuntimeException('El lote debe tener un cierre histórico para exportarlo.');
        }
        $stmt = $db->prepare(
            'SELECT jle_snapshot::text
             FROM jornada_lote_empleados
             WHERE jlot_id = ? AND jle_snapshot IS NOT NULL
             ORDER BY jle_id'
        );
        $stmt->execute([$lote_id]);
        $snapshots = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $json) {
            $snapshot = json_decode($json, true);
            $snapshot = $this->completar_datos_empleado($db, $snapshot);
            $snapshots[] = $this->completar_firmas_documento($db, $snapshot, $lote);
        }
        if (!$snapshots) {
            throw new RuntimeException('El lote no conserva reportes cerrados para exportar.');
        }
        return ['lote' => $lote, 'snapshots' => $snapshots];
    }

    /** Incorpora solo empleados faltantes; nunca actualiza periodos ya definidos en el lote. */
    private function agregar_empleados_sugeridos(PDO $db, $lote_id, $fecha_corte, $usuario_id)
    {
        $stmt = $db->prepare("SELECT DISTINCT empleado_id
            FROM (
                SELECT j.empleado_id FROM jornadas_trabajo j
                INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
                INNER JOIN jornada_estados estado ON estado.je_id = j.jornada_estado_id
                WHERE j.jornada_inicio::date <= ?::date
                  AND estado.je_codigo NOT IN ('ANULADO', 'RECHAZADO') AND emp.esta_empl = 1
                UNION
                SELECT e.empleado_id FROM jornada_lote_empleados e
                INNER JOIN jornada_lotes_reporte l ON l.jlot_id = e.jlot_id
                INNER JOIN empleados emp ON emp.id_empl = e.empleado_id
                WHERE l.jlot_estado = 'CERRADO' AND emp.esta_empl = 1
            ) candidatos
            WHERE NOT EXISTS (
                SELECT 1 FROM jornada_lote_empleados actual
                WHERE actual.jlot_id = ? AND actual.empleado_id = candidatos.empleado_id
            ) ORDER BY empleado_id");
        $stmt->execute([$fecha_corte, $lote_id]);
        $empleados = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $insertar = $db->prepare("INSERT INTO jornada_lote_empleados (
                jlot_id, empleado_id, jle_desde, jle_hasta, jle_origen_periodo, jle_actualizado_por
            ) VALUES (?, ?, ?::date, ?::date, 'SUGERIDO', ?)
            ON CONFLICT (jlot_id, empleado_id) DO NOTHING RETURNING jle_id");
        $filas_nuevas = [];
        foreach ($empleados as $empleado_id) {
            $desde = $this->calcular_inicio_sugerido($db, (int) $empleado_id, $fecha_corte);
            if ($desde === null || $desde > $fecha_corte) continue;
            $insertar->execute([$lote_id, (int) $empleado_id, $desde, $fecha_corte, $usuario_id]);
            $fila_id = $insertar->fetchColumn();
            if ($fila_id !== false) $filas_nuevas[] = (int) $fila_id;
        }
        return $filas_nuevas;
    }

    /** Primera jornada válida hasta el corte, posterior al último cierre si existe. */
    private function calcular_inicio_sugerido(PDO $db, $empleado_id, $corte)
    {
        $stmt = $db->prepare(
            "SELECT (MAX(le.jle_hasta) + 1)
             FROM jornada_lote_empleados le
             INNER JOIN jornada_lotes_reporte l ON l.jlot_id = le.jlot_id
             WHERE le.empleado_id = ? AND l.jlot_estado = 'CERRADO'"
        );
        $stmt->execute([$empleado_id]);
        // El día posterior al cierre es solo el límite inferior de la búsqueda.
        $fecha_base = $stmt->fetchColumn();
        $sql = "SELECT MIN(j.jornada_inicio::date)
             FROM jornadas_trabajo j
             INNER JOIN jornada_estados estado ON estado.je_id = j.jornada_estado_id
             WHERE j.empleado_id = ? AND j.jornada_inicio::date <= ?::date
               AND estado.je_codigo NOT IN ('ANULADO', 'RECHAZADO')";
        $parametros = [$empleado_id, $corte];
        // Sin cierre previo se conserva la primera jornada válida registrada.
        if ($fecha_base) {
            $sql .= ' AND j.jornada_inicio::date >= ?::date';
            $parametros[] = $fecha_base;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchColumn() ?: null;
    }

    private function recalcular_fila(PDO $db, $fila_id, $usuario_id)
    {
        $stmt = $db->prepare(
            'SELECT
                le.*,
                lote_actual.jlot_lote_origen_id,
                lote_actual.jlot_tipo
             FROM jornada_lote_empleados le
             INNER JOIN jornada_lotes_reporte lote_actual
                ON lote_actual.jlot_id = le.jlot_id
             WHERE le.jle_id = ?
             FOR UPDATE'
        );
        $stmt->execute([$fila_id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            throw new RuntimeException('No se encontró el empleado del lote.');
        }

        $sql = "SELECT
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo NOT IN ('RECHAZADO', 'ANULADO')
                    )::integer AS registradas,
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo IN ('APROBADO', 'PENDIENTE_LIQUIDACION', 'LIQUIDADO')
                    )::integer AS aprobadas,
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo NOT IN ('APROBADO', 'PENDIENTE_LIQUIDACION', 'LIQUIDADO', 'RECHAZADO', 'ANULADO')
                    )::integer AS sin_aprobar,
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo IN (
                        'APROBADO',
                            'PENDIENTE_LIQUIDACION',
                            'LIQUIDADO'
                        ) 
                          AND (
                            j.jornada_inconsistente = 1
                            OR COALESCE(c.minutos, 0) <> (
                                EXTRACT(EPOCH FROM (
                                    j.jornada_fin - j.jornada_inicio
                                ))::integer / 60
                            )
                          )
                    )::integer AS sin_clasificar,
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo IN ('APROBADO', 'PENDIENTE_LIQUIDACION', 'LIQUIDADO')
                          AND j.jornada_inconsistente = 1
                    )::integer AS inconsistentes,
                    COALESCE(SUM(c.reportables) FILTER (
                        WHERE estado.je_codigo IN ('APROBADO', 'PENDIENTE_LIQUIDACION', 'LIQUIDADO')
                    ), 0)::integer AS minutos_reportables
                FROM jornadas_trabajo j
                INNER JOIN jornada_estados estado
                    ON estado.je_id = j.jornada_estado_id
                LEFT JOIN (
                    SELECT
                        clas.jornada_id,
                        SUM(clas.jcla_minutos)::integer AS minutos,
                        SUM(clas.jcla_minutos) FILTER (
                            WHERE con.jcon_codigo <> 'NO_LIQ'
                        )::integer AS reportables
                    FROM jornada_clasificaciones clas
                    INNER JOIN jornada_conceptos con
                        ON con.jcon_id = clas.jcon_id
                    GROUP BY clas.jornada_id
                ) c ON c.jornada_id = j.jornada_id
                WHERE j.empleado_id = ?
                  AND j.jornada_inicio::date BETWEEN ?::date AND ?::date";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $fila['empleado_id'],
            $fila['jle_desde'],
            $fila['jle_hasta']
        ]);
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare(
            "SELECT lote_cerrado.jlot_id, lote_cerrado.jlot_nombre,
                    lote_cerrado.jlot_version, lote_cerrado.jlot_fecha_corte,
                    cerrada.jle_desde, cerrada.jle_hasta
                FROM jornada_lote_empleados cerrada
                INNER JOIN jornada_lotes_reporte lote_cerrado
                    ON lote_cerrado.jlot_id = cerrada.jlot_id
                WHERE cerrada.empleado_id = ?
                  AND cerrada.jlot_id <> ?
                  AND lote_cerrado.jlot_estado = 'CERRADO'
                  AND lote_cerrado.jlot_id <> COALESCE(?, -1)
                  AND cerrada.jle_desde <= ?::date
                  AND cerrada.jle_hasta >= ?::date
                ORDER BY lote_cerrado.jlot_fecha_corte, lote_cerrado.jlot_id"
        );
        $stmt->execute([
            $fila['empleado_id'],
            $fila['jlot_id'],
            $fila['jlot_lote_origen_id'],
            $fila['jle_hasta'],
            $fila['jle_desde']
        ]);
        $cruces = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$fila['jle_desde']) {
            $estado = 'SIN_BASE';
            $diagnostico = 'Defina la fecha inicial del empleado.';
        } elseif ($cruces) {
            $estado = 'BLOQUEADO';
            $diagnostico = $this->describir_cruces($cruces);
        } elseif ((int) $conteo['inconsistentes'] > 0) {
            $estado = 'BLOQUEADO';
            $diagnostico = 'Existen jornadas inconsistentes.';
        } elseif (
            (int) $conteo['sin_aprobar'] > 0 ||
            (int) $conteo['sin_clasificar'] > 0
        ) {
            $estado = 'PENDIENTE';
            $diagnostico = sprintf(
                '%d sin aprobar y %d sin clasificación completa.',
                (int) $conteo['sin_aprobar'],
                (int) $conteo['sin_clasificar']
            );
        } elseif ((int) $conteo['aprobadas'] === 0) {
            $estado = 'SIN_NOVEDAD';
            $diagnostico = 'No hay jornadas aprobadas en el periodo.';
        } else {
            $estado = 'LISTO';
            $diagnostico = 'Periodo listo para cierre y exportación.';
        }

        $stmt = $db->prepare(
            'UPDATE jornada_lote_empleados
             SET jle_estado = ?,
                 jle_cantidad_jornadas = ?,
                 jle_cantidad_pendientes = ?,
                 jle_minutos_reportables = ?,
                 jle_diagnostico = ?,
                 jle_actualizado_por = ?,
                 jle_fecha_actualizacion = CURRENT_TIMESTAMP
             WHERE jle_id = ?
             RETURNING *'
        );
        $pendientes = (int) $conteo['sin_aprobar']
            + (int) $conteo['sin_clasificar'];
        $stmt->execute([
            $estado,
            (int) $conteo['aprobadas'],
            $pendientes,
            (int) $conteo['minutos_reportables'],
            $diagnostico,
            $usuario_id,
            $fila_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function describir_cruces(array $cruces)
    {
        $detalles = [];
        foreach ($cruces as $lote) {
            $nombre = preg_replace('/^(?:Corrección v\d+ - )+/u', '', $lote['jlot_nombre']);
            $detalles[] = sprintf(
                '%s (#%d), versión %d, corte %s; periodo del empleado: %s al %s',
                $nombre,
                $lote['jlot_id'],
                $lote['jlot_version'],
                (new DateTimeImmutable($lote['jlot_fecha_corte']))->format('d/m/Y'),
                (new DateTimeImmutable($lote['jle_desde']))->format('d/m/Y'),
                (new DateTimeImmutable($lote['jle_hasta']))->format('d/m/Y')
            );
        }
        return 'El periodo se superpone con los siguientes lotes cerrados: '
            . implode(' | ', $detalles) . '.';
    }

    private function construir_snapshot(PDO $db, $fila_id)
    {
        $stmt = $db->prepare(
            'SELECT
                le.*,
                emp.nomb_empl AS empleado,
                emp.cedu_empl AS documento,
                cargo.nomb_carg AS cargo,
                emp.fecha_naci_empl AS fecha_nacimiento,
                genero.desc_gene AS sexo
             FROM jornada_lote_empleados le
             INNER JOIN empleados emp ON emp.id_empl = le.empleado_id
             LEFT JOIN cargo ON cargo.codi_carg = emp.carg_empl
             LEFT JOIN genero ON genero.id_gene = emp.gene_empl
             WHERE le.jle_id = ?'
        );
        $stmt->execute([$fila_id]);
        $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql = "SELECT
            j.jornada_id,
            j.jornada_inicio,
            j.jornada_fin,
            j.jornada_minutos_ordinarios,
            j.jornada_ubicacion,
            j.jornada_actividad,
            j.jornada_observaciones,
            j.jornada_origen,
            aprobador.nomb_empl AS aprobado_por,
            aprobador.empleado_id AS aprobado_por_id,
            COALESCE(
                jsonb_agg(
                    jsonb_build_object(
                        'codigo', con.jcon_codigo,
                        'concepto', con.jcon_nombre,
                        'codigo_contable', con.jcon_codigo_contable,
                        'inicio', c.jcla_inicio,
                        'fin', c.jcla_fin,
                        'minutos', c.jcla_minutos
                    )
                    ORDER BY c.jcla_inicio, c.jcla_id
                ) FILTER (
                    WHERE c.jcla_id IS NOT NULL
                ),
                '[]'::jsonb
            )::text AS segmentos
        FROM jornadas_trabajo j

        INNER JOIN jornada_estados estado
            ON estado.je_id = j.jornada_estado_id

        LEFT JOIN jornada_clasificaciones c
            ON c.jornada_id = j.jornada_id

        LEFT JOIN jornada_conceptos con
            ON con.jcon_id = c.jcon_id

        LEFT JOIN LATERAL (
            SELECT emp_apr.nomb_empl, apr.jap_empleado_id AS empleado_id
            FROM jornada_aprobaciones apr

            LEFT JOIN empleados emp_apr
                ON emp_apr.id_empl = apr.jap_empleado_id

            WHERE apr.jornada_id = j.jornada_id
              AND apr.jap_decision = 'APROBADO'

            ORDER BY apr.jap_fecha DESC, apr.jap_id DESC
            LIMIT 1
        ) aprobador ON TRUE

        WHERE j.empleado_id = ?
          AND estado.je_codigo IN (
              'APROBADO',
              'PENDIENTE_LIQUIDACION',
              'LIQUIDADO'
          )
          AND j.jornada_inicio::date BETWEEN ?::date AND ?::date

        GROUP BY
            j.jornada_id,
            aprobador.nomb_empl,
            aprobador.empleado_id

        ORDER BY
            j.jornada_inicio,
            j.jornada_id";

        /*         $sql = "SELECT
                            j.jornada_id,
                            j.jornada_inicio,
                            j.jornada_fin,
                            j.jornada_ubicacion,
                            j.jornada_actividad,
                            j.jornada_observaciones,
                            j.jornada_origen,
                            aprobador.nomb_empl AS aprobado_por,
                            COALESCE(
                                jsonb_agg(
                                    jsonb_build_object(
                                        'codigo', con.jcon_codigo,
                                        'concepto', con.jcon_nombre,
                                        'codigo_contable', con.jcon_codigo_contable,
                                        'inicio', c.jcla_inicio,
                                        'fin', c.jcla_fin,
                                        'minutos', c.jcla_minutos
                                    )
                                    ORDER BY c.jcla_inicio, c.jcla_id
                                ) FILTER (WHERE c.jcla_id IS NOT NULL),
                                '[]'::jsonb
                            )::text AS segmentos
                        FROM jornadas_trabajo j
                        INNER JOIN jornada_estados estado
                            ON estado.je_id = j.jornada_estado_id
                        LEFT JOIN jornada_clasificaciones c
                            ON c.jornada_id = j.jornada_id
                        LEFT JOIN jornada_conceptos con ON con.jcon_id = c.jcon_id
                        LEFT JOIN LATERAL (
                            SELECT emp_apr.nomb_empl
                            FROM jornada_aprobaciones apr
                            LEFT JOIN empleados emp_apr
                                ON emp_apr.id_empl = apr.jap_empleado_id
                            WHERE apr.jornada_id = j.jornada_id
                              AND apr.jap_decision = 'APROBADO'
                            ORDER BY apr.jap_fecha DESC
                            LIMIT 1
                        ) aprobador ON TRUE
                        WHERE j.empleado_id = ?
                          AND estado.je_codigo = 'APROBADO'
                          AND j.jornada_inicio::date BETWEEN ?::date AND ?::date
                        GROUP BY j.jornada_id, aprobador.nomb_empl
                        ORDER BY j.jornada_inicio, j.jornada_id"; */
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $cabecera['empleado_id'],
            $cabecera['jle_desde'],
            $cabecera['jle_hasta']
        ]);
        $jornadas = [];
        $totales = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $jornada) {
            $jornada['segmentos'] = json_decode($jornada['segmentos'], true);
            foreach ($jornada['segmentos'] as $segmento) {
                $codigo = $segmento['codigo'];
                if ($codigo !== 'NO_LIQ') {
                    $totales[$codigo] = ($totales[$codigo] ?? 0)
                        + (int) $segmento['minutos'];
                }
            }
            $jornadas[] = $jornada;
        }
        ksort($totales);
        $snapshot = [
            'fila_id' => (int) $cabecera['jle_id'],
            'empleado_id' => (int) $cabecera['empleado_id'],
            'empleado' => $cabecera['empleado'],
            'documento' => $cabecera['documento'],
            'cargo' => $cabecera['cargo'],
            'fecha_nacimiento' => $cabecera['fecha_nacimiento'],
            'sexo' => $cabecera['sexo'],
            'desde' => $cabecera['jle_desde'],
            'hasta' => $cabecera['jle_hasta'],
            'estado' => $cabecera['jle_estado'],
            'generado_en' => date('Y-m-d H:i:s'),
            'franja_nocturna' => '19:00-06:00',
            'jornadas' => $jornadas,
            'totales' => $totales
        ];
        // Las firmas verificadas quedan congeladas junto con las jornadas al cerrar.
        require_once __DIR__ . '/Jornada.php';
        return array_merge($snapshot, Jornada::obtener_firmas_pdf($db, $cabecera['empleado_id'], $jornadas));
    }

    /** Completa solo la presentación de lotes antiguos, sin escribir su snapshot histórico. */
    private function completar_firmas_documento(PDO $db, array $snapshot, array $lote)
    {
        if (array_key_exists('firma_empleado', $snapshot) && array_key_exists('firmas_jefes', $snapshot)) {
            $snapshot['mostrar_firmas'] = true;
            return $snapshot;
        }
        require_once __DIR__ . '/Jornada.php';
        $firmas = Jornada::obtener_firmas_pdf($db, (int)$snapshot['empleado_id'], $snapshot['jornadas'] ?? [],
            $lote['jlot_fecha_cierre'] ?? null);
        // Conserva la firma del trabajador ya congelada; corrige la atribución antigua
        // del jefe asignado con los aprobadores documentados, sin escribir el snapshot.
        return array_merge($firmas, $snapshot, ['mostrar_firmas' => true, 'firmas_recuperadas' => true,
            'jefe_nombre' => $firmas['jefe_nombre'], 'firma_jefe' => $firmas['firma_jefe'],
            'firmas_jefes' => $firmas['firmas_jefes']]);
    }

    private function bloquear_lote(PDO $db, $lote_id)
    {
        $stmt = $db->prepare(
            'SELECT * FROM jornada_lotes_reporte
             WHERE jlot_id = ? FOR UPDATE'
        );
        $stmt->execute([$lote_id]);
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lote) {
            throw new RuntimeException('No se encontró el lote.');
        }
        return $lote;
    }

    /**
     * Completa datos de encabezado para snapshots antiguos que fueron cerrados
     * antes de incorporar edad y sexo al formato PDF.
     */
    private function completar_datos_empleado(PDO $db, array $snapshot)
    {
        if (
            array_key_exists('fecha_nacimiento', $snapshot) &&
            array_key_exists('sexo', $snapshot)
        ) {
            return $snapshot;
        }
        $stmt = $db->prepare(
            'SELECT
                emp.fecha_naci_empl AS fecha_nacimiento,
                genero.desc_gene AS sexo
             FROM empleados emp
             LEFT JOIN genero ON genero.id_gene = emp.gene_empl
             WHERE emp.id_empl = ?'
        );
        $stmt->execute([(int) ($snapshot['empleado_id'] ?? 0)]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $snapshot['fecha_nacimiento'] =
            $datos['fecha_nacimiento'] ?? null;
        $snapshot['sexo'] = $datos['sexo'] ?? null;
        return $snapshot;
    }

    private function auditar(
        PDO $db,
        $lote_id,
        $fila_id,
        $accion,
        $anterior,
        $nuevo,
        $motivo,
        $usuario_id
    ) {
        $stmt = $db->prepare(
            'INSERT INTO jornada_lote_auditoria (
                jlot_id, jle_id, jla_accion, jla_datos_anteriores,
                jla_datos_nuevos, jla_motivo, jla_usuario_id
             ) VALUES (?, ?, ?, ?::jsonb, ?::jsonb, ?, ?)'
        );
        $stmt->execute([
            $lote_id,
            $fila_id,
            $accion,
            $anterior === null
                ? null
                : json_encode($anterior, JSON_UNESCAPED_UNICODE),
            $nuevo === null
                ? null
                : json_encode($nuevo, JSON_UNESCAPED_UNICODE),
            $motivo,
            $usuario_id
        ]);
    }
}

?>
