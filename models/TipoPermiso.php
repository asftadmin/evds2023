<?php 

class TipoPermiso extends Conectar {


    public function listar_tipo_permiso() {
        
        $conectar = parent::Conexion();
        $sql = "SELECT * FROM tipo_permiso";
        $query = $conectar->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);



    }

}

?>