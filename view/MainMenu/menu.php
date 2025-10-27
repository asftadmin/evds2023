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

                <!-- <li class="nav-item" id="menuInicio">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-copy"></i>
                        <p>
                            Inicio
                        </p>
                    </a>
                </li>

                <li class="nav-item" id="menuEvaluacion">
                    <a href="..\evaluacion\evaluacion.php" class="nav-link">
                        <i class="nav-icon fas fa-copy"></i>
                        <p>
                            Evaluación
                        </p>
                    </a>
                </li>

                <li class="nav-item" id="menuEmpleados">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-copy"></i>
                        <p>
                            Empleados
                        </p>
                    </a>
                </li>

                <li class="nav-item" id="menuRoles">
                    <a href="..\MntRol\index.php" class="nav-link">
                        <i class="nav-icon fas fa-copy"></i>
                        <p>
                            Roles
                        </p>
                    </a>
                </li>

                <li class="nav-item" id="menuInformes">
                    <a href="#" class="nav-link">
                        <i class="nav-icon far fa-envelope"></i>
                        <p>
                            Informes
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" id="menuInfoGen">
                            <a href="..\informes\informes.php" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Inforemes Generales</p>
                            </a>
                        </li>
                        <li class="nav-item" id="menuInfoEmpl">
                            <a href="pages/mailbox/compose.html" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Informe Empleados</p>
                            </a>
                        </li>

                    </ul>
                </li> -->

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>