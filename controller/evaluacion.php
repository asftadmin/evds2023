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
                }else {
                        echo "ID de empleado no recibido.";
                }
                
        
                break;
}