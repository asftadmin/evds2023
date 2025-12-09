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
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $stmt = $conectar->prepare($sql);

        // Enlazamos los parámetros en el orden correcto
        $stmt->bindValue(1, $id_empleado, PDO::PARAM_INT);
        $stmt->bindValue(2, $fecha_permiso, PDO::PARAM_STR);
        $stmt->bindValue(3, $hora_salida, PDO::PARAM_STR);
        $stmt->bindValue(4, $hora_ingreso, PDO::PARAM_STR);
        $stmt->bindValue(5, $motivo, PDO::PARAM_STR);
        $stmt->bindValue(6, $detalle, PDO::PARAM_STR);
        $stmt->bindValue(7, '1', PDO::PARAM_STR); // estado inicial
        $stmt->bindValue(8, $firma_base64, PDO::PARAM_STR);

        // Ejecutamos la consulta y devolvemos el resultado
        return $stmt->execute();
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
                INNER JOIN empleados em ON p.aprobado_jefe_id = em.id_empl
                INNER JOIN tipo_permiso tp ON tp.tipo_id = p.permiso_tipo
                WHERE p.empleado_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $codigo_empleado, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_solicitudes_recursos() {

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
                ON tp.tipo_id = p.permiso_tipo;
            ";
        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_detalle_permiso($permiso_id) {
        $conectar = parent::Conexion();
        $sql = "SELECT 
                p.*,
                em.nomb_empl AS empleado_nombre,
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $fecha_actu_rrhh
    ) {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "UPDATE permisos_personal 
            SET 
                permiso_fecha        = ?,
                permiso_hora_salida  = ?,
                permiso_hora_entrada = ?,
                permiso_tipo         = ?,
                permiso_detalle      = ?,
                permiso_estado       = ?,
                aprobado_rrhh_id     = ?,
                fecha_actu_rrhh      = NOW()
            WHERE permiso_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_fecha);
        $stmt->bindValue(2, $hora_salida);
        $stmt->bindValue(3, $hora_entrada);
        $stmt->bindValue(4, $motivo);
        $stmt->bindValue(5, $justificacion);
        $stmt->bindValue(6, $estado);
        $stmt->bindValue(7, $rrhh_id);
        $stmt->bindValue(8, $permiso_id);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_detalle_PDF($permiso_id) {
        $conectar = parent::Conexion();
        $sql = "SELECT 
                    p.*,
                    cg.nomb_carg AS cargo,
                    em.nomb_empl AS empleado_nombre,
                    p.permiso_firma AS firma_empleado,
                    jf.nomb_empl AS jefe_nombre,
                    fj.firma_base64 AS firma_jefe,
                    gh.nomb_empl AS recurso_humano,
                    fr.firma_base64 AS firma_rrhh,
                    tp.*
                FROM permisos_personal p
                INNER JOIN empleados em 
                    ON em.id_empl = p.empleado_id
                INNER JOIN cargo cg 
                    ON cg.codi_carg = em.carg_empl
                LEFT JOIN empleado_jefe ej 
                    ON ej.empleado_id = p.empleado_id
                LEFT JOIN empleados jf 
                    ON jf.id_empl = ej.jefe_id
                LEFT JOIN firma_usuario fj 
                    ON fj.user_id = ej.jefe_id
                LEFT JOIN empleados gh 
                    ON gh.id_empl = p.aprobado_rrhh_id
                LEFT JOIN firma_usuario fr 
                    ON fr.user_id = p.aprobado_rrhh_id
                INNER JOIN tipo_permiso tp 
                    ON tp.tipo_id = p.permiso_tipo
                WHERE p.permiso_id = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $permiso_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
