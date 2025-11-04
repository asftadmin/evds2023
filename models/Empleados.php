<?php

class Empleado extends Conectar
{

    public function get_empledo()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM empleados";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_empledo_activo()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM empleados WHERE esta_empl = 1 ORDER BY nomb_empl";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_empledo_grupo()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM empleados em INNER JOIN cargo cg ON cg.codi_carg = em.carg_empl 
            INNER JOIN grupoempleados gp ON gp.codi_grem = cg.grem_carg WHERE gp.codi_grem IN (2,4,6,8,9) ORDER BY em.id_empl";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_empledo_tipo_documento()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM empleados em INNER JOIN tipo_documento tp ON em.tpdc_empl = tp.codi_tpdc";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_empledo_x_id($codigo_empleado)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT *
                FROM empleados 
                WHERE id_empl = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $codigo_empleado);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function insertar_empleado($tipo_documento, $numero_documento, $nombre_empleado, $telefono_empleado, $direccion_empleado, $cargo_empleado, $fecha_ingreso)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO empleados (tpdc_empl, cedu_empl, nomb_empl, tele_empl, dire_empl, carg_empl, fecha_ingreso_empl, esta_empl) VALUES (?,?,?,?,?,?,?,'1')";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tipo_documento);
        $sql->bindValue(2, $numero_documento);
        $sql->bindValue(3, $nombre_empleado);
        $sql->bindValue(4, $telefono_empleado);
        $sql->bindValue(5, $direccion_empleado);
        $sql->bindValue(6, $cargo_empleado);
        $sql->bindValue(7, $fecha_ingreso);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function insertarEmplNuevo($data)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO empleados (cedu_empl, nomb_empl, fecha_ingreso_empl, fecha_naci_empl, dire_empl, tele_empl, esta_empl, tpdc_empl) VALUES (:cedu, :nomb, :fein, :fena, :dire, :celu, 1, 2)";
        $sql = $conectar->prepare($sql);
        $sql->bindParam(":cedu", $data['cedu_empl']);
        $sql->bindParam(":nomb", $data['nomb_empl']);
        $sql->bindParam(":fein", $data['fein_empl']);
        $sql->bindParam(":fena", $data['fena_empl']);
        $sql->bindParam(":dire", $data['dire_empl']);
        $sql->bindParam(":celu", $data['celu_empl']);

        return $sql->execute();
    }

    public function update_empleado($id_empl, $tipo_docto, $doct_empl, $nomb_empl, $telf_empl, $dire_empl, $carg_empl, $ingr_empl, $esta_empl, $fecha_nac = null, $genero = null, $nivel_edu = null, $profesion = null, $rh = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE empleados SET tpdc_empl = ?, cedu_empl = ?, nomb_empl = ?,
                 tele_empl = ?, dire_empl = ?, carg_empl = ?, fecha_ingreso_empl = ?,
                 esta_empl = ?, fecha_naci_empl = ?, nivel_educ_empl = ?, prof_empl = ?,
                 gene_empl = ?, grup_sang_empl = ?  WHERE id_empl = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tipo_docto);
        $sql->bindValue(2, $doct_empl);
        $sql->bindValue(3, $nomb_empl);
        $sql->bindValue(4, $telf_empl);
        $sql->bindValue(5, $dire_empl);
        $sql->bindValue(6, $carg_empl);
        $sql->bindValue(7, $ingr_empl);
        $sql->bindValue(8, $esta_empl);
        $sql->bindValue(9, $fecha_nac);
        $sql->bindValue(10, $genero);
        $sql->bindValue(11, $nivel_edu);
        $sql->bindValue(12, $profesion);
        $sql->bindValue(13, $rh);
        $sql->bindValue(14, $id_empl);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function inactivar_empleados($ids)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $id_list = implode(",", array_map('intval', $ids));
        $sql = "UPDATE empleados SET esta_empl = 0 WHERE id_empl IN ($id_list)";
        $stmt = $conectar->prepare($sql);
        return $stmt->execute();
    }
}
