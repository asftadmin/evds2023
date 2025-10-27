<?php

require_once("../config/conexion.php");
require_once("../models/Empleados.php");
require_once("curl.php");



$empleado = new Empleado();


switch ($_REQUEST["op"]) {

    case 'guardaryeditar':


        if (empty($_POST["codigo_empleado"])) {
            $empleado->insertar_empleado(
                $_POST["tipo_documento_empl"],
                $_POST["numero_documento"],
                $_POST["nombre_empleado"],
                $_POST["telefono_empleado"],
                $_POST["direccion_empleado"],
                $_POST["cargo_empleado"],
                $_POST["fecha_ingreso"]
            );
        } else {
            /*             $empleado->update_empledado(
                $_POST["txt_codigo_empleado"],
                $_POST["txt_numero_documento"],
                $_POST["txt_nombre_empleado"],
                $_POST["txt_telefono_empleado"],
                $_POST["txt_direccion_empleado"],
                $_POST["select_cargo_empleado"],
                $_POST["txt_fecha_ingreso"],
                $_POST["select_genero"],
                $_POST["select_nivel_educativo"],
                $_POST["txt_profesion"],
                $_POST["txt_rh"]
            ); */
        }


        break;

    case 'comboRol':

        $datos = $empleado->get_empledo_activo();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el empleado--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_empl'] . "'>" . $row['nomb_empl'] . "</option>";
            }
            echo $html;
        }



        break;
    case 'comboAsig':
        // Obtener evaluadores y empleados
        $evaluadores = $empleado->get_empledo();
        $empleados = $empleado->get_empledo_grupo();

        // Enviar datos como JSON
        echo json_encode([
            'evaluadores' => $evaluadores,
            'empleados' => $empleados
        ]);
        break;

    case 'comboEmpleados':

        $datos = $empleado->get_empledo_grupo();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el empleado--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_empl'] . "'>" . $row['nomb_empl'] . "</option>";
            }
            echo $html;
        }



        break;
    case 'buscarEmpleado':

        $datos = $empleado->get_empledo();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el empleado--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_empl'] . "'>" . $row['nomb_empl'] . "</option>";
            }
            echo $html;
        }



        break;
    case "listarEmpleado":

        $datos = $empleado->get_empledo();

        $data = array();
        foreach ($datos as $row) {

            $sub_array = array();
            $sub_array[] = $row["cedu_empl"];
            $sub_array[] = $row["nomb_empl"];
            $sub_array[] = $row["fecha_ingreso_empl"];
            $sub_array[] = '<div class="button-container text-center" >
                    <button type="button" onClick="editar(' . $row["id_empl"] . ');" id="' . $row["id_empl"] . '" class="btn btn-warning btn-icon " >
                        <div><i class="fa fa-edit"></i></div>
                    </button>
                    <button type="button" onClick="eliminar(' . $row["id_empl"] . ');" id="' . $row["id_empl"] . '" class="btn btn-danger btn-icon" >
                        <div><i class="fa fa-trash"></i></div>
                    </button>
                </div>';
            $data[] = $sub_array;
        }


        $resultado = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($resultado);

        break;

    case 'mostrar':
        $datos = $empleado->get_empledo_x_id($_POST["codigo_empleado"]);
        if (is_array($datos) == true and count($datos) > 0) {
            foreach ($datos as $row) {
                $output["txt_codigo_empleado"] = $row["id_empl"];
                $output["txt_numero_documento"] = $row["cedu_empl"];
                $output["txt_nombre_empleado"] = $row["nomb_empl"];
                $output["txt_telefono_empleado"] = $row["tele_empl"];
                $output["txt_direccion_empleado"] = $row["dire_empl"];
                $output["select_cargo_empleado"] = $row["carg_empl"];
                $output["txt_fecha_ingreso"] = $row["fecha_ingreso_empl"];
                $output["txt_fecha_nacimiento"] = $row["fecha_naci_empl"];
            }
            echo json_encode($output);
        }

        break;
    
    case "consultaEmpleadoSiesa":

        $data = array();
        $pagina = 1;
        $tamPag = 100;
        $totalPaginas = 1; // valor inicial, luego se actualiza en la primera iteración
        $fechainicio = $_GET['fechainicio'] ?? '2017-01-01';
        $fechafin = $_GET['fechafin'] ?? date('Y-m-d');
        
        do {
            $url = 'idCompania=6026';
            $url .= '&descripcion=asfaltart_CONSULTA_EMPLEADOS';
            $url .= '&paginacion=' . urlencode("numPag=$pagina|tamPag=$tamPag");
            $url .= '&parametros=' . urlencode("fechainicio=$fechainicio|fechafin=$fechafin");
        
            $method = "GET";
            $response = CurlController::requestEstandar($url, $method);
        
            if (isset($response->detalle->Datos) && is_array($response->detalle->Datos)) {
                if ($pagina === 1 && isset($response->detalle->total_páginas)) {
                    $totalPaginas = $response->detalle->total_páginas;
                }
        
                foreach ($response->detalle->Datos as $row) {
                    if (is_object($row)) {
                        $sub_array = array();
                        $sub_array[] = $row->f200_id ?? '';
                        $sub_array[] = $row->f200_razon_social ?? '';
                        $sub_array[] = $row->c0540_fecha_ingreso ?? '';
                        $sub_array[] = $row->c0540_fecha_nacimiento ?? '';
                        $sub_array[] = $row->f015_direccion1 ?? '';
                        $sub_array[] = $row->f015_email ?? '';
                        $sub_array[] = $row->f015_celular ?? '';
        
                        
        
                        $data[] = $sub_array;
                    }
                }
            }
        
            $pagina++; // avanzar a la siguiente página
        
        } while ($pagina <= $totalPaginas);
        
        // Enviar la respuesta final
        $resultado = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        
        echo json_encode($resultado);
        break;
    case 'consultarEmpleados':

        $datos = $empleado->get_empledo();

        $data = array();
        foreach ($datos as $row) {

            $sub_array = array();
            $sub_array[] = $row["cedu_empl"];
            $sub_array[] = $row["nomb_empl"];
            $data[] = $sub_array;
        }


        $resultado = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($resultado);

        break;

    case "guardarEmpleadoNuevo":
        
            $documento = $_POST["documento"];
            $nombre = $_POST["nombre"];
            $fecha_ingreso = $_POST ["fecha_ingreso"];
            $fecha_nacimiento = $_POST ["fecha_nacimiento"];
            $direccion = $_POST ["direccion"];
            $celular = $_POST ["celular"];

            $resultado = $empleado->insertarEmplNuevo([
                'cedu_empl' => $documento,
                'nomb_empl' => $nombre,
                'fein_empl' => $fecha_ingreso,
                'fena_empl' => $fecha_nacimiento,
                'dire_empl' => $direccion,
                'celu_empl' => $celular
            ]);
            
            echo json_encode(["success" => $resultado]);


            break;
}