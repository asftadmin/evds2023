<?php

require_once("../config/conexion.php");
require_once("../models/Permiso.php");


$permiso = new Permiso();

switch ($_GET["op"]) {

    case 'guardarPermiso':

        // ID del empleado logueado
        $id_empleado = $_POST["empleado_codi"];
        $fecha_permiso = $_POST["fecha_permiso"];
        $hora_salida = $_POST["timepicker_salida"];
        $hora_ingreso = $_POST["timepicker_entrada"];

        // Datos recibidos del formulario
        $motivo = $_POST["permiso_motivo"];
        $detalle = $_POST["permiso_detalle"];
        $firma_base64 = $_POST["firma"];

        // Llamar al modelo
        $resultado = $permiso->insertar_permiso($id_empleado, $fecha_permiso, $hora_salida, $hora_ingreso, $motivo, $detalle, $firma_base64);

        if ($resultado) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "No se pudo guardar el permiso."]);
        }



        break;


    case "listarSolicitudesOpen":

        $datos = $permiso->get_solicitudes();
        $data = array();
        //$tickets = [];
        foreach ($datos as $solicitud) {
            $sub_array = array();
            $sub_array[] = $solicitud["nomb_empl"];
            $sub_array[] = date('d-m-Y', strtotime($solicitud["permiso_fecha"]));
            $sub_array[] = $solicitud["tipo_nombre"];

            $sub_array[] = '<div class="button-container text-center" >
                    <button type="button" onClick="ver(' . $solicitud["permiso_id"] . ');" id="' . $solicitud["permiso_id"] . '" class="btn btn-secondary btn-icon " >
                        <div><i class="fas fa-envelope"></i></div>
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
}
