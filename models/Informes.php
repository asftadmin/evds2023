<?php

class Informes extends Conectar {

    public function getInfomeGeneral(){

        $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT
            e.id_empl,
            e.nomb_empl AS nombre_empleado,
            ROUND(AVG(p1_eval),2) AS productividad_pregunta_1,
            ROUND(AVG(p2_eval),2) AS productividad_pregunta_2,
            ROUND(AVG(p3_eval),2) AS productividad_pregunta_3,
            ROUND(AVG(p4_eval),2) AS productividad_pregunta_4,
            ROUND(AVG(p5_eval),2) AS productividad_pregunta_5,
            ROUND(AVG(p6_eval),2) AS productividad_pregunta_6,
            (SELECT ROUND(AVG(p1_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) +
            (SELECT ROUND(AVG(p2_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(p3_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(p4_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(p5_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(p6_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) AS PRODUCTIVIDAD,
            ROUND(AVG(l1_eval),2) AS liderazgo_pregunta_1,
            ROUND(AVG(l2_eval),2) AS liderazgo_pregunta_2,
            ROUND(AVG(l3_eval),2) AS liderazgo_pregunta_3,
            ROUND(AVG(l4_eval),2) AS liderazgo_pregunta_4,
            ROUND(AVG(l5_eval),2) AS liderazgo_pregunta_5,
            ROUND(AVG(l6_eval),2) AS liderazgo_pregunta_6,
            (SELECT ROUND(AVG(l1_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) +
            (SELECT ROUND(AVG(l2_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(l3_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(l4_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(l5_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(l6_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) AS LABORAL,
            ROUND(AVG(a1_eval),2) AS actitud_pregunta_1,
            ROUND(AVG(a2_eval),2) AS actitud_pregunta_2,
            ROUND(AVG(a3_eval),2) AS actitud_pregunta_3,
            ROUND(AVG(a4_eval),2) AS actitud_pregunta_4,
            (SELECT ROUND(AVG(a1_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) +
            (SELECT ROUND(AVG(a2_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(a3_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(a4_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) AS ACTITUD,
            ROUND(AVG(d1_eval),2) AS liderazgo_pregunta_1,
            ROUND(AVG(d2_eval),2) AS liderazgo_pregunta_2,
            ROUND(AVG(d3_eval),2) AS liderazgo_pregunta_3,
            ROUND(AVG(d4_eval),2) AS liderazgo_pregunta_4,
            ROUND(AVG(d5_eval),2) AS liderazgo_pregunta_5,
            (SELECT ROUND(AVG(d1_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) +
            (SELECT ROUND(AVG(d2_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(d3_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(d4_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)+
            (SELECT ROUND(AVG(d5_eval), 2) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) AS LIDERAZGO,
            ROUND(
            ((SELECT ROUND(AVG(p1_eval), 2) + ROUND(AVG(p2_eval), 2) + ROUND(AVG(p3_eval), 2) + ROUND(AVG(p4_eval), 2) + ROUND(AVG(p5_eval), 2) + AVG(p6_eval) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) +
             (SELECT ROUND(AVG(l1_eval), 2) + ROUND(AVG(l2_eval), 2) + ROUND(AVG(l3_eval), 2) + ROUND(AVG(l4_eval), 2) + ROUND(AVG(l5_eval), 2) + AVG(l6_eval) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) +
             (SELECT ROUND(AVG(a1_eval), 2) + ROUND(AVG(a2_eval), 2) + ROUND(AVG(a3_eval), 2) + AVG(a4_eval) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval) +
             (SELECT ROUND(AVG(d1_eval), 2) + ROUND(AVG(d2_eval), 2) + ROUND(AVG(d3_eval), 2) + ROUND(AVG(d4_eval), 2) + AVG(d5_eval) FROM evaluacion ev WHERE e.id_empl = ev.empl_eval)))AS TOTAL,
            STRING_AGG(obse_eval, ', ') AS observaciones
        FROM
            empleados e
        JOIN
            usuarios u ON e.user_empl = u.user_id
        JOIN
            evaluacion ev ON e.id_empl = ev.empl_eval
        GROUP BY
            e.id_empl, e.nomb_empl
        ORDER BY
            e.id_empl;";
            $sql = $conectar->prepare($sql);
            $sql->execute();
            return $resultado=$sql->fetchAll();

    }

    public function listar_colaboradores_permisos_mes() {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT
                    id_empl,
                    cedu_empl,
                    nomb_empl
                FROM empleados
                WHERE esta_empl = 1
                  AND cedu_empl IS NOT NULL
                  AND TRIM(cedu_empl) <> ''
                ORDER BY nomb_empl";

        $stmt = $conectar->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_colaborador_permisos_mes($empleado_id) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT
                    id_empl,
                    cedu_empl,
                    nomb_empl
                FROM empleados
                WHERE id_empl = ?
                LIMIT 1";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empleado_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function get_permisos_colaborador_mes($empleado_id, $fecha_inicio, $fecha_fin) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT
                    p.permiso_id,
                    p.empleado_id,
                    p.permiso_fecha,
                    p.permiso_hora_salida,
                    p.permiso_hora_entrada,
                    p.permiso_total_horas,
                    p.permiso_turno_nocturno,
                    p.permiso_estado,
                    em.nomb_empl AS colaborador,
                    em.cedu_empl,
                    tp.tipo_nombre AS motivo,
                    CASE
                        WHEN p.permiso_estado = '1' THEN 'Pendiente Aprobacion'
                        WHEN p.permiso_estado = '2' THEN 'Aprobado Jefe'
                        WHEN p.permiso_estado = '3' THEN 'Vbo. Gestion Humana'
                        WHEN p.permiso_estado = '4' THEN 'Aprobado con pendientes'
                        WHEN p.permiso_estado = '5' THEN 'Aprobado con pendientes'
                        WHEN p.permiso_estado = '6' THEN 'Rechazado'
                        WHEN p.permiso_estado = '7' THEN 'Cancelado Operacion'
                        ELSE 'Sin estado'
                    END AS estado_permiso
                FROM permisos_personal p
                INNER JOIN empleados em ON em.id_empl = p.empleado_id
                INNER JOIN tipo_permiso tp ON tp.tipo_id = p.permiso_tipo
                WHERE p.empleado_id = :empleado_id
                  AND p.permiso_fecha BETWEEN :fecha_inicio::date AND :fecha_fin::date
                ORDER BY p.permiso_fecha DESC, p.permiso_id DESC";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(":empleado_id", $empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(":fecha_inicio", $fecha_inicio, PDO::PARAM_STR);
        $stmt->bindValue(":fecha_fin", $fecha_fin, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



}

?>
