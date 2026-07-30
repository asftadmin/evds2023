<?php 

class Menu extends Conectar{

    /**
     * Lista los menús del rol principal y, cuando corresponda, añade los
     * menús asignados al perfil Jefe Inmediato sin reemplazar el rol base.
     */
    public function mostrar_menu_x_rol($rol_id, $es_jefe = false){

        $conectar=parent::Conexion();
        $sql="SELECT DISTINCT ON (m.menu_id)
                    p.perm_id,
                    p.perm_menu,
                    p.perm_rol,
                    p.perm_usua,
                    p.perm_esta,
                    m.menu_nomb,
                    m.menu_ruta,
                    m.menu_ident,
                    m.menu_icon
              FROM permisos p
              INNER JOIN menu m ON p.perm_menu = m.menu_id
              INNER JOIN rol r ON p.perm_rol = r.rol_id
              WHERE (
                    p.perm_rol = ?
                    OR (
                        ? = 1
                        AND r.rol_nomb = 'Jefe Inmediato'
                    )
              )
                AND p.perm_esta = 1
                AND m.menu_esta = 1
                AND r.rol_esta = 1
              ORDER BY
                    m.menu_id,
                    CASE WHEN p.perm_usua = 'Si' THEN 0 ELSE 1 END,
                    CASE WHEN p.perm_rol = ? THEN 0 ELSE 1 END,
                    p.perm_id";

        $query=$conectar->prepare($sql);
        $query->bindValue(1, $rol_id);
        $query->bindValue(2, $es_jefe ? 1 : 0, PDO::PARAM_INT);
        $query->bindValue(3, $rol_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);

    }

    /**
     * Verifica directamente en la base si el empleado autenticado tiene al
     * menos una relación activa como jefe. Sirve para sesiones iniciadas
     * antes de que se incorporara la marca es_jefe.
     */
    public function es_jefe_activo($empleado_id){

        $conectar=parent::Conexion();
        $sql="SELECT EXISTS (
                SELECT 1
                FROM empleado_jefe
                WHERE jefe_id = ?
                  AND ej_estado = 1
              )";
        $query=$conectar->prepare($sql);
        $query->bindValue(1, $empleado_id, PDO::PARAM_INT);
        $query->execute();
        return (bool)$query->fetchColumn();

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
