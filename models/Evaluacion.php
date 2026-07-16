<?php

class Evaluacion extends Conectar {

    /* SELECT DE OPERACIONES */
    public function get_mes_combo() {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT *,
            CASE 
                WHEN esta_mes = 1 THEN 'ACTIVO'
                WHEN esta_mes = 0 THEN 'INACTIVO'
                END AS MES_ESTADO
            FROM meses WHERE esta_mes = 1 ";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_mes_combo_total() {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT *,
            CASE 
                WHEN esta_mes = 1 THEN 'ACTIVO'
                WHEN esta_mes = 0 THEN 'INACTIVO'
                END AS MES_ESTADO
            FROM meses ORDER BY id_mes ASC";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function insert_evaluacion(
        $formulario,
        $fecha,
        $usuario_id,
        $anio,
        $p1,
        $p2,
        $p3,
        $p4,
        $p5,
        $p6,
        $l1,
        $l2,
        $l3,
        $l4,
        $l5,
        $l6,
        $a1,
        $a2,
        $a3,
        $a4,
        $d1,
        $d2,
        $d3,
        $d4,
        $observacion,
        $emple_id,
        $tipo_id,
        $d5
    ) {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO public.evaluacion(
                form_eval, fech_eval, usua_eval, anio_eval, p1_eval, p2_eval, p3_eval, p4_eval, p5_eval, p6_eval, l1_eval, l2_eval, l3_eval, l4_eval, l5_eval, l6_eval, a1_eval, a2_eval, a3_eval, a4_eval, d1_eval, d2_eval, d3_eval, d4_eval, obse_eval, empl_eval, tipo_eval, d5_eval)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $formulario);
        $sql->bindValue(2, $fecha);
        $sql->bindValue(3, $usuario_id);
        $sql->bindValue(4, $anio);
        $sql->bindValue(5, $p1);
        $sql->bindValue(6, $p2);
        $sql->bindValue(7, $p3);
        $sql->bindValue(8, $p4);
        $sql->bindValue(9, $p5);
        $sql->bindValue(10, $p6);
        $sql->bindValue(11, $l1);
        $sql->bindValue(12, $l2);
        $sql->bindValue(13, $l3);
        $sql->bindValue(14, $l4);
        $sql->bindValue(15, $l5);
        $sql->bindValue(16, $l6);
        $sql->bindValue(17, $a1);
        $sql->bindValue(18, $a2);
        $sql->bindValue(19, $a3);
        $sql->bindValue(20, $a4);
        $sql->bindValue(21, $d1);
        $sql->bindValue(22, $d2);
        $sql->bindValue(23, $d3);
        $sql->bindValue(24, $d4);
        $sql->bindValue(25, $observacion);
        $sql->bindValue(26, $emple_id);
        $sql->bindValue(27, $tipo_id);
        $sql->bindValue(28, $d5);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function guardarEvdsMes(
        $formulario,
        $p1,
        $p2,
        $p3,
        $p4,
        $p5,
        $p6,
        $p7,
        $observacion,
        $usuario_id,
        $emple_id,
        $fecha,
        $anio,
        $mes_eval,
        $tipo_id

    ) {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO public.evaluacion_mensual(
                numero_formulario_evme, pregunta_1_evme, pregunta_2_evme, pregunta_3_evme, pregunta_4_evme, pregunta_5_evme, pregunta_6_evme, pregunta_7_evme, 
                observaciones_evme, evaluador_evme, empleado_evme, fecha_evme, anio_evme, mes_evme, tipo_evaluacion_evme)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $formulario);
        $sql->bindValue(2, $p1);
        $sql->bindValue(3, $p2);
        $sql->bindValue(4, $p3);
        $sql->bindValue(5, $p4);
        $sql->bindValue(6, $p5);
        $sql->bindValue(7, $p6);
        $sql->bindValue(8, $p7);
        $sql->bindValue(9, $observacion);
        $sql->bindValue(10, $usuario_id);
        $sql->bindValue(11, $emple_id);
        $sql->bindValue(12, $fecha);
        $sql->bindValue(13, $anio);
        $sql->bindValue(14, $mes_eval);
        $sql->bindValue(15, $tipo_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function mostar_evalua_x_mes($evaluador, $mes, $anio) {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM evaluados_con_evaluacion (?, ?, ?) ORDER BY nombre_evaluador";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $evaluador);
        $stmt->bindValue(2, $mes);
        $stmt->bindValue(3, $anio);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function mostar_evalua_x_sept($evaluador, $mes, $anio) {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM evaluados_con_evaluacion_sept (?, ?, ?) ORDER BY nombre_evaluador";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $evaluador);
        $stmt->bindValue(2, $mes);
        $stmt->bindValue(3, $anio);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_evaluacion() {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM vista_evaluacion WHERE meses = 6 ";

        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function mostar_cumplimiento($mes, $anio) {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM porcentaje_cumplimiento (?, ?)";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $mes);
        $stmt->bindValue(2, $anio);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function mostar_cumplimiento_agosto($mes, $anio) {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM porcentaje_cumplimiento_agosto (?, ?)";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $mes);
        $stmt->bindValue(2, $anio);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_evaluacion_anual() {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM vista_evaluacion_anual WHERE periodo = '2023' AND id_empl = '103' ";

        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_coevaluacion_veinticinco($id_empl) {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM vw_resultado_ponderado_coevaluacion WHERE empl_eval = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $id_empl);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_autoevaluacion_veinticinco($id_empl) {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM vw_resultado_ponderado_evaluacion WHERE empl_eval = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $id_empl);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_subevaluacion_veinticinco($id_empl) {

        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT * FROM vw_resultado_ponderado_subevaluacion WHERE empl_eval = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $id_empl);
        $stmt->execute();
        return $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // EVALUACION 2026

    public function get_preguntas_desempeno() {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT evpr_id, evpr_bloque, evpr_pregunta, evpr_orden, evpr_ayuda
            FROM public.evaluacion_desempeno_preguntas
            WHERE evpr_estado = 1
            ORDER BY evpr_orden ASC";

        $stmt = $conectar->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validar_evaluacion_desempeno_unica($empleado_id, $evaluador_id, $anio, $tipo) {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT COUNT(*) AS total
            FROM public.evaluacion_desempeno
            WHERE evde_empleado_id = ?
              AND evde_evaluador_id = ?
              AND evde_anio = ?
              AND evde_tipo = ?";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empleado_id);
        $stmt->bindValue(2, $evaluador_id);
        $stmt->bindValue(3, $anio);
        $stmt->bindValue(4, $tipo);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert_evaluacion_desempeno(
        $empleado_id,
        $evaluador_id,
        $anio,
        $tipo,
        $respuestas,
        $observacion = null
    ) {
        $conectar = parent::conexion();
        parent::set_names();

        try {
            $conectar->beginTransaction();

            $bloques = [
                "PRODUCTIVIDAD" => [],
                "ACTITUD" => [],
                "CONDUCTA LABORAL" => []
            ];

            foreach ($respuestas as $respuesta) {
                $bloques[$respuesta["bloque"]][] = (int)$respuesta["calificacion"];
            }

            $prom_productividad = round(array_sum($bloques["PRODUCTIVIDAD"]) / count($bloques["PRODUCTIVIDAD"]), 2);
            $prom_actitud = round(array_sum($bloques["ACTITUD"]) / count($bloques["ACTITUD"]), 2);
            $prom_conducta = round(array_sum($bloques["CONDUCTA LABORAL"]) / count($bloques["CONDUCTA LABORAL"]), 2);

            $total_preguntas =
                count($bloques["PRODUCTIVIDAD"]) +
                count($bloques["ACTITUD"]) +
                count($bloques["CONDUCTA LABORAL"]);

            $total_puntos =
                array_sum($bloques["PRODUCTIVIDAD"]) +
                array_sum($bloques["ACTITUD"]) +
                array_sum($bloques["CONDUCTA LABORAL"]);

            $prom_general = round($total_puntos / $total_preguntas, 2);

            $sql = "INSERT INTO public.evaluacion_desempeno (
                    evde_empleado_id,
                    evde_evaluador_id,
                    evde_anio,
                    evde_tipo,
                    evde_prom_productividad,
                    evde_prom_actitud,
                    evde_prom_conducta,
                    evde_prom_general,
                    evde_fecha
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                RETURNING evde_id";

            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $empleado_id);
            $stmt->bindValue(2, $evaluador_id);
            $stmt->bindValue(3, $anio);
            $stmt->bindValue(4, $tipo);
            $stmt->bindValue(5, $prom_productividad);
            $stmt->bindValue(6, $prom_actitud);
            $stmt->bindValue(7, $prom_conducta);
            $stmt->bindValue(8, $prom_general);
            $stmt->execute();

            $evde_id = $stmt->fetchColumn();

            $sql_detalle = "INSERT INTO public.evaluacion_desempeno_detalle (
                            evdd_evaluacion_id,
                            evdd_bloque,
                            evdd_numero_pregunta,
                            evdd_pregunta,
                            evdd_calificacion
                        ) VALUES (?, ?, ?, ?, ?)";

            $stmt_detalle = $conectar->prepare($sql_detalle);

            foreach ($respuestas as $respuesta) {
                $stmt_detalle->bindValue(1, $evde_id);
                $stmt_detalle->bindValue(2, $respuesta["bloque"]);
                $stmt_detalle->bindValue(3, $respuesta["numero_pregunta"]);
                $stmt_detalle->bindValue(4, $respuesta["pregunta"]);
                $stmt_detalle->bindValue(5, $respuesta["calificacion"]);
                $stmt_detalle->execute();
            }

            if ($tipo == "SUBEVALUACION" && $observacion != null) {

                $sql_observacion = "INSERT INTO public.evaluacion_desempeno_observacion (
                                    evde_id,
                                    fortalezas,
                                    oportunidades_mejora,
                                    apoyo_requerido,
                                    fecha_revision
                                ) VALUES (?, ?, ?, ?, ?)";

                $stmt_observacion = $conectar->prepare($sql_observacion);
                $stmt_observacion->bindValue(1, $evde_id);
                $stmt_observacion->bindValue(2, $observacion["fortalezas"]);
                $stmt_observacion->bindValue(3, $observacion["oportunidades_mejora"]);
                $stmt_observacion->bindValue(4, $observacion["apoyo_requerido"]);
                $stmt_observacion->bindValue(5, $observacion["fecha_revision"]);
                $stmt_observacion->execute();
            }

            $conectar->commit();

            return [
                "status" => true,
                "evde_id" => $evde_id,
                "prom_general" => $prom_general
            ];
        } catch (Exception $e) {

            $conectar->rollBack();

            return [
                "status" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    /**
     * Consulta los periodos que tienen registros en el nuevo modulo de desempeno.
     * No recibe parametros y devuelve un arreglo asociativo ordenado del periodo mas reciente al mas antiguo.
     */
    public function get_periodos_reporte_desempeno() {
        $conectar = parent::conexion();
        parent::set_names();

        // El periodo proviene del dato registrado en la evaluacion y nunca del ano actual del servidor.
        $sql = "SELECT DISTINCT evde_anio AS periodo
                FROM public.evaluacion_desempeno
                WHERE BTRIM(evde_anio) <> ''
                ORDER BY evde_anio DESC";
        $stmt = $conectar->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca empleados activos para el Select2 del reporte.
     * Recibe el texto opcional de busqueda y el limite de filas; devuelve documento, nombre y cargo.
     */
    public function buscar_empleados_activos_reporte($busqueda = '', $limite = 20) {
        $conectar = parent::conexion();
        parent::set_names();

        $termino = '%' . trim($busqueda) . '%';
        $limite = max(1, min((int)$limite, 50));

        // La busqueda usa ILIKE de PostgreSQL y restringe de forma explicita el estado activo confirmado.
        $sql = "SELECT e.id_empl,
                       e.cedu_empl,
                       e.nomb_empl,
                       COALESCE(c.nomb_carg, '') AS nomb_carg
                FROM public.empleados e
                LEFT JOIN public.cargo c ON c.codi_carg = e.carg_empl
                WHERE e.esta_empl = 1
                  AND (
                      CAST(e.cedu_empl AS TEXT) ILIKE ?
                      OR COALESCE(e.nomb_empl, '') ILIKE ?
                  )
                ORDER BY e.nomb_empl ASC
                LIMIT " . $limite;
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $termino, PDO::PARAM_STR);
        $stmt->bindValue(2, $termino, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la ficha del colaborador y los tipos registrados para un periodo.
     * Recibe el id del empleado y el periodo; devuelve una fila o false cuando no existen evaluaciones.
     */
    public function get_resumen_reporte_desempeno($empleado_id, $periodo) {
        $conectar = parent::conexion();
        parent::set_names();

        // El INNER JOIN garantiza que la respuesta solo exista cuando coinciden empleado y periodo.
        $sql = "SELECT e.id_empl,
                       e.cedu_empl,
                       e.nomb_empl,
                       COALESCE(c.nomb_carg, 'No registrado') AS nomb_carg,
                       ed.evde_anio AS periodo,
                       STRING_AGG(DISTINCT ed.evde_tipo, ', ' ORDER BY ed.evde_tipo) AS tipos_evaluacion
                FROM public.empleados e
                LEFT JOIN public.cargo c ON c.codi_carg = e.carg_empl
                INNER JOIN public.evaluacion_desempeno ed ON ed.evde_empleado_id = e.id_empl
                WHERE e.id_empl = ?
                  AND ed.evde_anio = ?
                GROUP BY e.id_empl, e.cedu_empl, e.nomb_empl, c.nomb_carg, ed.evde_anio";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, (int)$empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (string)$periodo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Consulta promedios consolidados por tipo para un empleado y periodo.
     * Recibe el id del empleado y el periodo; devuelve una fila por tipo de evaluacion existente.
     */
    public function get_promedios_reporte_desempeno($empleado_id, $periodo) {
        $conectar = parent::conexion();
        parent::set_names();

        // AVG permite consolidar de forma determinista varias evaluaciones del mismo tipo si llegaran a existir.
        $sql = "SELECT ed.evde_tipo,
                       ROUND(AVG(ed.evde_prom_productividad), 2) AS prom_productividad,
                       ROUND(AVG(ed.evde_prom_actitud), 2) AS prom_actitud,
                       ROUND(AVG(ed.evde_prom_conducta), 2) AS prom_conducta,
                       ROUND(AVG(ed.evde_prom_general), 2) AS prom_general
                FROM public.evaluacion_desempeno ed
                WHERE ed.evde_empleado_id = ?
                  AND ed.evde_anio = ?
                  AND ed.evde_tipo IN ('AUTOEVALUACION', 'COEVALUACION', 'SUBEVALUACION')
                GROUP BY ed.evde_tipo
                ORDER BY ed.evde_tipo";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, (int)$empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (string)$periodo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consulta las respuestas agrupadas por bloque, pregunta y tipo de evaluacion.
     * Recibe el id del empleado y el periodo; devuelve las preguntas respondidas en su orden real.
     */
    public function get_detalle_reporte_desempeno($empleado_id, $periodo) {
        $conectar = parent::conexion();
        parent::set_names();

        // Se usa el detalle historico guardado y se promedian duplicados del mismo tipo sin mezclar otros periodos.
        $sql = "SELECT d.evdd_bloque,
                       d.evdd_numero_pregunta,
                       MIN(d.evdd_pregunta) AS evdd_pregunta,
                       ROUND(AVG(d.evdd_calificacion) FILTER (WHERE ed.evde_tipo = 'AUTOEVALUACION'), 2) AS autoevaluacion,
                       ROUND(AVG(d.evdd_calificacion) FILTER (WHERE ed.evde_tipo = 'COEVALUACION'), 2) AS coevaluacion,
                       ROUND(AVG(d.evdd_calificacion) FILTER (WHERE ed.evde_tipo = 'SUBEVALUACION'), 2) AS subevaluacion
                FROM public.evaluacion_desempeno ed
                INNER JOIN public.evaluacion_desempeno_detalle d ON d.evdd_evaluacion_id = ed.evde_id
                WHERE ed.evde_empleado_id = ?
                  AND ed.evde_anio = ?
                  AND ed.evde_tipo IN ('AUTOEVALUACION', 'COEVALUACION', 'SUBEVALUACION')
                GROUP BY d.evdd_bloque, d.evdd_numero_pregunta
                ORDER BY d.evdd_numero_pregunta, d.evdd_bloque";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, (int)$empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (string)$periodo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consulta el cierre y el superior real de la subevaluacion mas reciente del periodo.
     * Recibe el id del empleado y el periodo; devuelve una fila o false cuando no hay subevaluacion.
     */
    public function get_cierre_reporte_desempeno($empleado_id, $periodo) {
        $conectar = parent::conexion();
        parent::set_names();

        // La relacion con el superior usa la llave real evde_evaluador_id hacia empleados.id_empl.
        $sql = "SELECT evaluador.nomb_empl AS superior_evaluador,
                       o.fortalezas,
                       o.oportunidades_mejora,
                       o.apoyo_requerido,
                       o.fecha_revision
                FROM public.evaluacion_desempeno ed
                INNER JOIN public.empleados evaluador ON evaluador.id_empl = ed.evde_evaluador_id
                LEFT JOIN public.evaluacion_desempeno_observacion o ON o.evde_id = ed.evde_id
                WHERE ed.evde_empleado_id = ?
                  AND ed.evde_anio = ?
                  AND ed.evde_tipo = 'SUBEVALUACION'
                ORDER BY ed.evde_fecha DESC NULLS LAST, ed.evde_id DESC
                LIMIT 1";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, (int)$empleado_id, PDO::PARAM_INT);
        $stmt->bindValue(2, (string)$periodo, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
