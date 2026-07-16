<?php

require_once("../config/conexion.php");
require_once("../models/Evaluacion.php");


$evaluacion = new Evaluacion();


switch ($_GET["op"]) {
        case 'insert':


                $evaluacion->insert_evaluacion(
                        $_POST["formulario_id"],
                        $_POST["fecha"],
                        $_POST["evaluador"],
                        $_POST["aaa_eval"],
                        $_POST["productividad1"],
                        $_POST["productividad2"],
                        $_POST["productividad3"],
                        $_POST["productividad4"],
                        $_POST["productividad5"],
                        $_POST["productividad6"],
                        $_POST["laboral1"],
                        $_POST["laboral2"],
                        $_POST["laboral3"],
                        $_POST["laboral4"],
                        $_POST["laboral5"],
                        $_POST["laboral6"],
                        $_POST["actitud1"],
                        $_POST["actitud2"],
                        $_POST["actitud3"],
                        $_POST["actitud4"],
                        $_POST["liderazgo1"],
                        $_POST["liderazgo2"],
                        $_POST["liderazgo3"],
                        $_POST["liderazgo4"],
                        $_POST["eval_observaciones"],
                        $_POST["nomb_evaluad"],
                        $_POST["radioTipoEval"],
                        $_POST["liderazgo5"]
                );





                break;

        case 'guardarEvdsMes':

                $evaluacion->guardarEvdsMes(
                        $_POST["formulario_id_txt"],
                        $_POST["pregunta1"],
                        $_POST["pregunta2"],
                        $_POST["pregunta3"],
                        $_POST["pregunta4"],
                        $_POST["pregunta5"],
                        $_POST["pregunta6"],
                        $_POST["pregunta7"],
                        $_POST["txt_eval_observaciones"],
                        $_POST["txt_id_evaluador"],
                        $_POST["txt_nomb_evaluad"],
                        $_POST["txt_fech_eval"],
                        $_POST["txt_anio_eval"],
                        $_POST["txt_mes_eval"],
                        $_POST["radioTipoEval"]
                );

                break;

        case 'comboMes':

                $datos = $evaluacion->get_mes_combo();
                /*Preguntamos prinmero que hayan datos*/
                if (is_array($datos) == true and count($datos) > 0) {
                        $html = "<option disabled selected required>--Selecciona Mes..--</option>";
                        foreach ($datos as $row) {
                                $html .= "<option value='" . $row['id_mes'] . "'>" . $row['id_mes'] . " - " . $row['nomb_mes'] . "</option>";
                        }
                        echo $html;
                }

                break;


        case 'comboMesTotal':

                $datos = $evaluacion->get_mes_combo_total();
                /*Preguntamos prinmero que hayan datos*/
                if (is_array($datos) == true and count($datos) > 0) {
                        $html = "<option disabled selected required>--Selecciona Mes..--</option>";
                        foreach ($datos as $row) {
                                $html .= "<option value='" . $row['id_mes'] . "'>" . $row['id_mes'] . " - " . $row['nomb_mes'] . "</option>";
                        }
                        echo $html;
                }

                break;

        case 'listarEvaluacionMes':

                if (isset($_POST["mes_eval"])) {
                        $idMesEval = $_POST['mes_eval'];
                        $idAnioEval = $_POST['mes_ano'];
                        $idEvaluador = $_POST['mes_evaluador'];
                        $datos = $evaluacion->mostar_evalua_x_mes($idEvaluador, $idMesEval, $idAnioEval);

                        $data = array();
                        foreach ($datos as $row) {

                                $sub_array = array();
                                $sub_array[] = $row["mes_evaluacion"];
                                $sub_array[] = $row["nombre_evaluador"];
                                $sub_array[] = $row["nombre_evaluado"];
                                $sub_array[] = $row["tiene_evaluacion"];

                                $data[] = $sub_array;
                        }

                        $results = array(

                                "sEcho" => 1,
                                "iTotalRecords" => count($data),
                                "iTotalDisplayRecords" => count($data),
                                "aaData" => $data
                        );

                        echo json_encode($results);
                } else {
                        // Maneja el caso en el que 'id_mes_eval' no está presente
                        echo "El índice no está definido en el array." . $idMesEval . $idAnioEval;
                }




                break;

        case 'listarEvaluacionSeptiembre':

                if (isset($_POST["mes_eval"])) {
                        $idMesEval = $_POST['mes_eval'];
                        $idAnioEval = $_POST['mes_ano'];
                        $idEvaluador = $_POST['mes_evaluador'];
                        $datos = $evaluacion->mostar_evalua_x_sept($idEvaluador, $idMesEval, $idAnioEval);

                        $data = array();
                        foreach ($datos as $row) {

                                $sub_array = array();
                                $sub_array[] = $row["mes_evaluacion"];
                                $sub_array[] = $row["nombre_evaluador"];
                                $sub_array[] = $row["nombre_evaluado"];
                                $sub_array[] = $row["tiene_evaluacion"];

                                $data[] = $sub_array;
                        }

                        $results = array(

                                "sEcho" => 1,
                                "iTotalRecords" => count($data),
                                "iTotalDisplayRecords" => count($data),
                                "aaData" => $data
                        );

                        echo json_encode($results);
                } else {
                        // Maneja el caso en el que 'id_mes_eval' no está presente
                        echo "El índice no está definido en el array." . $idMesEval . $idAnioEval;
                }




                break;

        //Listar cumplimiento de septiembre en adelante

        case 'listarCumplimiento':

                $datos = $evaluacion->mostar_cumplimiento($_POST['txt_mes_eval'], $_POST['txt_mes_ano']);

                $data = array();
                foreach ($datos as $row) {

                        $sub_array = array();
                        $sub_array[] = $row["mes_evaluacion"];
                        $sub_array[] = $row["nombre_evaluador"];
                        $sub_array[] = $row["evaluaciones_correctas"];
                        $sub_array[] = $row["total_asignadas"];
                        $sub_array[] = $row["porcentaje_cumplimiento"] . "/100.00";

                        $data[] = $sub_array;
                }

                $results = array(

                        "sEcho" => 1,
                        "iTotalRecords" => count($data),
                        "iTotalDisplayRecords" => count($data),
                        "aaData" => $data
                );

                echo json_encode($results);


                break;

        case 'listarCumplimientoAgosto':

                $datos = $evaluacion->mostar_cumplimiento_agosto($_POST['txt_mes_eval'], $_POST['txt_mes_ano']);

                $data = array();
                foreach ($datos as $row) {

                        $sub_array = array();
                        $sub_array[] = $row["mes_evaluacion"];
                        $sub_array[] = $row["nombre_evaluador"];
                        $sub_array[] = $row["evaluaciones_correctas"];
                        $sub_array[] = $row["total_asignadas"];
                        $sub_array[] = $row["porcentaje_cumplimiento"] . "/100.00";

                        $data[] = $sub_array;
                }

                $results = array(

                        "sEcho" => 1,
                        "iTotalRecords" => count($data),
                        "iTotalDisplayRecords" => count($data),
                        "aaData" => $data
                );

                echo json_encode($results);


                break;

        case 'reportePDF':

                if (isset($_POST['id_empl'])) {

                        $id_empl = $_POST['id_empl'];
                        $autoEval = $evaluacion->listar_autoevaluacion_veinticinco($id_empl);
                        $coeEval = $evaluacion->listar_coevaluacion_veinticinco($id_empl);
                        $subEval = $evaluacion->listar_subevaluacion_veinticinco($id_empl);
                        require '../view/reportes/evaluacion_2025.php';
                } else {
                        echo "ID de empleado no recibido.";
                }


                break;

        case "listarPreguntasDesempeno":

                header('Content-Type: application/json; charset=utf-8');

                $datos = $evaluacion->get_preguntas_desempeno();

                echo json_encode([
                        "status" => "success",
                        "data" => $datos
                ]);

                break;

        case "guardarEvaluacionDesempeno":

                header('Content-Type: application/json; charset=utf-8');

                try {

                        $empleado_id = $_POST["empleado_id"];
                        $evaluador_id = $_SESSION["id_empl"];
                        $anio = $_POST["anio"];
                        $tipo = $_POST["tipo_evaluacion"];
                        $respuestas = json_decode($_POST["respuestas"], true);

                        $fortalezas = isset($_POST["fortalezas"]) ? trim($_POST["fortalezas"]) : "";
                        $oportunidades_mejora = isset($_POST["oportunidades_mejora"]) ? trim($_POST["oportunidades_mejora"]) : "";
                        $apoyo_requerido = isset($_POST["apoyo_requerido"]) ? trim($_POST["apoyo_requerido"]) : "";
                        $fecha_revision = isset($_POST["fecha_revision"]) ? trim($_POST["fecha_revision"]) : "";

                        if (
                                empty($empleado_id) ||
                                empty($evaluador_id) ||
                                empty($anio) ||
                                empty($tipo) ||
                                empty($respuestas)
                        ) {
                                echo json_encode([
                                        "status" => "warning",
                                        "message" => "Faltan datos obligatorios para guardar la evaluación."
                                ]);
                                exit();
                        }

                        if ($tipo == "SUBEVALUACION") {
                                if (
                                        empty($fortalezas) ||
                                        empty($oportunidades_mejora) ||
                                        empty($apoyo_requerido) ||
                                        empty($fecha_revision)
                                ) {
                                        echo json_encode([
                                                "status" => "warning",
                                                "message" => "Debe diligenciar todas las preguntas de cierre para la subevaluación."
                                        ]);
                                        exit();
                                }
                        }

                        $validacion = $evaluacion->validar_evaluacion_desempeno_unica(
                                $empleado_id,
                                $evaluador_id,
                                $anio,
                                $tipo
                        );

                        if ((int)$validacion["total"] > 0) {
                                echo json_encode([
                                        "status" => "warning",
                                        "message" => "Esta evaluación ya fue diligenciada para el periodo seleccionado."
                                ]);
                                exit();
                        }

                        $observacion = null;

                        if ($tipo == "SUBEVALUACION") {
                                $observacion = [
                                        "fortalezas" => $fortalezas,
                                        "oportunidades_mejora" => $oportunidades_mejora,
                                        "apoyo_requerido" => $apoyo_requerido,
                                        "fecha_revision" => $fecha_revision
                                ];
                        }

                        $resultado = $evaluacion->insert_evaluacion_desempeno(
                                $empleado_id,
                                $evaluador_id,
                                $anio,
                                $tipo,
                                $respuestas,
                                $observacion
                        );

                        if (!$resultado["status"]) {
                                echo json_encode([
                                        "status" => "error",
                                        "message" => $resultado["message"]
                                ]);
                                exit();
                        }



                        echo json_encode([
                                "status" => "success",
                                "message" => "La evaluación se ha culminado exitosamente.",
                                "prom_general" => $resultado["prom_general"]
                        ]);
                } catch (Exception $e) {

                        echo json_encode([
                                "status" => "error",
                                "message" => "Error al guardar la evaluación.",
                                "detail" => $e->getMessage()
                        ]);
                }

                break;

        case "listarPeriodosReporteDesempeno":

                header('Content-Type: application/json; charset=utf-8');

                try {
                        // Los periodos se consultan del nuevo modulo y no se calculan con la fecha actual.
                        $periodos = $evaluacion->get_periodos_reporte_desempeno();

                        echo json_encode([
                                "status" => "success",
                                "data" => $periodos
                        ], JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode([
                                "status" => "error",
                                "message" => "No fue posible consultar los periodos de evaluación."
                        ], JSON_UNESCAPED_UNICODE);
                }

                break;

        case "listarEmpleadosActivosReporte":

                header('Content-Type: application/json; charset=utf-8');

                try {
                        // El termino de Select2 es opcional, se limita para evitar consultas excesivas.
                        $busqueda = isset($_GET["q"]) ? trim((string)$_GET["q"]) : "";
                        $empleados = $evaluacion->buscar_empleados_activos_reporte($busqueda, 20);
                        $resultados = [];

                        foreach ($empleados as $empleado) {
                                $texto = trim($empleado["cedu_empl"] . " - " . $empleado["nomb_empl"]);
                                if (!empty($empleado["nomb_carg"])) {
                                        $texto .= " - " . $empleado["nomb_carg"];
                                }

                                $resultados[] = [
                                        "id" => (int)$empleado["id_empl"],
                                        "text" => $texto
                                ];
                        }

                        echo json_encode([
                                "results" => $resultados,
                                "pagination" => ["more" => false]
                        ], JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode([
                                "status" => "error",
                                "message" => "No fue posible consultar los empleados activos.",
                                "results" => []
                        ], JSON_UNESCAPED_UNICODE);
                }

                break;

        case "consultarReporteDesempeno":

                header('Content-Type: application/json; charset=utf-8');

                try {
                        // Se validan ambos filtros antes de entregar cualquier dato del colaborador.
                        $empleado_id = filter_input(INPUT_POST, "empleado_id", FILTER_VALIDATE_INT);
                        $periodo = isset($_POST["periodo"]) ? trim((string)$_POST["periodo"]) : "";

                        if (!$empleado_id || !preg_match('/^\d{4}$/', $periodo)) {
                                http_response_code(400);
                                echo json_encode([
                                        "status" => "warning",
                                        "message" => "Seleccione un empleado y un periodo válidos."
                                ], JSON_UNESCAPED_UNICODE);
                                break;
                        }

                        $resumen = $evaluacion->get_resumen_reporte_desempeno($empleado_id, $periodo);

                        if (!$resumen) {
                                echo json_encode([
                                        "status" => "empty",
                                        "message" => "El empleado no tiene evaluaciones registradas para el periodo seleccionado."
                                ], JSON_UNESCAPED_UNICODE);
                                break;
                        }

                        $resumen["tipos_evaluacion"] = array_values(array_filter(array_map(
                                'trim',
                                explode(',', $resumen["tipos_evaluacion"])
                        )));

                        echo json_encode([
                                "status" => "success",
                                "data" => $resumen
                        ], JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode([
                                "status" => "error",
                                "message" => "No fue posible consultar el reporte de desempeño."
                        ], JSON_UNESCAPED_UNICODE);
                }

                break;
}
