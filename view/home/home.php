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

                    <div class="card">
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="tab-1-tab" data-toggle="pill" href="#tab-1" role="tab"
                                        aria-controls="tab-1" aria-selected="true">Grafico Evaluación Mensual</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-2-tab" data-toggle="pill" href="#tab-2" role="tab"
                                        aria-controls="tab-2" aria-selected="false">Avance Evaluacion Mensual</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-3-tab" data-toggle="pill" href="#tab-3" role="tab"
                                        aria-controls="tab-2" aria-selected="false">Cumplimiento Evaluadores</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="custom-tabs-one-tabContent">
                                <div class="tab-pane fade show active" id="tab-1" role="tabpanel"
                                    aria-labelledby="tab-1-tab">
                                    <!-- Card inside Tab 1 -->
                                    <div class="card">
                                        <div class="card-body">
                                            <div style="width: 75%; margin: auto;">
                                                <div class="row justify-content-center">
                                                    <div class="col-sm-4">
                                                        <!-- text input -->
                                                        <div class="form-group">
                                                            <select class="form-control select2" name="nomb_evaluad"
                                                                id="nomb_evaluad" style="width: 100%;" required>

                                                            </select>

                                                            <input type="hidden" id="idEmpleado" name="idEmpleado">

                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <button id="btnBuscarEmpl" type="button"
                                                                class="btn btn-flat bg-gradient-info">Buscar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="div col-sm-6">
                                                        <canvas id="graficoEval"></canvas>
                                                    </div>
                                                    <div class="div col-sm-6">
                                                        <canvas id="graficoEvalSept"></canvas>
                                                    </div>
                                                </div>


                                            </div>


                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-2" role="tabpanel" aria-labelledby="tab-2-tab">
                                    <!-- Card inside Tab 2 -->
                                    <div class="card">
                                        <div class="card-header">

                                            <div style="width: 75%; margin: auto;">
                                                <div class="row justify-content-center">
                                                    <div class="col-sm-3">
                                                        <!-- text input -->
                                                        <div class="form-group">
                                                            <select class="form-control select2" name="mes_evaluador"
                                                                id="mes_evaluador" style="width: 100%;" required>

                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <!-- text input -->
                                                        <div class="form-group">
                                                            <select class="form-control select2" name="mes_eval"
                                                                id="mes_eval" style="width: 100%;" required>

                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <select class="form-control " name="mes_ano" id="mes_ano"
                                                                style="width: 100%;" required>
                                                                <option disabled selected required>--Selecciona Año--
                                                                </option>
                                                                <option value="2023">2023</option>
                                                                <option value="2024">2024</option>
                                                                <option value="2025">2025</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <div class="form-group">
                                                            <button id="btnBuscarSeguimiento" type="button"
                                                                class="btn btn-flat bg-gradient-info">Buscar</button>
                                                        </div>
                                                    </div>
                                                </div>


                                            </div>
                                        </div>
                                        <div class="card-body">

                                            <table id="avance_data" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Mes</th>
                                                        <th>Evaluador</th>
                                                        <th>Empleado Evaluado</th>
                                                        <th>Estado Evaluacion</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                </tbody>

                                            </table>

                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="tab-3" role="tabpanel" aria-labelledby="tab-3-tab">
                                    <!-- Card inside Tab 3 -->
                                    <div class="card">
                                        <div class="card-header">

                                            <div style="width: 75%; margin: auto;">
                                                <div class="row justify-content-center">
                                                    <div class="col-sm-4">
                                                        <!-- text input -->
                                                        <div class="form-group">
                                                            <select class="form-control " name="txt_mes_eval"
                                                                id="txt_mes_eval" style="width: 100%;" required>

                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <select class="form-control " name="txt_mes_ano"
                                                                id="txt_mes_ano" style="width: 100%;" required>
                                                                <option disabled selected required>--Selecciona Año--
                                                                </option>
                                                                <option value="2023">2023</option>
                                                                <option value="2024">2024</option>
                                                                <option value="2025">2025</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <div class="form-group">
                                                            <button id="btnBuscarEvaluador" type="button"
                                                                class="btn btn-flat bg-gradient-info">Buscar</button>
                                                        </div>
                                                    </div>
                                                </div>


                                            </div>


                                        </div>
                                        <div class="card-body">

                                            <table id="cumplimiento_data" class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Mes Evaluado</th>
                                                        <th>Funcionario Evaluador</th>
                                                        <th>Total Evaluaciones Realizadas</th>
                                                        <th>Total Evaluaciones Asignadas</th>
                                                        <th>Porcentaje Cumplimiento</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                </tbody>

                                            </table>

                                        </div>
                                    </div>
                                </div>
                            </div>
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
        <script src="../../config/config.js"></script>
        <script src="../../public/plugins/select2/js/select2.full.min.js"></script>
        <script type="text/javascript" src="home.js"></script>



    </body>

    </html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>