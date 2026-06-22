<?php

class BioPro extends Conectar {

    public function obtenerEmpleadoActivoPorDocumento($cedula) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT id_empl, nomb_empl
            FROM empleados
            WHERE cedu_empl = ?
            AND esta_empl = 1
            LIMIT 1";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $cedula);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listarDocumentosActivos() {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT cedu_empl
            FROM empleados
            WHERE esta_empl = 1
              AND cedu_empl IS NOT NULL
              AND TRIM(cedu_empl) <> ''";

        $stmt = $conectar->prepare($sql);
        $stmt->execute();

        // IMPORTANTE: solo columna (array de strings)
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listarEmpleadosActivos() {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT cedu_empl, nomb_empl
            FROM empleados
            WHERE esta_empl = 1
              AND cedu_empl IS NOT NULL
              AND TRIM(cedu_empl) <> ''
            ORDER BY nomb_empl";

        $stmt = $conectar->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarEmpleadosBiotimePorJefe($user_id) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT documento, empleado_nombre, cargo, grupo
            FROM vw_empleados_jefe_biotime
            WHERE jefe_user_id = ?
              AND estado = 1
            ORDER BY empleado_nombre";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDocumentosBiotimePorJefe($user_id) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT documento
            FROM vw_empleados_jefe_biotime
            WHERE jefe_user_id = ?
              AND estado = 1";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function obtenerEmpleadoBiotimePorJefeDocumento($user_id, $cedula) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT empleado_id AS id_empl,
                   empleado_nombre AS nomb_empl,
                   documento,
                   cargo,
                   grupo
            FROM vw_empleados_jefe_biotime
            WHERE jefe_user_id = ?
              AND documento = ?
              AND estado = 1
            LIMIT 1";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $cedula);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
