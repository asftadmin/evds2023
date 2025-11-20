<?php

require_once("../config/conexion.php");
require_once("../models/Rutas.php");


$rutas = new Rutas();

switch ($_GET["op"]) {
    case "guardaryeditar":
        if (isset($_POST['txt_empleado'])) {
            $empleado = $_POST["txt_empleado"];
            $jefe_id = $_POST["txt_jefe"];

            $rutas->insertar_rutas($empleado, $jefe_id);

            echo json_encode(['success' => true, 'message' => 'Registros Guardado Exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Fallo al registrar']);
        }


        break;


    case 'listarRutas':

        $datos = $rutas->mostrar_rutas();

        $data = array();
        foreach ($datos as $row) {

            $sub_array = array();
            $sub_array[] = $row["empleado_nombre"];
            $sub_array[] = $row["jefe_nombre"];
            $estado = $row["estado"];
            $badge = '';

            switch ($estado) {
                case 'Activo':
                    $badge = '<span class="badge bg-success">Activo</span>';
                    break;
                case 'Inactivo':
                    $badge = '<span class="badge bg-secondary">Inactivo</span>';
                    break;
                default:
                    $badge = '<span class="badge bg-dark">Desconocido</span>';
                    break;
            }

            $sub_array[] = '<div class="text-center">' . $badge . '</div>';
            $sub_array[] = '<div class="button-container text-center" >
                        <button type="button" onClick="eliminar(' . $row["id_empl_jefe"] . ');" id="' . $row["id_empl_jefe"] . '" class="btn btn-danger btn-icon" >
                            <div><i class="fas fa-minus"></i></div>
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


    case 'inactivar_empleado':

        $rutas->update_estado_empleado($_POST["codi_empleado_jefe"]);


        break;
}
