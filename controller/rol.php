<?php

require_once("../config/conexion.php");
require_once("../models/Rol.php");

$rol = new Rol();

// Todas las respuestas del controlador serán JSON.
header('Content-Type: application/json; charset=utf-8');

$op = isset($_GET["op"])
    ? $_GET["op"]
    : "";


switch ($op) {


    // =========================================================
    // CREAR O EDITAR ROL
    // =========================================================
    case "guardaryeditar":

        $rol_id = isset($_POST["rol_id"])
            ? trim($_POST["rol_id"])
            : "";

        $rol_nomb = isset($_POST["nomb_rol"])
            ? trim($_POST["nomb_rol"])
            : "";


        if ($rol_nomb === "") {

            echo json_encode([
                "success" => false,
                "message" => "Debe ingresar el nombre del rol."
            ]);

            break;
        }


        // Si no existe rol_id se crea un nuevo rol.
        if ($rol_id === "") {

            $resultado = $rol->insertar_rol(
                $rol_nomb
            );

            echo json_encode($resultado);

            break;
        }


        // Para edición también se requiere el estado.
        if (!isset($_POST["esta_rol"])) {

            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el estado del rol."
            ]);

            break;
        }


        $resultado = $rol->editar_rol(
            (int)$rol_id,
            $rol_nomb,
            (int)$_POST["esta_rol"]
        );


        echo json_encode($resultado);

        break;


    // =========================================================
    // LISTAR ROLES
    // =========================================================
    case "listar":

        $datos = $rol->listar_rol();

        $data = array();


        foreach ($datos as $row) {

            $sub_array = array();

            $rol_id = (int)$row["rol_id"];
            $rol_esta = (int)$row["rol_esta"];


            // Código del rol.
            $sub_array[] = $rol_id;


            // Nombre del rol.
            $sub_array[] = htmlspecialchars(
                $row["rol_nomb"],
                ENT_QUOTES,
                "UTF-8"
            );


            // Estado visual del rol.
            if ($rol_esta === 1) {

                $sub_array[] = '
                    <div class="text-center">
                        <span class="badge badge-success">
                            Activo
                        </span>
                    </div>
                ';
            } else {

                $sub_array[] = '
                    <div class="text-center">
                        <span class="badge badge-secondary">
                            Inactivo
                        </span>
                    </div>
                ';
            }


            // Botón para activar o inactivar el rol.
            if ($rol_esta === 1) {

                $boton_estado = '
                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        onclick="cambiarEstadoRol('
                            . $rol_id
                            . ', 0)"
                        title="Inactivar rol">

                        <i class="fas fa-ban"></i>

                    </button>
                ';
            } else {

                $boton_estado = '
                    <button
                        type="button"
                        class="btn btn-success btn-sm"
                        onclick="cambiarEstadoRol('
                            . $rol_id
                            . ', 1)"
                        title="Activar rol">

                        <i class="fas fa-check"></i>

                    </button>
                ';
            }


            // Acciones disponibles para cada rol.
            $sub_array[] = '
                <div class="text-center">

                    <button
                        type="button"
                        class="btn btn-warning btn-sm"
                        onclick="editar(' . $rol_id . ')"
                        title="Editar rol">

                        <i class="fas fa-edit"></i>

                    </button>


                    <button
                        type="button"
                        class="btn btn-info btn-sm"
                        onclick="permisos(' . $rol_id . ')"
                        title="Administrar permisos">

                        <i class="fas fa-cogs"></i>

                    </button>


                    ' . $boton_estado . '

                </div>
            ';


            $data[] = $sub_array;
        }


        // Estructura utilizada por DataTables.
        echo json_encode([
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        ]);

        break;


    // =========================================================
    // MOSTRAR ROL
    // =========================================================
    case "mostrar":

        if (!isset($_POST["rol_id"])) {

            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el rol."
            ]);

            break;
        }


        $datos = $rol->mostrar_rol_x_id(
            (int)$_POST["rol_id"]
        );


        if (!$datos) {

            echo json_encode([
                "success" => false,
                "message" => "El rol seleccionado no existe."
            ]);

            break;
        }


        echo json_encode([
            "success" => true,
            "data" => $datos
        ]);

        break;


    // =========================================================
    // CAMBIAR ESTADO DEL ROL
    // =========================================================
    case "cambiar_estado":

        if (
            !isset($_POST["rol_id"])
            || !isset($_POST["rol_esta"])
        ) {

            echo json_encode([
                "success" => false,
                "message" => "Datos incompletos para cambiar el estado."
            ]);

            break;
        }


        $resultado = $rol->cambiar_estado_rol(
            (int)$_POST["rol_id"],
            (int)$_POST["rol_esta"]
        );


        echo json_encode($resultado);

        break;


    // =========================================================
    // LISTAR PERMISOS DEL ROL
    // =========================================================
    case "listar_permisos":

        if (!isset($_POST["rol_id"])) {

            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el rol."
            ]);

            break;
        }


        $rol_id = (int)$_POST["rol_id"];

        // Consulta primero el rol para mostrar su información.
        $datos_rol = $rol->mostrar_rol_x_id(
            $rol_id
        );


        if (!$datos_rol) {

            echo json_encode([
                "success" => false,
                "message" => "El rol seleccionado no existe."
            ]);

            break;
        }


        // Consulta todos los menús disponibles para el rol.
        $datos = $rol->listar_permisos_rol(
            $rol_id
        );


        $data = array();


        foreach ($datos as $row) {

            $menu_id = (int)$row["menu_id"];
            $acceso = (int)$row["acceso"];


            // Define el estado inicial del switch.
            $checked = $acceso === 1
                ? "checked"
                : "";


            // Define el texto visual del acceso.
            $texto_acceso = $acceso === 1
                ? '<span class="permission-text text-success">
                        Con acceso
                   </span>'
                : '<span class="permission-text text-danger">
                        Sin acceso
                   </span>';


            $sub_array = array();


            // ID del menú.
            $sub_array[] = $menu_id;


            // Nombre e icono del menú.
            $sub_array[] = '
                <i class="'
                    . htmlspecialchars(
                        $row["menu_icon"],
                        ENT_QUOTES,
                        "UTF-8"
                    )
                    . ' mr-2 text-muted">
                </i>

                <strong>'
                    . htmlspecialchars(
                        $row["menu_nomb"],
                        ENT_QUOTES,
                        "UTF-8"
                    )
                    . '</strong>
            ';


            // Identificador del menú.
            $sub_array[] = htmlspecialchars(
                $row["menu_ident"],
                ENT_QUOTES,
                "UTF-8"
            );


            // Ruta del menú.
            $sub_array[] = htmlspecialchars(
                $row["menu_ruta"],
                ENT_QUOTES,
                "UTF-8"
            );


            // Estado del menú.
            $sub_array[] = '
                <div class="text-center">

                    <span class="badge badge-success">
                        Activo
                    </span>

                </div>
            ';


            // Switch para conceder o revocar acceso.
            $sub_array[] = '
                <div class="text-center">

                    <label class="permission-switch">

                        <input
                            type="checkbox"
                            class="permiso_menu"
                            value="' . $menu_id . '"
                            ' . $checked . '>

                        <span class="permission-slider"></span>

                    </label>

                    ' . $texto_acceso . '

                </div>
            ';


            $data[] = $sub_array;
        }


        echo json_encode([
            "success" => true,

            "rol" => [
                "rol_id" =>
                    (int)$datos_rol["rol_id"],

                "rol_nomb" =>
                    $datos_rol["rol_nomb"],

                "rol_esta" =>
                    (int)$datos_rol["rol_esta"]
            ],

            "total_menus" =>
                count($datos),

            "total_acceso" =>
                count(
                    array_filter(
                        $datos,
                        function ($item) {
                            return (int)$item["acceso"] === 1;
                        }
                    )
                ),

            "aaData" =>
                $data
        ]);

        break;


    // =========================================================
    // GUARDAR PERMISOS DEL ROL
    // =========================================================
    case "guardar_permisos":

        if (!isset($_POST["rol_id"])) {

            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el rol."
            ]);

            break;
        }


        /*
         * La vista enviará únicamente los menu_id que quedaron
         * seleccionados mediante los switches.
         */
        $menus_acceso = isset($_POST["menus_acceso"])
            ? $_POST["menus_acceso"]
            : array();


        // Garantiza que siempre se envíe un arreglo al modelo.
        if (!is_array($menus_acceso)) {

            $menus_acceso = array();
        }


        $resultado = $rol->guardar_permisos_rol(
            (int)$_POST["rol_id"],
            $menus_acceso
        );


        echo json_encode($resultado);

        break;


    // =========================================================
    // OPERACIÓN NO VÁLIDA
    // =========================================================
    default:

        echo json_encode([
            "success" => false,
            "message" => "Operación no válida."
        ]);

        break;
}