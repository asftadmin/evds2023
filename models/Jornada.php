<?php

/**
 * Acceso a datos y reglas transaccionales del módulo de jornadas.
 *
 * El controlador se ocupa del protocolo HTTP y este modelo concentra todo
 * el SQL, la autorización basada en menú, los cambios de estado y auditoría.
 */
class Jornada extends Conectar {

    /**
     * Obtiene el empleado relacionado con el usuario autenticado.
     */
    public function obtener_empleado_por_usuario($user_id) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                    e.id_empl,
                    e.cedu_empl,
                    e.nomb_empl,
                    e.esta_empl,
                    e.trabaja_sabado,
                    u.user_id,
                    u.user_rol,
                    r.rol_nomb
                FROM usuarios u
                INNER JOIN empleados e ON e.user_empl = u.user_id
                INNER JOIN rol r ON r.rol_id = u.user_rol
                WHERE u.user_id = ?
                LIMIT 1";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Determina si un empleado tiene subordinados activos.
     */
    public function es_jefe_activo($empleado_id) {
        $conectar = parent::Conexion();

        $sql = "SELECT EXISTS (
                    SELECT 1
                    FROM empleado_jefe
                    WHERE jefe_id = ?
                      AND ej_estado = 1
                )";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Valida el acceso al menú usando exclusivamente rol, menú y permisos.
     * Si el usuario es jefe también puede heredar los menús del rol 5.
     */
    public function tiene_permiso_menu($rol_id, $menu_ident, $es_jefe = false) {
        $conectar = parent::Conexion();

        $sql = "SELECT EXISTS (
                    SELECT 1
                    FROM permisos p
                    INNER JOIN menu m ON m.menu_id = p.perm_menu
                    INNER JOIN rol r ON r.rol_id = p.perm_rol
                    WHERE m.menu_ident = ?
                      AND m.menu_ruta = '../MntJornadas/'
                      AND m.menu_esta = 1
                      AND p.perm_esta = 1
                      AND p.perm_usua = 'Si'
                      AND (
                            p.perm_rol = ?
                            OR (
                                ? = 1
                                AND r.rol_nomb = 'Jefe Inmediato'
                            )
                      )
                      AND r.rol_esta = 1
                )";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $menu_ident, PDO::PARAM_STR);
        $stmt->bindValue(2, $rol_id, PDO::PARAM_INT);
        $stmt->bindValue(3, $es_jefe ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Consulta si una fecha está marcada como festiva y activa.
     */
    public function fecha_es_festiva($fecha) {
        $conectar = parent::Conexion();

        $sql = "SELECT EXISTS (
                    SELECT 1
                    FROM calendario_festivos
                    WHERE cf_fecha = ?::date
                      AND cf_estado = 1
                )";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Obtiene la regla vigente que contiene el descuento de almuerzo.
     */
    public function obtener_regla_vigente($fecha) {
        $conectar = parent::Conexion();

        $sql = "SELECT
                    jreg_id,
                    jreg_hora_diurna_inicio,
                    jreg_hora_nocturna_inicio,
                    jreg_max_lunes_viernes_min,
                    jreg_max_sabado_min,
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
        $stmt->bindValue(1, $fecha, PDO::PARAM_STR);
        $stmt->bindValue(2, $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Detecta cruces con registros vigentes del mismo empleado.
     */
    public function existe_superposicion(
        $empleado_id,
        $inicio,
        $fin,
        $jornada_excluir = null
    ) {
        $conectar = parent::Conexion();

        $sql = "SELECT EXISTS (
                    SELECT 1
                    FROM jornadas_trabajo j
                    INNER JOIN jornada_estados e
                        ON e.je_id = j.jornada_estado_id
                    WHERE j.empleado_id = :empleado_id
                      AND e.je_codigo <> 'RECHAZADO'
                      AND j.jornada_inicio < :fin
                      AND j.jornada_fin > :inicio";

        if ($jornada_excluir !== null) {
            $sql .= " AND j.jornada_id <> :jornada_excluir";
        }

        $sql .= ")";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(':empleado_id', $empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(':inicio', $inicio, PDO::PARAM_STR);
        $stmt->bindValue(':fin', $fin, PDO::PARAM_STR);

        if ($jornada_excluir !== null) {
            $stmt->bindValue(':jornada_excluir', $jornada_excluir, PDO::PARAM_INT);
        }

        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Crea o actualiza un borrador propio y registra su auditoría.
     */
    public function guardar_borrador_propio(
        $jornada_id,
        $empleado_id,
        $user_id,
        $inicio,
        $fin,
        $minutos_ordinarios,
        $ubicacion,
        $actividad,
        $observaciones
    ) {
        $conectar = parent::Conexion();
        parent::set_names();

        try {
            $conectar->beginTransaction();
            $estado_borrador = $this->obtener_estado_id($conectar, 'BORRADOR');

            $datos_nuevos = [
                'empleado_id' => (int)$empleado_id,
                'jornada_inicio' => $inicio,
                'jornada_fin' => $fin,
                'jornada_minutos_ordinarios' => (int)$minutos_ordinarios,
                'jornada_ubicacion' => $ubicacion,
                'jornada_actividad' => $actividad,
                'jornada_observaciones' => $observaciones
            ];

            if ($jornada_id === null) {
                $sql = "INSERT INTO jornadas_trabajo (
                            empleado_id,
                            jornada_inicio,
                            jornada_fin,
                            jornada_minutos_ordinarios,
                            jornada_ubicacion,
                            jornada_actividad,
                            jornada_observaciones,
                            jornada_origen,
                            jornada_estado_id,
                            jornada_creado_por
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'AUTOREGISTRO', ?, ?)
                        RETURNING jornada_id";

                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $inicio, PDO::PARAM_STR);
                $stmt->bindValue(3, $fin, PDO::PARAM_STR);
                $stmt->bindValue(4, $minutos_ordinarios, PDO::PARAM_INT);
                $stmt->bindValue(5, $ubicacion, PDO::PARAM_STR);
                $stmt->bindValue(6, $actividad, PDO::PARAM_STR);
                $stmt->bindValue(7, $observaciones, PDO::PARAM_STR);
                $stmt->bindValue(8, $estado_borrador, PDO::PARAM_INT);
                $stmt->bindValue(9, $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $jornada_id = (int)$stmt->fetchColumn();

                $this->registrar_auditoria(
                    $conectar,
                    $jornada_id,
                    'CREAR_BORRADOR',
                    null,
                    'BORRADOR',
                    null,
                    $datos_nuevos,
                    null,
                    $user_id
                );
            } else {
                $anterior = $this->obtener_jornada_bloqueada(
                    $conectar,
                    $jornada_id,
                    $empleado_id
                );

                if (!$anterior || $anterior['je_codigo'] !== 'BORRADOR') {
                    throw new RuntimeException(
                        'La jornada no existe o ya no puede editarse.'
                    );
                }

                $sql = "UPDATE jornadas_trabajo
                        SET jornada_inicio = ?,
                            jornada_fin = ?,
                            jornada_minutos_ordinarios = ?,
                            jornada_ubicacion = ?,
                            jornada_actividad = ?,
                            jornada_observaciones = ?,
                            jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                            jornada_version = jornada_version + 1
                        WHERE jornada_id = ?
                          AND empleado_id = ?";

                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $inicio, PDO::PARAM_STR);
                $stmt->bindValue(2, $fin, PDO::PARAM_STR);
                $stmt->bindValue(3, $minutos_ordinarios, PDO::PARAM_INT);
                $stmt->bindValue(4, $ubicacion, PDO::PARAM_STR);
                $stmt->bindValue(5, $actividad, PDO::PARAM_STR);
                $stmt->bindValue(6, $observaciones, PDO::PARAM_STR);
                $stmt->bindValue(7, $jornada_id, PDO::PARAM_INT);
                $stmt->bindValue(8, $empleado_id, PDO::PARAM_INT);
                $stmt->execute();

                $this->registrar_auditoria(
                    $conectar,
                    $jornada_id,
                    'ACTUALIZAR_BORRADOR',
                    'BORRADOR',
                    'BORRADOR',
                    $anterior,
                    $datos_nuevos,
                    null,
                    $user_id
                );
            }

            $conectar->commit();
            return (int)$jornada_id;
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Envía un borrador propio a aprobación utilizando el estado esperado
     * para impedir transiciones duplicadas.
     */
    public function enviar_aprobacion_propia($jornada_id, $empleado_id, $user_id) {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();
            $anterior = $this->obtener_jornada_bloqueada(
                $conectar,
                $jornada_id,
                $empleado_id
            );

            if (!$anterior || $anterior['je_codigo'] !== 'BORRADOR') {
                throw new RuntimeException(
                    'La jornada no está disponible para enviar a aprobación.'
                );
            }

            $estado_pendiente = $this->obtener_estado_id(
                $conectar,
                'PENDIENTE_APROBACION'
            );

            $sql = "UPDATE jornadas_trabajo
                    SET jornada_estado_id = ?,
                        jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                        jornada_version = jornada_version + 1
                    WHERE jornada_id = ?
                      AND empleado_id = ?
                      AND jornada_estado_id = ?";

            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $estado_pendiente, PDO::PARAM_INT);
            $stmt->bindValue(2, $jornada_id, PDO::PARAM_INT);
            $stmt->bindValue(3, $empleado_id, PDO::PARAM_INT);
            $stmt->bindValue(4, $anterior['jornada_estado_id'], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'La jornada cambió de estado antes de completar la operación.'
                );
            }

            $this->registrar_auditoria(
                $conectar,
                $jornada_id,
                'ENVIAR_APROBACION',
                'BORRADOR',
                'PENDIENTE_APROBACION',
                $anterior,
                null,
                null,
                $user_id
            );

            $conectar->commit();
            return true;
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lista únicamente los campos operativos de las jornadas propias.
     */
    public function listar_mis_jornadas(
        $empleado_id,
        $fecha_desde = null,
        $fecha_hasta = null
    ) {
        $conectar = parent::Conexion();

        $where = ["j.empleado_id = :empleado_id"];
        $params = [':empleado_id' => (int)$empleado_id];

        if ($fecha_desde !== null) {
            $where[] = "j.jornada_inicio::date >= :fecha_desde::date";
            $params[':fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta !== null) {
            $where[] = "j.jornada_inicio::date <= :fecha_hasta::date";
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $sql = "SELECT
                    j.jornada_id,
                    j.jornada_inicio,
                    j.jornada_fin,
                    j.jornada_minutos_ordinarios,
                    j.jornada_ubicacion,
                    j.jornada_actividad,
                    j.jornada_observaciones,
                    j.jornada_inconsistente,
                    j.jornada_inconsistencia_detalle,
                    e.je_codigo AS estado_codigo,
                    e.je_nombre AS estado_nombre
                FROM jornadas_trabajo j
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY j.jornada_inicio DESC, j.jornada_id DESC";

        $stmt = $conectar->prepare($sql);
        foreach ($params as $clave => $valor) {
            $tipo = $clave === ':empleado_id' ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($clave, $valor, $tipo);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el detalle operativo de una jornada propia, sin clasificación.
     */
    public function obtener_mi_jornada($jornada_id, $empleado_id) {
        $conectar = parent::Conexion();

        $sql = "SELECT
                    j.jornada_id,
                    j.jornada_inicio,
                    j.jornada_fin,
                    j.jornada_minutos_ordinarios,
                    j.jornada_ubicacion,
                    j.jornada_actividad,
                    j.jornada_observaciones,
                    e.je_codigo AS estado_codigo,
                    e.je_nombre AS estado_nombre
                FROM jornadas_trabajo j
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE j.jornada_id = ?
                  AND j.empleado_id = ?
                LIMIT 1";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista las jornadas pendientes de empleados relacionados activamente con
     * el jefe. No retorna clasificaciones ni conceptos contables.
     */
    public function listar_pendientes_jefe(
        $jefe_empleado_id,
        $fecha_desde = null,
        $fecha_hasta = null
    ) {
        $conectar = parent::Conexion();

        $where = [
            "e.je_codigo = 'PENDIENTE_APROBACION'",
            "EXISTS (
                SELECT 1
                FROM empleado_jefe ej
                WHERE ej.empleado_id = j.empleado_id
                  AND ej.jefe_id = :jefe_empleado_id
                  AND ej.ej_estado = 1
            )"
        ];
        $params = [':jefe_empleado_id' => (int)$jefe_empleado_id];

        if ($fecha_desde !== null) {
            $where[] = "j.jornada_inicio::date >= :fecha_desde::date";
            $params[':fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta !== null) {
            $where[] = "j.jornada_inicio::date <= :fecha_hasta::date";
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $sql = "SELECT
                    j.jornada_id,
                    j.jornada_inicio,
                    j.jornada_fin,
                    j.jornada_minutos_ordinarios,
                    j.jornada_ubicacion,
                    j.jornada_actividad,
                    j.jornada_observaciones,
                    j.jornada_fecha_actualizacion,
                    emp.id_empl AS empleado_id,
                    emp.cedu_empl AS empleado_documento,
                    emp.nomb_empl AS empleado_nombre,
                    e.je_codigo AS estado_codigo,
                    e.je_nombre AS estado_nombre
                FROM jornadas_trabajo j
                INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY j.jornada_fecha_actualizacion ASC, j.jornada_id ASC";

        $stmt = $conectar->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue(
                $clave,
                $valor,
                $clave === ':jefe_empleado_id'
                    ? PDO::PARAM_INT
                    : PDO::PARAM_STR
            );
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista los empleados activos que conservan una relación vigente con el
     * jefe autenticado.
     */
    public function listar_subordinados_jefe($jefe_empleado_id) {
        $conectar = parent::Conexion();

        $sql = "SELECT DISTINCT
                    e.id_empl AS empleado_id,
                    e.cedu_empl AS empleado_documento,
                    e.nomb_empl AS empleado_nombre
                FROM empleado_jefe ej
                INNER JOIN empleados e ON e.id_empl = ej.empleado_id
                WHERE ej.jefe_id = ?
                  AND ej.ej_estado = 1
                  AND e.esta_empl = 1
                ORDER BY e.nomb_empl, e.cedu_empl";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jefe_empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista las jornadas de los empleados actualmente relacionados con el
     * jefe, sin exponer clasificación ni conceptos contables.
     */
    public function listar_jornadas_equipo(
        $jefe_empleado_id,
        $fecha_desde = null,
        $fecha_hasta = null
    ) {
        $conectar = parent::Conexion();

        $where = [
            "EXISTS (
                SELECT 1
                FROM empleado_jefe ej
                WHERE ej.empleado_id = j.empleado_id
                  AND ej.jefe_id = :jefe_empleado_id
                  AND ej.ej_estado = 1
            )"
        ];
        $params = [':jefe_empleado_id' => (int)$jefe_empleado_id];

        if ($fecha_desde !== null) {
            $where[] = "j.jornada_inicio::date >= :fecha_desde::date";
            $params[':fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta !== null) {
            $where[] = "j.jornada_inicio::date <= :fecha_hasta::date";
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $sql = "SELECT
                    j.jornada_id,
                    j.jornada_inicio,
                    j.jornada_fin,
                    j.jornada_minutos_ordinarios,
                    j.jornada_ubicacion,
                    j.jornada_actividad,
                    j.jornada_observaciones,
                    j.jornada_origen,
                    emp.id_empl AS empleado_id,
                    emp.cedu_empl AS empleado_documento,
                    emp.nomb_empl AS empleado_nombre,
                    e.je_codigo AS estado_codigo,
                    e.je_nombre AS estado_nombre
                FROM jornadas_trabajo j
                INNER JOIN empleados emp ON emp.id_empl = j.empleado_id
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY j.jornada_inicio DESC, j.jornada_id DESC";

        $stmt = $conectar->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue(
                $clave,
                $valor,
                $clave === ':jefe_empleado_id'
                    ? PDO::PARAM_INT
                    : PDO::PARAM_STR
            );
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra una jornada en nombre de un subordinado y la aprueba en la
     * misma transacción por autoridad del jefe.
     */
    public function guardar_jornada_equipo_aprobada(
        $empleado_id,
        $jefe_empleado_id,
        $user_id,
        $inicio,
        $fin,
        $minutos_ordinarios,
        $ubicacion,
        $actividad,
        $observaciones
    ) {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();

            // Serializa registros simultáneos del mismo empleado para que la
            // verificación de superposición y la inserción sean atómicas.
            $stmt = $conectar->prepare(
                "SELECT pg_advisory_xact_lock(?)"
            );
            $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
            $stmt->execute();

            $sql = "SELECT EXISTS (
                        SELECT 1
                        FROM empleado_jefe ej
                        INNER JOIN empleados e
                            ON e.id_empl = ej.empleado_id
                        WHERE ej.empleado_id = ?
                          AND ej.jefe_id = ?
                          AND ej.ej_estado = 1
                          AND e.esta_empl = 1
                    )";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $jefe_empleado_id, PDO::PARAM_INT);
            $stmt->execute();

            if (!(bool)$stmt->fetchColumn()) {
                throw new RuntimeException(
                    'El empleado no está relacionado activamente con el jefe.'
                );
            }

            $sql = "SELECT EXISTS (
                        SELECT 1
                        FROM jornadas_trabajo j
                        INNER JOIN jornada_estados e
                            ON e.je_id = j.jornada_estado_id
                        WHERE j.empleado_id = ?
                          AND e.je_codigo <> 'RECHAZADO'
                          AND j.jornada_inicio < ?
                          AND j.jornada_fin > ?
                    )";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $fin, PDO::PARAM_STR);
            $stmt->bindValue(3, $inicio, PDO::PARAM_STR);
            $stmt->execute();

            if ((bool)$stmt->fetchColumn()) {
                throw new RuntimeException(
                    'El intervalo se superpone con otra jornada del empleado.'
                );
            }

            $estado_aprobado = $this->obtener_estado_id(
                $conectar,
                'APROBADO'
            );

            $sql = "INSERT INTO jornadas_trabajo (
                        empleado_id,
                        jornada_inicio,
                        jornada_fin,
                        jornada_minutos_ordinarios,
                        jornada_ubicacion,
                        jornada_actividad,
                        jornada_observaciones,
                        jornada_origen,
                        jornada_estado_id,
                        jornada_creado_por
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'REGISTRO_JEFE', ?, ?)
                    RETURNING jornada_id";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $inicio, PDO::PARAM_STR);
            $stmt->bindValue(3, $fin, PDO::PARAM_STR);
            $stmt->bindValue(4, $minutos_ordinarios, PDO::PARAM_INT);
            $stmt->bindValue(5, $ubicacion, PDO::PARAM_STR);
            $stmt->bindValue(6, $actividad, PDO::PARAM_STR);
            $stmt->bindValue(7, $observaciones, PDO::PARAM_STR);
            $stmt->bindValue(8, $estado_aprobado, PDO::PARAM_INT);
            $stmt->bindValue(9, $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $jornada_id = (int)$stmt->fetchColumn();

            $sql = "INSERT INTO jornada_aprobaciones (
                        jornada_id,
                        jap_etapa,
                        jap_decision,
                        jap_usuario_id,
                        jap_empleado_id,
                        jap_motivo
                    )
                    VALUES (
                        ?,
                        'REGISTRO_JEFE',
                        'APROBADO',
                        ?,
                        ?,
                        'Aprobación automática por registro del jefe'
                    )";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(3, $jefe_empleado_id, PDO::PARAM_INT);
            $stmt->execute();

            $datos_nuevos = [
                'empleado_id' => (int)$empleado_id,
                'jornada_inicio' => $inicio,
                'jornada_fin' => $fin,
                'jornada_minutos_ordinarios' => (int)$minutos_ordinarios,
                'jornada_ubicacion' => $ubicacion,
                'jornada_actividad' => $actividad,
                'jornada_observaciones' => $observaciones,
                'jornada_origen' => 'REGISTRO_JEFE'
            ];
            $this->registrar_auditoria(
                $conectar,
                $jornada_id,
                'REGISTRAR_APROBAR_JEFE',
                null,
                'APROBADO',
                null,
                $datos_nuevos,
                'Aprobación automática por registro del jefe',
                $user_id
            );

            $conectar->commit();
            return $jornada_id;
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Aprueba o rechaza una jornada pendiente. El bloqueo de fila garantiza
     * que, cuando hay varios jefes relacionados, solo la primera decisión
     * cambie el estado.
     */
    public function decidir_jornada_jefe(
        $jornada_id,
        $jefe_empleado_id,
        $user_id,
        $decision,
        $motivo
    ) {
        $decision = strtoupper(trim((string)$decision));
        if (!in_array($decision, ['APROBAR', 'RECHAZAR'], true)) {
            throw new InvalidArgumentException('La decisión indicada no es válida.');
        }

        $motivo = trim((string)$motivo);
        if ($decision === 'RECHAZAR' && $motivo === '') {
            throw new InvalidArgumentException(
                'Debe indicar el motivo del rechazo.'
            );
        }

        if (mb_strlen($motivo) > 2000) {
            throw new InvalidArgumentException(
                'El motivo admite máximo 2000 caracteres.'
            );
        }

        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();
            $anterior = $this->obtener_jornada_equipo_bloqueada(
                $conectar,
                $jornada_id,
                $jefe_empleado_id
            );

            if (!$anterior) {
                throw new RuntimeException(
                    'La jornada no pertenece a un empleado relacionado con el jefe.'
                );
            }

            if ($anterior['je_codigo'] !== 'PENDIENTE_APROBACION') {
                throw new RuntimeException(
                    'La jornada ya fue decidida por otro jefe o cambió de estado.'
                );
            }

            $estado_nuevo = $decision === 'APROBAR'
                ? 'APROBADO'
                : 'RECHAZADO';
            $estado_nuevo_id = $this->obtener_estado_id(
                $conectar,
                $estado_nuevo
            );

            $sql = "UPDATE jornadas_trabajo
                    SET jornada_estado_id = ?,
                        jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                        jornada_version = jornada_version + 1
                    WHERE jornada_id = ?
                      AND jornada_estado_id = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $estado_nuevo_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $jornada_id, PDO::PARAM_INT);
            $stmt->bindValue(
                3,
                $anterior['jornada_estado_id'],
                PDO::PARAM_INT
            );
            $stmt->execute();

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'La jornada cambió de estado antes de completar la decisión.'
                );
            }

            $sql = "INSERT INTO jornada_aprobaciones (
                        jornada_id,
                        jap_etapa,
                        jap_decision,
                        jap_usuario_id,
                        jap_empleado_id,
                        jap_motivo
                    )
                    VALUES (?, 'JEFE_INMEDIATO', ?, ?, ?, ?)";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $estado_nuevo, PDO::PARAM_STR);
            $stmt->bindValue(3, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(4, $jefe_empleado_id, PDO::PARAM_INT);
            $stmt->bindValue(
                5,
                $motivo === '' ? null : $motivo,
                $motivo === '' ? PDO::PARAM_NULL : PDO::PARAM_STR
            );
            $stmt->execute();

            $this->registrar_auditoria(
                $conectar,
                $jornada_id,
                $decision === 'APROBAR'
                    ? 'APROBAR_JEFE'
                    : 'RECHAZAR_JEFE',
                'PENDIENTE_APROBACION',
                $estado_nuevo,
                $anterior,
                null,
                $motivo === '' ? null : $motivo,
                $user_id
            );

            $conectar->commit();
            return $estado_nuevo;
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Resuelve un estado activo dentro de la transacción actual.
     */
    private function obtener_estado_id(PDO $conectar, $codigo) {
        $sql = "SELECT je_id
                FROM jornada_estados
                WHERE je_codigo = ?
                  AND je_estado = 1
                LIMIT 1";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $estado_id = $stmt->fetchColumn();

        if ($estado_id === false) {
            throw new RuntimeException(
                'No se encuentra configurado el estado ' . $codigo . '.'
            );
        }

        return (int)$estado_id;
    }

    /**
     * Bloquea una jornada propia para garantizar una transición consistente.
     */
    private function obtener_jornada_bloqueada(
        PDO $conectar,
        $jornada_id,
        $empleado_id
    ) {
        $sql = "SELECT
                    j.*,
                    e.je_codigo
                FROM jornadas_trabajo j
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE j.jornada_id = ?
                  AND j.empleado_id = ?
                FOR UPDATE";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Bloquea una jornada únicamente si el jefe conserva una relación activa
     * con el empleado propietario.
     */
    private function obtener_jornada_equipo_bloqueada(
        PDO $conectar,
        $jornada_id,
        $jefe_empleado_id
    ) {
        $sql = "SELECT
                    j.*,
                    e.je_codigo
                FROM jornadas_trabajo j
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE j.jornada_id = ?
                  AND EXISTS (
                        SELECT 1
                        FROM empleado_jefe ej
                        WHERE ej.empleado_id = j.empleado_id
                          AND ej.jefe_id = ?
                          AND ej.ej_estado = 1
                  )
                FOR UPDATE OF j";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $jefe_empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registra los cambios relevantes como JSON para conservar trazabilidad.
     */
    private function registrar_auditoria(
        PDO $conectar,
        $jornada_id,
        $accion,
        $estado_anterior,
        $estado_nuevo,
        $datos_anteriores,
        $datos_nuevos,
        $motivo,
        $user_id
    ) {
        $sql = "INSERT INTO jornada_auditoria (
                    jornada_id,
                    jaud_accion,
                    jaud_estado_anterior,
                    jaud_estado_nuevo,
                    jaud_datos_anteriores,
                    jaud_datos_nuevos,
                    jaud_motivo,
                    jaud_usuario_id
                )
                VALUES (?, ?, ?, ?, ?::jsonb, ?::jsonb, ?, ?)";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $accion, PDO::PARAM_STR);
        $stmt->bindValue(3, $estado_anterior, PDO::PARAM_STR);
        $stmt->bindValue(4, $estado_nuevo, PDO::PARAM_STR);
        $stmt->bindValue(
            5,
            $datos_anteriores === null
                ? null
                : json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE),
            $datos_anteriores === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );
        $stmt->bindValue(
            6,
            $datos_nuevos === null
                ? null
                : json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE),
            $datos_nuevos === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );
        $stmt->bindValue(7, $motivo, PDO::PARAM_STR);
        $stmt->bindValue(8, $user_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}

?>
