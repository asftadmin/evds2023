<?php

require_once '../../config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('location:' . Conectar::ruta() . 'index.php');
    exit;
}

// Token utilizado por las operaciones que modifican jornadas.
if (empty($_SESSION['csrf_jornadas'])) {
    $_SESSION['csrf_jornadas'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="es">

<?php require_once ('../MainHead/head.php'); ?>

<link rel="stylesheet" href="jornadas.css">

<title>Jornadas de mi Equipo</title>

</head>

<body class="hold-transition sidebar-mini layout-fixed">

    <div class="wrapper">

        <?php require_once ('../MainNav/nav.php'); ?>
        <?php require_once ('../MainMenu/menu.php'); ?>

        <div class="content-wrapper">

            <section class="content-header">

                <div class="container-fluid">

                    <div class="row mb-2">

                        <div class="col-sm-7">

                            <h1>
                                <i class="fas fa-users mr-2"></i>
                                Jornadas de mi Equipo
                            </h1>

                        </div>

                        <div class="col-sm-5">

                            <ol class="breadcrumb float-sm-right">

                                <li class="breadcrumb-item">
                                    <a href="../home/home2.php">Inicio</a>
                                </li>

                                <li class="breadcrumb-item active">
                                    Jornadas del equipo
                                </li>

                            </ol>

                        </div>

                    </div>

                </div>

            </section>

            <section class="content">

                <div class="container-fluid p-2">

                    <!-- Valida el contexto del jefe autenticado. -->
                    <div id="alerta-contexto" class="alert alert-info py-2">

                        <i class="fas fa-info-circle mr-1"></i>

                        <span id="texto-contexto">
                            Validando jefe inmediato...
                        </span>

                    </div>

                    <!-- Selección del empleado y periodo que formarán el expediente. -->
                    <div class="card card-outline card-primary">

                        <div class="card-header">

                            <h3 class="card-title">

                                <i class="fas fa-folder-open mr-1"></i>
                                Consultar expediente

                            </h3>

                        </div>

                        <div class="card-body">

                            <div class="row">

                                <!-- Empleado relacionado con el jefe inmediato. -->
                                <div class="col-md-5">

                                    <div class="form-group">

                                        <label for="empleado_id">
                                            Empleado
                                        </label>

                                        <select class="form-control select2" id="empleado_id" style="width: 100%;">
                                            <option value="">
                                                Seleccione un subordinado
                                            </option>
                                        </select>

                                        <small class="form-text text-muted">
                                            Solo se muestran empleados relacionados
                                            activamente con el jefe inmediato.
                                        </small>

                                    </div>

                                </div>

                                <!-- Periodo de trabajo del expediente. -->
                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="filtro_fechas">
                                            Periodo
                                        </label>

                                        <input type="text" class="form-control" id="filtro_fechas" autocomplete="off"
                                            readonly>
                                        <small class="form-text text-muted">
                                            Disponible desde el primer día del mes anterior hasta hoy.
                                        </small>

                                    </div>

                                </div>

                                <!-- Consulta las jornadas del empleado seleccionado. -->
                                <div class="col-md-3 d-flex align-items-end">

                                    <div class="form-group w-100">

                                        <button type="button" class="btn btn-info btn-block"
                                            id="btn-consultar-expediente">
                                            <i class="fas fa-search mr-1"></i>
                                            Consultar
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Expediente del empleado. Inicialmente permanece oculto. -->
                    <div id="contenedor-expediente" style="display: none;">

                        <!-- Identificación del expediente consultado. -->
                        <div class="card card-outline card-info">

                            <div class="card-body py-3">

                                <div class="row align-items-center">

                                    <div class="col-md-8">

                                        <h5 id="expediente-empleado" class="font-weight-bold mb-1">
                                            Empleado
                                        </h5>

                                        <div class="text-muted">

                                            Documento:
                                            <span id="expediente-documento">
                                                -
                                            </span>

                                            <span class="mx-2">|</span>

                                            Periodo:
                                            <span id="expediente-periodo">
                                                -
                                            </span>

                                        </div>

                                    </div>

                                    <div class="col-md-4 text-md-right mt-3 mt-md-0">

                                        <button type="button" class="btn btn-outline-danger btn-sm mr-2"
                                            id="btn-pdf-borrador">
                                            <i class="fas fa-file-pdf mr-1"></i>
                                            PDF borrador
                                        </button>

                                        <span class="badge badge-info p-2">
                                            <i class="fas fa-folder-open mr-1"></i>
                                            Expediente activo
                                        </span>

                                        <div id="resumen-confirmacion-empleado" class="mt-2 text-md-right"
                                            style="display: none;">
                                            <span id="badge-confirmacion-empleado" class="badge badge-secondary p-2">
                                                <i class="fas fa-user-check mr-1"></i>
                                                Pendiente de confirmación
                                            </span>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- Token utilizado para guardar y aprobar jornadas. -->
                        <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars(
    $_SESSION['csrf_jornadas'],
    ENT_QUOTES,
    'UTF-8'
); ?>">

                        <!-- Planilla editable del periodo seleccionado. -->
                        <div class="card">

                            <div class="card-header">

                                <h3 class="card-title">

                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Registro de jornadas

                                </h3>

                                <div class="d-flex flex-wrap justify-content-start pt-3" style="clear: both;">
                                    <button type="button" class="btn btn-secondary mr-2 mb-2" id="btn-guardar-borrador"
                                        disabled>
                                        <i class="fas fa-save mr-1"></i>
                                        Guardar borrador
                                    </button>

                                    <button type="button" class="btn btn-success mr-2 mb-2" id="btn-registrar-aprobar"
                                        disabled
                                        title="Disponible cuando las jornadas del periodo sean confirmadas por el empleado">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Registrar y aprobar jornadas
                                    </button>
                                </div>

                            </div>

                            <div class="card-body">

                                <div class="alert alert-light border py-2">

                                    <i class="fas fa-info-circle text-info mr-1"></i>

                                    Diligencie únicamente los días trabajados.
                                    Las filas completamente vacías no serán guardadas.
                                    Use «Agregar turno» para registrar otra jornada del mismo día sin superponer
                                    horarios.

                                </div>

                                <div class="table-responsive">

                                    <table id="tabla-expediente" class="table table-bordered table-hover table-sm"
                                        width="100%">

                                        <thead class="thead-light">

                                            <tr>

                                                <th style="min-width: 85px;">
                                                    Día
                                                </th>

                                                <th style="min-width: 105px;">
                                                    Fecha
                                                </th>

                                                <th style="min-width: 100px;">
                                                    Entrada
                                                </th>

                                                <th style="min-width: 100px;">
                                                    Salida
                                                </th>

                                                <th style="min-width: 90px;">
                                                    Horas
                                                </th>

                                                <th style="min-width: 160px;">
                                                    Ubicación
                                                </th>

                                                <th style="min-width: 220px;">
                                                    Actividad
                                                </th>

                                                <th style="min-width: 220px;">
                                                    Observaciones
                                                </th>

                                                <th style="min-width: 110px;">
                                                    Estado
                                                </th>

                                            </tr>

                                        </thead>

                                        <tbody></tbody>

                                    </table>

                                </div>

                            </div>


                        </div>

                    </div>

                </div>

            </section>

        </div>

        <?php require_once ('../MainFooter/footer.php'); ?>

    </div>

    <?php require_once ('../MainJS/JS.php'); ?>

    <script src="equipo.js"></script>

</body>

</html>