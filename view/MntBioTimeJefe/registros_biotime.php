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

            <div class="content-wrapper">

                <section class="content-header">
                    <div class="container-fluid">

                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1>
                                    <i class="fas fa-fingerprint"></i>
                                    Registros Biotime
                                </h1>
                            </div>

                            <div class="col-sm-6">
                                <ol class="breadcrumb float-sm-right">
                                    <li class="breadcrumb-item">
                                        <a href="home">Inicio</a>
                                    </li>
                                    <li class="breadcrumb-item active">
                                        Registros Biotime
                                    </li>
                                </ol>
                            </div>
                        </div>

                    </div>
                </section>

                <section class="content">

                    <div class="container-fluid">

                        <div class="card card-primary card-outline">

                            <div class="card-header">
                                <h3 class="card-title">
                                    Consulta de marcaciones Biotime
                                </h3>
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-4">

                                        <div class="form-group">
                                            <label>
                                                Empleado
                                            </label>

                                            <select id="filtro-empleado" class="form-control select2bs4"
                                                style="width:100%;">
                                            </select>

                                        </div>

                                    </div>

                                    <div class="col-md-4">

                                        <div class="form-group">

                                            <label>
                                                Rango de Fechas
                                            </label>

                                            <input type="text" class="form-control" id="filtro-fechas" autocomplete="off">

                                        </div>

                                    </div>

                                    <div class="col-md-4">

                                        <div class="form-group">

                                            <label>&nbsp;</label>

                                            <div>

                                                <button type="button" id="btn-filtrar-fechas" class="btn btn-primary">

                                                    <i class="fas fa-search"></i>
                                                    Consultar

                                                </button>

                                                <button type="button" id="btn-limpiar-filtros" class="btn btn-secondary">

                                                    <i class="fas fa-eraser"></i>
                                                    Limpiar

                                                </button>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="card">

                            <div class="card-header">

                                <h3 class="card-title">
                                    Resultado de la consulta
                                </h3>

                            </div>

                            <div class="card-body">

                                <div class="table-responsive">

                                    <table id="tabla_registros_biotime"
                                        class="table table-bordered table-striped table-hover" width="100%">

                                        <thead>

                                            <tr>

                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Documento</th>
                                                <th>Empleado</th>
                                                <th>Cargo</th>
                                                <th>Grupo</th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                        </tbody>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>

                </section>

            </div>

            <script src="view/MntRegistrosBiotime/registros_biotime.js"></script>

            <?php require_once("../MainFooter/footer.php") ?>

            <!-- Control Sidebar -->
            <aside class="control-sidebar control-sidebar-dark">
                <!-- Control sidebar content goes here -->
            </aside>
            <!-- /.control-sidebar -->
        </div>
        <!-- ./wrapper -->

        <?php require_once("../MainJS/JS.php") ?>
        <script type="text/javascript" src="registros_biotime.js"></script>
    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>