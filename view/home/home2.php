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

        <!-- Select2 -->
        <link rel="stylesheet" href="../../public/plugins/select2/css/select2.min.css">
        <link rel="stylesheet" href="../../public/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
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
                                <h1>Dashboard</h1>
                            </div>
                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                                    <li class="breadcrumb-item active">Dashboard</li>
                                </ol>
                            </div>
                        </div>
                    </div><!-- /.container-fluid -->
                </section>

                <!-- Main content -->
                <section class="content">

                    <div class="container-fluid">

                        <!-- TÍTULO -->
                        <div class="row mb-4">
                            <div class="col-12 text-center">
                                <h3 class="font-weight-bold">
                                    Panel General del Sistema
                                </h3>
                                <p class="text-muted">Seleccione una opción para continuar</p>
                            </div>
                        </div>

                        <!-- CARDS -->
                        <div class="row">

                            <!-- EVALUACIÓN MENSUAL -->
                            <div class="col-lg-3 col-md-6" id="card_mensual">

                                <div class="small-box bg-info elevation-2">
                                    <div class="inner">
                                        <h4>Evaluación Mensual</h4>
                                        <p>Gestión mensual de desempeño</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <a href="../evdsMensual/evaluacion_mensual.php" class=" small-box-footer">
                                        Ingresar <i class="fas fa-arrow-circle-right"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- EVALUACIÓN ANUAL -->
                            <div class="col-lg-3 col-md-6" id="card_anual">
                                <div class="small-box bg-success elevation-2">
                                    <div class="inner">
                                        <h4>Evaluación Anual</h4>
                                        <p>Consolidado anual de desempeño</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <a href="../evaluacion/evaluacion.php" class="small-box-footer">
                                        Ingresar <i class="fas fa-arrow-circle-right"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- APROBACIONES DE PERMISOS -->


                            <div class="col-lg-3 col-md-6" id="card_aprobaciones">
                                <div class="small-box bg-warning elevation-2">
                                    <div class="inner">
                                        <h4>Aprobaciones</h4>
                                        <p>Permisos pendientes por aprobar</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <a href="../MntInbox/inboxSol.php" class="small-box-footer">
                                        Ingresar <i class="fas fa-arrow-circle-right"></i>
                                    </a>
                                </div>
                            </div>
                            <!-- SOLICITUDES DE PERMISOS -->
                            <div class="col-lg-3 col-md-6" id="card_solicitudes">
                                <div class="small-box bg-dark elevation-2">
                                    <div class="inner">
                                        <h4>Solicitudes</h4>
                                        <p>Registrar nueva solicitud</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-file-signature"></i>
                                    </div>
                                    <a href="../MntPermisosp/permiso.php" class="small-box-footer">
                                        Ingresar <i class="fas fa-arrow-circle-right"></i>
                                    </a>
                                </div>
                            </div>

                        </div>

                    </div>
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

        <script src="../../config/config.js"></script>
        <script src="../../public/plugins/select2/js/select2.full.min.js"></script>


        <script>
            document.addEventListener("DOMContentLoaded", function() {

                let rol = document.getElementById("rol_idx").value;

                if (rol == 4) {
                    // Ocultar los que NO debe ver
                    document.getElementById("card_mensual").style.display = "none";
                    document.getElementById("card_aprobaciones").style.display = "none";

                    // Opcional: centrar los que quedan
                    document.getElementById("card_anual").classList.remove("col-lg-3");
                    document.getElementById("card_solicitudes").classList.remove("col-lg-3");

                    document.getElementById("card_anual").classList.add("col-lg-4", "offset-lg-2");
                    document.getElementById("card_solicitudes").classList.add("col-lg-4");
                }

            });
        </script>



    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>