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

        <style>
            .titulo-formato {
                font-weight: bold;
                text-align: center;
                margin-bottom: 1rem;
                text-transform: uppercase;
            }
        </style>


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
                                <h1>Permiso y/o salida</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="../home/home.js">Inicio</a></li>
                                    <li class="breadcrumb-item active">Solicitud Permiso</li>
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

                            <h4 class="titulo-formato">Autorización de Permiso y/o Salida</h4>



                        </div>
                        <div class="card-body">


                            <form id="form_permiso">

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Nombres y Apellido</label>
                                        <input id="empleado_id" name="empleado_id" class="form-control" readonly>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Cargo</label>
                                        <input type="text" id="empleado_cargo" name="empleado_cargo" class="form-control" readonly>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="fecha_permiso" class="col-form-label">Fecha:
                                        </label>
                                        <div class="input-group date" id="reservationdate_fecha"
                                            data-target-input="nearest">
                                            <input type="text" class="form-control "
                                                data-target="#reservationdate_fecha" name="fecha_permiso"
                                                id="fecha_permiso" required />
                                            <div class="input-group-append" data-target="#reservationdate_fecha"
                                                data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Hora de salida</label>

                                        <label for="timepicker_salida">Hora de salida</label>
                                        <div class="input-group">
                                            <input type="text" id="timepicker_salida" class="form-control" autocomplete="off">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="timepicker_entrada">Hora de entrada</label>
                                        <div class="input-group">
                                            <input type="text" id="timepicker_entrada" class="form-control" autocomplete="off">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Motivo del permiso</label>
                                    <select id="permiso_motivo" name="permiso_motivo" class="form-control" required></select>
                                </div>

                                <div class="form-group">
                                    <label>Justificación</label>
                                    <textarea name="permiso_detalle" id="permiso_detalle" rows="4" class="form-control"></textarea>
                                </div>

                                <button type="submit" class="btn btn-dark btn-block">Registrar Permiso</button>

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
        <script type="text/javascript" src="permiso.js"></script>
    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>