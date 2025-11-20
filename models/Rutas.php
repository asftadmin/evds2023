<?php

class Rutas extends Conectar
{
    public function insertar_rutas($empleado, $jefe_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        // Verificar si el array de empleados no está vacío
        if (empty($empleado)) {
            throw new Exception('El array de empleados está vacío.');
        }
        // Iterar sobre el array de empleados y ejecutar la consulta para cada uno
        foreach ($empleado as $empleado_id) {
            // Preparar la consulta dentro del bucle para cada empleado
            $sql = "INSERT INTO empleado_jefe (empleado_id, jefe_id) 
            VALUES (:empleado_id, :jefe_id)";
            $stmt = $conectar->prepare($sql);

            // Ejecutar la consulta con los valores actualess
            $stmt->execute([
                ':empleado_id' => $empleado_id,
                ':jefe_id' => $jefe_id,
            ]);
        }
        //return $resultado = $sql->fetchAll();
    }

    public function mostrar_rutas()
    {

        $conectar = parent::Conexion();
        $sql = "SELECT 
                    ej.ej_id as id_empl_jefe,
                    ej.empleado_id,
                    emp.nomb_empl AS empleado_nombre,
                    ej.jefe_id,
                    jef.nomb_empl AS jefe_nombre,
                    CASE 
                        WHEN ej.ej_estado = 1 THEN 'Activo'
                        WHEN ej.ej_estado = 2 THEN 'Inactivo'
                        ELSE NULL
                    END AS estado
                FROM empleado_jefe ej
                INNER JOIN empleados emp ON emp.id_empl = ej.empleado_id
                INNER JOIN empleados jef ON jef.id_empl = ej.jefe_id
                ORDER BY emp.nomb_empl ASC";

        $query = $conectar->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update_estado_empleado($empleado_jefe)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE empleado_jefe SET ej_estado = '2' WHERE ej_id = ? ";
        $query = $conectar->prepare($sql);
        $query->bindValue(1, $empleado_jefe);
        $query->execute();
        return $resultado = $query->fetchAll();
    }
}
