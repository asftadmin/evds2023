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
                                <h1>Certificado Laboral</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                    <li class="breadcrumb-item active">Certificado Laboral</li>
                                </ol>
                            </div>
                        </div>
                    </div><!-- /.container-fluid -->
                </section>

                <!-- Main content -->
                <section class="content">

                    <!-- Default box -->
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-10 offset-md-1">

                                    <div class="card card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-file-alt mr-2"></i>
                                                Generar Certificado Laboral
                                            </h3>
                                        </div>

                                        <div class="card-body">

                                            <form id="formReporte" method="POST">

                                                <div class="row">

                                                    <!-- EMPLEADO -->
                                                    <div class="col-md-5">
                                                        <div class="form-group">
                                                            <label class="mr-2">Empleado</label>
                                                            <select class="form-control select2bs4" name="nomb_empl"
                                                                id="nomb_empl" style="width:100%;" required>
                                                            </select>
                                                            <input type="hidden" id="codi_empl" name="codi_empl">
                                                        </div>
                                                    </div>

                                                    <!-- RADICADO -->
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Código interno de radicado</label>
                                                            <input type="text" class="form-control" name="radicado"
                                                                id="radicado" placeholder="Ej: ASF-GH-2.6-0000-26" required>
                                                        </div>
                                                    </div>

                                                    <!-- BOTÓN -->
                                                    <div class="col-md-3 d-flex align-items-start">
                                                        <div class="form-group w-100">
                                                            <button id="btnDescargRpte" type="button"
                                                                class="btn btn-info btn-block">
                                                                <i class="fas fa-file-download mr-1"></i>
                                                                Descargar
                                                            </button>
                                                        </div>
                                                    </div>

                                                </div>

                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>
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
        <script type="text/javascript" src="certificados.js"></script>
    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>