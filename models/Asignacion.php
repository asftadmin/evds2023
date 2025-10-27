<?php

class Asignacion extends Conectar
{

    public function insertar_asignacion($nombre_evaluador, $nombre_empleado, $mes, $anio)
    {
        $conectar = parent::conexion();
        parent::set_names();
        // Verificar si el array de empleados no está vacío
        if (empty($nombre_empleado)) {
            throw new Exception('El array de empleados está vacío.');
        }
        // Iterar sobre el array de empleados y ejecutar la consulta para cada uno
        foreach ($nombre_empleado as $empleado_id) {
            // Preparar la consulta dentro del bucle para cada empleado
            $sql = "INSERT INTO asignaciones (codi_evaluador_asig, codi_evaluado_asig, mes_asig, año_asig) 
            VALUES (:nombre_evaluador, :empleado_id, :mes_asig, :ano_asig)";
            $stmt = $conectar->prepare($sql);

            // Ejecutar la consulta con los valores actuales
            $stmt->execute([
                ':nombre_evaluador' => $nombre_evaluador,
                ':empleado_id' => $empleado_id,
                ':mes_asig' => $mes,
                ':ano_asig' => $anio // Cambiado para evitar caracteres especiales
            ]);
        }
        //return $resultado = $sql->fetchAll();
    }



    public function editar_asig($codi_evaluador_asig, $codi_evaluado_asig, $mes_asig, $año_asig, $codi_asig)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE asignaciones SET codi_evaluador_asig = ?, codi_evaluado_asig = ?, mes_asig=?, año_asig=? WHERE codi_asig = ? ";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $codi_evaluador_asig);
        $sql->bindValue(2, $codi_evaluado_asig);
        $sql->bindValue(3, $mes_asig);
        $sql->bindValue(4, $año_asig);
        $sql->bindValue(5, $codi_asig);

        $sql->execute();
        return $resultado = $sql->fetchAll();
    }


    public function mostrar_asignacion()
    {

        $conectar = parent::Conexion();
        $sql = "SELECT
                    a.codi_asig, 
                    e1.nomb_empl AS nombre_evaluador,
                    e2.nomb_empl AS nombre_evaluado,
                    me.nomb_mes,
                    a.año_asig
                FROM 
                    asignaciones a
                JOIN 
                    empleados e1 ON a.codi_evaluador_asig = e1.id_empl
                JOIN 
                    empleados e2 ON a.codi_evaluado_asig = e2.id_empl
                JOIN 
                    meses me ON a.mes_asig = me.id_mes
                ORDER BY e1.nomb_empl";

        $query = $conectar->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete_asignacion($codig_asignacion)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "DELETE FROM asignaciones WHERE codi_asig=?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $codig_asignacion);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function listar_asignacion_x_id($codigo_asignacion)
    {

        $conectar = parent::Conexion();
        $sql = "SELECT 
                    a.codi_asig, 
                    e1.id_empl,        
                    e1.nomb_empl AS nombre_evaluador,
                    e2.id_empl as id_evaluado,
                    e2.nomb_empl AS nombre_evaluado,
                    me.id_mes,
                    me.nomb_mes,
                    a.año_asig
                FROM 
                    asignaciones a
                JOIN 
                    empleados e1 ON a.codi_evaluador_asig = e1.id_empl
                JOIN 
                    empleados e2 ON a.codi_evaluado_asig = e2.id_empl
                JOIN 
                    meses me ON a.mes_asig = me.id_mes
                WHERE 
                    a.codi_asig = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $codigo_asignacion);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
}
