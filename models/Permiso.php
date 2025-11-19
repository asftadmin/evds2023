<?php

class Permiso extends Conectar
{


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

    public function get_solicitudes(){

        $conectar = parent::Conexion();
        $sql = 'SELECT * FROM permisos_personal
                INNER JOIN empleados ON empleados.id_empl = permisos_personal.empleado_id
                INNER JOIN tipo_permiso ON tipo_permiso.tipo_id = permisos_personal.permiso_tipo
                WHERE permiso_estado = 1';
        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
