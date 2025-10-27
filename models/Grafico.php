<?php

class Grafico extends Conectar

{
    
    public function get_grafico($empleado_id){
        $conectar=parent::Conexion();
        parent::set_names();
        $sql = "SELECT * FROM obtener_promedios_empleado(?) ";
        
        $query = $conectar->prepare($sql);
        $query-> bindValue(1, $empleado_id);

        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


}



?>