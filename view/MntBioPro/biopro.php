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
                            <h1>Asistencia Biotime Versión 2</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                                <li class="breadcrumb-item active">Asistencia Biotime V2</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">

                <ul class="nav nav-tabs" id="tabs-asistencia" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-registro-tab" data-toggle="pill" href="#tab-registro"
                            role="tab">
                            Registro
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-dashboard-tab" data-toggle="pill" href="#tab-dashboard" role="tab">
                            Dashboard
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="tabs-asistencia-content">


                    <!-- TAB 1: REGISTRO -->
                    <div class="tab-pane fade show active" id="tab-registro" role="tabpanel">
                        <!-- Default box -->
                        <div class="card">

                            <div class="card-header">

                                <div class="row align-items-end">
                                    <div class="col-md-4">
                                        <label>Rango de Fechas</label>
                                        <input type="text" id="filtro-fechas" class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <label>Empleado</label>
                                        <select id="filtro-empleado" class="form-control select2bs4" style="width:100%;">
                                            <option value="">Todos los empleados</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <button id="btn-filtrar-fechas" class="btn btn-primary btn-block">
                                            Buscar
                                        </button>
                                    </div>

                                    <div class="col-md-2">
                                        <button id="btn-limpiar-filtros" class="btn btn-secondary btn-block">
                                            Limpiar
                                        </button>
                                    </div>
                                </div>

                            </div>
                            <div class="card-body">

                                <table id="asistencia_biotime_v2" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Empleado</th>
                                            <th>Hora de Entrada</th>
                                            <th>Hora de Salida</th>
                                            <th>Tiempo Bruto</th>
                                            <th>Tiempo Laborado</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                    </tbody>

                                </table>

                            </div>
                            <!-- /.card-body -->

                            <!-- /.card-footer-->
                        </div>
                        <!-- /.card -->
                    </div>

                    <!-- TAB 2: DASHBOARD -->
                    <div class="tab-pane fade" id="tab-dashboard" role="tabpanel">

                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Métricas de Asistencia</h3>
                            </div>

                            <div class="card-body">
                                <div class="row align-items-end">

                                    <div class="col-md-4">
                                        <label>Rango de Fechas</label>
                                        <input type="text" id="dash-fechas" class="form-control"
                                            placeholder="Rango de fechas">
                                    </div>

                                    <div class="col-md-3">
                                        <label>Empleado (opcional)</label>
                                        <select id="dash-empleado" class="form-control select2bs4" style="width:100%;">
                                            <option value="">Todos los empleados</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label>Métrica</label>
                                        <select id="dash-metrica" class="form-control">
                                            <option value="arrival_hist">Distribución de llegadas</option>
                                            <option value="punctuality_rate">Tasa de puntualidad</option>
                                            <option value="hours_by_employee">Horas por empleado</option>
                                            <option value="absenteeism_by_area">Ausentismo por área</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <button id="btn-dash-mostrar" class="btn btn-info btn-block">Mostrar</button>
                                    </div>

                                </div>

                                <hr>

                                <!-- Contenedor gráfico / resultado -->
                                <div id="dash-result">

                                    <!-- Para gráficos -->
                                    <div id="dash-chart-wrap" style="display:none;">
                                        <canvas id="dashChart" height="150"></canvas>

                                    </div>
                                    <div id="arrival-detail-wrap" class="mt-4" style="display:none;">
                                        <h5>Detalle de llegadas</h5>

                                        <table id="arrivalDetailTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Hora entrada</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                    <!-- Para tabla -->
                                    <div id="dash-table-wrap" style="display:none;">
                                        <table id="dashTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Empleado</th>
                                                    <th>Documento</th>
                                                    <th>Total Horas</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>

                                    <div id="nota-ausentismo" class="alert alert-secondary mt-3" style="display:none;">
                                        <strong>Metodología:</strong>
                                        El indicador se calcula por área tomando únicamente los empleados pertenecientes
                                        a la misma.
                                        Para el período consultado se determina el porcentaje de asistencia y ausentismo
                                        sobre el total
                                        de días hábiles esperados. Los porcentajes son complementarios, por lo que la
                                        suma de
                                        <strong>Presencia (%)</strong> y <strong>Ausentismo (%)</strong> es igual al
                                        <strong>100%</strong>.
                                    </div>

                                    <!-- KPI puntualidad -->
                                    <div id="dash-kpi-wrap" style="display:none;">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="small-box bg-success">
                                                    <div class="inner">
                                                        <h3 id="kpi-puntualidad">0%</h3>
                                                        <p>Tasa de puntualidad</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="small-box bg-info">
                                                    <div class="inner">
                                                        <h3 id="kpi-a-tiempo">0</h3>
                                                        <p>Días a tiempo</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="small-box bg-secondary">
                                                    <div class="inner">
                                                        <h3 id="kpi-sin-marcacion">0</h3>
                                                        <p>Días sin marcación</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="small-box bg-warning">
                                                    <div class="inner">
                                                        <h3 id="kpi-tarde">0</h3>
                                                        <p>Llegadas tarde</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div><!-- /dash-result -->
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
    <script type="text/javascript" src="biopro.js"></script>
</body>

</html>
<?php
} else {
    header("location:" . Conectar::ruta() . "index.php");
}
?>
