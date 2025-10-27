<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once("../MainHead/head.php")?>
    <!-- Select2 -->
    <link rel="stylesheet" href="../../public/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../../public/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="../../public/css/preop.css">

    <link rel="stylesheet" href="../../public/css/style.css">
    <title>EVALUACIÓN DESEMPEÑO</title>


</head>

<body class="hold-transition sidebar-mini">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        <?php require_once("../MainNav/nav.php")?>
        <!-- /.navbar -->

        <?php require_once("../MainMenu/menu.php")?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Mantenimiento Roles</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item active">Roles</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">

                <!-- Default box -->
                <div class="card">
                    <div class="card-header">

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>

                        </div>
                        <button id="btnNuevoRol" type="button" class="btn btn-flat bg-gradient-info">Nuevo Rol</button>
                    </div>
                    <div class="card-body">

                        <table id="pre_data" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Nombre Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                            </tbody>

                        </table>


                    </div>
                    <!-- /.card-body -->

                </div>
                <!-- /.card -->

            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <?php require_once("../MainFooter/footer.php")?>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->
    <?php require_once("mantenimiento_rol.php")?>
    <?php require_once("mantenimiento_permisos.php")?>
    <?php require_once("../MainJS/JS.php")?>
    <!-- Select2 -->
    <script src="../../public/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript" src="mntrol.js"></script>

</body>

</html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>