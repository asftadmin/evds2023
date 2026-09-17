<?php

/**
 * Acceso a datos y reglas transaccionales del módulo de jornadas.
 *
 * El controlador se ocupa del protocolo HTTP y este modelo concentra todo
 * el SQL, la autorización basada en menú, los cambios de estado y auditoría.
 */
class Jornada extends Conectar
{
    /**
     * Obtiene el empleado relacionado con el usuario autenticado.
     */
    public function obtener_empleado_por_usuario($user_id)
    {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = 'SELECT
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
                LIMIT 1';

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Determina si un empleado tiene subordinados activos.
     */
    public function es_jefe_activo($empleado_id)
    {
        $conectar = parent::Conexion();

        $sql = 'SELECT EXISTS (
                    SELECT 1
                    FROM empleado_jefe
                    WHERE jefe_id = ?
                      AND ej_estado = 1
                )';

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Valida el acceso al menú usando exclusivamente rol, menú y permisos.
     * Si el usuario es jefe también puede heredar los menús del rol 5.
     */
    public function tiene_permiso_menu($rol_id, $menu_ident, $es_jefe = false)
    {
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
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Consulta si una fecha está marcada como festiva y activa.
     */
    public function fecha_es_festiva($fecha)
    {
        $conectar = parent::Conexion();

        $sql = 'SELECT EXISTS (
                    SELECT 1
                    FROM calendario_festivos
                    WHERE cf_fecha = ?::date
                      AND cf_estado = 1
                )';

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $fecha, PDO::PARAM_STR);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Obtiene la regla vigente que contiene el descuento de almuerzo.
     */
    public function obtener_regla_vigente($fecha)
    {
        $conectar = parent::Conexion();

        $sql = 'SELECT
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
                LIMIT 1';

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
                      AND e.je_codigo NOT IN ('RECHAZADO', 'ANULADO')
                      AND j.jornada_inicio < :fin
                      AND j.jornada_fin > :inicio";

        if ($jornada_excluir !== null) {
            $sql .= ' AND j.jornada_id <> :jornada_excluir';
        }

        $sql .= ')';

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(':empleado_id', $empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(':inicio', $inicio, PDO::PARAM_STR);
        $stmt->bindValue(':fin', $fin, PDO::PARAM_STR);

        if ($jornada_excluir !== null) {
            $stmt->bindValue(':jornada_excluir', $jornada_excluir, PDO::PARAM_INT);
        }

        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Consulta el día en todo el historial, sin depender del periodo visible.
     */
    public function obtener_jornada_fecha($empleado_id, $fecha, $jornada_excluir = null)
    {
        return $this->buscar_conflicto(
            parent::Conexion(),
            $empleado_id,
            $fecha . ' 00:00:00',
            null,
            $jornada_excluir
        );
    }

    /**
     * Serializa las altas, ediciones y envíos del mismo empleado.
     */
    private function bloquear_empleado(PDO $conectar, $empleado_id)
    {
        $stmt = $conectar->prepare('SELECT pg_advisory_xact_lock(?)');
        $stmt->execute([(int) $empleado_id]);
    }

    private function buscar_conflicto(
        PDO $conectar,
        $empleado_id,
        $inicio,
        $fin = null,
        $jornada_excluir = null
    ) {
        $sql = "SELECT
                j.jornada_id,
                j.jornada_inicio::date AS fecha,
                e.je_nombre AS estado_nombre
            FROM jornadas_trabajo j
            INNER JOIN jornada_estados e
                ON e.je_id = j.jornada_estado_id
            WHERE j.empleado_id = :empleado_id
              AND e.je_codigo NOT IN ('RECHAZADO', 'ANULADO')";

        $params = [
            ':empleado_id' => (int) $empleado_id
        ];

        /*
         * Si se recibe inicio y fin, solamente existe conflicto cuando
         * los intervalos horarios realmente se superponen.
         */
        if ($fin !== null) {
            $sql .= '
            AND j.jornada_inicio < :fin
            AND j.jornada_fin > :inicio';

            $params[':inicio'] = $inicio;
            $params[':fin'] = $fin;
        } else {
            /*
             * Esta variante se conserva para las consultas que requieren
             * localizar cualquier jornada existente en una fecha.
             */
            $sql .= '
            AND j.jornada_inicio::date = CAST(:fecha AS date)';

            $params[':fecha'] = substr($inicio, 0, 10);
        }

        // Al editar se excluye la misma jornada de la validación.
        if ($jornada_excluir !== null) {
            $sql .= ' AND j.jornada_id <> :excluir';

            $params[':excluir'] = (int) $jornada_excluir;
        }

        $sql .= '
        ORDER BY j.jornada_inicio, j.jornada_id
        LIMIT 1';

        $stmt = $conectar->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Valida que el horario no se superponga con otra jornada vigente.
     */
    private function validar_disponibilidad(
        PDO $conectar,
        $empleado_id,
        $inicio,
        $fin,
        $jornada_excluir = null
    ) {
        $conflicto = $this->buscar_conflicto(
            $conectar,
            $empleado_id,
            $inicio,
            $fin,
            $jornada_excluir
        );

        if ($conflicto) {
            $fecha = (new DateTimeImmutable(
                $conflicto['fecha']
            ))->format('d/m/Y');

            throw new RuntimeException(
                'El horario se cruza con otra jornada registrada para el '
                . $fecha
                . '. Consulte el historial (registro #'
                . $conflicto['jornada_id']
                . ').'
            );
        }
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
            $this->bloquear_empleado($conectar, $empleado_id);
            $estado_borrador = $this->obtener_estado_id($conectar, 'BORRADOR');

            $anterior = null;
            if ($jornada_id !== null) {
                $anterior = $this->obtener_jornada_bloqueada($conectar, $jornada_id, $empleado_id);
                if (!$anterior || $anterior['je_codigo'] !== 'BORRADOR') {
                    throw new RuntimeException('La jornada no existe o ya no puede editarse.');
                }
            }
            $ubicaciones_permitidas = $this->listar_ubicaciones_jornada();

            if (
                !in_array($ubicacion, $ubicaciones_permitidas, true) &&
                (
                    !$anterior ||
                    $ubicacion !== $anterior['jornada_ubicacion']
                )
            ) {
                throw new InvalidArgumentException(
                    'Seleccione una ubicación válida.'
                );
            }
            $this->validar_disponibilidad($conectar, $empleado_id, $inicio, $fin, $jornada_id);

            $datos_nuevos = [
                'empleado_id' => (int) $empleado_id,
                'jornada_inicio' => $inicio,
                'jornada_fin' => $fin,
                'jornada_minutos_ordinarios' => (int) $minutos_ordinarios,
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
                $jornada_id = (int) $stmt->fetchColumn();

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
                $sql = 'UPDATE jornadas_trabajo
                        SET jornada_inicio = ?,
                            jornada_fin = ?,
                            jornada_minutos_ordinarios = ?,
                            jornada_ubicacion = ?,
                            jornada_actividad = ?,
                            jornada_observaciones = ?,
                            jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                            jornada_version = jornada_version + 1
                        WHERE jornada_id = ?
                          AND empleado_id = ?';

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
            return (int) $jornada_id;
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
    public function enviar_aprobacion_propia($jornada_id, $empleado_id, $user_id)
    {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();
            $this->bloquear_empleado($conectar, $empleado_id);
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

            $this->validar_disponibilidad(
                $conectar,
                $empleado_id,
                $anterior['jornada_inicio'],
                $anterior['jornada_fin'],
                $jornada_id
            );

            $estado_pendiente = $this->obtener_estado_id(
                $conectar,
                'PENDIENTE_APROBACION'
            );

            $sql = 'UPDATE jornadas_trabajo
                    SET jornada_estado_id = ?,
                        jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                        jornada_version = jornada_version + 1
                    WHERE jornada_id = ?
                      AND empleado_id = ?
                      AND jornada_estado_id = ?';

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
     * Cada envío es independiente y conserva su propia auditoría.
     */
    public function enviar_aprobacion_masiva(array $ids, $empleado_id, $user_id)
    {
        $resultado = ['enviados' => [], 'fallidos' => []];
        foreach ($ids as $id) {
            try {
                $this->enviar_aprobacion_propia($id, $empleado_id, $user_id);
                $resultado['enviados'][] = $id;
            } catch (PDOException $e) {
                error_log('Envío masivo de jornadas: ' . $e->getMessage());
                $resultado['fallidos'][] = [
                    'jornada_id' => $id,
                    'message' => 'No fue posible procesar la jornada.'
                ];
            } catch (RuntimeException $e) {
                $resultado['fallidos'][] = ['jornada_id' => $id, 'message' => $e->getMessage()];
            }
        }
        return $resultado;
    }

    /**
     * Anula únicamente borradores propios, conservando datos y auditoría.
     */
    public function anular_borrador_propio($jornada_id, $empleado_id, $user_id, $motivo)
    {
        $motivo = trim((string) $motivo);
        if ($motivo === '' || mb_strlen($motivo) > 2000) {
            throw new InvalidArgumentException('Indique el motivo de anulación (máximo 2000 caracteres).');
        }
        $conectar = parent::Conexion();
        try {
            $conectar->beginTransaction();
            $this->bloquear_empleado($conectar, $empleado_id);
            $anterior = $this->obtener_jornada_bloqueada($conectar, $jornada_id, $empleado_id);
            if (!$anterior || $anterior['je_codigo'] !== 'BORRADOR') {
                throw new RuntimeException('Solo puede anular sus jornadas en estado borrador.');
            }
            $estado = $this->obtener_estado_id($conectar, 'ANULADO');
            $stmt = $conectar->prepare(
                'UPDATE jornadas_trabajo SET jornada_estado_id = ?,
                    jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                    jornada_version = jornada_version + 1
                 WHERE jornada_id = ? AND empleado_id = ?'
            );
            $stmt->execute([$estado, $jornada_id, $empleado_id]);
            $this->registrar_auditoria(
                $conectar,
                $jornada_id,
                'ANULAR_BORRADOR',
                'BORRADOR',
                'ANULADO',
                $anterior,
                null,
                $motivo,
                $user_id
            );
            $conectar->commit();
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

        $where = ['j.empleado_id = :empleado_id'];
        $params = [':empleado_id' => (int) $empleado_id];

        if ($fecha_desde !== null) {
            $where[] = 'j.jornada_inicio::date >= :fecha_desde::date';
            $params[':fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta !== null) {
            $where[] = 'j.jornada_inicio::date <= :fecha_hasta::date';
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
                    anulacion.jaud_motivo AS anulacion_motivo,
                    anulacion.jaud_fecha AS anulacion_fecha,
                    e.je_codigo AS estado_codigo,
                    e.je_nombre AS estado_nombre
                FROM jornadas_trabajo j
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                LEFT JOIN LATERAL (
                    SELECT jaud_motivo, jaud_fecha FROM jornada_auditoria
                    WHERE jornada_id = j.jornada_id AND jaud_accion = 'ANULAR_BORRADOR'
                    ORDER BY jaud_fecha DESC, jaud_id DESC LIMIT 1
                ) anulacion ON true
                WHERE " . implode(' AND ', $where) . '
                ORDER BY j.jornada_inicio DESC, j.jornada_id DESC';

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
    public function obtener_mi_jornada($jornada_id, $empleado_id)
    {
        $conectar = parent::Conexion();

        $sql = 'SELECT
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
                LIMIT 1';

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
        $fecha_hasta = null,
        $empleado_id = null
    ) {
        $conectar = parent::Conexion();

        $where = [
            "e.je_codigo = 'PENDIENTE_APROBACION'",
            'EXISTS (
                SELECT 1
                FROM empleado_jefe ej
                WHERE ej.empleado_id = j.empleado_id
                  AND ej.jefe_id = :jefe_empleado_id
                  AND ej.ej_estado = 1
            )'
        ];
        $params = [':jefe_empleado_id' => (int) $jefe_empleado_id];

        if ($empleado_id !== null) {
            $where[] = 'j.empleado_id = :empleado_id';
            $params[':empleado_id'] = (int) $empleado_id;
        }

        if ($fecha_desde !== null) {
            $where[] = 'j.jornada_inicio::date >= :fecha_desde::date';
            $params[':fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta !== null) {
            $where[] = 'j.jornada_inicio::date <= :fecha_hasta::date';
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $sql = 'SELECT
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
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY j.jornada_fecha_actualizacion ASC, j.jornada_id ASC';

        $stmt = $conectar->prepare($sql);
        foreach ($params as $clave => $valor) {
            $stmt->bindValue(
                $clave,
                $valor,
                in_array($clave, [':jefe_empleado_id', ':empleado_id'], true)
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
    public function listar_subordinados_jefe($jefe_empleado_id)
    {
        $conectar = parent::Conexion();

        $sql = 'SELECT DISTINCT
                    e.id_empl AS empleado_id,
                    e.cedu_empl AS empleado_documento,
                    e.nomb_empl AS empleado_nombre
                FROM empleado_jefe ej
                INNER JOIN empleados e ON e.id_empl = ej.empleado_id
                WHERE ej.jefe_id = ?
                  AND ej.ej_estado = 1
                  AND e.esta_empl = 1
                ORDER BY e.nomb_empl, e.cedu_empl';

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jefe_empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna las ubicaciones habilitadas para el registro de jornadas.
     */
    public function listar_ubicaciones_jornada()
    {
        return [
            'Sede principal',
            'Obras varias',
            'Mario Huertas',
            'Isagen'
        ];
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
            'EXISTS (
                SELECT 1
                FROM empleado_jefe ej
                WHERE ej.empleado_id = j.empleado_id
                  AND ej.jefe_id = :jefe_empleado_id
                  AND ej.ej_estado = 1
            )'
        ];
        $params = [':jefe_empleado_id' => (int) $jefe_empleado_id];

        if ($fecha_desde !== null) {
            $where[] = 'j.jornada_inicio::date >= :fecha_desde::date';
            $params[':fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta !== null) {
            $where[] = 'j.jornada_inicio::date <= :fecha_hasta::date';
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $sql = 'SELECT
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
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY j.jornada_inicio DESC, j.jornada_id DESC';

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
     * Lista las jornadas de un subordinado específico dentro de un periodo.
     * Valida que el empleado continúe relacionado activamente con el jefe.
     */
    public function listar_jornadas_equipo_empleado(
        $jefe_empleado_id,
        $empleado_id,
        $fecha_desde = null,
        $fecha_hasta = null
    ) {
        $conectar = parent::Conexion();

        $where = [
            'j.empleado_id = :empleado_id',
            'EXISTS (
            SELECT 1
            FROM empleado_jefe ej
            WHERE ej.empleado_id = j.empleado_id
              AND ej.jefe_id = :jefe_empleado_id
              AND ej.ej_estado = 1
        )'
        ];

        $params = [
            ':jefe_empleado_id' => (int) $jefe_empleado_id,
            ':empleado_id' => (int) $empleado_id
        ];

        if ($fecha_desde !== null) {
            $where[] = 'j.jornada_inicio::date >= :fecha_desde::date';
            $params[':fecha_desde'] = $fecha_desde;
        }

        if ($fecha_hasta !== null) {
            $where[] = 'j.jornada_inicio::date <= :fecha_hasta::date';
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $sql = 'SELECT
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
            INNER JOIN empleados emp
                ON emp.id_empl = j.empleado_id
            INNER JOIN jornada_estados e
                ON e.je_id = j.jornada_estado_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY j.jornada_inicio ASC, j.jornada_id ASC';

        $stmt = $conectar->prepare($sql);

        foreach ($params as $clave => $valor) {
            $tipo = in_array(
                $clave,
                [':jefe_empleado_id', ':empleado_id'],
                true
            )
                ? PDO::PARAM_INT
                : PDO::PARAM_STR;

            $stmt->bindValue($clave, $valor, $tipo);
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

            $this->bloquear_empleado($conectar, $empleado_id);

            $sql = 'SELECT EXISTS (
                        SELECT 1
                        FROM empleado_jefe ej
                        INNER JOIN empleados e
                            ON e.id_empl = ej.empleado_id
                        WHERE ej.empleado_id = ?
                          AND ej.jefe_id = ?
                          AND ej.ej_estado = 1
                          AND e.esta_empl = 1
                    )';
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $jefe_empleado_id, PDO::PARAM_INT);
            $stmt->execute();

            if (!(bool) $stmt->fetchColumn()) {
                throw new RuntimeException(
                    'El empleado no está relacionado activamente con el jefe.'
                );
            }

            $this->validar_disponibilidad($conectar, $empleado_id, $inicio, $fin);

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
            $jornada_id = (int) $stmt->fetchColumn();

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
                'empleado_id' => (int) $empleado_id,
                'jornada_inicio' => $inicio,
                'jornada_fin' => $fin,
                'jornada_minutos_ordinarios' => (int) $minutos_ordinarios,
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
     * Guarda o actualiza múltiples jornadas del equipo como BORRADOR.
     * Las filas se procesan dentro de una única transacción.
     */
    public function guardar_borradores_equipo_masivo(
        $empleado_id,
        $jefe_empleado_id,
        $user_id,
        array $jornadas
    ) {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();

            // Serializa operaciones simultáneas sobre el mismo empleado.
            $this->bloquear_empleado(
                $conectar,
                $empleado_id
            );

            // Valida que el jefe todavía tenga autoridad sobre el empleado.
            $this->validar_subordinado_activo(
                $conectar,
                $empleado_id,
                $jefe_empleado_id
            );

            $estado_borrador = $this->obtener_estado_id(
                $conectar,
                'BORRADOR'
            );

            $resultado = [
                'creadas' => [],
                'actualizadas' => []
            ];

            foreach ($jornadas as $fila) {
                $jornada_id = $fila['jornada_id'];

                /*
                 * Si existe jornada_id debe corresponder a un BORRADOR
                 * del mismo empleado.
                 */
                if ($jornada_id !== null) {
                    $anterior = $this->obtener_jornada_equipo_borrador_bloqueada(
                        $conectar,
                        $jornada_id,
                        $empleado_id,
                        $jefe_empleado_id
                    );

                    if (!$anterior) {
                        throw new RuntimeException(
                            'Una de las jornadas ya no está disponible como borrador.'
                        );
                    }

                    // Valida que la modificación no genere cruces.
                    $this->validar_disponibilidad(
                        $conectar,
                        $empleado_id,
                        $fila['inicio'],
                        $fila['fin'],
                        $jornada_id
                    );

                    $sql = 'UPDATE jornadas_trabajo
                        SET jornada_inicio = ?,
                            jornada_fin = ?,
                            jornada_minutos_ordinarios = ?,
                            jornada_ubicacion = ?,
                            jornada_actividad = ?,
                            jornada_observaciones = ?,
                            jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                            jornada_version = jornada_version + 1
                        WHERE jornada_id = ?
                          AND empleado_id = ?
                          AND jornada_estado_id = ?';

                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $fila['inicio'], PDO::PARAM_STR);
                    $stmt->bindValue(2, $fila['fin'], PDO::PARAM_STR);
                    $stmt->bindValue(
                        3,
                        $fila['minutos_ordinarios'],
                        PDO::PARAM_INT
                    );
                    $stmt->bindValue(
                        4,
                        $fila['ubicacion'],
                        PDO::PARAM_STR
                    );
                    $stmt->bindValue(
                        5,
                        $fila['actividad'],
                        PDO::PARAM_STR
                    );
                    $stmt->bindValue(
                        6,
                        $fila['observaciones'],
                        PDO::PARAM_STR
                    );
                    $stmt->bindValue(
                        7,
                        $jornada_id,
                        PDO::PARAM_INT
                    );
                    $stmt->bindValue(
                        8,
                        $empleado_id,
                        PDO::PARAM_INT
                    );
                    $stmt->bindValue(
                        9,
                        $estado_borrador,
                        PDO::PARAM_INT
                    );
                    $stmt->execute();

                    if ($stmt->rowCount() !== 1) {
                        throw new RuntimeException(
                            'Una de las jornadas cambió antes de guardar el lote.'
                        );
                    }

                    $datos_nuevos = [
                        'empleado_id' => (int) $empleado_id,
                        'jornada_inicio' => $fila['inicio'],
                        'jornada_fin' => $fila['fin'],
                        'jornada_minutos_ordinarios' =>
                            (int) $fila['minutos_ordinarios'],
                        'jornada_ubicacion' => $fila['ubicacion'],
                        'jornada_actividad' => $fila['actividad'],
                        'jornada_observaciones' => $fila['observaciones']
                    ];

                    $this->registrar_auditoria(
                        $conectar,
                        $jornada_id,
                        'ACTUALIZAR_BORRADOR_JEFE',
                        'BORRADOR',
                        'BORRADOR',
                        $anterior,
                        $datos_nuevos,
                        'Actualización masiva desde Jornadas de mi Equipo',
                        $user_id
                    );

                    $resultado['actualizadas'][] = (int) $jornada_id;
                    continue;
                }

                // Nueva jornada: valida que no exista conflicto.
                $this->validar_disponibilidad(
                    $conectar,
                    $empleado_id,
                    $fila['inicio'],
                    $fila['fin']
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
                    VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'REGISTRO_JEFE',
                        ?,
                        ?
                    )
                    RETURNING jornada_id";

                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $fila['inicio'], PDO::PARAM_STR);
                $stmt->bindValue(3, $fila['fin'], PDO::PARAM_STR);
                $stmt->bindValue(
                    4,
                    $fila['minutos_ordinarios'],
                    PDO::PARAM_INT
                );
                $stmt->bindValue(
                    5,
                    $fila['ubicacion'],
                    PDO::PARAM_STR
                );
                $stmt->bindValue(
                    6,
                    $fila['actividad'],
                    PDO::PARAM_STR
                );
                $stmt->bindValue(
                    7,
                    $fila['observaciones'],
                    PDO::PARAM_STR
                );
                $stmt->bindValue(
                    8,
                    $estado_borrador,
                    PDO::PARAM_INT
                );
                $stmt->bindValue(
                    9,
                    $user_id,
                    PDO::PARAM_INT
                );
                $stmt->execute();

                $nuevo_id = (int) $stmt->fetchColumn();

                $datos_nuevos = [
                    'empleado_id' => (int) $empleado_id,
                    'jornada_inicio' => $fila['inicio'],
                    'jornada_fin' => $fila['fin'],
                    'jornada_minutos_ordinarios' =>
                        (int) $fila['minutos_ordinarios'],
                    'jornada_ubicacion' => $fila['ubicacion'],
                    'jornada_actividad' => $fila['actividad'],
                    'jornada_observaciones' => $fila['observaciones'],
                    'jornada_origen' => 'REGISTRO_JEFE'
                ];

                $this->registrar_auditoria(
                    $conectar,
                    $nuevo_id,
                    'CREAR_BORRADOR_JEFE',
                    null,
                    'BORRADOR',
                    null,
                    $datos_nuevos,
                    'Registro masivo desde Jornadas de mi Equipo',
                    $user_id
                );

                $resultado['creadas'][] = $nuevo_id;
            }

            $conectar->commit();

            return [
                'creadas' => count($resultado['creadas']),
                'actualizadas' => count($resultado['actualizadas']),
                'jornadas_creadas' => $resultado['creadas'],
                'jornadas_actualizadas' => $resultado['actualizadas']
            ];
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) {
                $conectar->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Registra o actualiza las jornadas del expediente y las aprueba
     * automáticamente por autoridad del jefe inmediato.
     */
    public function registrar_aprobar_equipo_masivo(
        $empleado_id,
        $jefe_empleado_id,
        $user_id,
        array $jornadas
    ) {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();

            // Evita operaciones concurrentes del mismo empleado.
            $this->bloquear_empleado(
                $conectar,
                $empleado_id
            );

            // El empleado debe seguir relacionado activamente con el jefe.
            $this->validar_subordinado_activo(
                $conectar,
                $empleado_id,
                $jefe_empleado_id
            );

            $estado_borrador = $this->obtener_estado_id(
                $conectar,
                'BORRADOR'
            );

            $estado_aprobado = $this->obtener_estado_id(
                $conectar,
                'APROBADO'
            );

            $resultado = [
                'creadas' => [],
                'aprobadas' => []
            ];

            foreach ($jornadas as $fila) {
                $jornada_id = $fila['jornada_id'];

                /*
                 * Jornada previamente guardada como borrador.
                 */
                if ($jornada_id !== null) {
                    $anterior = $this->obtener_jornada_equipo_borrador_bloqueada(
                        $conectar,
                        $jornada_id,
                        $empleado_id,
                        $jefe_empleado_id
                    );

                    if (!$anterior) {
                        throw new RuntimeException(
                            'Una de las jornadas ya no está disponible para aprobar.'
                        );
                    }

                    $this->validar_disponibilidad(
                        $conectar,
                        $empleado_id,
                        $fila['inicio'],
                        $fila['fin'],
                        $jornada_id
                    );

                    $sql = 'UPDATE jornadas_trabajo
                        SET jornada_inicio = ?,
                            jornada_fin = ?,
                            jornada_minutos_ordinarios = ?,
                            jornada_ubicacion = ?,
                            jornada_actividad = ?,
                            jornada_observaciones = ?,
                            jornada_estado_id = ?,
                            jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                            jornada_version = jornada_version + 1
                        WHERE jornada_id = ?
                          AND empleado_id = ?
                          AND jornada_estado_id = ?';

                    $stmt = $conectar->prepare($sql);
                    $stmt->bindValue(1, $fila['inicio'], PDO::PARAM_STR);
                    $stmt->bindValue(2, $fila['fin'], PDO::PARAM_STR);
                    $stmt->bindValue(
                        3,
                        $fila['minutos_ordinarios'],
                        PDO::PARAM_INT
                    );
                    $stmt->bindValue(
                        4,
                        $fila['ubicacion'],
                        PDO::PARAM_STR
                    );
                    $stmt->bindValue(
                        5,
                        $fila['actividad'],
                        PDO::PARAM_STR
                    );
                    $stmt->bindValue(
                        6,
                        $fila['observaciones'],
                        PDO::PARAM_STR
                    );
                    $stmt->bindValue(
                        7,
                        $estado_aprobado,
                        PDO::PARAM_INT
                    );
                    $stmt->bindValue(
                        8,
                        $jornada_id,
                        PDO::PARAM_INT
                    );
                    $stmt->bindValue(
                        9,
                        $empleado_id,
                        PDO::PARAM_INT
                    );
                    $stmt->bindValue(
                        10,
                        $estado_borrador,
                        PDO::PARAM_INT
                    );
                    $stmt->execute();

                    if ($stmt->rowCount() !== 1) {
                        throw new RuntimeException(
                            'Una de las jornadas cambió de estado antes de aprobar.'
                        );
                    }

                    $this->registrar_aprobacion_jefe_masiva(
                        $conectar,
                        $jornada_id,
                        $user_id,
                        $jefe_empleado_id
                    );

                    $datos_nuevos = [
                        'empleado_id' => (int) $empleado_id,
                        'jornada_inicio' => $fila['inicio'],
                        'jornada_fin' => $fila['fin'],
                        'jornada_minutos_ordinarios' =>
                            (int) $fila['minutos_ordinarios'],
                        'jornada_ubicacion' => $fila['ubicacion'],
                        'jornada_actividad' => $fila['actividad'],
                        'jornada_observaciones' => $fila['observaciones']
                    ];

                    $this->registrar_auditoria(
                        $conectar,
                        $jornada_id,
                        'APROBAR_BORRADOR_JEFE_MASIVO',
                        'BORRADOR',
                        'APROBADO',
                        $anterior,
                        $datos_nuevos,
                        'Aprobación masiva por registro del jefe',
                        $user_id
                    );

                    $resultado['aprobadas'][] = (int) $jornada_id;
                    continue;
                }

                /*
                 * Jornada completamente nueva.
                 */
                $this->validar_disponibilidad(
                    $conectar,
                    $empleado_id,
                    $fila['inicio'],
                    $fila['fin']
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
                    VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'REGISTRO_JEFE',
                        ?,
                        ?
                    )
                    RETURNING jornada_id";

                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $fila['inicio'], PDO::PARAM_STR);
                $stmt->bindValue(3, $fila['fin'], PDO::PARAM_STR);
                $stmt->bindValue(
                    4,
                    $fila['minutos_ordinarios'],
                    PDO::PARAM_INT
                );
                $stmt->bindValue(
                    5,
                    $fila['ubicacion'],
                    PDO::PARAM_STR
                );
                $stmt->bindValue(
                    6,
                    $fila['actividad'],
                    PDO::PARAM_STR
                );
                $stmt->bindValue(
                    7,
                    $fila['observaciones'],
                    PDO::PARAM_STR
                );
                $stmt->bindValue(
                    8,
                    $estado_aprobado,
                    PDO::PARAM_INT
                );
                $stmt->bindValue(
                    9,
                    $user_id,
                    PDO::PARAM_INT
                );
                $stmt->execute();

                $nuevo_id = (int) $stmt->fetchColumn();

                $this->registrar_aprobacion_jefe_masiva(
                    $conectar,
                    $nuevo_id,
                    $user_id,
                    $jefe_empleado_id
                );

                $datos_nuevos = [
                    'empleado_id' => (int) $empleado_id,
                    'jornada_inicio' => $fila['inicio'],
                    'jornada_fin' => $fila['fin'],
                    'jornada_minutos_ordinarios' =>
                        (int) $fila['minutos_ordinarios'],
                    'jornada_ubicacion' => $fila['ubicacion'],
                    'jornada_actividad' => $fila['actividad'],
                    'jornada_observaciones' => $fila['observaciones'],
                    'jornada_origen' => 'REGISTRO_JEFE'
                ];

                $this->registrar_auditoria(
                    $conectar,
                    $nuevo_id,
                    'REGISTRAR_APROBAR_JEFE_MASIVO',
                    null,
                    'APROBADO',
                    null,
                    $datos_nuevos,
                    'Aprobación masiva por registro del jefe',
                    $user_id
                );

                $resultado['creadas'][] = $nuevo_id;
            }

            $conectar->commit();

            return [
                'creadas_aprobadas' => count($resultado['creadas']),
                'borradores_aprobados' => count($resultado['aprobadas']),
                'total' =>
                    count($resultado['creadas'])
                    + count($resultado['aprobadas'])
            ];
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
        $decision = strtoupper(trim((string) $decision));
        if (!in_array($decision, ['APROBAR', 'RECHAZAR'], true)) {
            throw new InvalidArgumentException('La decisión indicada no es válida.');
        }

        $motivo = trim((string) $motivo);
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

            $sql = 'UPDATE jornadas_trabajo
                    SET jornada_estado_id = ?,
                        jornada_fecha_actualizacion = CURRENT_TIMESTAMP,
                        jornada_version = jornada_version + 1
                    WHERE jornada_id = ?
                      AND jornada_estado_id = ?';
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
    private function obtener_estado_id(PDO $conectar, $codigo)
    {
        $sql = 'SELECT je_id
                FROM jornada_estados
                WHERE je_codigo = ?
                  AND je_estado = 1
                LIMIT 1';
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $codigo, PDO::PARAM_STR);
        $stmt->execute();
        $estado_id = $stmt->fetchColumn();

        if ($estado_id === false) {
            throw new RuntimeException(
                'No se encuentra configurado el estado ' . $codigo . '.'
            );
        }

        return (int) $estado_id;
    }

    /**
     * Bloquea una jornada propia para garantizar una transición consistente.
     */
    private function obtener_jornada_bloqueada(
        PDO $conectar,
        $jornada_id,
        $empleado_id
    ) {
        $sql = 'SELECT
                    j.*,
                    e.je_codigo
                FROM jornadas_trabajo j
                INNER JOIN jornada_estados e
                    ON e.je_id = j.jornada_estado_id
                WHERE j.jornada_id = ?
                  AND j.empleado_id = ?
                FOR UPDATE OF j';

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
        $sql = 'SELECT
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
                FOR UPDATE OF j';

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $jefe_empleado_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Bloquea una jornada del empleado únicamente cuando continúa en BORRADOR
     * y el jefe conserva la relación activa con el subordinado.
     */
    private function obtener_jornada_equipo_borrador_bloqueada(
        PDO $conectar,
        $jornada_id,
        $empleado_id,
        $jefe_empleado_id
    ) {
        $sql = "SELECT
                j.*,
                e.je_codigo
            FROM jornadas_trabajo j
            INNER JOIN jornada_estados e
                ON e.je_id = j.jornada_estado_id
            WHERE j.jornada_id = ?
              AND j.empleado_id = ?
              AND e.je_codigo = 'BORRADOR'
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
        $stmt->bindValue(2, $empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(
            3,
            $jefe_empleado_id,
            PDO::PARAM_INT
        );
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registra la aprobación automática de una jornada creada o procesada
     * mediante el expediente masivo del jefe.
     */
    private function registrar_aprobacion_jefe_masiva(
        PDO $conectar,
        $jornada_id,
        $user_id,
        $jefe_empleado_id
    ) {
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
                'Aprobación masiva por registro del jefe'
            )";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $jornada_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(
            3,
            $jefe_empleado_id,
            PDO::PARAM_INT
        );
        $stmt->execute();
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
        $sql = 'INSERT INTO jornada_auditoria (
                    jornada_id,
                    jaud_accion,
                    jaud_estado_anterior,
                    jaud_estado_nuevo,
                    jaud_datos_anteriores,
                    jaud_datos_nuevos,
                    jaud_motivo,
                    jaud_usuario_id
                )
                VALUES (?, ?, ?, ?, ?::jsonb, ?::jsonb, ?, ?)';

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

    /**
     * Valida que el empleado esté activo y relacionado con el jefe autenticado.
     */
    private function validar_subordinado_activo(
        PDO $conectar,
        $empleado_id,
        $jefe_empleado_id
    ) {
        $sql = 'SELECT EXISTS (
                SELECT 1
                FROM empleado_jefe ej
                INNER JOIN empleados e
                    ON e.id_empl = ej.empleado_id
                WHERE ej.empleado_id = ?
                  AND ej.jefe_id = ?
                  AND ej.ej_estado = 1
                  AND e.esta_empl = 1
            )';

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $jefe_empleado_id, PDO::PARAM_INT);
        $stmt->execute();

        if (!(bool) $stmt->fetchColumn()) {
            throw new RuntimeException(
                'El empleado no está relacionado activamente con el jefe.'
            );
        }
    }
}
