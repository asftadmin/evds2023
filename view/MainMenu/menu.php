<?php 
               
    require_once("../../models/Menu.php");
    $menu = new Menu();
    $datos =$menu->mostrar_menu_x_rol($_SESSION["user_rol"]);                    
               
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