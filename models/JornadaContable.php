<?php

/**
 * Consultas exclusivas de Contabilidad para liquidación, inconsistencias y
 * reportes. Mantiene los conceptos fuera de los endpoints operativos.
 */
class JornadaContable extends Conectar {

    /**
     * Lista empleados con jornadas aprobadas dentro del periodo solicitado.
     */
    public function listar_empleados_periodo($fecha_desde, $fecha_hasta) {
        $conectar = parent::Conexion();

        $sql = "SELECT DISTINCT
                    emp.id_empl AS empleado_id,
                    emp.cedu_empl AS documento,
                    emp.nomb_empl AS empleado
                FROM jornadas_trabajo j
                INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE e.je_codigo = 'APROBADO'
                  AND j.jornada_inicio::date >= ?::date
                  AND j.jornada_inicio::date <= ?::date
                ORDER BY emp.nomb_empl, emp.cedu_empl";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $fecha_desde, PDO::PARAM_STR);
        $stmt->bindValue(2, $fecha_hasta, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consulta jornadas aprobadas y su avance de clasificación contable.
     */
    public function listar_liquidacion(
        $fecha_desde,
        $fecha_hasta,
        $empleado_id = null
    ) {
        $conectar = parent::Conexion();

        $where = [
            "e.je_codigo = 'APROBADO'",
            "j.jornada_inicio::date >= :fecha_desde::date",
            "j.jornada_inicio::date <= :fecha_hasta::date"
        ];

        if ($empleado_id !== null) {
            $where[] = "j.empleado_id = :empleado_id";
        }

        $sql = "SELECT
                    j.jornada_id,
                    j.jornada_inicio,
                    j.jornada_fin,
                    j.jornada_minutos_ordinarios,
                    j.jornada_ubicacion,
                    j.jornada_actividad,
                    j.jornada_origen,
                    emp.id_empl AS empleado_id,
                    emp.cedu_empl AS documento,
                    emp.nomb_empl AS empleado,
                    COUNT(c.jcla_id) AS cantidad_segmentos,
                    COALESCE(SUM(c.jcla_minutos), 0) AS minutos_clasificados,
                    COALESCE(SUM(
                        CASE
                            WHEN concepto.jcon_codigo <> 'NO_LIQ'
                                THEN c.jcla_minutos
                            ELSE 0
                        END
                    ), 0) AS minutos_liquidables,
                    COALESCE(
                        resumen.resumen_conceptos,
                        '[]'::jsonb
                    )::text AS resumen_conceptos,
                    EXTRACT(
                        EPOCH FROM (j.jornada_fin - j.jornada_inicio)
                    )::integer / 60 AS minutos_intervalo
                FROM jornadas_trabajo j
                INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                LEFT JOIN jornada_clasificaciones c
                    ON c.jornada_id = j.jornada_id
                LEFT JOIN jornada_conceptos concepto
                    ON concepto.jcon_id = c.jcon_id
                LEFT JOIN (
                    SELECT
                        agrupado.jornada_id,
                        jsonb_agg(
                            jsonb_build_object(
                                'codigo', agrupado.jcon_codigo,
                                'concepto', agrupado.jcon_nombre,
                                'minutos', agrupado.minutos
                            )
                            ORDER BY agrupado.jcon_id
                        ) AS resumen_conceptos
                    FROM (
                        SELECT
                            clasificacion.jornada_id,
                            catalogo.jcon_id,
                            catalogo.jcon_codigo,
                            catalogo.jcon_nombre,
                            SUM(clasificacion.jcla_minutos)::integer AS minutos
                        FROM jornada_clasificaciones clasificacion
                        INNER JOIN jornada_conceptos catalogo
                            ON catalogo.jcon_id = clasificacion.jcon_id
                        WHERE catalogo.jcon_codigo <> 'NO_LIQ'
                        GROUP BY
                            clasificacion.jornada_id,
                            catalogo.jcon_id,
                            catalogo.jcon_codigo,
                            catalogo.jcon_nombre
                    ) agrupado
                    GROUP BY agrupado.jornada_id
                ) resumen ON resumen.jornada_id = j.jornada_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY
                    j.jornada_id,
                    emp.id_empl,
                    emp.cedu_empl,
                    emp.nomb_empl,
                    resumen.resumen_conceptos
                ORDER BY j.jornada_inicio DESC, j.jornada_id DESC";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(':fecha_desde', $fecha_desde, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_hasta', $fecha_hasta, PDO::PARAM_STR);
        if ($empleado_id !== null) {
            $stmt->bindValue(':empleado_id', $empleado_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clasifica todos los minutos de una jornada aprobada y reemplaza de forma
     * atómica cualquier cálculo anterior.
     */
    public function clasificar_jornada($jornada_id, $user_id) {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();

            $sql = "SELECT
                        j.*,
                        e.je_codigo
                    FROM jornadas_trabajo j
                    INNER JOIN jornada_estados e
                        ON e.je_id = j.jornada_estado_id
                    WHERE j.jornada_id = ?
                    FOR UPDATE OF j";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
            $stmt->execute();
            $jornada = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$jornada) {
                throw new RuntimeException('No se encontró la jornada.');
            }
            if ($jornada['je_codigo'] !== 'APROBADO') {
                throw new RuntimeException(
                    'Solo pueden clasificarse jornadas aprobadas.'
                );
            }
            if ((int)$jornada['jornada_inconsistente'] === 1) {
                $conectar->rollBack();
                return [
                    'clasificada' => false,
                    'inconsistente' => true,
                    'message' => 'La jornada está marcada como inconsistente y no puede liquidarse.'
                ];
            }

            $sql = "SELECT EXISTS (
                        SELECT 1
                        FROM jornadas_trabajo otra
                        INNER JOIN jornada_estados estado
                            ON estado.je_id = otra.jornada_estado_id
                        WHERE otra.empleado_id = ?
                          AND otra.jornada_id <> ?
                          AND estado.je_codigo <> 'RECHAZADO'
                          AND otra.jornada_inicio < ?
                          AND otra.jornada_fin > ?
                    )";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $jornada['empleado_id'], PDO::PARAM_INT);
            $stmt->bindValue(2, $jornada_id, PDO::PARAM_INT);
            $stmt->bindValue(3, $jornada['jornada_fin'], PDO::PARAM_STR);
            $stmt->bindValue(4, $jornada['jornada_inicio'], PDO::PARAM_STR);
            $stmt->execute();

            if ((bool)$stmt->fetchColumn()) {
                $stmt = $conectar->prepare(
                    "DELETE FROM jornada_clasificaciones
                     WHERE jornada_id = ?"
                );
                $stmt->execute([$jornada_id]);

                $sql = "UPDATE jornadas_trabajo
                        SET jornada_inconsistente = 1,
                            jornada_inconsistencia_detalle = ?,
                            jornada_fecha_actualizacion = CURRENT_TIMESTAMP
                        WHERE jornada_id = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->execute([
                    'No se clasificó porque el intervalo se superpone con otra jornada.',
                    $jornada_id
                ]);

                $sql = "INSERT INTO jornada_auditoria (
                            jornada_id,
                            jaud_accion,
                            jaud_estado_anterior,
                            jaud_estado_nuevo,
                            jaud_motivo,
                            jaud_usuario_id
                        )
                        VALUES (
                            ?,
                            'MARCAR_INCONSISTENCIA_CONTABILIDAD',
                            'APROBADO',
                            'APROBADO',
                            ?,
                            ?
                        )";
                $stmt = $conectar->prepare($sql);
                $stmt->execute([
                    $jornada_id,
                    'Intervalo superpuesto; clasificación contable bloqueada.',
                    $user_id
                ]);
                $conectar->commit();
                return [
                    'clasificada' => false,
                    'inconsistente' => true,
                    'message' => 'La jornada se superpone con otro registro y fue enviada a inconsistencias.'
                ];
            }

            $inicio = new DateTimeImmutable($jornada['jornada_inicio']);
            $fin = new DateTimeImmutable($jornada['jornada_fin']);
            if ($fin <= $inicio) {
                throw new RuntimeException(
                    'La jornada no tiene un intervalo válido.'
                );
            }

            $regla = $this->obtener_regla_clasificacion(
                $conectar,
                $inicio->format('Y-m-d')
            );
            $conceptos = $this->obtener_conceptos_clasificacion($conectar);
            $festivos = $this->obtener_festivos_intervalo(
                $conectar,
                $inicio->format('Y-m-d'),
                $fin->format('Y-m-d')
            );
            $segmentos = $this->generar_segmentos(
                $inicio,
                $fin,
                $festivos,
                $regla
            );

            $minutos_intervalo = (int)(
                ($fin->getTimestamp() - $inicio->getTimestamp()) / 60
            );
            $minutos_segmentados = array_sum(array_column($segmentos, 'minutos'));
            if (
                $minutos_intervalo <= 0
                || $minutos_segmentados !== $minutos_intervalo
            ) {
                throw new RuntimeException(
                    'La clasificación no cubrió exactamente el intervalo.'
                );
            }

            $stmt = $conectar->prepare(
                "DELETE FROM jornada_clasificaciones WHERE jornada_id = ?"
            );
            $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
            $stmt->execute();

            $sql = "INSERT INTO jornada_clasificaciones (
                        jornada_id,
                        jcon_id,
                        jreg_id,
                        jcla_inicio,
                        jcla_fin,
                        jcla_minutos,
                        jcla_calculado_por
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conectar->prepare($sql);

            foreach ($segmentos as $segmento) {
                if (!isset($conceptos[$segmento['concepto']])) {
                    throw new RuntimeException(
                        'No está configurado el concepto '
                        . $segmento['concepto']
                        . '.'
                    );
                }
                $stmt->execute([
                    $jornada_id,
                    $conceptos[$segmento['concepto']],
                    $regla['jreg_id'],
                    $segmento['inicio'],
                    $segmento['fin'],
                    $segmento['minutos'],
                    $user_id
                ]);
            }

            $sql = "INSERT INTO jornada_auditoria (
                        jornada_id,
                        jaud_accion,
                        jaud_estado_anterior,
                        jaud_estado_nuevo,
                        jaud_datos_nuevos,
                        jaud_motivo,
                        jaud_usuario_id
                    )
                    VALUES (
                        ?,
                        'CLASIFICAR_CONTABILIDAD',
                        'APROBADO',
                        'APROBADO',
                        ?::jsonb,
                        'Clasificación automática de tiempo',
                        ?
                    )";
            $stmt = $conectar->prepare($sql);
            $stmt->execute([
                $jornada_id,
                json_encode($segmentos, JSON_UNESCAPED_UNICODE),
                $user_id
            ]);

            $conectar->commit();
            return [
                'clasificada' => true,
                'inconsistente' => false,
                'segmentos' => count($segmentos),
                'minutos' => $minutos_segmentados
            ];
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Devuelve los segmentos contables de una jornada aprobada.
     */
    public function listar_segmentos_jornada($jornada_id) {
        $conectar = parent::Conexion();

        $sql = "SELECT
                    c.jcla_id,
                    c.jcla_inicio,
                    c.jcla_fin,
                    c.jcla_minutos,
                    con.jcon_codigo,
                    con.jcon_nombre,
                    con.jcon_codigo_contable
                FROM jornada_clasificaciones c
                INNER JOIN jornada_conceptos con
                    ON con.jcon_id = c.jcon_id
                INNER JOIN jornadas_trabajo j
                    ON j.jornada_id = c.jornada_id
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE c.jornada_id = ?
                  AND e.je_codigo = 'APROBADO'
                ORDER BY c.jcla_inicio, c.jcla_id";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista jornadas marcadas para revisión contable.
     */
    public function listar_inconsistencias(
        $fecha_desde,
        $fecha_hasta,
        $empleado_id = null
    ) {
        $conectar = parent::Conexion();

        $where = [
            "j.jornada_inconsistente = 1",
            "j.jornada_inicio::date >= :fecha_desde::date",
            "j.jornada_inicio::date <= :fecha_hasta::date"
        ];

        if ($empleado_id !== null) {
            $where[] = "j.empleado_id = :empleado_id";
        }

        $sql = "SELECT
                    j.jornada_id,
                    j.jornada_inicio,
                    j.jornada_fin,
                    j.jornada_minutos_ordinarios,
                    j.jornada_ubicacion,
                    j.jornada_actividad,
                    j.jornada_inconsistencia_detalle,
                    emp.id_empl AS empleado_id,
                    emp.cedu_empl AS documento,
                    emp.nomb_empl AS empleado,
                    e.je_codigo AS estado_codigo,
                    e.je_nombre AS estado_nombre
                FROM jornadas_trabajo j
                INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY j.jornada_inicio DESC, j.jornada_id DESC";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(':fecha_desde', $fecha_desde, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_hasta', $fecha_hasta, PDO::PARAM_STR);
        if ($empleado_id !== null) {
            $stmt->bindValue(':empleado_id', $empleado_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consolida únicamente segmentos ya clasificados por empleado y concepto.
     */
    public function reporte_contable(
        $fecha_desde,
        $fecha_hasta,
        $empleado_id = null
    ) {
        $conectar = parent::Conexion();

        $where = [
            "e.je_codigo = 'APROBADO'",
            "j.jornada_inicio::date >= :fecha_desde::date",
            "j.jornada_inicio::date <= :fecha_hasta::date"
        ];

        if ($empleado_id !== null) {
            $where[] = "j.empleado_id = :empleado_id";
        }

        $sql = "SELECT
                    emp.id_empl AS empleado_id,
                    emp.cedu_empl AS documento,
                    emp.nomb_empl AS empleado,
                    con.jcon_codigo AS concepto_codigo,
                    con.jcon_nombre AS concepto,
                    con.jcon_codigo_contable AS codigo_contable,
                    SUM(c.jcla_minutos) AS minutos
                FROM jornada_clasificaciones c
                INNER JOIN jornadas_trabajo j
                    ON j.jornada_id = c.jornada_id
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
                INNER JOIN jornada_conceptos con
                    ON con.jcon_id = c.jcon_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY
                    emp.id_empl,
                    emp.cedu_empl,
                    emp.nomb_empl,
                    con.jcon_id,
                    con.jcon_codigo,
                    con.jcon_nombre,
                    con.jcon_codigo_contable
                ORDER BY emp.nomb_empl, con.jcon_codigo";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(':fecha_desde', $fecha_desde, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_hasta', $fecha_hasta, PDO::PARAM_STR);
        if ($empleado_id !== null) {
            $stmt->bindValue(':empleado_id', $empleado_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Expone a Contabilidad la regla vigente y el catálogo de conceptos.
     */
    public function obtener_parametrizacion() {
        $conectar = parent::Conexion();

        $regla = $conectar->query(
            "SELECT
                jreg_id,
                jreg_nombre,
                jreg_vigencia_desde,
                jreg_vigencia_hasta,
                jreg_hora_diurna_inicio,
                jreg_hora_nocturna_inicio,
                jreg_recargo_nocturno_inicio,
                jreg_recargo_nocturno_fin,
                jreg_ordinaria_continuacion_fin,
                jreg_ordinaria_diurna_fin,
                jreg_max_lunes_viernes_min,
                jreg_max_sabado_min,
                jreg_almuerzo_min
             FROM jornada_reglas
             WHERE jreg_estado = 1
             ORDER BY jreg_vigencia_desde DESC, jreg_id DESC
             LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);

        $conceptos = $conectar->query(
            "SELECT
                jcon_codigo,
                jcon_nombre,
                jcon_codigo_contable
             FROM jornada_conceptos
             WHERE jcon_estado = 1
             ORDER BY jcon_id"
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'regla' => $regla,
            'conceptos' => $conceptos
        ];
    }

    /**
     * Lista las fechas especiales configuradas para un año.
     */
    public function listar_festivos($anio) {
        $conectar = parent::Conexion();

        $sql = "SELECT
                    f.cf_id,
                    f.cf_fecha,
                    f.cf_descripcion,
                    f.cf_estado,
                    f.cf_fecha_creacion,
                    COALESCE(e.nomb_empl, u.user_nick) AS creado_por
                FROM calendario_festivos f
                LEFT JOIN usuarios u ON u.user_id = f.cf_creado_por
                LEFT JOIN empleados e ON e.user_empl = u.user_id
                WHERE EXTRACT(YEAR FROM f.cf_fecha)::integer = ?
                ORDER BY f.cf_fecha";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $anio, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea o actualiza una fecha especial y reactiva el registro si estaba
     * inactivo.
     */
    public function guardar_festivo($fecha, $descripcion, $user_id) {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();
            $stmt = $conectar->prepare(
                "SELECT *
                 FROM calendario_festivos
                 WHERE cf_fecha = ?::date
                 FOR UPDATE"
            );
            $stmt->execute([$fecha]);
            $anterior = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($anterior) {
                $stmt = $conectar->prepare(
                    "UPDATE calendario_festivos
                     SET cf_descripcion = ?,
                         cf_estado = 1
                     WHERE cf_id = ?"
                );
                $stmt->execute([$descripcion, $anterior['cf_id']]);
                $festivo_id = (int)$anterior['cf_id'];
                $accion = 'ACTUALIZAR_FECHA_ESPECIAL';
            } else {
                $stmt = $conectar->prepare(
                    "INSERT INTO calendario_festivos (
                        cf_fecha,
                        cf_descripcion,
                        cf_estado,
                        cf_creado_por
                     )
                     VALUES (?::date, ?, 1, ?)
                     RETURNING cf_id"
                );
                $stmt->execute([$fecha, $descripcion, $user_id]);
                $festivo_id = (int)$stmt->fetchColumn();
                $accion = 'CREAR_FECHA_ESPECIAL';
            }

            $afectadas = $this->invalidar_clasificaciones_fecha(
                $conectar,
                $fecha
            );
            $this->auditar_configuracion(
                $conectar,
                $accion,
                $anterior ?: null,
                [
                    'cf_id' => $festivo_id,
                    'cf_fecha' => $fecha,
                    'cf_descripcion' => $descripcion,
                    'cf_estado' => 1
                ],
                $user_id
            );
            $conectar->commit();

            return [
                'festivo_id' => $festivo_id,
                'jornadas_invalidadas' => $afectadas
            ];
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Activa o inactiva una fecha especial conservando su historial.
     */
    public function cambiar_estado_festivo(
        $festivo_id,
        $estado,
        $user_id
    ) {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();
            $stmt = $conectar->prepare(
                "SELECT *
                 FROM calendario_festivos
                 WHERE cf_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$festivo_id]);
            $anterior = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$anterior) {
                throw new RuntimeException(
                    'No se encontró la fecha especial.'
                );
            }

            $stmt = $conectar->prepare(
                "UPDATE calendario_festivos
                 SET cf_estado = ?
                 WHERE cf_id = ?"
            );
            $stmt->execute([$estado, $festivo_id]);
            $afectadas = $this->invalidar_clasificaciones_fecha(
                $conectar,
                $anterior['cf_fecha']
            );
            $this->auditar_configuracion(
                $conectar,
                $estado === 1
                    ? 'ACTIVAR_FECHA_ESPECIAL'
                    : 'INACTIVAR_FECHA_ESPECIAL',
                $anterior,
                [
                    'cf_id' => (int)$festivo_id,
                    'cf_fecha' => $anterior['cf_fecha'],
                    'cf_descripcion' => $anterior['cf_descripcion'],
                    'cf_estado' => (int)$estado
                ],
                $user_id
            );
            $conectar->commit();

            return ['jornadas_invalidadas' => $afectadas];
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene la versión de reglas vigente para dejar trazabilidad del cálculo.
     */
    private function obtener_regla_clasificacion(PDO $conectar, $fecha) {
        $sql = "SELECT
                    jreg_id,
                    jreg_hora_diurna_inicio,
                    jreg_hora_nocturna_inicio,
                    jreg_recargo_nocturno_inicio,
                    jreg_recargo_nocturno_fin,
                    jreg_ordinaria_continuacion_fin,
                    jreg_ordinaria_diurna_fin,
                    jreg_max_lunes_viernes_min,
                    jreg_almuerzo_min
                FROM jornada_reglas
                WHERE jreg_estado = 1
                  AND jreg_vigencia_desde <= ?::date
                  AND (
                        jreg_vigencia_hasta IS NULL
                        OR jreg_vigencia_hasta >= ?::date
                  )
                ORDER BY jreg_vigencia_desde DESC, jreg_id DESC
                LIMIT 1";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([$fecha, $fecha]);
        $regla = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$regla) {
            throw new RuntimeException(
                'No existe una regla laboral vigente para la jornada.'
            );
        }
        return $regla;
    }

    /**
     * Carga el catálogo activo como mapa código-identificador.
     */
    private function obtener_conceptos_clasificacion(PDO $conectar) {
        $stmt = $conectar->query(
            "SELECT jcon_id, jcon_codigo
             FROM jornada_conceptos
             WHERE jcon_estado = 1"
        );
        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $mapa[$fila['jcon_codigo']] = (int)$fila['jcon_id'];
        }
        return $mapa;
    }

    /**
     * Obtiene festivos activos para evaluar cada fecha del intervalo.
     */
    private function obtener_festivos_intervalo(
        PDO $conectar,
        $fecha_desde,
        $fecha_hasta
    ) {
        $sql = "SELECT cf_fecha::text
                FROM calendario_festivos
                WHERE cf_estado = 1
                  AND cf_fecha >= ?::date
                  AND cf_fecha <= ?::date";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([$fecha_desde, $fecha_hasta]);
        return array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
    }

    /**
     * Segmenta minuto a minuto y fusiona minutos contiguos del mismo concepto.
     */
    private function generar_segmentos(
        DateTimeImmutable $inicio,
        DateTimeImmutable $fin,
        array $festivos,
        array $regla
    ) {
        $segmentos = [];
        $cursor = $inicio;

        while ($cursor < $fin) {
            $siguiente = $cursor->modify('+1 minute');
            if ($siguiente > $fin) {
                $siguiente = $fin;
            }

            $concepto = $this->clasificar_minuto(
                $cursor,
                $inicio,
                $fin,
                $festivos,
                $regla
            );
            $minutos = (int)(
                ($siguiente->getTimestamp() - $cursor->getTimestamp()) / 60
            );

            $ultimo = count($segmentos) - 1;
            if (
                $ultimo >= 0
                && $segmentos[$ultimo]['concepto'] === $concepto
                && $segmentos[$ultimo]['fin'] === $cursor->format('Y-m-d H:i:s')
            ) {
                $segmentos[$ultimo]['fin'] = $siguiente->format('Y-m-d H:i:s');
                $segmentos[$ultimo]['minutos'] += $minutos;
            } else {
                $segmentos[] = [
                    'concepto' => $concepto,
                    'inicio' => $cursor->format('Y-m-d H:i:s'),
                    'fin' => $siguiente->format('Y-m-d H:i:s'),
                    'minutos' => $minutos
                ];
            }
            $cursor = $siguiente;
        }
        return $segmentos;
    }

    /**
     * Aplica las franjas confirmadas por día, hora y continuidad de jornada.
     */
    private function clasificar_minuto(
        DateTimeImmutable $momento,
        DateTimeImmutable $inicio,
        DateTimeImmutable $fin,
        array $festivos,
        array $regla
    ) {
        $fecha = $momento->format('Y-m-d');
        $hora = $momento->format('H:i');
        $dia = (int)$momento->format('N');
        $es_festivo = $dia === 7 || isset($festivos[$fecha]);
        $hora_diurna = substr($regla['jreg_hora_diurna_inicio'], 0, 5);
        $hora_nocturna = substr($regla['jreg_hora_nocturna_inicio'], 0, 5);
        $recargo_inicio = substr(
            $regla['jreg_recargo_nocturno_inicio'],
            0,
            5
        );
        $recargo_fin = substr($regla['jreg_recargo_nocturno_fin'], 0, 5);
        $ordinaria_continuacion_fin = substr(
            $regla['jreg_ordinaria_continuacion_fin'],
            0,
            5
        );
        $ordinaria_diurna_fin = substr(
            $regla['jreg_ordinaria_diurna_fin'],
            0,
            5
        );
        $es_nocturno = $hora >= $hora_nocturna || $hora < $hora_diurna;

        if ($es_festivo) {
            return $es_nocturno ? 'HENF' : 'HEDF';
        }

        if ($dia === 6) {
            return $es_nocturno ? 'HEN' : 'HED';
        }

        $es_dia_continuado = $fecha > $inicio->format('Y-m-d');
        if ($es_dia_continuado) {
            if ($hora >= $recargo_inicio && $hora < $recargo_fin) {
                return 'RN';
            }
            if (
                $hora >= $recargo_fin
                && $hora < $ordinaria_continuacion_fin
            ) {
                return 'ORD';
            }
            return $hora < $hora_nocturna ? 'HED' : 'HEN';
        }

        // Si el turno empieza antes de las 06:00 en un día hábil, las
        // primeras ocho horas cumplen la jornada ordinaria. La parte nocturna
        // genera recargo y la parte diurna conserva el concepto ordinario.
        if ($inicio->format('H:i') < $hora_diurna) {
            $fin_ordinario = $inicio->modify(
                '+' . (int)$regla['jreg_max_lunes_viernes_min'] . ' minutes'
            );
            if ($momento < $fin_ordinario) {
                return $es_nocturno ? 'RN' : 'ORD';
            }
        }

        // Para la jornada diurna, el almuerzo es una deducción abstracta:
        // se ubica al final del tramo ordinario solo para representar sus
        // minutos, sin afirmar a qué hora almorzó realmente el colaborador.
        if (
            $inicio->format('H:i') >= $hora_diurna
            && $inicio->format('H:i') < $ordinaria_diurna_fin
            && $hora < $ordinaria_diurna_fin
        ) {
            $fin_ordinario = new DateTimeImmutable(
                $inicio->format('Y-m-d') . ' ' . $ordinaria_diurna_fin
            );
            $fin_descuento = $fin < $fin_ordinario ? $fin : $fin_ordinario;
            $inicio_descuento = $fin_descuento->modify(
                '-' . (int)$regla['jreg_almuerzo_min'] . ' minutes'
            );
            if ($momento >= $inicio_descuento) {
                return 'NO_LIQ';
            }
            return 'ORD';
        }

        // En el caso operativo 18:00-06:00, la primera hora no se liquida.
        $inicio_no_liquidable = (new DateTimeImmutable(
            '2000-01-01 ' . $hora_nocturna
        ))->modify('-1 hour')->format('H:i');
        if (
            $inicio->format('H:i') === $inicio_no_liquidable
            && $hora >= $inicio_no_liquidable
            && $hora < $hora_nocturna
        ) {
            return 'NO_LIQ';
        }

        return $es_nocturno ? 'HEN' : 'HED';
    }

    /**
     * Elimina cálculos que atraviesan la fecha modificada y retorna cuántas
     * jornadas deberán recalcularse.
     */
    private function invalidar_clasificaciones_fecha(
        PDO $conectar,
        $fecha
    ) {
        $stmt = $conectar->prepare(
            "SELECT COUNT(DISTINCT c.jornada_id)
             FROM jornada_clasificaciones c
             INNER JOIN jornadas_trabajo j
                 ON j.jornada_id = c.jornada_id
             WHERE j.jornada_inicio < (?::date + INTERVAL '1 day')
               AND j.jornada_fin > ?::date"
        );
        $stmt->execute([$fecha, $fecha]);
        $afectadas = (int)$stmt->fetchColumn();

        $stmt = $conectar->prepare(
            "DELETE FROM jornada_clasificaciones c
             USING jornadas_trabajo j
             WHERE j.jornada_id = c.jornada_id
               AND j.jornada_inicio < (?::date + INTERVAL '1 day')
               AND j.jornada_fin > ?::date"
        );
        $stmt->execute([$fecha, $fecha]);
        return $afectadas;
    }

    /**
     * Registra cambios de configuración en la auditoría general del módulo.
     */
    private function auditar_configuracion(
        PDO $conectar,
        $accion,
        $datos_anteriores,
        $datos_nuevos,
        $user_id
    ) {
        $sql = "INSERT INTO jornada_auditoria (
                    jornada_id,
                    jaud_accion,
                    jaud_datos_anteriores,
                    jaud_datos_nuevos,
                    jaud_usuario_id
                )
                VALUES (NULL, ?, ?::jsonb, ?::jsonb, ?)";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([
            $accion,
            $datos_anteriores === null
                ? null
                : json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE),
            json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE),
            $user_id
        ]);
    }
}

?>
