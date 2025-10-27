<?php 

    class Rol extends Conectar{


        public function listar_rol(){

            $conectar=parent::Conexion();
            $sql = "SELECT * FROM rol";
            $query = $conectar->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);


        }

        public function mostar_rol_x_id($rol_id){

            $conectar=parent::Conexion();
            $sql = "SELECT * FROM rol WHERE rol_id=?";
            $query = $conectar->prepare($sql);
            $query-> bindValue(1, $rol_id);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);


        }


        public function insertar_rol($nomb_rol){
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "INSERT INTO rol (rol_nomb, rol_esta) VALUES (?,'1')";
            $sql = $conectar->prepare($sql);
            $sql -> bindValue(1, $nomb_rol);
            $sql->execute();

        }

        public function editar_rol($rol_id,$nomb_rol){
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "UPDATE rol SET rol_nomb = ?, rol_esta = ? WHERE rol_id = ? " ;
            $sql = $conectar->prepare($sql);
            $sql -> bindValue(1, $nomb_rol);
            $sql -> bindValue(2, $nomb_rol);
            $sql -> bindValue(3, $nomb_rol);
            $sql->execute();

        }


        public function delete_rol($rol_id){
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "DELETE FROM rol WHERE rol_id=?";
            $sql = $conectar->prepare($sql);
            $sql -> bindValue(1, $rol_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }



    }


?>