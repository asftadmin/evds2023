<?php

require_once "../../config/conexion.php";

if (isset($_SESSION["user_id"])) {

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php require_once("../MainHead/head.php") ?>


    <!-- Select2 -->
    <link rel="stylesheet" href="../../public/plugins/select2/css/select2.min.css">

    <link rel="stylesheet" href="../../public/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">


    <title>ADMINISTRACIÓN DE ROLES</title>

</head>


<body class="hold-transition sidebar-mini layout-fixed layout-footer-fixed">

    <div class="wrapper">


        <!-- Navbar -->
        <?php require_once("../MainNav/nav.php") ?>


        <!-- Main Sidebar -->
        <?php require_once("../MainMenu/menu.php") ?>


        <!-- Content Wrapper -->
        <div class="content-wrapper">


            <!-- ================================================= -->
            <!-- CONTENT HEADER                                    -->
            <!-- ================================================= -->

            <section class="content-header">

                <div class="container-fluid">

                    <div class="row mb-2">


                        <div class="col-sm-6">

                            <h1>
                                Administración de Roles
                            </h1>

                        </div>


                        <div class="col-sm-6">

                            <ol class="breadcrumb float-sm-right">

                                <li class="breadcrumb-item">

                                    <a href="#">
                                        Inicio
                                    </a>

                                </li>

                                <li class="breadcrumb-item active">
                                    Roles
                                </li>

                            </ol>

                        </div>


                    </div>

                </div>

            </section>


            <!-- ================================================= -->
            <!-- MAIN CONTENT                                      -->
            <!-- ================================================= -->

            <section class="content">


                <!-- ================================================= -->
                <!-- CARD ROLES REGISTRADOS                            -->
                <!-- ================================================= -->

                <div class="card card-outline card-dark">


                    <div class="card-header">

                        <h3 class="card-title">

                            <i class="fas fa-user-shield mr-2"></i>

                            Roles Registrados

                        </h3>


                        <div class="card-tools">

                            <button type="button" id="btnNuevoRol" class="btn btn-info btn-sm">

                                <i class="fas fa-plus mr-1"></i>

                                Nuevo Rol

                            </button>

                        </div>

                    </div>


                    <div class="card-body">


                        <div class="alert alert-light border">

                            <i class="fas fa-info-circle text-info mr-1"></i>

                            Desde esta sección puede crear roles,
                            modificar su estado y administrar los menús
                            disponibles para cada rol.

                        </div>


                        <div class="table-responsive">


                            <table id="roles_data" class="table table-hover table-striped table-bordered"
                                style="width:100%;">


                                <thead class="thead-light">

                                    <tr>


                                        <th style="width: 90px;">

                                            <i class="fas fa-hashtag mr-1"></i>

                                            Código

                                        </th>


                                        <th>

                                            <i class="fas fa-user-tag mr-1"></i>

                                            Nombre del Rol

                                        </th>


                                        <th class="text-center" style="width: 120px;">

                                            Estado

                                        </th>


                                        <th class="text-center" style="width: 180px;">

                                            Acciones

                                        </th>


                                    </tr>

                                </thead>


                                <tbody>

                                </tbody>


                            </table>


                        </div>


                    </div>


                </div>


            </section>
            <!-- /.content -->


        </div>
        <!-- /.content-wrapper -->


        <!-- Footer -->
        <?php require_once("../MainFooter/footer.php") ?>


        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">

        </aside>


    </div>
    <!-- ./wrapper -->


    <!-- ========================================================= -->
    <!-- MODALES                                                   -->
    <!-- ========================================================= -->

    <?php require_once("mantenimiento_rol.php") ?>

    <?php require_once("mantenimiento_permisos.php") ?>


    <!-- ========================================================= -->
    <!-- JS GENERAL                                                -->
    <!-- ========================================================= -->

    <?php require_once("../MainJS/JS.php") ?>


    <!-- Select2 -->
    <script src="../../public/plugins/select2/js/select2.full.min.js">
    </script>


    <!-- ========================================================= -->
    <!-- JS DEL MÓDULO                                             -->
    <!-- ========================================================= -->

    <script type="text/javascript" src="mntrol.js">
    </script>


</body>

</html>


<?php

} else {

    header(
        "location:"
            . Conectar::ruta()
            . "index.php"
    );

    exit();
}

?>