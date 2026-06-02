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
}