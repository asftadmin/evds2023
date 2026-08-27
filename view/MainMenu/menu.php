<?php

require_once("../../models/Menu.php");

$menu = new Menu();

// Validar si el empleado autenticado tiene una relación activa como Jefe Inmediato.
$es_jefe = $menu->es_jefe_activo(
    (int)($_SESSION["id_empl"] ?? 0)
);

$_SESSION["es_jefe"] = $es_jefe ? 1 : 0;

// Obtener los menús permitidos para el rol principal y, si aplica, Jefe Inmediato.
$datos = $menu->mostrar_menu_x_rol(
    $_SESSION["user_rol"],
    $es_jefe
);

// Separar accesos directos de los menús agrupados.
$menus_directos = [];
$grupos_menu = [];

foreach ($datos as $row) {

    // Solo mostrar opciones cuyo permiso esté habilitado.
    if ($row["perm_usua"] !== "Si") {
        continue;
    }

    // Los menús sin grupo se muestran directamente en el sidebar.
    if (empty($row["menu_grupo"])) {
        $menus_directos[] = $row;
        continue;
    }

    $grupo_id = $row["grme_id"];

    // Crear el grupo solo cuando tenga al menos un menú autorizado.
    if (!isset($grupos_menu[$grupo_id])) {
        $grupos_menu[$grupo_id] = [
            "nombre" => $row["grme_nomb"],
            "icono" => $row["grme_icon"],
            "menus" => []
        ];
    }

    $grupos_menu[$grupo_id]["menus"][] = $row;
}

// Identificar la página actual para marcar la opción activa.
$pagina_actual = basename($_SERVER["PHP_SELF"]);

?>


<!-- ========================================================= -->
<!-- MAIN SIDEBAR                                              -->
<!-- ========================================================= -->

<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <!-- Logo -->
    <div class="brand-link sidebar-brand-custom">

        <img src="../../public/img/logosidebar.svg" alt="ASFALTAR S.A.S" class="sidebar-brand-logo">

    </div>


    <!-- ===================================================== -->
    <!-- SIDEBAR                                               -->
    <!-- ===================================================== -->

    <div class="sidebar">

        <div class="sidebar-menu-title">
            Menú principal
        </div>


        <!-- ================================================= -->
        <!-- SIDEBAR MENU                                      -->
        <!-- ================================================= -->

        <nav class="sidebar-navigation">

            <ul class="nav nav-pills nav-sidebar flex-column sidebar-menu-custom" data-widget="treeview" role="menu"
                data-accordion="false">


                <!-- ========================================= -->
                <!-- MENÚS DIRECTOS                            -->
                <!-- ========================================= -->

                <?php foreach ($menus_directos as $row) { ?>

                    <?php

                    // Construir ruta y validar si la opción está activa.
                    $ruta_menu = $row["menu_ruta"] . $row["menu_ident"] . ".php";
                    $archivo_menu = $row["menu_ident"] . ".php";
                    $menu_activo = $pagina_actual === $archivo_menu;

                    ?>

                    <li class="nav-item sidebar-direct-item">

                        <a href="<?php echo htmlspecialchars($ruta_menu); ?>"
                            class="nav-link direct-menu-link <?php echo $menu_activo ? 'active' : ''; ?>">

                            <i class="<?php echo htmlspecialchars($row["menu_icon"]); ?> nav-icon sidebar-menu-icon"></i>

                            <p class="sidebar-menu-text">
                                <?php echo htmlspecialchars($row["menu_nomb"]); ?>
                            </p>

                        </a>

                    </li>

                <?php } ?>


                <!-- ========================================= -->
                <!-- GRUPOS DEL MENÚ                           -->
                <!-- ========================================= -->

                <?php foreach ($grupos_menu as $grupo) { ?>

                    <?php

                    // Verificar si algún menú del grupo corresponde a la página actual.
                    $grupo_activo = false;

                    foreach ($grupo["menus"] as $item) {

                        if ($pagina_actual === $item["menu_ident"] . ".php") {
                            $grupo_activo = true;
                            break;
                        }
                    }

                    ?>

                    <li class="nav-item sidebar-group-item <?php echo $grupo_activo ? 'menu-open' : ''; ?>">

                        <!-- Encabezado del grupo -->
                        <a href="#" class="nav-link menu-group-link">

                            <i class="<?php echo htmlspecialchars($grupo["icono"]); ?> nav-icon sidebar-group-icon"></i>

                            <p class="sidebar-group-text">

                                <?php echo htmlspecialchars($grupo["nombre"]); ?>

                                <i class="right fas fa-chevron-left sidebar-group-arrow"></i>

                            </p>

                        </a>


                        <!-- Submenús -->
                        <ul class="nav nav-treeview sidebar-submenu">

                            <?php foreach ($grupo["menus"] as $row) { ?>

                                <?php

                                // Construir ruta y validar si el submenú está activo.
                                $ruta_menu = $row["menu_ruta"] . $row["menu_ident"] . ".php";
                                $archivo_menu = $row["menu_ident"] . ".php";
                                $menu_activo = $pagina_actual === $archivo_menu;

                                ?>

                                <li class="nav-item sidebar-submenu-item">

                                    <a href="<?php echo htmlspecialchars($ruta_menu); ?>"
                                        class="nav-link sidebar-submenu-link <?php echo $menu_activo ? 'active' : ''; ?>">

                                        <i
                                            class="<?php echo htmlspecialchars($row["menu_icon"]); ?> nav-icon sidebar-submenu-icon"></i>

                                        <p class="sidebar-submenu-text">
                                            <?php echo htmlspecialchars($row["menu_nomb"]); ?>
                                        </p>

                                    </a>

                                </li>

                            <?php } ?>

                        </ul>

                    </li>

                <?php } ?>


            </ul>

        </nav>
        <!-- /.sidebar-menu -->

    </div>
    <!-- /.sidebar -->

</aside>