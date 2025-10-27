<?php 

class Menu extends Conectar{

    public function mostrar_menu_x_rol($rol_id){

        $conectar=parent::Conexion();
        $sql="SELECT perm_id, perm_menu, perm_rol, perm_usua, perm_esta, menu_nomb, menu_ruta, menu_ident, menu_icon FROM permisos 
              INNER JOIN menu  ON perm_menu = menu_id WHERE perm_rol = ? ORDER BY perm_id ASC";

        $query=$conectar->prepare($sql);
        $query->bindValue(1, $rol_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);

    }


    public function activar_menu($perm_id){

        $conectar=parent::Conexion();
        $sql="UPDATE permisos SET perm_usua = 'Si' WHERE perm_id = ?";
        $query=$conectar->prepare($sql);
        $query->bindValue(1, $perm_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);

    }


    public function desactivar_menu($perm_id){

        $conectar=parent::Conexion();
        $sql="UPDATE permisos SET perm_usua = 'No' WHERE perm_id = ?";
        $query=$conectar->prepare($sql);
        $query->bindValue(1, $perm_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);

    }



}






?>