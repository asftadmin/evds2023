<?php

require_once dirname(__DIR__, 2) . '/config/conexion.php';

class EmpleadoApi extends Conectar
{
    public function buscarPorDocumento($documento)
    {
        $conexion = parent::Conexion();
        parent::set_names();

        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT
                    e.cedu_empl AS documento,
                    e.nomb_empl AS nombre,
                    e.email_empl AS correo,
                    c.nomb_carg AS cargo,
                    d.desc_depen AS area,
                    e.esta_empl AS estado
                FROM empleados e
                LEFT JOIN cargo c ON c.codi_carg = e.carg_empl
                LEFT JOIN dependencia d ON d.id_depen = e.depen_empl
                WHERE e.cedu_empl = :documento
                ORDER BY e.id_empl ASC
                LIMIT 1";

        $sentencia = $conexion->prepare($sql);
        $sentencia->bindValue(':documento', $documento, PDO::PARAM_STR);
        $sentencia->execute();

        $empleado = $sentencia->fetch(PDO::FETCH_ASSOC);

        return $empleado === false ? null : $empleado;
    }
}
