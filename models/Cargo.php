<?php 

class Cargo extends Conectar {


    public function listar_cargo() {
        
        $conectar = parent::Conexion();
        $sql = "SELECT * FROM cargo cg INNER JOIN grupoempleados ge ON cg.grem_carg = ge.codi_grem WHERE cg.esta_carg = 1";
        $query = $conectar->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);

    }

    public function insertar_cargo($nomb_grupo, $grupo_empleado){
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO cargo (nomb_carg, esta_carg, grem_carg) VALUES (?,'1',?)";
        $sql = $conectar->prepare($sql);
        $sql -> bindValue(1, $nomb_grupo);
        $sql -> bindValue(2, $grupo_empleado);
        $sql->execute();
        return $resultado=$sql->fetchAll();
    }

    public function update_cargo($id_cargo, $nomb_cargo, $estado_cargo){
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE cargo SET nomb_carg = ?, esta_carg = ? WHERE codi_carg = ?";
        $sql = $conectar->prepare($sql);
        $sql -> bindValue(1, $nomb_cargo);
        $sql -> bindValue(2, $estado_cargo);
        $sql -> bindValue(3, $id_cargo);
        $sql->execute();
        return $resultado=$sql->fetchAll();
    }

    public function delete_cargo($id_cargo){
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "DELETE FROM cargo WHERE codi_carg=?";
        $sql = $conectar->prepare($sql);
        $sql -> bindValue(1, $id_cargo);
        $sql->execute();
        return $resultado=$sql->fetchAll();
    }


    public function listar_cargo_x_id($codigo_cargo) {
        
        $conectar = parent::Conexion();
        $sql = "SELECT * FROM cargo WHERE codi_carg = ?";
        $sql = $conectar->prepare($sql);
        $sql -> bindValue(1, $codigo_cargo);
        $sql->execute();
        return $resultado=$sql->fetchAll();

    } 

}

?>