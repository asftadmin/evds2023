<?php

class Permiso extends Conectar {


    public function insertar_permiso(
        $id_empleado,
        $fecha_permiso,
        $hora_salida,
        $hora_ingreso,
        $motivo,
        $detalle,
        $firma_base64
    ) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "INSERT INTO permisos_personal(
                empleado_id,
                permiso_fecha,
                permiso_hora_salida,
                permiso_hora_entrada,
                permiso_tipo,
                permiso_detalle,
                permiso_estado,
                permiso_creado,
                permiso_firma
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            RETURNING permiso_id";

        $stmt = $conectar->prepare($sql);

        $stmt->bindValue(1, $id_empleado, PDO::PARAM_INT);
        $stmt->bindValue(2, $fecha_permiso, PDO::PARAM_STR);
        $stmt->bindValue(3, $hora_salida, PDO::PARAM_STR);
        $stmt->bindValue(4, $hora_ingreso, PDO::PARAM_STR);
        $stmt->bindValue(5, $motivo, PDO::PARAM_STR);
        $stmt->bindValue(6, $detalle, PDO::PARAM_STR);
        $stmt->bindValue(7, '1', PDO::PARAM_STR);
        $stmt->bindValue(8, $firma_base64, PDO::PARAM_STR);

        $stmt->execute();

