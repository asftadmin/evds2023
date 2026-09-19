<?php

require_once '../../config/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('location:' . Conectar::ruta() . 'index.php');
    exit;
}

// Token independiente para proteger las operaciones AJAX de jornadas.
if (empty($_SESSION['csrf_jornadas'])) {
    $_SESSION['csrf_jornadas'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php require_once ('../MainHead/head.php'); ?>

    <title>Confirmar Jornadas</title>

    <style>
    .card-confirmacion {
        border-radius: 0.5rem;
    }

    .resumen-confirmacion {
        font-size: 0.95rem;
    }

    .tabla-jornadas td {
        vertical-align: middle;
    }

    .actividad-jornada {
        min-width: 220px;
        white-space: normal;
    }

    .estado-confirmacion {
        font-size: 0.9rem;
        padding: 0.45rem 0.7rem;
    }

    @media (max-width: 767.98px) {
        .contenido-confirmacion {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .card-body {
            padding: 1rem;
        }

        #btn-consultar-jornadas,
        #btn-revisar-firmar {
            width: 100%;
        }
    }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">

    <div class="wrapper">

        <?php require_once ('../MainNav/nav.php'); ?>
        <?php require_once ('../MainMenu/menu.php'); ?>

        <div class="content-wrapper">

            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">

                        <div class="col-sm-6">
                            <h1>
                                <i class="fas fa-user-check mr-2"></i>
                                Confirmar Jornadas
                            </h1>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item">
                                    <a href="../home/">
                                        Inicio
                                    </a>
                                </li>

                                <li class="breadcrumb-item active">
                                    Confirmar Jornadas
                                </li>
                            </ol>
                        </div>

                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid contenido-confirmacion">

                    <input type="hidden" id="csrf_token" value="<?php echo htmlspecialchars(
    $_SESSION['csrf_jornadas'],
    ENT_QUOTES,
    'UTF-8'
); ?>">

                    <div class="card card-primary card-outline card-confirmacion">

                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-check mr-1"></i>
                                Jornadas registradas
                            </h3>
                        </div>

                        <div class="card-body">

                            <div class="row align-items-end">

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="filtro_fechas_confirmacion">
                                            Periodo
                                        </label>

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="far fa-calendar-alt"></i>
                                                </span>
                                            </div>

                                            <input type="text" class="form-control" id="filtro_fechas_confirmacion"
                                                autocomplete="off" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <button type="button" class="btn btn-primary" id="btn-consultar-jornadas">
                                            <i class="fas fa-search mr-1"></i>
                                            Consultar
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <div id="bloque-identificacion-empleado" class="alert alert-light border"
                                style="display: none;">
                                <div class="row">

                                    <div class="col-md-4 mb-2 mb-md-0">
                                        <strong>
                                            <i class="fas fa-user mr-1"></i>
                                            Empleado:
                                        </strong>

                                        <span id="confirmacion-empleado">
                                            -
                                        </span>
                                    </div>

                                    <div class="col-md-4 mb-2 mb-md-0">
                                        <strong>
                                            <i class="fas fa-id-card mr-1"></i>
                                            Documento:
                                        </strong>

                                        <span id="confirmacion-documento">
                                            -
                                        </span>
                                    </div>

                                    <div class="col-md-4">
                                        <strong>
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            Periodo:
                                        </strong>

                                        <span id="confirmacion-periodo">
                                            -
                                        </span>
                                    </div>

                                </div>
                            </div>

                            <div id="resumen-jornadas-confirmacion" class="mb-3" style="display: none;">
                                <span id="badge-jornadas-confirmacion"
                                    class="badge badge-secondary estado-confirmacion">
                                    <i class="fas fa-clock mr-1"></i>
                                    Pendiente de consulta
                                </span>
                            </div>

                            <div id="mensaje-sin-jornadas" class="alert alert-info" style="display: none;">
                                <i class="fas fa-info-circle mr-1"></i>
                                No existen jornadas de obra pendientes de confirmación
                                para el periodo seleccionado.
                            </div>

                            <div id="contenedor-tabla-confirmacion" style="display: none;">
                                <div class="table-responsive">
                                    <table id="tabla-jornadas-confirmacion"
                                        class="table table-bordered table-striped table-hover tabla-jornadas"
                                        style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Día</th>
                                                <th>Fecha</th>
                                                <th>Entrada</th>
                                                <th>Salida</th>
                                                <th>Horas</th>
                                                <th>Ubicación</th>
                                                <th>Actividad</th>
                                                <th>Observación</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div id="acciones-confirmacion" class="mt-3" style="display: none;">
                                <div class="alert alert-warning py-2">
                                    <i class="fas fa-signature mr-1"></i>
                                    Revise cuidadosamente las jornadas antes de firmar.
                                    La firma confirma que la información corresponde
                                    al tiempo laborado durante el periodo seleccionado.
                                </div>

                                <button type="button" class="btn btn-success btn-lg" id="btn-revisar-firmar" disabled>
                                    <i class="fas fa-pen-nib mr-1"></i>
                                    Revisar y firmar jornadas
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </section>

        </div>

        <?php require_once ('firma_confirmacion.php'); ?>

        <?php require_once ('../MainFooter/footer.php'); ?>

    </div>

    <?php require_once ('../MainJS/JS.php'); ?>


    <script src="confirmar_jornadas.js"></script>

</body>

</html>