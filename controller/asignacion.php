<?php

require_once("../config/conexion.php");
require_once("../models/Asignacion.php");


$asignacion = new Asignacion();


switch ($_GET["op"]) {

    case 'guardaryeditar':


        if (isset($_POST['nomb_evaluador'], $_POST['nomb_empleado']) && !empty($_POST['nomb_evaluador']) && empty($_POST["codigo_asignacion"])) {
            $evaluadorId = $_POST["nomb_evaluador"];
            $empleados = $_POST["nomb_empleado"];
            $mes = $_POST["mes_evalua"];
            $anio = $_POST["año_evalua"];


            $asignacion->insertar_asignacion($evaluadorId, $empleados, $mes, $anio);

            echo json_encode(['success' => true, 'message' => 'Asignación realizada con éxito.']);
        } else {
            /* $asignacion->editar_asig(
                $_POST["nomb_evaluador"],
                $_POST["nomb_empleado"],
                $_POST["mes_evalua"],
                $_POST["año_evalua"],
                $_POST["codigo_asignacion"]
            ); */
        }




        break;

    case 'listarAsignacion':

        $datos = $asignacion->mostrar_asignacion();

        $data = array();
        foreach ($datos as $row) {

            $sub_array = array();
            $sub_array[] = $row["año_asig"];
            $sub_array[] = $row["nomb_mes"];
            $sub_array[] = $row["nombre_evaluador"];
            $sub_array[] = $row["nombre_evaluado"];
            $sub_array[] = '<div class="button-container text-center" >
                        <button type="button" onClick="editar(' . $row["codi_asig"] . ');" id="' . $row["codi_asig"] . '" class="btn btn-warning btn-icon " >
                            <div><i class="fa fa-edit"></i></div>
                        </button>
                        <button type="button" onClick="eliminar(' . $row["codi_asig"] . ');" id="' . $row["codi_asig"] . '" class="btn btn-danger btn-icon" >
                            <div><i class="fa fa-trash"></i></div>
                        </button>
                    </div>';

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

    case 'eliminar_asignacion':

        $asignacion->delete_asignacion($_POST["codi_asignacion"]);


        break;

    case 'mostrar_asignacion':
        $datos = $asignacion->listar_asignacion_x_id($_POST["codi_asignacion"]);
        if (is_array($datos) == true and count($datos) > 0) {
            foreach ($datos as $row) {
                $output["codigo_asignacion"] = $row['codi_asig'];
                $output["nomb_evaluador"] = $row['id_empl'];
                $output["nomb_empleado"] = $row["id_evaluado"];
                $output["mes_evalua"] = $row["id_mes"];
                $output["año_evalua"] = $row["año_asig"];
            }
            echo json_encode($output);
        }

        break;
}
