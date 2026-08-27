<?php

require_once("../config/conexion.php");
require_once("../models/Menu.php");

$menu = new Menu();

header("Content-Type: application/json; charset=utf-8");

$op = isset($_GET["op"]) ? $_GET["op"] : "";

switch ($op) {

    // Compatibilidad con el listado antiguo de permisos por rol.
    case "listar":

        if (!isset($_POST["rol_id"])) {
            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el rol."
            ]);
            break;
        }

        $datos = $menu->mostrar_menu_x_rol((int)$_POST["rol_id"]);
        $data = [];

        foreach ($datos as $row) {
            $perm_id = (int)$row["perm_id"];

            $boton = $row["perm_usua"] === "Si"
                ? '<button type="button"
                        class="btn btn-info btn-sm btn-desactivar-permiso"
                        data-id="' . $perm_id . '">
                        Si
                   </button>'
                : '<button type="button"
                        class="btn btn-danger btn-sm btn-activar-permiso"
                        data-id="' . $perm_id . '">
                        No
                   </button>';

            $data[] = [
                htmlspecialchars($row["menu_nomb"], ENT_QUOTES, "UTF-8"),
                '<div class="text-center">' . $boton . '</div>'
            ];
        }

        echo json_encode([
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        ]);

        break;


    // Lista los menús para la nueva vista administrativa.
    case "listar_menus":

        $datos = $menu->listar_menu();
        $data = [];

        foreach ($datos as $row) {
            $menu_id = (int)$row["menu_id"];
            $menu_esta = (int)$row["menu_esta"];

            $nombre = htmlspecialchars($row["menu_nomb"], ENT_QUOTES, "UTF-8");
            $ruta = htmlspecialchars($row["menu_ruta"], ENT_QUOTES, "UTF-8");
            $ident = htmlspecialchars($row["menu_ident"], ENT_QUOTES, "UTF-8");
            $icono = htmlspecialchars($row["menu_icon"], ENT_QUOTES, "UTF-8");

            $grupo = !empty($row["grme_nomb"])
                ? htmlspecialchars($row["grme_nomb"], ENT_QUOTES, "UTF-8")
                : '<span class="text-muted">Sin grupo</span>';

            $estado = $menu_esta === 1
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-secondary">Inactivo</span>';

            // Los botones solo llevan clases y datos. Los eventos estarán en menu.js.
            $boton_estado = $menu_esta === 1
                ? '<button type="button"
                        class="btn btn-danger btn-sm btn-estado-menu"
                        data-id="' . $menu_id . '"
                        data-estado="0"
                        title="Inactivar menú">
                        <i class="fas fa-ban"></i>
                   </button>'
                : '<button type="button"
                        class="btn btn-success btn-sm btn-estado-menu"
                        data-id="' . $menu_id . '"
                        data-estado="1"
                        title="Activar menú">
                        <i class="fas fa-check"></i>
                   </button>';

            $acciones = '
                <div class="text-center">
                    <button type="button"
                        class="btn btn-warning btn-sm btn-editar-menu"
                        data-id="' . $menu_id . '"
                        title="Editar menú">
                        <i class="fas fa-edit"></i>
                    </button>
                    ' . $boton_estado . '
                </div>';

            $data[] = [
                $menu_id,
                '<i class="' . $icono . ' mr-2 text-muted"></i><strong>' . $nombre . '</strong>',
                $grupo,
                $ruta,
                $ident,
                (int)$row["menu_orden"],
                '<div class="text-center">' . $estado . '</div>',
                $acciones
            ];
        }

        echo json_encode([
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        ]);

        break;


    // Crea o actualiza un menú.
    case "guardaryeditar":

        $menu_id = isset($_POST["menu_id"]) ? trim($_POST["menu_id"]) : "";
        $menu_nomb = isset($_POST["menu_nomb"]) ? trim($_POST["menu_nomb"]) : "";
        $menu_ruta = isset($_POST["menu_ruta"]) ? trim($_POST["menu_ruta"]) : "";
        $menu_ident = isset($_POST["menu_ident"]) ? trim($_POST["menu_ident"]) : "";
        $menu_icon = isset($_POST["menu_icon"]) ? trim($_POST["menu_icon"]) : "";
        $menu_grupo = isset($_POST["menu_grupo"]) ? trim($_POST["menu_grupo"]) : "";
        $menu_orden = isset($_POST["menu_orden"]) ? trim($_POST["menu_orden"]) : "";

        if ($menu_nomb === "" || $menu_ruta === "" || $menu_ident === "" || $menu_icon === "" || $menu_orden === "") {
            echo json_encode([
                "success" => false,
                "message" => "Debe completar los datos obligatorios del menú."
            ]);
            break;
        }

        if ($menu_id === "") {
            $resultado = $menu->insertar_menu(
                $menu_nomb,
                $menu_ruta,
                $menu_ident,
                $menu_icon,
                $menu_grupo,
                $menu_orden
            );

            echo json_encode($resultado);
            break;
        }

        if (!isset($_POST["menu_esta"])) {
            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el estado del menú."
            ]);
            break;
        }

        $resultado = $menu->editar_menu(
            (int)$menu_id,
            $menu_nomb,
            $menu_ruta,
            $menu_ident,
            $menu_icon,
            $menu_grupo,
            $menu_orden,
            (int)$_POST["menu_esta"]
        );

        echo json_encode($resultado);

        break;


    // Consulta un menú para edición.
    case "mostrar":

        if (!isset($_POST["menu_id"])) {
            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el menú."
            ]);
            break;
        }

        $datos = $menu->mostrar_menu_x_id((int)$_POST["menu_id"]);

        if (!$datos) {
            echo json_encode([
                "success" => false,
                "message" => "El menú seleccionado no existe."
            ]);
            break;
        }

        echo json_encode([
            "success" => true,
            "data" => $datos
        ]);

        break;


    // Lista los grupos activos para el Select2.
    case "listar_grupos":

        $datos = $menu->listar_grupos_menu();
        $data = [];

        foreach ($datos as $row) {
            $data[] = [
                "id" => (int)$row["grme_id"],
                "text" => $row["grme_nomb"]
            ];
        }

        echo json_encode([
            "success" => true,
            "data" => $data
        ]);

        break;


    // Activa o inactiva un menú.
    case "cambiar_estado":

        if (!isset($_POST["menu_id"]) || !isset($_POST["menu_esta"])) {
            echo json_encode([
                "success" => false,
                "message" => "Datos incompletos para cambiar el estado."
            ]);
            break;
        }

        echo json_encode(
            $menu->cambiar_estado_menu(
                (int)$_POST["menu_id"],
                (int)$_POST["menu_esta"]
            )
        );

        break;


    // Compatibilidad temporal con permisos antiguos.
    case "activar":

        if (!isset($_POST["perm_id"])) {
            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el permiso."
            ]);
            break;
        }

        $menu->activar_menu((int)$_POST["perm_id"]);

        echo json_encode([
            "success" => true,
            "message" => "Permiso activado correctamente."
        ]);

        break;


    case "desactivar":

        if (!isset($_POST["perm_id"])) {
            echo json_encode([
                "success" => false,
                "message" => "Debe especificar el permiso."
            ]);
            break;
        }

        $menu->desactivar_menu((int)$_POST["perm_id"]);

        echo json_encode([
            "success" => true,
            "message" => "Permiso desactivado correctamente."
        ]);

        break;


    default:

        echo json_encode([
            "success" => false,
            "message" => "Operación no válida."
        ]);

        break;
}