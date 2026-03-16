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

    public function get_genero()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM genero";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_estado_civil()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM estado_civil";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_grupo_sanguineo()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM grupo_sanguineo";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_estrato_socie()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM estrato";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_lugar_exp()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM lugar_expedicion";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_nivel_educativo()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM nivel_educativo";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_tipo_contrato()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM tipo_contrato";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_tipo_dependencia()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM dependencia";
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
        $sql = "SELECT  
                    e.*,
                    c.codi_carg                                 AS cargo_id,
                    c.nomb_carg                                 AS cargo_desc,
                    le.desc_lugar                               AS lugar_desc,
                    le.depto_lugar                              AS depto_lugar,
                    tc.id_contra                                AS tipo_contrato_id,
                    tc.desc_contra                              AS tipo_cont_desc,
                    td.codi_tpdc                                AS tpdc_id,
                    td.nomb_tpdc                                AS tpdc_nombre,
                    d.id_depen                                  AS dependencia_id,
                    gs.id_grup_sang                             AS grupo_sang_id,
                    gs.desc_grup_sang                           AS grupo_sang_desc,
                    d.desc_depen                                AS dependencia_descripcion,
                    ge.id_gene                                  AS genero_empleado,
                    ge.desc_gene                                AS genero_descrip,
                    ni.id_nivel                                 AS nivel_educativo,
                    ni.desc_nivel                               AS nivel_educ_descrip,
                    TO_CHAR(e.fecha_ingreso_empl, 'DD/MM/YYYY') AS fecha_ingreso_fmt,
                    TO_CHAR(e.fecha_naci_empl,      'DD/MM/YYYY') AS fecha_naci_fmt,
                    TO_CHAR(e.fech_exp_empl,      'DD/MM/YYYY') AS fecha_exp_fmt
                FROM empleados e
                LEFT JOIN cargo             c  ON c.codi_carg      = e.carg_empl
                LEFT JOIN lugar_expedicion  le ON le.id_lugar      = e.lugar_exp_empl
                LEFT JOIN tipo_contrato     tc ON tc.id_contra     = e.tipo_contrato_empl
                LEFT JOIN tipo_documento    td ON td.codi_tpdc     = e.tpdc_empl
                LEFT JOIN dependencia       d  ON d.id_depen       = e.depen_empl
                LEFT JOIN grupo_sanguineo   gs ON gs.id_grup_sang  = e.grup_sang_empl
                LEFT JOIN genero            ge ON ge.id_gene       = e.gene_empl
                LEFT JOIN nivel_educativo   ni ON ni.id_nivel      = e.nivel_educ_empl
                WHERE e.id_empl = ?";
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

    public function update_empleado(
        $id_empl,
        $tipo_docto,
        $doct_empl,
        $nomb_empl,
        $telf_empl,
        $dire_empl,
        $carg_empl,
        $ingr_empl,
        $esta_empl,
        $fecha_nac       = null,
        $genero          = null,
        $nivel_edu       = null,
        $profesion       = null,
        $rh              = null,
        // ── Campos nuevos ──
        $esta_civil      = null,
        $estrato         = null,
        $lugar_exp       = null,
        $email           = null,
        $fecha_exp       = null,
        $anio_grado      = null,
        $tipo_contrato   = null,
        $salario         = null,
        $dependencia     = null,
        $fecha_retiro    = null
    ) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "UPDATE empleados SET
                tpdc_empl           = ?,
                cedu_empl           = ?,
                nomb_empl           = ?,
                tele_empl           = ?,
                dire_empl           = ?,
                carg_empl           = ?,
                fecha_ingreso_empl  = ?,
                esta_empl           = ?,
                fecha_naci_empl     = ?,
                nivel_educ_empl     = ?,
                prof_empl           = ?,
                gene_empl           = ?,
                grup_sang_empl      = ?,
                esta_civil_empl     = ?,
                estra_empl          = ?,
                lugar_exp_empl      = ?,
                email_empl          = ?,
                fech_exp_empl       = ?,
                anio_grado_empl     = ?,
                tipo_contrato_empl  = ?,
                salario_empl        = ?,
                depen_empl          = ?,
                fecha_retiro_empl   = ?
            WHERE id_empl = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1,  $tipo_docto,                       PDO::PARAM_INT);
        $stmt->bindValue(2,  $doct_empl,                        PDO::PARAM_STR);
        $stmt->bindValue(3,  $nomb_empl,                        PDO::PARAM_STR);
        $stmt->bindValue(4,  $telf_empl,                        PDO::PARAM_STR);
        $stmt->bindValue(5,  $dire_empl,                        PDO::PARAM_STR);
        $stmt->bindValue(6,  $carg_empl,                        PDO::PARAM_INT);
        $stmt->bindValue(7,  $ingr_empl,                        PDO::PARAM_STR);
        $stmt->bindValue(8,  $esta_empl,                               PDO::PARAM_INT);
        $stmt->bindValue(9,  $fecha_nac,    $fecha_nac  ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(10, $nivel_edu,    $nivel_edu  ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(11, $profesion,    $profesion  ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(12, $genero,       $genero     ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(13, $rh,           $rh         ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(14, $esta_civil,   $esta_civil ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(15, $estrato,      $estrato    ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(16, $lugar_exp,    $lugar_exp  ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(17, $email,        $email      ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(18, $fecha_exp,    $fecha_exp  ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(19, $anio_grado,   $anio_grado ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(20, $tipo_contrato, $tipo_contrato ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(21, $salario,      $salario    ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(22, $dependencia,  $dependencia ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(23, $fecha_retiro,  $fecha_retiro  ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(24, $id_empl,                                  PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount() > 0;
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


    public function get_empleado_por_id($id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM empleados 
                INNER JOIN cargo ON cargo.codi_carg = empleados.carg_empl
                WHERE id_empl = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $id);
        $stmt->execute();
        return $resultado = $stmt->fetchAll();
    }
}
