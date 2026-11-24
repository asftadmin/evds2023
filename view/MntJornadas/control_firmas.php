<?php
require_once '../../config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('location:' . Conectar::ruta() . 'index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<?php require_once ('../MainHead/head.php'); ?>

<title>Control de firmas de jornadas</title>

<link rel="stylesheet" href="jornadas.css">

</head>

<body class="hold-transition sidebar-mini layout-fixed">

    <div class="wrapper">

        <?php require_once ('../MainNav/nav.php'); ?>
        <?php require_once ('../MainMenu/menu.php'); ?>

        <div class="content-wrapper">

            <!-- Encabezado -->
            <section class="content-header">
                <div class="container-fluid">

                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Control de firmas de jornadas</h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item">
                                    <a href="#">Inicio</a>
                                </li>

                                <li class="breadcrumb-item active">
                                    Control de firmas
                                </li>
                            </ol>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Contenido -->
            <section class="content">
                <div class="container-fluid">

                    <!-- Filtros -->
                    <div class="card card-outline card-primary">

                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-filter mr-1"></i>
                                Periodo, empleado y estado
                            </h3>
                        </div>

                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label for="filtro_fechas">
                                            Periodo
                                        </label>

                                        <input type="text" id="filtro_fechas" class="form-control" autocomplete="off">
                                    </div>

                                </div>

                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label for="empleado_id">
                                            Empleado
                                        </label>

                                        <select id="empleado_id" class="form-control select2" style="width: 100%;">
                                            <option value="">
                                                Todos los empleados
                                            </option>
                                        </select>
                                    </div>

                                </div>

                                <div class="col-md-4">

                                    <div class="form-group">
                                        <label for="estado_control">
                                            Estado
                                        </label>

                                        <select id="estado_control" class="form-control select2" style="width: 100%;">
                                            <option value="">
                                                Todos
                                            </option>

                                            <option value="PENDIENTE_CONFIRMACION">
                                                Pendiente confirmación
                                            </option>

                                            <option value="PENDIENTE_APROBACION">
                                                Pendiente aprobación
                                            </option>

                                            <option value="PENDIENTE_FIRMA">
                                                Pendiente firma reporte
                                            </option>

                                            <option value="FIRMADO">
                                                Firmado
                                            </option>

                                            <option value="EN_PROCESO">
                                                En proceso
                                            </option>
                                        </select>
                                    </div>

                                </div>

                            </div>

                            <button type="button" id="btn-consultar" class="btn btn-info">
                                <i class="fas fa-search mr-1"></i>
                                Consultar
                            </button>

                        </div>

                    </div>

                    <!-- Resumen -->
                    <div class="row">

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-info">

                                <div class="inner">
                                    <h3 id="card-total">0</h3>
                                    <p>Empleados del periodo</p>
                                </div>

                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>

                            </div>

                        </div>

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-warning">

                                <div class="inner">
                                    <h3 id="card-pendiente-confirmacion">
                                        0
                                    </h3>

                                    <p>
                                        Pendientes confirmación
                                    </p>
                                </div>

                                <div class="icon">
                                    <i class="fas fa-signature"></i>
                                </div>

                            </div>

                        </div>

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-warning">

                                <div class="inner">
                                    <h3 id="card-pendiente-firma">
                                        0
                                    </h3>

                                    <p>
                                        Pendientes firma reporte
                                    </p>
                                </div>

                                <div class="icon">
                                    <i class="fas fa-file-signature"></i>
                                </div>

                            </div>

                        </div>

                        <div class="col-lg-3 col-6">

                            <div class="small-box bg-success">

                                <div class="inner">
                                    <h3 id="card-firmados">
                                        0
                                    </h3>

                                    <p>
                                        Firmados
                                    </p>
                                </div>

                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Tabla -->
                    <div class="card">

                        <div class="card-header">

                            <h3 class="card-title">
                                <i class="fas fa-list mr-1"></i>
                                Estado de firmas por empleado
                            </h3>

                        </div>

                        <div class="card-body">

                            <div class="table-responsive">

                                <table id="tabla-control-firmas" class="table table-bordered table-striped table-hover"
                                    style="width: 100%;">

                                    <thead>
                                        <tr>
                                            <th>Empleado</th>
                                            <th>Documento</th>
                                            <th>Jornadas</th>
                                            <th>Confirmadas</th>
                                            <th>
                                                Confirmación empleado
                                            </th>
                                            <th>Aprobadas</th>
                                            <th>Firma reporte</th>
                                            <th>Última firma</th>
                                            <th>Estado</th>
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

        <?php require_once ('../MainFooter/footer.php'); ?>

    </div>

    <?php require_once ('../MainJS/JS.php'); ?>

    <script src="control_firmas.js"></script>

</body>

</html>
```