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



}

?>