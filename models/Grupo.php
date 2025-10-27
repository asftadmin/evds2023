<?php 

class Grupo extends Conectar {


    public function listar_grupo() {
        
        $conectar = parent::Conexion();
        $sql = "SELECT * FROM grupoempleados";
        $query = $conectar->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);



    }

    public function listar_grupo_x_id($codigo_grupo) {
        
        $conectar = parent::Conexion();
        $sql = "SELECT * FROM grupoempleados WHERE codi_grem = ?";
        $sql = $conectar->prepare($sql);
        $sql -> bindValue(1, $codigo_grupo);
        $sql->execute();
        return $resultado=$sql->fetchAll();

    } 

    public function insertar_grupo($nomb_grupo){
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO grupoempleados (nomb_grem, esta_grem) VALUES (?,'1')";
        $sql = $conectar->prepare($sql);
        $sql -> bindValue(1, $nomb_grupo);
        $sql->execute();
        return $resultado=$sql->fetchAll();
    }

    public function update_grupo($id_grupo, $nomb_grupo, $estado_grupo){
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE grupoempleados SET nomb_grem = ?, esta_grem = ? WHERE codi_grem = ?";
        $sql = $conectar->prepare($sql);
        $sql -> bindValue(1, $nomb_grupo);
        $sql -> bindValue(2, $estado_grupo);
        $sql -> bindValue(3, $id_grupo);
        $sql->execute();
        return $resultado=$sql->fetchAll();
    }

    public function delete_grupo($codigo_grupo){
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "DELETE FROM grupoempleados WHERE codi_grem=?";
        $sql = $conectar->prepare($sql);
        $sql -> bindValue(1, $codigo_grupo);
        $sql->execute();
        return $resultado=$sql->fetchAll();
    }

    public function get_grupo(){
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM grupoempleados";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado=$sql->fetchAll();
    }
}

?> 