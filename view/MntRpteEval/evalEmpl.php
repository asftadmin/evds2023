<?php
require_once "../../config/conexion.php";
if (isset($_SESSION["user_id"])) {

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php require_once("../MainHead/head.php") ?>
    <link rel="stylesheet" href="../../public/css/preop.css">
    <title>EVALUACIÓN DESEMPEÑO</title>


</head>

<body class="hold-transition sidebar-mini">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        <?php require_once("../MainNav/nav.php") ?>
        <!-- /.navbar -->

        <?php require_once("../MainMenu/menu.php") ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Reportes Evaluaciones</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item active">Reportes Evaluaciones</li>
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

                    </div>
                    <div class="card-body">
                        <form id="formReporte" method="POST" target="_blank">
                            <div class="row justify-content-center">
                                <div class="col-sm-4">
                                    <!-- text input -->
                                    <div class="form-group">
                                        <select class="form-control select2" name="nomb_empl" id="nomb_empl"
                                            style="width: 100%;" required>

                                        </select>

                                        <input type="hidden" id="codi_empl" name="codi_empl">

                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        <button id="btnDescargRpte" type="button"
                                            class="btn btn-flat bg-gradient-info"><i class="fas fa-file-download"></i>
                                            Descargar</button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                    <!-- /.card-body -->

                    <!-- /.card-footer-->
                </div>
                <!-- /.card -->

            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <?php require_once("../MainFooter/footer.php") ?>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <?php require_once("../MainJS/JS.php") ?>
    <script type="text/javascript" src="evalEmpl.js"></script>
</body>

</html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>