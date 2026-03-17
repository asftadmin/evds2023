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
                                <h1>Empleados</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="../home/home.php">Inicio</a></li>
                                    <li class="breadcrumb-item"><a href="empleados.php">Empleados</a></li>
                                    <li class="breadcrumb-item active">Inactivar</li>
                                </ol>
                            </div>
                        </div>
                    </div><!-- /.container-fluid -->
                </section>

                <!-- Main content -->
                <section class="content">

                    <!-- Default box -->
                    <div class="card p-2">


                        <div class="card-header">
                            <button id="btnInactivar" class="btn btn-danger">Inactivar Seleccionados</button>

                        </div>


                        <div class="card-body">

                            <form class="form-horizontal" method="post" id="disabled_empleado">
                                <div class="modal-body">

                                    <table id="data_disabled" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="checkAll"></th>
                                                <th>No. Documento</th>
                                                <th>Nombre Completo</th>
                                                <th>Estado</th>

                                            </tr>
                                        </thead>

                                        <tbody>

                                        </tbody>

                                    </table>



                                </div>


                            </form>



                        </div>
                        <!-- /.card-body -->
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


        <?php require_once("mantenimiento_empleado.php") ?>


        <?php require_once("../MainJS/JS.php") ?>
        <script src="../../config/config.js"></script>
        <script type="text/javascript" src="inactivar.js"></script>

    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>