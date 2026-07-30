<?php

/**
 * Lotes documentales de jornadas. Los periodos pueden variar por empleado y,
 * al cerrar, se conserva un snapshot inmutable para reproducir los archivos.
 */
class JornadaReporteContable extends Conectar {

    public function listar_lotes() {
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

    public function crear_lote($nombre, $fecha_corte, $usuario_id) {
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $stmt = $db->prepare(
                "INSERT INTO jornada_lotes_reporte (
                    jlot_nombre, jlot_fecha_corte, jlot_creado_por
                 ) VALUES (?, ?::date, ?)
                 RETURNING jlot_id"
            );
            $stmt->execute([$nombre, $fecha_corte, $usuario_id]);
            $lote_id = (int)$stmt->fetchColumn();

            /*
             * Incluye empleados con jornadas hasta el corte y quienes ya
             * tuvieron un periodo cerrado, aunque ahora no tengan novedad.
             */
            $sql = "SELECT DISTINCT empleado_id
                    FROM (
                        SELECT j.empleado_id
                        FROM jornadas_trabajo j
                        INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
                        WHERE j.jornada_inicio::date <= ?::date
                          AND emp.esta_empl = 1
                        UNION
                        SELECT e.empleado_id
                        FROM jornada_lote_empleados e
                        INNER JOIN jornada_lotes_reporte l
                            ON l.jlot_id = e.jlot_id
                        INNER JOIN empleados emp
                            ON emp.id_empl = e.empleado_id
                        WHERE l.jlot_estado = 'CERRADO'
                          AND emp.esta_empl = 1
                    ) candidatos
                    ORDER BY empleado_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([$fecha_corte]);
            $empleados = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($empleados as $empleado_id) {
                $desde = $this->calcular_inicio_sugerido(
                    $db,
                    (int)$empleado_id,
                    $fecha_corte
                );
                if ($desde === null || $desde > $fecha_corte) {
                    continue;
                }
                $stmtInsertar = $db->prepare(
                    "INSERT INTO jornada_lote_empleados (
                        jlot_id, empleado_id, jle_desde, jle_hasta,
                        jle_origen_periodo, jle_actualizado_por
                     ) VALUES (?, ?, ?::date, ?::date, 'SUGERIDO', ?)
                     RETURNING jle_id"
                );
                $stmtInsertar->execute([
                    $lote_id,
                    (int)$empleado_id,
                    $desde,
                    $fecha_corte,
                    $usuario_id
                ]);
                $this->recalcular_fila(
                    $db,
                    (int)$stmtInsertar->fetchColumn(),
                    $usuario_id
                );
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
    public function crear_correccion($lote_origen_id, $usuario_id) {
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

            $version = (int)$origen['jlot_version'] + 1;
            $nombre = 'Corrección v' . $version . ' - '
                . $origen['jlot_nombre'];
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
            $nuevo_id = (int)$stmt->fetchColumn();

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
                $this->recalcular_fila($db, (int)$fila_id, $usuario_id);
            }

            $this->auditar(
                $db,
                $nuevo_id,
                null,
                'CREAR_CORRECCION',
                [
                    'lote_origen_id' => (int)$lote_origen_id,
                    'version' => (int)$origen['jlot_version']
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

    public function obtener_lote($lote_id) {
        $db = parent::Conexion();
        $stmt = $db->prepare(
            "SELECT * FROM jornada_lotes_reporte WHERE jlot_id = ?"
        );
        $stmt->execute([$lote_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listar_empleados_lote($lote_id) {
        $db = parent::Conexion();
        $stmt = $db->prepare(
            "SELECT
                le.*,
                emp.nomb_empl AS empleado,
                emp.cedu_empl AS documento,
                cargo.nomb_carg AS cargo
             FROM jornada_lote_empleados le
             INNER JOIN empleados emp ON emp.id_empl = le.empleado_id
             LEFT JOIN cargo ON cargo.codi_carg = emp.carg_empl
             WHERE le.jlot_id = ?
             ORDER BY emp.nomb_empl, emp.cedu_empl"
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
                "SELECT
                    le.*,
                    l.jlot_estado,
                    l.jlot_fecha_corte,
                    l.jlot_lote_origen_id
                 FROM jornada_lote_empleados le
                 INNER JOIN jornada_lotes_reporte l ON l.jlot_id = le.jlot_id
                 WHERE le.jle_id = ?
                 FOR UPDATE OF le, l"
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
                "SELECT EXISTS (
                    SELECT 1
                    FROM jornada_lote_empleados otra
                    INNER JOIN jornada_lotes_reporte lote
                        ON lote.jlot_id = otra.jlot_id
                    WHERE otra.empleado_id = ?
                      AND otra.jle_id <> ?
                      AND lote.jlot_estado = 'CERRADO'
                      AND lote.jlot_id <> COALESCE(?, -1)
                      AND otra.jle_desde <= ?::date
                      AND otra.jle_hasta >= ?::date
                 )"
            );
            $stmt->execute([
                $anterior['empleado_id'],
                $fila_id,
                $anterior['jlot_lote_origen_id'],
                $hasta,
                $desde
            ]);
            if ((bool)$stmt->fetchColumn()) {
                throw new RuntimeException(
                    'El periodo se superpone con otro lote ya cerrado.'
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
                (int)$anterior['jlot_id'],
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

    public function refrescar_lote($lote_id, $usuario_id) {
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $lote = $this->bloquear_lote($db, $lote_id);
            if ($lote['jlot_estado'] !== 'BORRADOR') {
                throw new RuntimeException('El lote ya está cerrado.');
            }
            $stmt = $db->prepare(
                "SELECT jle_id FROM jornada_lote_empleados WHERE jlot_id = ?"
            );
            $stmt->execute([$lote_id]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fila_id) {
                $this->recalcular_fila($db, (int)$fila_id, $usuario_id);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function cerrar_lote($lote_id, $usuario_id) {
        $db = parent::Conexion();
        try {
            $db->beginTransaction();
            $lote = $this->bloquear_lote($db, $lote_id);
            if ($lote['jlot_estado'] !== 'BORRADOR') {
                throw new RuntimeException('El lote ya fue cerrado.');
            }
            $stmt = $db->prepare(
                "SELECT jle_id FROM jornada_lote_empleados
                 WHERE jlot_id = ? ORDER BY jle_id FOR UPDATE"
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
                    (int)$fila_id,
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
                $snapshot = $this->construir_snapshot($db, (int)$fila_id);
                $stmt = $db->prepare(
                    "UPDATE jornada_lote_empleados
                     SET jle_snapshot = ?::jsonb,
                         jle_fecha_actualizacion = CURRENT_TIMESTAMP
                     WHERE jle_id = ?"
                );
                $stmt->execute([
                    json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                    (int)$fila_id
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
                $lote['jlot_tipo'] === 'CORRECCION'
                && !empty($lote['jlot_lote_origen_id'])
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

    public function obtener_snapshot_empleado($fila_id) {
        $db = parent::Conexion();
        $stmt = $db->prepare(
            "SELECT le.jle_snapshot::text AS snapshot, l.*
             FROM jornada_lote_empleados le
             INNER JOIN jornada_lotes_reporte l ON l.jlot_id = le.jlot_id
             WHERE le.jle_id = ?
               AND l.jlot_estado IN ('CERRADO', 'REEMPLAZADO')"
        );
        $stmt->execute([$fila_id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila || !$fila['snapshot']) {
            throw new RuntimeException('El reporte solicitado no está cerrado.');
        }
        $snapshot = json_decode($fila['snapshot'], true);
        $snapshot = $this->completar_datos_empleado($db, $snapshot);
        return [
            'lote' => $fila,
            'snapshot' => $snapshot
        ];
    }

    public function obtener_snapshots_lote($lote_id) {
        $db = parent::Conexion();
        $lote = $this->obtener_lote($lote_id);
        if (
            !$lote
            || !in_array(
                $lote['jlot_estado'],
                ['CERRADO', 'REEMPLAZADO'],
                true
            )
        ) {
            throw new RuntimeException('El lote debe estar cerrado para exportarlo.');
        }
        $stmt = $db->prepare(
            "SELECT jle_snapshot::text
             FROM jornada_lote_empleados
             WHERE jlot_id = ? AND jle_snapshot IS NOT NULL
             ORDER BY jle_id"
        );
        $stmt->execute([$lote_id]);
        $snapshots = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $json) {
            $snapshot = json_decode($json, true);
            $snapshots[] = $this->completar_datos_empleado(
                $db,
                $snapshot
            );
        }
        return ['lote' => $lote, 'snapshots' => $snapshots];
    }

    private function calcular_inicio_sugerido(PDO $db, $empleado_id, $corte) {
        $stmt = $db->prepare(
            "SELECT (MAX(le.jle_hasta) + 1)
             FROM jornada_lote_empleados le
             INNER JOIN jornada_lotes_reporte l ON l.jlot_id = le.jlot_id
             WHERE le.empleado_id = ? AND l.jlot_estado = 'CERRADO'"
        );
        $stmt->execute([$empleado_id]);
        $desde = $stmt->fetchColumn();
        if ($desde) {
            return $desde;
        }
        $stmt = $db->prepare(
            "SELECT MIN(jornada_inicio::date)
             FROM jornadas_trabajo
             WHERE empleado_id = ? AND jornada_inicio::date <= ?::date"
        );
        $stmt->execute([$empleado_id, $corte]);
        return $stmt->fetchColumn() ?: null;
    }

    private function recalcular_fila(PDO $db, $fila_id, $usuario_id) {
        $stmt = $db->prepare(
            "SELECT
                le.*,
                lote_actual.jlot_lote_origen_id,
                lote_actual.jlot_tipo
             FROM jornada_lote_empleados le
             INNER JOIN jornada_lotes_reporte lote_actual
                ON lote_actual.jlot_id = le.jlot_id
             WHERE le.jle_id = ?
             FOR UPDATE"
        );
        $stmt->execute([$fila_id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            throw new RuntimeException('No se encontró el empleado del lote.');
        }

        $sql = "SELECT
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo <> 'RECHAZADO'
                    )::integer AS registradas,
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo = 'APROBADO'
                    )::integer AS aprobadas,
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo NOT IN ('APROBADO', 'RECHAZADO')
                    )::integer AS sin_aprobar,
                    COUNT(*) FILTER (
                        WHERE estado.je_codigo = 'APROBADO'
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
                        WHERE estado.je_codigo = 'APROBADO'
                          AND j.jornada_inconsistente = 1
                    )::integer AS inconsistentes,
                    COALESCE(SUM(c.reportables) FILTER (
                        WHERE estado.je_codigo = 'APROBADO'
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
            "SELECT EXISTS (
                SELECT 1
                FROM jornada_lote_empleados cerrada
                INNER JOIN jornada_lotes_reporte lote_cerrado
                    ON lote_cerrado.jlot_id = cerrada.jlot_id
                WHERE cerrada.empleado_id = ?
                  AND cerrada.jlot_id <> ?
                  AND lote_cerrado.jlot_estado = 'CERRADO'
                  AND lote_cerrado.jlot_id <> COALESCE(?, -1)
                  AND cerrada.jle_desde <= ?::date
                  AND cerrada.jle_hasta >= ?::date
             )"
        );
        $stmt->execute([
            $fila['empleado_id'],
            $fila['jlot_id'],
            $fila['jlot_lote_origen_id'],
            $fila['jle_hasta'],
            $fila['jle_desde']
        ]);
        $superpone_cierre = (bool)$stmt->fetchColumn();

        if (!$fila['jle_desde']) {
            $estado = 'SIN_BASE';
            $diagnostico = 'Defina la fecha inicial del empleado.';
        } elseif ($superpone_cierre) {
            $estado = 'BLOQUEADO';
            $diagnostico = 'El periodo se superpone con otro lote ya cerrado.';
        } elseif ((int)$conteo['inconsistentes'] > 0) {
            $estado = 'BLOQUEADO';
            $diagnostico = 'Existen jornadas inconsistentes.';
        } elseif (
            (int)$conteo['sin_aprobar'] > 0
            || (int)$conteo['sin_clasificar'] > 0
        ) {
            $estado = 'PENDIENTE';
            $diagnostico = sprintf(
                '%d sin aprobar y %d sin clasificación completa.',
                (int)$conteo['sin_aprobar'],
                (int)$conteo['sin_clasificar']
            );
        } elseif ((int)$conteo['aprobadas'] === 0) {
            $estado = 'SIN_NOVEDAD';
            $diagnostico = 'No hay jornadas aprobadas en el periodo.';
        } else {
            $estado = 'LISTO';
            $diagnostico = 'Periodo listo para cierre y exportación.';
        }

        $stmt = $db->prepare(
            "UPDATE jornada_lote_empleados
             SET jle_estado = ?,
                 jle_cantidad_jornadas = ?,
                 jle_cantidad_pendientes = ?,
                 jle_minutos_reportables = ?,
                 jle_diagnostico = ?,
                 jle_actualizado_por = ?,
                 jle_fecha_actualizacion = CURRENT_TIMESTAMP
             WHERE jle_id = ?
             RETURNING *"
        );
        $pendientes = (int)$conteo['sin_aprobar']
            + (int)$conteo['sin_clasificar'];
        $stmt->execute([
            $estado,
            (int)$conteo['aprobadas'],
            $pendientes,
            (int)$conteo['minutos_reportables'],
            $diagnostico,
            $usuario_id,
            $fila_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function construir_snapshot(PDO $db, $fila_id) {
        $stmt = $db->prepare(
            "SELECT
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
             WHERE le.jle_id = ?"
        );
        $stmt->execute([$fila_id]);
        $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);

        $sql = "SELECT
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
                ORDER BY j.jornada_inicio, j.jornada_id";
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
                        + (int)$segmento['minutos'];
                }
            }
            $jornadas[] = $jornada;
        }
        ksort($totales);
        return [
            'fila_id' => (int)$cabecera['jle_id'],
            'empleado_id' => (int)$cabecera['empleado_id'],
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
    }

    private function bloquear_lote(PDO $db, $lote_id) {
        $stmt = $db->prepare(
            "SELECT * FROM jornada_lotes_reporte
             WHERE jlot_id = ? FOR UPDATE"
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
    private function completar_datos_empleado(PDO $db, array $snapshot) {
        if (
            array_key_exists('fecha_nacimiento', $snapshot)
            && array_key_exists('sexo', $snapshot)
        ) {
            return $snapshot;
        }
        $stmt = $db->prepare(
            "SELECT
                emp.fecha_naci_empl AS fecha_nacimiento,
                genero.desc_gene AS sexo
             FROM empleados emp
             LEFT JOIN genero ON genero.id_gene = emp.gene_empl
             WHERE emp.id_empl = ?"
        );
        $stmt->execute([(int)($snapshot['empleado_id'] ?? 0)]);
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
            "INSERT INTO jornada_lote_auditoria (
                jlot_id, jle_id, jla_accion, jla_datos_anteriores,
                jla_datos_nuevos, jla_motivo, jla_usuario_id
             ) VALUES (?, ?, ?, ?::jsonb, ?::jsonb, ?, ?)"
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
