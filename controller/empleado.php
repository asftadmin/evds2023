<?php

require_once("../config/conexion.php");
require_once("../models/Empleados.php");
require_once("curl.php");
require_once("../config/helpers.php");



$empleado = new Empleado();


switch ($_REQUEST["op"]) {

    case 'guardaryeditar':

        // ── Nullable ───────────────────────────────────────────────────
        $fecha_nacimiento = convertirFecha($_POST["txt_fecha_nacimiento"] ?? '');
        $fecha_exp        = convertirFecha($_POST["txt_fecha_exp"]        ?? '');
        $fecha_ingreso    = convertirFecha($_POST["txt_fecha_ingreso"]    ?? '');
        $fecha_retiro     = convertirFecha($_POST["txt_fecha_retiro"] ?? '');

        $genero          = (!empty($_POST["txt_genero"])             && $_POST["txt_genero"]             != 'NULL') ? $_POST["txt_genero"]             : null;
        $nivel_educativo = (!empty($_POST["txt_nivel"])              && $_POST["txt_nivel"]              != 'NULL') ? $_POST["txt_nivel"] : null;
        $profesion       = (!empty($_POST["txt_profesion"])          && $_POST["txt_profesion"]          != 'NULL') ? $_POST["txt_profesion"]          : null;
        $sanguineo       = (!empty($_POST["txt_rh"])                 && $_POST["txt_rh"]                 != 'NULL') ? $_POST["txt_rh"]                 : null;
        $esta_civil      = (!empty($_POST["txt_civil"])              && $_POST["txt_civil"]              != 'NULL') ? $_POST["txt_civil"]              : null;
        $estrato         = (!empty($_POST["txt_estrato"])            && $_POST["txt_estrato"]            != 'NULL') ? $_POST["txt_estrato"]            : null;
        $lugar_exp       = (!empty($_POST["txt_lugar_exp"])          && $_POST["txt_lugar_exp"]          != 'NULL') ? $_POST["txt_lugar_exp"]          : null;
        $anio_grado      = (!empty($_POST["txt_anio_grado"])         && $_POST["txt_anio_grado"]         != 'NULL') ? $_POST["txt_anio_grado"]         : null;
        $tipo_contrato   = (!empty($_POST["select_tipo_contrato"])   && $_POST["select_tipo_contrato"]   != 'NULL') ? $_POST["select_tipo_contrato"]   : null;
        $dependencia     = (!empty($_POST["select_dependencia"])     && $_POST["select_dependencia"]     != 'NULL') ? $_POST["select_dependencia"]     : null;
        $email           = (!empty($_POST["txt_correo"])             && $_POST["txt_correo"]             != 'NULL') ? $_POST["txt_correo"]             : null;


        // ── Salario — quitar formato colombiano ────────────────────────
        $salario = !empty($_POST["txt_salario"])
            ? str_replace(['.', ','], ['', '.'], $_POST["txt_salario"])
            : null;

        $codigo = $_POST["txt_codigo_empleado"] ?? '';

        if (empty($codigo)) {
            // ── INSERTAR ──────────────────────────────────────────────
            $empleado->insertar_empleado(
                $_POST["txt_tipo_documento_empl"],
                $_POST["txt_numero_documento"],
                $_POST["txt_nombre_empleado"],
                $_POST["txt_telefono_empleado"],
                $_POST["txt_direccion_empleado"],
                $_POST["select_cargo_empleado"],
                $fecha_ingreso,
                $fecha_nacimiento,
                $nivel_educativo,
                $profesion,
                $genero,
                $sanguineo
            );
            echo json_encode(['success' => true, 'accion' => 'insert']);
        } else {
            // ── ACTUALIZAR ────────────────────────────────────────────
            $resultado = $empleado->update_empleado(
                $codigo,
                $_POST["txt_tipo_documento_empl"],
                $_POST["txt_numero_documento"],
                $_POST["txt_nombre_empleado"],
                $_POST["txt_telefono_empleado"],
                $_POST["txt_direccion_empleado"],
                $_POST["select_cargo_empleado"],
                $fecha_ingreso,
                $_POST["select_esta_empl"] ?? 1,
                $fecha_nacimiento,
                $genero,
                $nivel_educativo,
                $profesion,
                $sanguineo,
                $esta_civil,
                $estrato,
                $lugar_exp,
                $email,
                $fecha_exp,
                $anio_grado,
                $tipo_contrato,
                $salario,
                $dependencia,
                $fecha_retiro

            );

            echo json_encode([
                'success' => $resultado,
                'accion'  => 'update',
                'error'   => $resultado ? null : 'No se pudo actualizar el empleado'
            ]);
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

    case 'comboGenero':

        $datos = $empleado->get_genero();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el genero--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_gene'] . "'>" . $row['desc_gene'] . "</option>";
            }
            echo $html;
        }



        break;

    case 'comboCivil':

        $datos = $empleado->get_estado_civil();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el estado civil--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_esta_civil'] . "'>" . $row['desc_esta_civil'] . "</option>";
            }
            echo $html;
        }



        break;
    case 'comboRH':

        $datos = $empleado->get_grupo_sanguineo();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el grupo sanguineo--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_grup_sang'] . "'>" . $row['desc_grup_sang'] . "</option>";
            }
            echo $html;
        }



        break;

    case 'comboEstrato':

        $datos = $empleado->get_estrato_socie();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el estrato--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_estra'] . "'>" . $row['desc_estra'] . "</option>";
            }
            echo $html;
        }



        break;

    case 'comboLugarExp':

        $datos = $empleado->get_lugar_exp();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el lugar de expedicion del documento--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_lugar'] . "'>" . $row['depto_lugar'] . " - " . $row['desc_lugar'] . "</option>";
            }
            echo $html;
        }



        break;
    case 'comboNivelEduc':

        $datos = $empleado->get_nivel_educativo();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el nivel educativo--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_nivel'] . "'>" . $row['desc_nivel'] . "</option>";
            }
            echo $html;
        }



        break;

    case 'comboTipoContrato':

        $datos = $empleado->get_tipo_contrato();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el tipo de contrato--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_contra'] . "'>" . $row['desc_contra'] . "</option>";
            }
            echo $html;
        }



        break;
    case 'comboDependencia':

        $datos = $empleado->get_tipo_dependencia();
        if (is_array($datos) == true and count($datos) > 0) {

            $html = "<option disabled selected required>--Selecciona el area o dependencia--</option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['id_depen'] . "'>" . $row['desc_depen'] . "</option>";
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

                $output["txt_codigo_empleado"]      = $row["id_empl"];
                $output["txt_numero_documento"]     = $row["cedu_empl"];
                $output["txt_nombre_empleado"]      = $row["nomb_empl"];
                $output["txt_telefono_empleado"]    = $row["tele_empl"];
                $output["txt_direccion_empleado"]   = $row["dire_empl"];
                $output["select_cargo_empleado"]    = $row["carg_empl"];
                $output["txt_fecha_ingreso"]        = $row["fecha_ingreso_fmt"];
                $output["txt_fecha_nacimiento"]     = $row["fecha_naci_fmt"];
                //$output["select_esta_empl"]         = $row["esta_empl"];
                $output["txt_profesion"]            = $row["prof_empl"];
                $output["txt_anio_grado"]           = $row["anio_grado_empl"];
                $output["txt_civil"]                = $row["esta_civil_empl"]  ?? '';
                $output["txt_estrato"]              = $row["estra_empl"]       ?? '';
                $output["txt_correo"]               = $row["email_empl"]        ?? '';
                $output["txt_fecha_exp"]            = $row["fecha_exp_fmt"]    ?? '';
                $output["txt_genero"]               = $row["genero_empleado"]    ?? '';
                $output["select_nivel_educativo"]   = $row["nivel_educativo"]    ?? '';
                $output["txt_fecha_retiro_cesantias"]      = $row["cesantias_empl"] ?? '';

                // ── Estado — 1=Activo, 0=Retirado ──
                $output["select_esta_empl"]          = $row["esta_empl"];
                $output["txt_estado_desc"]           = $row["esta_empl"] == 1 ? 'Activo' : 'Retirado';

                // ── Tipo documento — ID para el select, nombre para mostrar ──
                $output["txt_tipo_documento_empl"]  = $row["tpdc_empl"];   // ID → para Select2
                $output["txt_tipo_documento_nombre"] = $row["tpdc_nombre"]  ?? ''; // nombre → informativo

                // ── Tipo documento — ID para el select, nombre para mostrar ──
                $output["select_tipo_contrato"]  = $row["tipo_contrato_id"];   // ID → para Select2
                $output["txt_tipo_contrato_nombre"] = $row["tipo_cont_desc"]  ?? ''; // nombre → informativo

                // ── Lugar expedición — ID para el select, desc para preview ──
                $output["txt_lugar_exp"]            = $row["lugar_exp_empl"] ?? ''; // ID → para Select2
                $output["txt_lugar_exp_desc"]       = $row["lugar_desc"]     ?? ''; // nombre → preview
                $output["txt_depto_lugar"]          = $row["depto_lugar"]    ?? '';
                //$output["txt_fecha_exp"]            = $row["fecha_exp_fmt"]    ?? '';

                // ── Grupo sanguíneo ──
                $output["txt_rh"]                   = $row["grupo_sang_id"] ?? '';
                $output["txt_rh_nombre"]            = $row["grupo_sang_desc"] ?? '';

                // ── Campos preview ──
                $output["txt_cargo"]                = $row["cargo_id"];
                $output["txt_cargo_desc"]           = $row["cargo_desc"]     ?? '';
                $output["select_dependencia"]          = $row["dependencia_id"];
                $output["txt_dependencia_desc"]     = $row["dependencia_descripcion"]     ?? '';

                // ── Salario ──
                $salario = (float)($row["salario_empl"] ?? 0);
                $output["txt_salario"]              = number_format($salario, 0, ',', '.');
                $output["txt_salario_letras"]       = numeroALetras($salario);

                // ── Auxilio de transporte ──
                $smmlv   = 1750905;
                $auxilio = ($salario > 0 && $salario <= ($smmlv * 2)) ? 200000 : 0;
                $output["txt_auxilio_letras"]       = $auxilio > 0 ? numeroALetras($auxilio) : null;

                // ── Bonificaciones ──
                $output["txt_bonif_letras"]         = null;

                // ── Fechas en letras ──
                $output["txt_fecha_ingreso_letras"] = fechaALetras($row["fecha_ingreso_empl"]);
                $output["txt_fecha_hoy_letras"]     = fechaALetras(date('Y-m-d'));

                // ── Datos empresa ──
                $output["txt_ciudad_expedicion"]    = 'San Juan de Girón (Santander)';
                $output["txt_nombre_firmante"]      = 'JORGE CARDENAS';
                $output["txt_cargo_firmante"]       = 'Director de Gestión Humana y Jurídico.';
                $output["txt_telefono_empresa"]     = '3134008506 o 3175424050';
                $output["txt_correo_empresa"]       = 'rhumano@asfaltart.com o dirhumano@asfaltart.com';

                $output["txt_fecha_retiro"] = $row["fecha_retiro_empl"]
                    ? (new DateTime($row["fecha_retiro_empl"]))->format('d/m/Y')
                    : null;
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

    case 'listar_activos':

        $datos = $empleado->get_empledo();

        $data = array();
        foreach ($datos as $row) {

            $sub_array = array();
            $sub_array[] = $row["id_empl"];
            $sub_array[] = $row["cedu_empl"];
            $sub_array[] = $row["nomb_empl"];
            $sub_array[] = $row["esta_empl"];
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
        $fecha_ingreso = $_POST["fecha_ingreso"];
        $fecha_nacimiento = $_POST["fecha_nacimiento"];
        $direccion = $_POST["direccion"];
        $celular = $_POST["celular"];

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

    case "inactivar_masivo":
        $ids = $_POST["ids"];
        $result = $empleado->inactivar_empleados($ids);
        echo json_encode(["success" => $result]);
        break;


    case 'getEmployee':
        $id = $_SESSION["id_empl"]; // ID del empleado logueado

        error_log("ID de sesión: " . $id);

        $datos = $empleado->get_empleado_por_id($id);

        if (is_array($datos) && count($datos) > 0) {
            $row = $datos[0];
            echo json_encode($row);
        } else {
            echo json_encode(["error" => "Empleado no encontrado"]);
        }
        break;

    case 'exportar_egreso_pdf':
        $codigo   = $_GET["codigo"]   ?? null;
        $radicado = $_GET["radicado"] ?? '';

        if (!$codigo) {
            echo json_encode(['success' => false, 'error' => 'Código de empleado requerido']);
            break;
        }

        // ── Obtener datos del empleado ──
        $datos = $empleado->get_empledo_x_id($codigo);

        if (!$datos || count($datos) == 0) {
            echo json_encode(['success' => false, 'error' => 'Empleado no encontrado']);
            break;
        }

        $row = $datos[0];

        // ── Preparar variables para la vista PDF ──
        $pdf_nombre          = $row["nomb_empl"]           ?? '';
        $pdf_cedula          = $row["cedu_empl"]            ?? '';
        $pdf_lugar_exp       = $row["lugar_desc"]           ?? '';
        $pdf_fecha_retiro    = fechaALetras($row["fecha_retiro_empl"] ?? '');
        $pdf_fecha_retiro_fmt = $row["fecha_retiro_empl"]
            ? (new DateTime($row["fecha_retiro_empl"]))->format('d/m/Y')
            : '';
        $pdf_cargo           = $row["cargo_desc"]           ?? '';
        $pdf_radicado        = $radicado;
        $pdf_fecha_carta     = fechaALetras(date('Y-m-d'));
        $pdf_ciudad          = 'San Juan de Girón (Santander)';
        $pdf_nombre_firmante = 'JORGE CARDENAS';
        $pdf_cargo_firmante  = 'Director de Gestión Humana y Jurídico.';

        // ── Incluir vista PDF ──
        require_once '../view/PDF/examen_egreso.php';
        break;

    case 'exportar_cesantias_pdf':
        $codigo   = $_GET["codigo"]   ?? null;
        $radicado = $_GET["radicado"] ?? '';
        $fondo    = $_GET["fondo"]    ?? '';

        if (!$codigo) {
            echo json_encode(['success' => false, 'error' => 'Código requerido']);
            break;
        }

        $datos = $empleado->get_empledo_x_id($codigo);
        if (!$datos || count($datos) == 0) {
            echo json_encode(['success' => false, 'error' => 'Empleado no encontrado']);
            break;
        }

        $row = $datos[0];

        $pdf_nombre          = $row["nomb_empl"]           ?? '';
        $pdf_cedula          = $row["cedu_empl"]            ?? '';
        $pdf_lugar_exp       = $row["lugar_desc"]           ?? '';
        $pdf_fecha_retiro    = fechaALetras($row["fecha_retiro_empl"] ?? '');
        $pdf_fecha_retiro_fmt = $row["fecha_retiro_empl"]
            ? (new DateTime($row["fecha_retiro_empl"]))->format('d/m/Y')
            : '';
        $pdf_fondo           = $fondo;
        $pdf_radicado        = $radicado;
        $pdf_fecha_carta     = fechaALetras(date('Y-m-d'));
        $pdf_ciudad          = 'San Juan de Girón (Santander)';
        $pdf_nombre_firmante = 'JORGE CARDENAS';
        $pdf_cargo_firmante  = 'Director de Gestión Humana y Jurídico.';

        require_once '../view/PDF/cesantias.php';
        break;
}
