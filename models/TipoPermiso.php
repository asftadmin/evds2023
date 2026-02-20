<?php

class TipoPermiso extends Conectar {


    public function listar_tipo_permiso() {

        $conectar = parent::Conexion();
        $sql = "SELECT * FROM tipo_permiso";
        $query = $conectar->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_tipo_permiso_x_id($motivo_id) {

        $conectar = parent::Conexion();
        $sql = "SELECT tipo_nombre FROM tipo_permiso WHERE tipo_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $motivo_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
