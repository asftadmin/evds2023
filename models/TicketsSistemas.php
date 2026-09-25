<?php

class TicketsSistemas extends Conectar
{
    /*
     * =====================================================
     * OBTENER EMPLEADO DEL USUARIO AUTENTICADO
     * =====================================================
     */
    public function obtenerEmpleadoSesion($usuarioId)
    {
        $conexion = parent::Conexion();

        $sql = '
            SELECT
                e.id_empl,
                e.cedu_empl,
                e.nomb_empl,
                e.email_empl,
                c.nomb_carg
            FROM empleados e
            LEFT JOIN cargo c
                ON c.codi_carg = e.carg_empl
            WHERE e.user_empl = :usuario
              AND e.esta_empl = 1
            LIMIT 1
        ';

        $sentencia = $conexion->prepare($sql);

        $sentencia->execute(array(
            ':usuario' => $usuarioId
        ));

        $empleado = $sentencia->fetch(PDO::FETCH_ASSOC);

        return $empleado === false
            ? null
            : $empleado;
    }
}
