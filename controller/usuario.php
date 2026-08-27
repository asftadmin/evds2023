<?php

require_once("../config/conexion.php");
require_once("../models/Usuarios.php");


$usuarios = new Usuario();

/**
 * Todas las respuestas de este controlador serán JSON.
 */
header('Content-Type: application/json; charset=utf-8');


switch ($_GET["op"]) {


    /*
     * =========================================================
     * CREAR USUARIO
     * =========================================================
     *
     * Recibe los datos enviados mediante AJAX desde la vista.
     * El modelo será responsable de:
     *
     * 1. Validar que el empleado exista.
     * 2. Validar que no tenga usuario asignado.
     * 3. Validar que el usuario no exista.
     * 4. Validar que el rol exista.
     * 5. Crear el registro en usuarios.
     * 6. Actualizar empleados.user_empl.
     * 7. Manejar la transacción.
     */
    case "guardaryeditar":

        /*
     * Identifica si se está creando o editando.
     */
        $user_id = isset($_POST["user_id"])
            ? $_POST["user_id"]
            : "";


        /*
     * ========================================================
     * CREAR USUARIO
     * ========================================================
     */
        if (empty($user_id)) {

            if (
                isset($_POST["empleado_id"])
                && isset($_POST["user_nick"])
                && isset($_POST["user_pass"])
                && isset($_POST["user_rol"])
            ) {

                $resultado =
                    $usuarios->insertar_usuario(
                        $_POST["empleado_id"],
                        trim($_POST["user_nick"]),
                        $_POST["user_pass"],
                        $_POST["user_rol"]
                    );


                echo json_encode($resultado);
            } else {

                echo json_encode([
                    "success" => false,
                    "message" =>
                    "Datos incompletos para crear el usuario."
                ]);
            }


            /*
     * ========================================================
     * EDITAR USUARIO
     * ========================================================
     */
        } else {

            if (
                isset($_POST["user_nick"])
                && isset($_POST["user_rol"])
            ) {

                $user_pass =
                    isset($_POST["user_pass"])
                    ? $_POST["user_pass"]
                    : "";


                $resultado =
                    $usuarios->editar_usuario(
                        $user_id,
                        trim($_POST["user_nick"]),
                        $user_pass,
                        $_POST["user_rol"]
                    );


                echo json_encode($resultado);
            } else {

                echo json_encode([
                    "success" => false,
                    "message" =>
                    "Datos incompletos para editar el usuario."
                ]);
            }
        }

        break;


    /*
     * =========================================================
     * LISTAR USUARIOS
     * =========================================================
     *
     * Consulta los usuarios registrados y prepara la información
     * en el formato utilizado actualmente por DataTables.
     */
    case "listarUsuarios":

        $datos = $usuarios->mostrar_usuarios();

        $data = array();


        foreach ($datos as $row) {

            $sub_array = array();


            /*
             * Documento del empleado.
             */
            $sub_array[] = $row["cedu_empl"];


            /*
             * Nombre del empleado.
             */
            $sub_array[] = $row["nomb_empl"];


            /*
             * Nombre de usuario.
             */
            $sub_array[] = $row["user_nick"];


            /*
             * Rol asignado.
             */
            $sub_array[] =
                '<div class="text-center">
                    <span class="badge badge-info">'
                . htmlspecialchars(
                    $row["rol_nomb"],
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '</span>
                </div>';


            /*
             * Acciones.
             *
             * Por ahora únicamente dejamos preparado el espacio.
             * Más adelante podemos incorporar edición de rol,
             * cambio de contraseña o administración del usuario.
             */
            $sub_array[] = '
                <div class="text-center">

                    <button
                        type="button"
                        class="btn btn-warning btn-sm btn_editar_usuario"
                        data-user_id="' . $row["user_id"] . '">

                        <i class="fas fa-edit"></i>

                    </button>

                </div>
            ';


            $data[] = $sub_array;
        }


        /*
         * Estructura utilizada actualmente por los DataTables
         * del proyecto.
         */
        $results = array(

            "sEcho" => 1,

            "iTotalRecords" =>
            count($data),

            "iTotalDisplayRecords" =>
            count($data),

            "aaData" =>
            $data

        );


        echo json_encode($results);

        break;



    /*
     * =========================================================
     * EMPLEADOS SIN USUARIO
     * =========================================================
     *
     * Se utilizará para llenar el Select2 de empleados.
     *
     * Solo deben aparecer empleados activos que todavía
     * no tengan relación mediante empleados.user_empl.
     */
    case "listarEmpleadosSinUsuario":

        $datos =
            $usuarios->listar_empleados_sin_usuario();


        $data = array();


        foreach ($datos as $row) {

            $data[] = array(

                "id" =>
                $row["id_empl"],

                "text" =>
                $row["cedu_empl"]
                    . " - "
                    . $row["nomb_empl"]

            );
        }


        echo json_encode([
            "results" => $data
        ]);

        break;



    /*
     * =========================================================
     * LISTAR ROLES
     * =========================================================
     *
     * Los roles se consultan directamente desde la tabla rol.
     *
     * No se deben quemar números de rol en JavaScript ni PHP.
     */
    case "listarRoles":

        $datos =
            $usuarios->listar_roles();


        $data = array();


        foreach ($datos as $row) {

            $data[] = array(

                "id" =>
                $row["rol_id"],

                "text" =>
                $row["rol_nomb"]

            );
        }


        echo json_encode([
            "results" => $data
        ]);

        break;



    /*
     * =========================================================
     * MOSTRAR USUARIO
     * =========================================================
     *
     * Permitirá posteriormente cargar los datos de un usuario
     * en el formulario para editar el rol.
     */
    case "mostrar":

        if (isset($_POST["user_id"])) {

            $datos =
                $usuarios->mostrar_usuario(
                    $_POST["user_id"]
                );


            if (is_array($datos)) {

                echo json_encode([
                    'success' => true,
                    'data' => $datos
                ]);
            } else {

                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado.'
                ]);
            }
        } else {

            echo json_encode([
                'success' => false,
                'message' => 'Usuario no especificado.'
            ]);
        }

        break;



    /*
     * =========================================================
     * OPERACIÓN NO VÁLIDA
     * =========================================================
     */
    default:

        echo json_encode([
            'success' => false,
            'message' => 'Operación no válida.'
        ]);

        break;
}
