<?php 
               
    require_once("../../models/Menu.php");
    $menu = new Menu();
    // La relación activa es la fuente vigente. Así una sesión iniciada antes
    // de asignar o activar al jefe actualiza el menú sin exigir otro ingreso.
    $es_jefe = $menu->es_jefe_activo(
        (int)($_SESSION["id_empl"] ?? 0)
    );
    $_SESSION["es_jefe"] = $es_jefe ? 1 : 0;

    $datos =$menu->mostrar_menu_x_rol($_SESSION["user_rol"], $es_jefe);
               
?>


<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <div class="image">
        <img src="../../public/img/logosidebar.svg" alt="ASFALTAR S.A.S">
    </div>

    <!-- Sidebar -->
    <div class="sidebar">

        <!-- Sidebar Menu -->
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->

                <?php 
                
                    foreach ($datos as $row) {
                        if ($row["perm_usua"] == "Si") {
                        ?>
                <li class="nav-item">
                    <a href="<?php echo $row["menu_ruta"].$row["menu_ident"].'.php'; ?>" class="nav-link">
                        <i class="<?php echo $row["menu_icon"];?>"></i>
                        <p>
                            <?php echo $row["menu_nomb"]; ?>
                        </p>
                    </a>
                </li>
                <?php  
                        }



                    }
                
                ?>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
