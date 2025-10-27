<?php 

class TipoDocumento extends Conectar {


    public function listar_tipo_documento() {
        
        $conectar = parent::Conexion();
        $sql = "SELECT * FROM tipo_documento";
        $query = $conectar->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);



    }

}

?>