        // 🔥 AQUÍ ESTÁ LA CLAVE
        return $stmt->fetchColumn(); // devuelve el permiso_id real
    }

    public function get_solicitudes($codigo_empleado) {

        $conectar = parent::Conexion();
        $sql = "SELECT p.*, em.*, tp.*
                FROM permisos_personal p
                JOIN empleado_jefe ej ON ej.empleado_id = p.empleado_id
                INNER JOIN empleados em ON em.id_empl = p.empleado_id
                INNER JOIN tipo_permiso tp ON tp.tipo_id = p.permiso_tipo
                WHERE ej.jefe_id = ? AND ej.ej_estado = 1 AND permiso_estado = '1'";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $codigo_empleado, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_permiso($codigo_permiso) {
        $conectar = parent::Conexion();
        $sql = "SELECT permiso_id,
                    permiso_creado,
                    permiso_estado,
                    fecha_actu_permiso,
                    rechazo_permiso,
                    fecha_actu_rrhh
                FROM permisos_personal p WHERE p.permiso_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $codigo_permiso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_solicitudes_jefe($codigo_empleado) {

        $conectar = parent::Conexion();
        $sql = "SELECT p.*, em.*, tp.*,
                CASE 
                        WHEN p.permiso_estado = '1' THEN 'Pendiente Aprobacion'
                        WHEN p.permiso_estado = '2' THEN 'Aprobado Jefe'
                        WHEN p.permiso_estado = '3' THEN 'Vbo. Gestion Humana'
                        WHEN p.permiso_estado = '4' THEN 'Aprobado con pendientes'
                        WHEN p.permiso_estado = '5' THEN 'Aprobado con pendientes'
                        WHEN p.permiso_estado = '6' THEN 'Rechazado'
                        WHEN p.permiso_estado = '7' THEN 'Cancelado Operacion'
                        ELSE NULL
                    END AS estado_permiso
                FROM permisos_personal p
                LEFT JOIN empleados em ON p.aprobado_jefe_id = em.id_empl
                INNER JOIN tipo_permiso tp ON tp.tipo_id = p.permiso_tipo
                WHERE p.empleado_id = ? ORDER BY p.permiso_creado DESC";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $codigo_empleado, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_solicitudes_recursos($empleado_id = "", $fecha_desde = null, $fecha_hasta = null) {

        $conectar = parent::Conexion();

        $sql = "SELECT 
                p.*,
                em.nomb_empl AS empleado_nombre,
                jf.nomb_empl AS jefe_nombre,
                tp.*,
                CASE 
                    WHEN p.permiso_estado = '1' THEN 'Pendiente Aprobacion'
                    WHEN p.permiso_estado = '2' THEN 'Aprobado Jefe'
                    WHEN p.permiso_estado = '3' THEN 'Vbo. Gestion Humana'
                    WHEN p.permiso_estado = '4' THEN 'Aprobado con pendientes'
                    WHEN p.permiso_estado = '5' THEN 'Aprobado con pendientes'
                    WHEN p.permiso_estado = '6' THEN 'Rechazado'
                    WHEN p.permiso_estado = '7' THEN 'Cancelado Operacion'
                    ELSE NULL
                END AS estado_permiso
            FROM permisos_personal p
            INNER JOIN empleados em 
                ON em.id_empl = p.empleado_id
            LEFT JOIN empleados jf 
                ON jf.id_empl = p.aprobado_jefe_id
            INNER JOIN tipo_permiso tp 
                ON tp.tipo_id = p.permiso_tipo
            WHERE 1=1 ";

        // Filtro por empleado (si viene)
        if (!empty($empleado_id)) {
            $sql .= " AND p.empleado_id = :empleado_id ";
        }

        // Filtro por rango de fechas
        if (!empty($fecha_desde)) {
            $sql .= " AND p.permiso_fecha >= :fecha_desde::date";
        }

        if (!empty($fecha_hasta)) {
            $sql .= " AND p.permiso_fecha <= :fecha_hasta::date";
        }

        $stmt = $conectar->prepare($sql);

        if (!empty($empleado_id)) {
            $stmt->bindValue(":empleado_id", $empleado_id, PDO::PARAM_INT);
        }

        if (!empty($fecha_desde)) {
            $stmt->bindValue(':fecha_desde', $fecha_desde, PDO::PARAM_STR);
        }

        if (!empty($fecha_hasta)) {
            $stmt->bindValue(':fecha_hasta', $fecha_hasta, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function get_detalle_permiso($permiso_id) {
        $conectar = parent::Conexion();
        $sql = "SELECT 
                p.*,
                em.nomb_empl AS empleado_nombre,
                em.trabaja_sabado   AS trabaja_sabado,
                jf.nomb_empl AS jefe_nombre,
                tp.*
            FROM permisos_personal p
            INNER JOIN empleados em ON em.id_empl = p.empleado_id
            INNER JOIN empleado_jefe ej ON ej.empleado_id = p.empleado_id
            INNER JOIN empleados jf ON jf.id_empl = ej.jefe_id
            INNER JOIN tipo_permiso tp ON tp.tipo_id = p.permiso_tipo
            WHERE p.permiso_id = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update_aprobado($codigo_permiso, $codigo_empleado) {
        $conectar = parent::Conexion();
        $sql = "UPDATE permisos_personal SET permiso_estado = '2', fecha_actu_permiso = NOW(),  aprobado_jefe_id = ? WHERE permiso_id = ? ";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $codigo_empleado, PDO::PARAM_INT);
        $stmt->bindValue(2, $codigo_permiso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function update_rechazo($codigo_permiso, $codigo_empleado, $motivo) {
        $conectar = parent::Conexion();
        $sql = "UPDATE permisos_personal SET permiso_estado = '6', fecha_actu_permiso = NOW(),  aprobado_jefe_id = ?, rechazo_permiso = ? WHERE permiso_id = ? ";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $codigo_empleado, PDO::PARAM_INT);
        $stmt->bindValue(2, $motivo, PDO::PARAM_INT);
        $stmt->bindValue(3, $codigo_permiso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrar_soporte_permiso($permiso_id, $nombre_archivo, $ruta_remota) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "INSERT INTO permisos_soportes 
            (permiso_id, soporte_nombre, soporte_ruta, soporte_fecha)
            VALUES (?, ?, ?, NOW())";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_id);
        $stmt->bindValue(2, $nombre_archivo);
        $stmt->bindValue(3, $ruta_remota);

        return $stmt->execute();
    }

    public function get_soportes_permiso($permiso_id) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT * FROM permisos_soportes 
            WHERE permiso_id = ?
            ORDER BY soporte_fecha ASC";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_permiso_by_id($permiso_id) {
        $conectar = parent::Conexion();
        $sql = "SELECT p.*, e.nomb_empl
            FROM permisos_personal p
            INNER JOIN empleados e ON e.id_empl = p.empleado_id
            WHERE permiso_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizar_permiso_rrhh(
        $permiso_id,
        $permiso_fecha,
        $hora_salida,
        $hora_entrada,
        $motivo,
        $justificacion,
        $estado,
        $rrhh_id,
        $fecha_cierre,
        $total_horas,
        $incapacidad_id,
        $turno_nocturno = 0
    ) {
        $conectar = parent::Conexion();
        parent::set_names();

        // SOLUCIÓN: Solo incluir incapacidad_id si el motivo es 3
        if ($motivo == 3) { // Asumiendo que 3 es el código para incapacidades
            $sql = "UPDATE permisos_personal 
            SET 
                permiso_fecha        = ?,
                permiso_hora_salida  = ?,
                permiso_hora_entrada = ?,
                permiso_tipo         = ?,
                permiso_detalle      = ?,
                permiso_estado       = ?,
                aprobado_rrhh_id     = ?,
                fecha_actu_rrhh      = NOW(),
                perm_fecha_cierre    = ?,
                permiso_total_horas  = ?,
                perm_inca_id         = ?,
                permiso_turno_nocturno  = ?
            WHERE permiso_id = ?";

            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $permiso_fecha);
            $stmt->bindValue(2, $hora_salida);
            $stmt->bindValue(3, $hora_entrada);
            $stmt->bindValue(4, $motivo);
            $stmt->bindValue(5, $justificacion);
            $stmt->bindValue(6, $estado);
            $stmt->bindValue(7, $rrhh_id);
            $stmt->bindValue(8, $fecha_cierre);
            $stmt->bindValue(9, $total_horas);
            $stmt->bindValue(10, $incapacidad_id);
            $stmt->bindValue(11, $turno_nocturno);
            $stmt->bindValue(12, $permiso_id);
        } else {
            // Si no es motivo 3, no actualizamos el campo perm_inca_id
            $sql = "UPDATE permisos_personal 
            SET 
                permiso_fecha        = ?,
                permiso_hora_salida  = ?,
                permiso_hora_entrada = ?,
                permiso_tipo         = ?,
                permiso_detalle      = ?,
                permiso_estado       = ?,
                aprobado_rrhh_id     = ?,
                fecha_actu_rrhh      = NOW(),
                perm_fecha_cierre    = ?,
                permiso_total_horas  = ?,
                perm_inca_id         = NULL,  -- Establecer NULL explícitamente
                permiso_turno_nocturno  = ?
            WHERE permiso_id = ?";

            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $permiso_fecha);
            $stmt->bindValue(2, $hora_salida);
            $stmt->bindValue(3, $hora_entrada);
            $stmt->bindValue(4, $motivo);
            $stmt->bindValue(5, $justificacion);
            $stmt->bindValue(6, $estado);
            $stmt->bindValue(7, $rrhh_id);
            $stmt->bindValue(8, $fecha_cierre);
            $stmt->bindValue(9, $total_horas);
            $stmt->bindValue(10, $turno_nocturno);
            $stmt->bindValue(11, $permiso_id);
        }

        $ok = $stmt->execute();

        if (!$ok) {
            $err = $stmt->errorInfo();
            return [
                "success" => false,
                "sqlstate" => $err[0],
                "code" => $err[1],
                "error" => $err[2],
                "permiso_id" => $permiso_id
            ];
        }

        return [
            "success" => true,
            "rows" => $stmt->rowCount()
        ];
    }

    public function get_detalle_PDF($permiso_id) {
        $conectar = parent::Conexion();
        $sql = "SELECT 
                p.*,
                cg.nomb_carg        AS cargo,
                em.nomb_empl        AS empleado_nombre,
                p.permiso_firma     AS firma_empleado,
                jf.nomb_empl        AS jefe_nombre,
                fj.firma_base64     AS firma_jefe,
                gh.nomb_empl        AS recurso_humano,
                fr.firma_base64     AS firma_rrhh,
                tp.*
            FROM permisos_personal p
            INNER JOIN empleados    em  ON em.id_empl      = p.empleado_id
            INNER JOIN cargo        cg  ON cg.codi_carg    = em.carg_empl

            -- ── Jefe — quien realmente aprobó ────────────────────
            LEFT JOIN empleados     jf  ON jf.id_empl      = p.aprobado_jefe_id
            LEFT JOIN usuarios      uj  ON uj.user_id      = jf.user_empl
            LEFT JOIN firma_usuario fj  ON fj.user_id      = uj.user_id

            -- ── RRHH ──────────────────────────────────────────────
            LEFT JOIN empleados     gh  ON gh.id_empl      = p.aprobado_rrhh_id
            LEFT JOIN usuarios      ur  ON ur.user_id      = gh.user_empl
            LEFT JOIN firma_usuario fr  ON fr.user_id      = ur.user_id

            INNER JOIN tipo_permiso tp  ON tp.tipo_id      = p.permiso_tipo
            WHERE p.permiso_id = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get_ausentismo($fecha_ini = "", $fecha_fin = "", $empleado_id = "") {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT
                p.permiso_id,
                p.permiso_fecha,
                p.perm_fecha_cierre,
                p.permiso_total_horas,
                inc.inca_codigo,          
                inc.inca_nombre,  
                em.nomb_empl,
                em.cedu_empl, 
                tp.tipo_nombre
            FROM permisos_personal p
            INNER JOIN empleados em ON em.id_empl = p.empleado_id
            INNER JOIN tipo_permiso tp ON tp.tipo_id = p.permiso_tipo
            LEFT JOIN incapacidades inc ON inc.inca_id = p.perm_inca_id
            WHERE 1=1";


        if (!empty($empleado_id)) {
            $sql .= " AND p.empleado_id = :empleado_id ";
        }

        if (!empty($fecha_ini) && !empty($fecha_fin)) {
            $sql .= " AND p.permiso_fecha BETWEEN :fecha_ini AND :fecha_fin ";
        }

        $sql .= " ORDER BY p.permiso_fecha ASC, em.nomb_empl ASC ";

        $stmt = $conectar->prepare($sql);

        if (!empty($empleado_id)) {
            $stmt->bindValue(":empleado_id", $empleado_id, PDO::PARAM_INT);
        }

        if (!empty($fecha_ini) && !empty($fecha_fin)) {
            $stmt->bindValue(":fecha_ini", $fecha_ini);
            $stmt->bindValue(":fecha_fin", $fecha_fin);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrar_soporte_temp($token, $nombre, $ruta) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "INSERT INTO permisos_soportes_temp
            (permiso_token, soporte_nombre, soporte_ruta)
            VALUES (?, ?, ?)";

        $stmt = $conectar->prepare($sql);
        $stmt->execute([$token, $nombre, $ruta]);

        return true;
    }

    // OBTENER SOPORTES TEMPORALES POR TOKEN
    public function get_soportes_temp_por_token($token) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT soporte_nombre, soporte_ruta 
            FROM permisos_soportes_temp 
            WHERE permiso_token = :token";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(":token", $token, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ELIMINAR SOPORTES TEMPORALES POR TOKEN
    public function eliminar_soportes_temp_por_token($token) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "DELETE FROM permisos_soportes_temp WHERE permiso_token = :token";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(":token", $token, PDO::PARAM_STR);

        return $stmt->execute();
    }

    /*============================================

LISTAR SOLCITUDES JEFE INMEDIATO PARA APROBAR

===============================================*/

    // ── Listar con filtros ────────────────────────────────
    public function get_solicitudes_filtradas($jefe_id, $busqueda, $fecha_desde, $fecha_hasta, $estados) {

        $conectar = parent::Conexion();

        $where  = [];
        $params = [];

        $params[':jefe_id']  = $jefe_id;
        $params[':busqueda'] = '%' . $busqueda . '%';

        $where[] = "(em.cedu_empl ILIKE :busqueda OR em.nomb_empl ILIKE :busqueda)";

        if (!empty($fecha_desde)) {
            $where[]                = "p.permiso_fecha >= :fecha_desde::date";
            $params[':fecha_desde'] = $fecha_desde;
        }

        if (!empty($fecha_hasta)) {
            $where[]                = "p.permiso_fecha <= :fecha_hasta::date";
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        // ── Estados — construir IN con valores enteros controlados ──
        if (!empty($estados) && is_array($estados)) {
            // Forzar enteros para evitar inyección — son valores del sistema
            $in      = implode(',', array_map('intval', $estados));
            $where[] = "p.permiso_estado::integer IN ($in)";
        }

        $whereSQL = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT 
                p.permiso_id,
                p.permiso_estado,
                p.permiso_fecha,
                p.permiso_hora_salida,
                p.permiso_hora_entrada,
                p.permiso_detalle,
                p.permiso_creado,
                p.rechazo_permiso,
                em.nomb_empl     AS nombre_empleado,
                em.cedu_empl     AS cedula_empleado,
                tp.tipo_nombre   AS tipo_permiso,
                ej_apr.nomb_empl AS nombre_jefe,
                er.nomb_empl     AS nombre_rrhh,
                p.fecha_actu_permiso,
                p.fecha_actu_rrhh
            FROM permisos_personal p
            INNER JOIN empleado_jefe ej    ON ej.empleado_id  = p.empleado_id
                                          AND ej.jefe_id      = :jefe_id
                                          AND ej.ej_estado    = 1
            INNER JOIN empleados    em     ON em.id_empl       = p.empleado_id
            INNER JOIN tipo_permiso tp     ON tp.tipo_id       = p.permiso_tipo
            LEFT  JOIN empleados    ej_apr ON ej_apr.id_empl   = p.aprobado_jefe_id
            LEFT  JOIN empleados    er     ON er.id_empl       = p.aprobado_rrhh_id
            {$whereSQL}
            ORDER BY p.permiso_creado DESC";

        $stmt = $conectar->prepare($sql);

        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Conteo por estado ─────────────────────────────────
    public function get_conteo_por_estado($jefe_id) {

        $conectar = parent::Conexion();

        $sql = "SELECT 
                COUNT(*) FILTER (WHERE p.permiso_estado = '1') AS pendientes,
                COUNT(*) FILTER (WHERE p.permiso_estado IN ('2','3','4','5')) AS aprobados,
                COUNT(*) FILTER (WHERE p.permiso_estado = '6') AS rechazados,
                COUNT(*)                                        AS total
            FROM permisos_personal p
            INNER JOIN empleado_jefe ej ON ej.empleado_id = p.empleado_id
                                       AND ej.jefe_id     = :jefe_id
                                       AND ej.ej_estado   = 1";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(':jefe_id', $jefe_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ── Obtener permiso por ID ────────────────────────────
    public function detalle_permiso_id($permiso_id) {

        $conectar = parent::Conexion();

        $sql = "SELECT 
                p.permiso_id,
                p.permiso_estado,
                p.permiso_fecha,
                p.permiso_hora_salida,
                p.permiso_hora_entrada,
                p.permiso_detalle,
                p.permiso_creado,
                p.rechazo_permiso,
                p.fecha_actu_permiso,
                p.fecha_actu_rrhh,
                em.nomb_empl     AS nombre_empleado,
                em.cedu_empl     AS cedula_empleado,
                tp.tipo_nombre   AS tipo_permiso,
                ej_apr.nomb_empl AS nombre_jefe,
                er.nomb_empl     AS nombre_rrhh
            FROM permisos_personal p
            INNER JOIN empleados    em     ON em.id_empl      = p.empleado_id
            INNER JOIN tipo_permiso tp     ON tp.tipo_id      = p.permiso_tipo
            LEFT  JOIN empleados    ej_apr ON ej_apr.id_empl  = p.aprobado_jefe_id
            LEFT  JOIN empleados    er     ON er.id_empl      = p.aprobado_rrhh_id
            WHERE p.permiso_id = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**==================================================
     *  LISTAR BUZON EMPLEADOS 
     *====================================================*/

    public function get_mis_permisos($empleado_id, $fecha_desde, $fecha_hasta, $estados) {

        $conectar = parent::Conexion();

        $where  = ["p.empleado_id = :empleado_id"];
        $params = [':empleado_id' => $empleado_id];

        if (!empty($fecha_desde)) {
            $where[]                = "p.permiso_fecha >= :fecha_desde::date";
            $params[':fecha_desde'] = $fecha_desde;
        }

        if (!empty($fecha_hasta)) {
            $where[]                = "p.permiso_fecha <= :fecha_hasta::date";
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        if (!empty($estados) && is_array($estados)) {
            $in      = implode(',', array_map('intval', $estados));
            $where[] = "p.permiso_estado::integer IN ($in)";
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT
                p.permiso_id,
                p.permiso_estado,
                p.permiso_fecha,
                p.permiso_hora_salida,
                p.permiso_hora_entrada,
                p.permiso_detalle,
                p.permiso_creado,
                p.rechazo_permiso,
                p.aprobado_jefe_id,  -- ← agregar
                p.aprobado_rrhh_id,  -- ← agregar
                p.fecha_actu_permiso,
                p.fecha_actu_rrhh,
                tp.tipo_nombre AS tipo_permiso
            FROM permisos_personal p
            INNER JOIN tipo_permiso tp ON tp.tipo_id = p.permiso_tipo
            {$whereSQL}
            ORDER BY p.permiso_creado DESC";

        $stmt = $conectar->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_permiso_empleado($permiso_id) {
        $conectar = parent::Conexion();
        $sql = "SELECT 
                p.permiso_id,
                p.permiso_estado,
                p.permiso_fecha,
                p.permiso_hora_salida,
                p.permiso_hora_entrada,
                p.permiso_detalle,
                p.permiso_creado,
                p.rechazo_permiso,
                p.aprobado_jefe_id,
                p.aprobado_rrhh_id,
                tp.tipo_nombre AS tipo_permiso
            FROM permisos_personal p
            INNER JOIN tipo_permiso tp ON tp.tipo_id = p.permiso_tipo
            WHERE p.permiso_id = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function eliminar_permiso($permiso_id) {
        $conectar = parent::Conexion();

        try {
            $conectar->beginTransaction();

            // 1. Eliminar soportes del permiso
            $sql1 = "DELETE FROM permisos_soportes WHERE permiso_id = ?";
            $stmt1 = $conectar->prepare($sql1);
            $stmt1->bindValue(1, $permiso_id, PDO::PARAM_INT);
            $stmt1->execute();

            // 2. Eliminar el permiso
            $sql2 = "DELETE FROM permisos_personal WHERE permiso_id = ?";
            $stmt2 = $conectar->prepare($sql2);
            $stmt2->bindValue(1, $permiso_id, PDO::PARAM_INT);
            $stmt2->execute();

            $conectar->commit();
            return true;
        } catch (Exception $e) {
            $conectar->rollBack();
            return false;
        }
    }
}
