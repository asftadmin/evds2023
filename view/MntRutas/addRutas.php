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
        <link rel="stylesheet" href="../../public/css/style.css">
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
                                <h1>Agregar Ruta</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                    <li class="breadcrumb-item"><a href="rutas.php"> Rutas</a></li>
                                    <li class="breadcrumb-item active">Agregar Ruta</li>
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

                            <div class="form-group row mb-3">
                                <label for="txt_empleado" class="col-sm-3 col-form-label fw-bold">
                                    Empleado(s):
                                </label>
                                <div class="col-sm-9">
                                    <select class="form-control select2" multiple="multiple" name="txt_empleado[]"
                                        id="txt_empleado" required></select>
                                </div>
                            </div>

                            <div class="form-group row mb-3">
                                <label for="txt_jefe" class="col-sm-3 col-form-label fw-bold">
                                    Jefe / Supervisor:
                                </label>
                                <div class="col-sm-9">
                                    <select class="form-control select2" name="txt_jefe" id="txt_jefe" required></select>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer">

                            <div class="row">
                                <div class="col-md-6">
                                    <button type="submit" name="action" value="add"
                                        class="btn btn-info float-right">Guardar</button>

                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-dark" id="btnVolverR"><i
                                            class="fas fa-arrow-circle-left"></i>
                                        Regresar</button>
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
        <script type="text/javascript" src="rutas.js"></script>
    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>