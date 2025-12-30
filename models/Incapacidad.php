<?php

class Incapacidad extends Conectar {

    public function get_incapacidades() {
        $conectar = parent::Conexion();
        parent::set_names();

        $sql = "SELECT inca_id, inca_codigo, inca_nombre
            FROM incapacidades
            ORDER BY inca_codigo ASC";

        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
